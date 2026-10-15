<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/crypto.php';
require_once __DIR__ . '/SmtpMailer.php';
require_once __DIR__ . '/contract_pdf.php';
require_once __DIR__ . '/email_template.php';
require_once __DIR__ . '/email_settings.php';

const CONTRACT_OPEN_DEFAULT_RECIPIENTS = ['cleanteam@function-concept.de', 'info@cleanteam-group.com'];

function ensure_contract_open_notification_columns(PDO $pdo): void
{
    foreach ([
        'open_enabled' => 'ALTER TABLE contract_notifications ADD COLUMN open_enabled TINYINT(1) NOT NULL DEFAULT 1',
        'open_recipients' => 'ALTER TABLE contract_notifications ADD COLUMN open_recipients TEXT NULL',
    ] as $column => $sql) {
        if (!$pdo->query("SHOW COLUMNS FROM contract_notifications LIKE '{$column}'")->fetch()) $pdo->exec($sql);
    }
    if (!$pdo->query("SHOW COLUMNS FROM offers LIKE 'contract_open_notified_at'")->fetch()) {
        $pdo->exec('ALTER TABLE offers ADD COLUMN contract_open_notified_at DATETIME NULL');
    }
}

function load_contract_notification_settings(PDO $pdo): array
{
    ensure_contract_open_notification_columns($pdo);
    $stmt = $pdo->query('SELECT * FROM contract_notifications WHERE id = 1');
    $row = $stmt->fetch();

    return $row ?: ['enabled' => 0, 'recipients' => '', 'open_enabled' => 1, 'open_recipients' => '', 'updated_at' => null];
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

function contract_open_recipients(array $settings): array
{
    $configured = contract_notification_recipients((string) ($settings['open_recipients'] ?? ''));
    if ($configured !== []) return $configured;
    return CONTRACT_OPEN_DEFAULT_RECIPIENTS;
}

function contract_call_url(string $phone): ?string
{
    $dial = preg_replace('/[^0-9+]/', '', trim($phone)) ?? '';
    $dial = preg_replace('/(?!^)\+/', '', $dial) ?? '';
    return strlen(preg_replace('/\D/', '', $dial) ?? '') >= 3 ? 'tel:' . $dial : null;
}

function contract_open_is_notifiable(array $context, string $linkKind): bool
{
    return ($context['status'] ?? '') === 'entwurf'
        && ($linkKind !== 'quote' || ($context['quote_status'] ?? '') === 'signing');
}

function contract_open_message_content(array $context): string
{
    $phone = trim((string) ($context['phone'] ?? ''));
    $callUrl = contract_call_url($phone);
    $contact = trim((string) ($context['salutation'] ?? '') . ' ' . (string) ($context['contact_last_name'] ?? ''));
    $html = '<p style="margin:0 0 14px 0;">Der Kunde hat gerade den Vertrag geöffnet und befindet sich im Unterschriftsprozess. Der Vertrag ist noch nicht unterschrieben.</p>'
        . '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:0 0 16px 0;">'
        . '<tr><td style="padding:5px 10px 5px 0;font-weight:700;">Kunde</td><td>' . email_h((string) ($context['name'] ?? '')) . '</td></tr>'
        . '<tr><td style="padding:5px 10px 5px 0;font-weight:700;">Ansprechpartner</td><td>' . email_h($contact) . '</td></tr>'
        . '<tr><td style="padding:5px 10px 5px 0;font-weight:700;">Telefonnummer</td><td>' . email_h($phone !== '' ? $phone : 'Nicht hinterlegt') . '</td></tr>'
        . '</table>';
    return $callUrl !== null ? $html . email_button_html($callUrl, 'Kunden jetzt anrufen') : $html;
}

// Benachrichtigt intern beim Einstieg in eine noch offene Vertragsansicht.
// Der atomare Zeitstempel verhindert E-Mail-Fluten durch Neuladen oder Tab-Wechsel.
function notify_contract_opened(PDO $pdo, string $offerId, string $linkKind): void
{
    $claimedAt = null;
    try {
        if (!email_delivery_is_allowed($pdo, 'internal_contract_notification')) return;
        $settings = load_contract_notification_settings($pdo);
        if (empty($settings['open_enabled'])) return;
        $smtp = load_mailbox_smtp($pdo);
        if ($smtp === null) return;
        $recipients = contract_open_recipients($settings);
        if ($recipients === []) return;

        $stmt = $pdo->prepare('SELECT o.id, o.quote_status, c.name, c.phone, c.salutation, c.contact_last_name, ct.status
            FROM offers o JOIN customers c ON c.id = o.customer_id
            JOIN contracts ct ON ct.offer_id = o.id WHERE o.id = :id');
        $stmt->execute(['id' => $offerId]);
        $context = $stmt->fetch();
        if (!$context || !contract_open_is_notifiable($context, $linkKind)) return;

        $messageContent = contract_open_message_content($context);
        $message = render_email_template_message($pdo, $messageContent, [
            'title' => 'Vertrag geöffnet',
            'preheader' => 'Kunde ist im Unterschriftsprozess.',
            'fromName' => $smtp['from_name'] ?? 'CleanTeam',
            'signatureText' => $smtp['signature'] ?? '',
            'signatureContext' => 'internal_contract_notification',
        ]);

        $claimedAt = gmdate('Y-m-d H:i:s');
        $threshold = gmdate('Y-m-d H:i:s', time() - 15 * 60);
        $claim = $pdo->prepare('UPDATE offers SET contract_open_notified_at = :claimed_at
            WHERE id = :id AND (contract_open_notified_at IS NULL OR contract_open_notified_at <= :threshold)
            AND EXISTS (SELECT 1 FROM contracts WHERE offer_id = offers.id AND status NOT IN (\'signiert\', \'bestaetigt\'))');
        $claim->execute(['claimed_at' => $claimedAt, 'id' => $offerId, 'threshold' => $threshold]);
        if ($claim->rowCount() !== 1) return;

        $mailer = new SmtpMailer($smtp['host'], (int) $smtp['smtp_port'], $smtp['smtp_encryption'],
            $smtp['username'], decrypt_secret($smtp['password_encrypted']));
        $sent = false;
        foreach ($recipients as $recipient) {
            try {
                $mailer->send($smtp['username'], $smtp['from_name'], $recipient, $recipient,
                    'Vertrag geöffnet - ' . $context['name'], $message['html'], true, $message['inlineImages']);
                $sent = true;
            } catch (Throwable $exception) {
                error_log('Vertrag-geöffnet-Benachrichtigung fehlgeschlagen (' . $recipient . '): ' . $exception->getMessage());
            }
        }
        if ($sent) return;
    } catch (Throwable $exception) {
        error_log('Vertrag-geöffnet-Benachrichtigung fehlgeschlagen: ' . $exception->getMessage());
    }
    if ($claimedAt !== null) {
        try {
            $pdo->prepare('UPDATE offers SET contract_open_notified_at = NULL WHERE id = :id AND contract_open_notified_at = :claimed_at')
                ->execute(['id' => $offerId, 'claimed_at' => $claimedAt]);
        } catch (Throwable $exception) {
            error_log('Vertrag-geöffnet-Meldestatus konnte nicht zurückgesetzt werden: ' . $exception->getMessage());
        }
    }
}

// Laedt Vertrag, Vertragsentwurf und Kunde zusammen. Gibt null zurueck, wenn irgendetwas davon fehlt.
function load_contract_context(PDO $pdo, string $contractId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT
            c.*,
            o.id as offer_id_alias, o.customer_id, o.is_existing_contract, o.square_meters, o.interval_label, o.service, o.start_date, o.notes AS offer_notes, o.price, o.created_at AS offer_created_at, o.token AS offer_token,
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
    $contract = array_filter($row, fn($key) => !in_array($key, ['offer_id_alias', 'customer_id_alias', 'name', 'email', 'phone', 'salutation', 'contact_last_name', 'address', 'house_number', 'zip', 'city', 'is_existing_contract', 'square_meters', 'interval_label', 'service', 'start_date', 'offer_notes', 'price', 'offer_created_at', 'offer_token']), ARRAY_FILTER_USE_KEY);
    $offer = array_intersect_key($row, array_flip(['id', 'customer_id', 'is_existing_contract', 'square_meters', 'interval_label', 'service', 'start_date', 'notes', 'price', 'created_at']));
    $offer['id'] = $row['offer_id_alias']; // 'id' kommt von contracts, also überschreiben
    $offer['notes'] = $row['offer_notes'];
    $offer['created_at'] = $row['offer_created_at'];
    $offer['token'] = $row['offer_token'];
    $customer = array_intersect_key($row, array_flip(['id', 'name', 'email', 'phone', 'salutation', 'contact_last_name', 'address', 'house_number', 'zip', 'city']));
    $customer['id'] = $row['customer_id_alias'];

    return ['contract' => $contract, 'offer' => $offer, 'customer' => $customer];
}

// Liefert das E-Mail-Versand-Konto (Einstellungen > E-Mails), sofern vollstaendig konfiguriert,
// sonst null. Bewusst nicht die separaten "SMTP-Server-Einstellungen" - die sind oft nie befuellt,
// das Versand-Konto dagegen schon.
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

// Baut die Willkommens-/Auftragsbestätigungs-E-Mail für einen unterschriebenen Vertrag und
// verschickt sie inkl. PDF-Anhang an die angegebene Adresse. Wirft bei Fehlern (kein SMTP-Konto,
// PDF nicht ladbar, Versand fehlgeschlagen) eine Exception - Aufrufer entscheiden, ob das best
// effort (automatischer Versand nach Unterschrift) oder fatal (manueller Versand über die UI) ist.
function send_signed_contract_email_to_customer(PDO $pdo, array $context, string $toEmail): void
{
    $smtp = load_mailbox_smtp($pdo);
    if ($smtp === null) {
        throw new RuntimeException('Kein E-Mail-Versand-Konto unter Einstellungen > E-Mails eingerichtet.');
    }

    $pdf = save_contract_pdf($pdo, $context['contract']['id'], 'customer', true);

    $signatureSettings = load_email_signature_settings($pdo);
    $senderName = trim((string) $signatureSettings['senderName']) !== ''
        ? trim((string) $signatureSettings['senderName'])
        : 'Ihr CleanTeam-Team';

    $downloadToken = trim((string) ($context['offer']['token'] ?? ''));
    $downloadUrl = $downloadToken !== ''
        ? base_url() . '/contract.php?token=' . rawurlencode($downloadToken) . '&format=pdf&download=1'
        : '';
    $documentLabel = 'Ihre unterschriebene Auftragsbestätigung';
    $downloadLabel = 'Auftragsbestätigung herunterladen';

    $messageContent = '<p style="margin:0 0 14px 0;">Willkommen bei CleanTeam!</p>'
        . '<p>Mein Name ist ' . email_h($senderName) . '. Ich bin Ihr Ansprechpartner f&uuml;r Ihre Auftragsbest&auml;tigung.</p>'
        . '<p>Sie finden ' . $documentLabel . ' im Anhang dieser E-Mail. Alternativ können Sie das Dokument jederzeit über den folgenden Button herunterladen:</p>'
        . ($downloadUrl !== '' ? email_button_html($downloadUrl, $downloadLabel) : '')
        . '<p>Bei Fragen stehe ich Ihnen gerne zur Verf&uuml;gung &ndash; per E-Mail oder direkt telefonisch im B&uuml;ro.</p>';
    $message = render_email_template_message($pdo, $messageContent, [
        'title' => 'Ihre Auftragsbestätigung von CleanTeam',
        'preheader' => 'Ihre unterschriebene Auftragsbestätigung steht bereit.',
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
        $toEmail,
        $context['customer']['name'],
        'Ihre Auftragsbestätigung von CleanTeam',
        $message['html'],
        (string) $pdf['filename'],
        (string) $pdf['content'],
        'application/pdf',
        $message['inlineImages']
    );
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
        if (!empty($context['offer']['is_existing_contract'])) {
            return;
        }
        $customerEmail = trim((string) $context['customer']['email']);
        if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        send_signed_contract_email_to_customer($pdo, $context, $customerEmail);
    } catch (Throwable $exception) {
        error_log('Kunden-Vertragsbestätigung fehlgeschlagen: ' . $exception->getMessage());
    }
}
