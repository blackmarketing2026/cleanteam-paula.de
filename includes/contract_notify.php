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

// Sendet den gerade erstellten Vertrag als E-Mail an die konfigurierten Empfaenger.
// Wird sowohl beim manuellen Anlegen im Dashboard als auch beim automatischen Anlegen
// ueber den Kunden-Vertragswizard aufgerufen. Fehler hier duerfen den eigentlichen
// Vertrags-Anlegevorgang nicht abbrechen, daher best effort mit Logging.
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

        $contractStmt = $pdo->prepare('SELECT * FROM contracts WHERE id = :id');
        $contractStmt->execute(['id' => $contractId]);
        $contract = $contractStmt->fetch();
        if (!$contract) {
            return;
        }

        $offerStmt = $pdo->prepare('SELECT * FROM offers WHERE id = :id');
        $offerStmt->execute(['id' => $contract['offer_id']]);
        $offer = $offerStmt->fetch();
        if (!$offer) {
            return;
        }

        $customerStmt = $pdo->prepare('SELECT * FROM customers WHERE id = :id');
        $customerStmt->execute(['id' => $offer['customer_id']]);
        $customer = $customerStmt->fetch();
        if (!$customer) {
            return;
        }

        $smtpStmt = $pdo->query('SELECT * FROM smtp_settings WHERE id = 1');
        $smtp = $smtpStmt->fetch();
        if (!$smtp || $smtp['host'] === '' || $smtp['from_email'] === '') {
            return;
        }

        $html = render_contract_document($offer, $customer, $contract);

        $mailer = new SmtpMailer(
            $smtp['host'],
            (int) $smtp['port'],
            $smtp['encryption'],
            $smtp['username'],
            decrypt_secret($smtp['password_encrypted'])
        );

        $subject = 'Neuer Vertrag ' . ($contract['number'] ?? '') . ' – ' . $customer['name'];

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
