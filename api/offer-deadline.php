<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/crypto.php';
require_once __DIR__ . '/../includes/SmtpMailer.php';
require_once __DIR__ . '/../includes/email_template.php';
require_once __DIR__ . '/../includes/email_settings.php';

require_login();
require_method('POST');
$pdo = db();
ensure_offers_discount_column($pdo);
ensure_offers_discount_expiry_column($pdo);
ensure_offers_existing_contract_columns($pdo);
$offerId = trim((string) ($_GET['id'] ?? ''));
if ($offerId === '') json_error('Vertragsentwurf fehlt.', 422);

expire_offer_discounts($pdo, $offerId);
$stmt = $pdo->prepare(
    'SELECT o.*, c.name AS c_name, c.email AS c_email, c.salutation AS c_salutation,
            c.contact_last_name AS c_contact_last_name, ct.status AS contract_status
     FROM offers o INNER JOIN customers c ON c.id = o.customer_id
     LEFT JOIN contracts ct ON ct.offer_id = o.id WHERE o.id = :id'
);
$stmt->execute(['id' => $offerId]);
$offer = $stmt->fetch();
if (!$offer) json_error('Vertragsentwurf wurde nicht gefunden.', 404);
if (!empty($offer['is_existing_contract'])) json_error('Für Bestandsverträge ist keine Rabattfrist vorgesehen.', 409);
if (in_array((string) ($offer['contract_status'] ?? ''), ['signiert', 'bestaetigt'], true)) {
    json_error('Ein unterschriebener Vertrag kann nicht mehr geändert werden.', 409);
}
if ((float) ($offer['discount_percent'] ?? 0) <= 0 || !empty($offer['discount_expired_at'])) {
    json_error('Dieser Vertragsentwurf hat keinen aktiven Rabatt mehr.', 409);
}

$body = read_json_body();
$action = (string) ($body['action'] ?? '');

if ($action === 'update') {
    $days = filter_var($body['days'] ?? null, FILTER_VALIDATE_INT);
    $hours = filter_var($body['hours'] ?? null, FILTER_VALIDATE_INT);
    if (!is_int($days) || !is_int($hours) || $days < 0 || $days > 365
        || $hours < 0 || $hours > 24 || ($days === 0 && $hours === 0)) {
        json_error('Bitte eine Frist zwischen einer Stunde und 365 Tagen wählen.', 422);
    }
    $update = $pdo->prepare(
        'UPDATE offers SET validity_days = :days, validity_hours = :hours,
            expires_at = DATE_ADD(DATE_ADD(UTC_TIMESTAMP(), INTERVAL :days2 DAY), INTERVAL :hours2 HOUR)
         WHERE id = :id AND discount_percent > 0 AND expires_at > UTC_TIMESTAMP()'
    );
    $update->execute(['days' => $days, 'days2' => $days, 'hours' => $hours, 'hours2' => $hours, 'id' => $offerId]);
    if ($update->rowCount() === 0) json_error('Die Rabattfrist ist inzwischen abgelaufen. Bitte laden Sie die Seite neu.', 409);
    $stmt->execute(['id' => $offerId]);
    $offer = $stmt->fetch();
    json_response(['ok' => true, 'expiresAt' => to_iso($offer['expires_at']), 'validityDays' => $days, 'validityHours' => $hours]);
}

if ($action === 'email') {
    email_delivery_assert_allowed($pdo, 'offer');
    $toEmail = trim((string) ($body['toEmail'] ?? $offer['c_email']));
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) json_error('Bitte eine gültige Kunden-E-Mail-Adresse eintragen.', 422);
    $messageText = trim((string) ($body['message'] ?? ''));
    if ($messageText === '' || strlen($messageText) > 16000) {
        json_error('Bitte einen Nachrichtentext mit höchstens 4000 Zeichen eintragen.', 422);
    }
    $settings = $pdo->query('SELECT * FROM mailbox_settings WHERE id = 1')->fetch();
    if (!$settings || empty($settings['host']) || empty($settings['username']) || empty($settings['password_encrypted'])) {
        json_error('Bitte zuerst das E-Mail-Versand-Konto einrichten.', 422);
    }
    $deadline = (new DateTimeImmutable($offer['expires_at'], new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('Europe/Berlin'))->format('d.m.Y \u\m H:i \U\h\r');
    $discounted = number_format((float) $offer['price'], 2, ',', '.') . ' €';
    $regular = number_format((float) $offer['base_price'], 2, ',', '.') . ' €';
    $url = base_url() . '/o.php?token=' . $offer['token'];
    $content = '<p>' . email_plain_text_html($messageText) . '</p>'
        . '<p><strong>Rabattpreis:</strong> ' . email_h($discounted) . ' netto monatlich<br>'
        . '<strong>Rabatt gültig bis:</strong> ' . email_h($deadline) . '<br>'
        . '<strong>Preis nach Ablauf:</strong> ' . email_h($regular) . ' netto monatlich</p>'
        . '<p>Bitte erteilen Sie die Auftragsbestätigung bis zu diesem Zeitpunkt, wenn Sie den Rabatt nutzen möchten. Danach entfällt der Rabatt automatisch. Der Link bleibt zum regulären Preis nutzbar.</p>'
        . email_button_html($url, 'Jetzt die Auftragsbestätigung erteilen');
    $message = render_email_template_message($pdo, $content, [
        'title' => 'Aktualisierte Rabattfrist für Ihre Auftragsbestätigung',
        'preheader' => 'Bitte erteilen Sie die Auftragsbestätigung innerhalb der neuen Rabattfrist.',
        'fromName' => $settings['from_name'] ?? 'CleanTeam',
        'signatureText' => $settings['signature'] ?? '',
        'signatureContext' => 'offer',
    ]);
    try {
        $mailer = new SmtpMailer($settings['host'], (int) $settings['smtp_port'], $settings['smtp_encryption'],
            $settings['username'], decrypt_secret($settings['password_encrypted']));
        $mailer->send($settings['username'], $settings['from_name'], $toEmail, (string) $offer['c_name'],
            'Ihre neue Rabattfrist für die CleanTeam-Auftragsbestätigung', $message['html'], true, $message['inlineImages']);
    } catch (Throwable $exception) {
        json_error('Frist gespeichert, aber E-Mail-Versand fehlgeschlagen: ' . $exception->getMessage(), 502);
    }
    json_response(['ok' => true, 'sentTo' => $toEmail]);
}

json_error('Unbekannte Aktion.', 422);
