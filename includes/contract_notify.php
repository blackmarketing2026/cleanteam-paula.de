<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/crypto.php';
require_once __DIR__ . '/SmtpMailer.php';
require_once __DIR__ . '/contract_pdf.php';
require_once __DIR__ . '/email_template.php';
require_once __DIR__ . '/email_settings.php';

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

// Laedt Vertrag, Kostenvoranschlag und Kunde zusammen. Gibt null zurueck, wenn irgendetwas davon fehlt.
function load_contract_context(PDO $pdo, string $contractId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT
            c.*,
            o.id as offer_id_alias, o.customer_id, o.site_visit_id, o.square_meters, o.interval_label, o.service, o.start_date, o.notes AS offer_notes, o.price, o.created_at AS offer_created_at,
            cust.id as customer_id_alias, cust.name, cust.email, cust.phone, cust.salutation, cust.contact_last_name, cust.address, cust.house_number, cust.zip, cust.city
        FROM contracts c
        JOIN offers o ON c.offer_id = o.id
        JOIN customers cust ON o.customer_id = cust.id
        WHERE c.id = :id'
    );
    $stmt->execute(['id' => $contractId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    // Manuell die Arrays für Vertrag, Angebot und Kunde zusammenstellen,
    // um die gleiche Struktur wie vorher zu haben.
    $contract = array_filter($row, fn($key) => !in_array($key, ['offer_id_alias', 'customer_id_alias', 'name', 'email', 'phone', 'salutation', 'contact_last_name', 'address', 'house_number', 'zip', 'city', 'site_visit_id', 'square_meters', 'interval_label', 'service', 'start_date', 'offer_notes', 'price', 'offer_created_at']), ARRAY_FILTER_USE_KEY);
    $offer = array_intersect_key($row, array_flip(['id', 'customer_id', 'site_visit_id', 'square_meters', 'interval_label', 'service', 'start_date', 'notes', 'price', 'created_at']));
    $offer['id'] = $row['offer_id_alias']; // 'id' kommt von contracts, also überschreiben
    $offer['notes'] = $row['offer_notes'];
    $offer['created_at'] = $row['offer_created_at'];
    $customer = array_intersect_key($row, array_flip(['id', 'name', 'email', 'phone', 'salutation', 'contact_last_name', 'address', 'house_number', 'zip', 'city']));
    $customer['id'] = $row['customer_id_alias'];

    return ['contract' => $contract, 'offer' => $offer, 'customer' => $customer];
}

// Liefert das Kundenmanagement-Postfach (dasselbe Konto wie beim Kostenvoranschlagsversand und im
// Postfach-Bereich), sofern vollstaendig konfiguriert, sonst null. Bewusst nicht die separaten
// "SMTP-Server-Einstellungen" - die sind oft nie befuellt, das Postfach dagegen schon.
function load_mailbox_smtp(PDO $pdo): ?array
{
    $smtp = $pdo->query('SELECT * FROM mailbox_settings WHERE id = 1')->fetch();
    if (!$smtp || $smtp['host'] === '' || $smtp['username'] === '' || ($smtp['password_encrypted'] ?? '') === '') {
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
        if (!email_delivery_is_allowed($pdo, 'internal_contract_notification')) {
            return;
        }

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

        $smtp = load_mailbox_smtp($pdo);
        if ($smtp === null) {
            return;
        }

        $pdf = save_contract_pdf($pdo, $contractId, 'cleanteam', false);
        $messageContent = '<p style="margin:0 0 14px 0;">Ein Vertrag wurde angelegt oder aktualisiert.</p>'
            . '<p style="margin:0;">Im Anhang finden Sie die CleanTeam-Ausfertigung als PDF inklusive Signaturprotokoll.</p>';

        $mailer = new SmtpMailer(
            $smtp['host'],
            (int) $smtp['smtp_port'],
            $smtp['smtp_encryption'],
            $smtp['username'],
            decrypt_secret($smtp['password_encrypted'])
        );

        $subject = 'Neuer Vertrag ' . ($context['contract']['number'] ?? '') . ' – ' . $context['customer']['name'];
        $message = render_email_template($pdo, $messageContent, [
            'title' => 'Neuer Vertrag',
            'preheader' => 'Ein Vertrag wurde angelegt oder aktualisiert.',
            'fromName' => $smtp['from_name'] ?? 'CleanTeam',
            'signatureText' => $smtp['signature'] ?? '',
        ]);

        foreach ($recipients as $recipient) {
            try {
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
        if (!email_delivery_is_allowed($pdo, 'contract_customer')) {
            return;
        }

        $context = load_contract_context($pdo, $contractId);
        if ($context === null) {
            return;
        }

        $customerEmail = trim((string) $context['customer']['email']);
        if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $smtp = load_mailbox_smtp($pdo);
        if ($smtp === null) {
            return;
        }

        $pdf = save_contract_pdf($pdo, $contractId, 'customer', false);
        $number = (string) ($context['contract']['number'] ?? '');

        $messageContent = '<p style="margin:0 0 14px 0;">Sehr geehrte Damen und Herren,</p>'
            . '<p>herzlich willkommen bei CleanTeam Group! Ihr Vertrag wurde soeben von Ihnen unterschrieben und ist damit gültig.</p>'
            . '<p>Im Anhang finden Sie eine Kopie Ihres Vertrags' . ($number !== '' ? ' (' . email_h($number) . ')' : '') . '.</p>';
        $message = render_email_template($pdo, $messageContent, [
            'title' => 'Ihr Vertrag bei CleanTeam Group',
            'preheader' => 'Ihr unterschriebener Vertrag liegt als PDF bei.',
            'fromName' => $smtp['from_name'] ?? 'CleanTeam',
            'signatureText' => $smtp['signature'] ?? '',
        ]);

        $mailer = new SmtpMailer(
            $smtp['host'],
            (int) $smtp['smtp_port'],
            $smtp['smtp_encryption'],
            $smtp['username'],
            decrypt_secret($smtp['password_encrypted'])
        );

        $mailer->sendWithAttachment(
            $smtp['username'],
            $smtp['from_name'],
            $customerEmail,
            $context['customer']['name'],
            'Ihr Vertrag bei CleanTeam Group',
            $message,
            (string) $pdf['filename'],
            (string) $pdf['content'],
            'application/pdf'
        );
    } catch (Throwable $exception) {
        error_log('Kunden-Vertragsbestätigung fehlgeschlagen: ' . $exception->getMessage());
    }
}
