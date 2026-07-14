<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/crypto.php';
require_once __DIR__ . '/SmtpMailer.php';
require_once __DIR__ . '/contract_template.php';

function load_contract_notification_settings(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM contract_notifications WHERE id = 1');
    $row = $stmt->fetch();

    return $row ?: ['enabled' => 0, 'recipients' => '', 'updated_at' => null];
}

function contract_notification_recipients(string $raw): array
{
    $parts = preg_split('/[\r\n,;]+/', $raw) ?: [];
    $emails = [];
    foreach ($parts as $part) {
        $email = trim($part);
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $email;
        }
    }

    return array_values(array_unique($emails));
}

// Laedt Vertrag, Angebot und Kunde zusammen. Gibt null zurueck, wenn irgendetwas davon fehlt.
function load_contract_context(PDO $pdo, string $contractId): ?array
{
    $contractStmt = $pdo->prepare('SELECT * FROM contracts WHERE id = :id');
    $contractStmt->execute(['id' => $contractId]);
    $contract = $contractStmt->fetch();
    if (!$contract) {
        return null;
    }

    $offerStmt = $pdo->prepare('SELECT * FROM offers WHERE id = :id');
    $offerStmt->execute(['id' => $contract['offer_id']]);
    $offer = $offerStmt->fetch();
    if (!$offer) {
        return null;
    }

    $customerStmt = $pdo->prepare('SELECT * FROM customers WHERE id = :id');
    $customerStmt->execute(['id' => $offer['customer_id']]);
    $customer = $customerStmt->fetch();
    if (!$customer) {
        return null;
    }

    return ['contract' => $contract, 'offer' => $offer, 'customer' => $customer];
}

// Liefert die SMTP-Angebots-Kontoeinstellungen, sofern vollstaendig konfiguriert, sonst null.
function load_active_smtp(PDO $pdo): ?array
{
    $smtp = $pdo->query('SELECT * FROM smtp_settings WHERE id = 1')->fetch();
    if (!$smtp || $smtp['host'] === '' || $smtp['from_email'] === '') {
        return null;
    }

    return $smtp;
}

// Sendet den gerade erstellten/unterschriebenen Vertrag als E-Mail an die unter
// Einstellungen konfigurierten Empfaenger (z. B. Buchhaltung). Wird sowohl beim manuellen
// Anlegen im Dashboard als auch beim automatischen Anlegen und beim Unterschreiben ueber
// den Kunden-Vertragswizard aufgerufen. Fehler hier duerfen den eigentlichen Vorgang nicht
// abbrechen, daher best effort mit Logging.
function notify_contract_created(PDO $pdo, string $contractId): void
{
    try {
        $notifySettings = load_contract_notification_settings($pdo);
        if (!$notifySettings['enabled']) {
            return;
        }

        $recipients = contract_notification_recipients((string) ($notifySettings['recipients'] ?? ''));
        if ($recipients === []) {
            return;
        }

        $context = load_contract_context($pdo, $contractId);
        if ($context === null) {
            return;
        }

        $smtp = load_active_smtp($pdo);
        if ($smtp === null) {
            return;
        }

        $html = render_contract_document($context['offer'], $context['customer'], $context['contract']);

        $mailer = new SmtpMailer(
            $smtp['host'],
            (int) $smtp['port'],
            $smtp['encryption'],
            $smtp['username'],
            decrypt_secret($smtp['password_encrypted'])
        );

        $subject = 'Neuer Vertrag ' . ($context['contract']['number'] ?? '') . ' – ' . $context['customer']['name'];

        foreach ($recipients as $recipient) {
            try {
                $mailer->send($smtp['from_email'], $smtp['from_name'], $recipient, $recipient, $subject, $html, true);
            } catch (Throwable $exception) {
                error_log('Vertragsbenachrichtigung fehlgeschlagen (' . $recipient . '): ' . $exception->getMessage());
            }
        }
    } catch (Throwable $exception) {
        error_log('Vertragsbenachrichtigung fehlgeschlagen: ' . $exception->getMessage());
    }
}

// Schickt dem Kunden nach dem Unterschreiben eine Willkommens-E-Mail mit dem unterschriebenen
// Vertrag als Anhang. Unabhaengig von der Vertragsbenachrichtigung an die Buchhaltung, immer
// aktiv sobald ein SMTP-Konto hinterlegt ist. Best effort, wirft keine Exceptions nach aussen.
function notify_customer_contract_signed(PDO $pdo, string $contractId): void
{
    try {
        $context = load_contract_context($pdo, $contractId);
        if ($context === null) {
            return;
        }

        $customerEmail = trim((string) $context['customer']['email']);
        if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $smtp = load_active_smtp($pdo);
        if ($smtp === null) {
            return;
        }

        $contractHtml = render_contract_document($context['offer'], $context['customer'], $context['contract']);
        $number = (string) ($context['contract']['number'] ?? '');

        $message = '<p>Sehr geehrte Damen und Herren,</p>'
            . '<p>herzlich willkommen bei CleanTeam Group! Ihr Vertrag wurde soeben von Ihnen unterschrieben und ist damit gültig.</p>'
            . '<p>Im Anhang finden Sie eine Kopie Ihres Vertrags' . ($number !== '' ? ' (' . htmlspecialchars($number, ENT_QUOTES, 'UTF-8') . ')' : '') . '.</p>'
            . '<p>Mit freundlichen Grüßen<br>Ihr CleanTeam-Team</p>';

        $mailer = new SmtpMailer(
            $smtp['host'],
            (int) $smtp['port'],
            $smtp['encryption'],
            $smtp['username'],
            decrypt_secret($smtp['password_encrypted'])
        );

        $filename = 'Vertrag' . ($number !== '' ? '-' . preg_replace('/[^A-Za-z0-9\-_]/', '', $number) : '') . '.html';

        $mailer->sendWithAttachment(
            $smtp['from_email'],
            $smtp['from_name'],
            $customerEmail,
            $context['customer']['name'],
            'Ihr Vertrag bei CleanTeam Group',
            $message,
            $filename,
            $contractHtml,
            'text/html'
        );
    } catch (Throwable $exception) {
        error_log('Kunden-Vertragsbestätigung fehlgeschlagen: ' . $exception->getMessage());
    }
}
