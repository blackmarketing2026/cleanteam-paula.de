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

// Sendet den unterschriebenen Vertrag als E-Mail an die unter Einstellungen konfigurierten
// Empfaenger (z. B. Buchhaltung). Fehler hier duerfen den eigentlichen Vorgang nicht
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
        $customer = $context['customer'];
        $customerAddress = trim((string) ($customer['address'] ?? '') . ' ' . (string) ($customer['house_number'] ?? ''));
        $customerCity = trim((string) ($customer['zip'] ?? '') . ' ' . (string) ($customer['city'] ?? ''));
        $customerAddressLine = trim($customerAddress . ', ' . $customerCity, ' ,');
        $messageContent = '<p style="margin:0 0 14px 0;">Ein neuer Vertrag wurde unterschrieben und liegt der Buchhaltung vor.</p>'
            . '<p style="margin:0 0 18px 0;">Im Anhang befindet sich der Vertrag als PDF inklusive Signaturprotokoll. Bitte ausdrucken, abheften und entsprechend korrekt abspeichern.</p>'
            . '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 4px 0;">'
            . '<tr><td style="padding:5px 10px 5px 0;color:#5b6b80;font-weight:700;">Kunde</td><td style="padding:5px 0;">' . email_h((string) ($customer['name'] ?? '')) . '</td></tr>'
            . '<tr><td style="padding:5px 10px 5px 0;color:#5b6b80;font-weight:700;">Telefon</td><td style="padding:5px 0;">' . email_h((string) ($customer['phone'] ?? '')) . '</td></tr>'
            . '<tr><td style="padding:5px 10px 5px 0;color:#5b6b80;font-weight:700;">Adresse</td><td style="padding:5px 0;">' . email_h($customerAddressLine) . '</td></tr>'
            . '<tr><td style="padding:5px 10px 5px 0;color:#5b6b80;font-weight:700;">E-Mail</td><td style="padding:5px 0;">' . email_h((string) ($customer['email'] ?? '')) . '</td></tr>'
            . '</table>';

        $mailer = new SmtpMailer(
            $smtp['host'],
            (int) $smtp['smtp_port'],
            $smtp['smtp_encryption'],
            $smtp['username'],
            decrypt_secret($smtp['password_encrypted'])
        );

        $subject = 'Neuer Vertrag/Buchhaltung - ' . $context['customer']['name'];
        $message = render_email_template_message($pdo, $messageContent, [
            'title' => 'Neuer Vertrag/Buchhaltung',
            'preheader' => 'Neuer Vertrag fuer die Buchhaltung.',
            'fromName' => $smtp['from_name'] ?? 'CleanTeam',
            'signatureText' => $smtp['signature'] ?? '',
            'signatureContext' => 'internal_contract_notification',
        ]);

        foreach ($recipients as $recipient) {
            try {
                $mailer->sendWithAttachment(
                    $smtp['username'],
                    $smtp['from_name'],
                    $recipient,
                    $recipient,
                    $subject,
                    $message['html'],
                    (string) $pdf['filename'],
                    (string) $pdf['content'],
                    'application/pdf',
                    $message['inlineImages']
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

        $pdf = save_contract_pdf($pdo, $contractId, 'customer', true);

        $messageContent = '<p style="margin:0 0 14px 0;">Sehr geehrte Damen und Herren,</p>'
            . '<p>herzlichen Gl&uuml;ckwunsch und herzlich willkommen bei CleanTeam Group! Wir freuen uns sehr auf die Zusammenarbeit mit Ihnen.</p>'
            . '<p>Der Vertrag befindet sich im Anhang und kann bei Bedarf ausgedruckt werden.</p>';
        $message = render_email_template_message($pdo, $messageContent, [
            'title' => 'Willkommen bei CleanTeam Group',
            'preheader' => 'Herzlich willkommen bei CleanTeam Group.',
            'fromName' => $smtp['from_name'] ?? 'CleanTeam',
            'signatureText' => $smtp['signature'] ?? '',
            'signatureContext' => 'contract_customer',
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
            'Willkommen bei CleanTeam Group',
            $message['html'],
            (string) $pdf['filename'],
            (string) $pdf['content'],
            'application/pdf',
            $message['inlineImages']
        );
    } catch (Throwable $exception) {
        error_log('Kunden-Vertragsbestätigung fehlgeschlagen: ' . $exception->getMessage());
    }
}
