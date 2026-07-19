<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/contract_notify.php';

require_admin();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $settings = load_contract_notification_settings($pdo);
    json_response([
        'enabled' => (bool) $settings['enabled'],
        'recipients' => contract_notification_recipients((string) ($settings['recipients'] ?? '')),
        'updatedAt' => to_iso($settings['updated_at']),
    ]);
}

if ($method === 'POST' && ($_GET['action'] ?? '') === 'test') {
    email_delivery_assert_allowed($pdo, 'contract_notification_test');

    $notifySettings = load_contract_notification_settings($pdo);
    $recipients = contract_notification_recipients((string) ($notifySettings['recipients'] ?? ''));
    if ($recipients === []) {
        json_error('Bitte zuerst mindestens eine E-Mail-Adresse speichern.', 422);
    }

    $contractRow = $pdo->query('SELECT id FROM contracts ORDER BY created_at DESC LIMIT 1')->fetch();
    if (!$contractRow) {
        json_error('Es existiert noch kein Vertrag zum Testen. Bitte zuerst einen Kostenvoranschlag mit Vertrag anlegen.', 422);
    }

    $context = load_contract_context($pdo, $contractRow['id']);
    if ($context === null) {
        json_error('Testvertrag konnte nicht geladen werden.', 500);
    }

    $smtp = load_mailbox_smtp($pdo);
    if ($smtp === null) {
        json_error('Bitte zuerst das Postfach unter "Postfach" einrichten.', 422);
    }

    $pdf = save_contract_pdf($pdo, $context['contract']['id'], 'cleanteam', false);
    $messageContent = '<p style="margin:0 0 14px 0;">Dies ist eine Test-E-Mail für Vertragsbenachrichtigungen.</p>'
        . '<p style="margin:0;">Im Anhang befindet sich die CleanTeam-Ausfertigung als PDF.</p>';
    $subject = '[Test] Vertragsbenachrichtigung – ' . ($context['contract']['number'] ?? $context['customer']['name']);
    $message = render_email_template($pdo, $messageContent, [
        'title' => 'Test: Vertragsbenachrichtigung',
        'preheader' => 'Test-E-Mail für Vertragsbenachrichtigungen.',
        'fromName' => $smtp['from_name'] ?? 'CleanTeam',
        'signatureText' => $smtp['signature'] ?? '',
        'signatureContext' => 'internal_contract_notification',
    ]);

    try {
        $mailer = new SmtpMailer(
            $smtp['host'],
            (int) $smtp['smtp_port'],
            $smtp['smtp_encryption'],
            $smtp['username'],
            decrypt_secret($smtp['password_encrypted'])
        );
        foreach ($recipients as $recipient) {
            $mailer->sendWithAttachment(
                $smtp['username'],
                $smtp['from_name'],
                $recipient,
                $recipient,
                $subject,
                $message,
                (string) $pdf['filename'],
                (string) $pdf['content'],
                'application/pdf'
            );
        }
    } catch (Throwable $exception) {
        json_error('Test-E-Mail fehlgeschlagen: ' . $exception->getMessage(), 502);
    }

    json_response(['ok' => true, 'sentTo' => $recipients]);
}

if ($method === 'POST') {
    $body = read_json_body();
    $enabled = (bool) ($body['enabled'] ?? false);
    $recipientsInput = $body['recipients'] ?? [];
    $recipientsInput = is_array($recipientsInput) ? $recipientsInput : [];

    $emails = [];
    foreach ($recipientsInput as $entry) {
        $email = trim((string) $entry);
        if ($email === '') {
            continue;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_error('Ungültige E-Mail-Adresse: ' . $email, 422);
        }
        $emails[] = $email;
    }
    $emails = array_values(array_unique($emails));

    if ($enabled && $emails === []) {
        json_error('Bitte mindestens eine E-Mail-Adresse angeben.', 422);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO contract_notifications (id, enabled, recipients, updated_at)
         VALUES (1, :enabled, :recipients, UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE enabled = :enabled2, recipients = :recipients2, updated_at = UTC_TIMESTAMP()'
    );
    $stmt->execute([
        'enabled' => $enabled ? 1 : 0,
        'recipients' => implode("\n", $emails),
        'enabled2' => $enabled ? 1 : 0,
        'recipients2' => implode("\n", $emails),
    ]);

    json_response(['ok' => true]);
}

json_error('Methode nicht erlaubt.', 405);
