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
email_delivery_assert_allowed($pdo, 'offer');

$offerId = (string) ($_GET['id'] ?? '');
if ($offerId === '') {
    json_error('Vertrags-ID fehlt.', 422);
}

$stmt = $pdo->prepare(
    'SELECT o.*, c.name AS c_name, c.email AS c_email, c.salutation AS c_salutation, c.contact_last_name AS c_contact_last_name
     FROM offers o INNER JOIN customers c ON c.id = o.customer_id WHERE o.id = :id'
);
$stmt->execute(['id' => $offerId]);
$offer = $stmt->fetch();

if (!$offer) {
    json_error('Vertrag wurde nicht gefunden.', 404);
}

$requestBody = read_json_body();
$toEmail = trim((string) ($requestBody['toEmail'] ?? ''));
if ($toEmail === '') {
    $toEmail = trim((string) $offer['c_email']);
}

if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
    json_error('Bitte eine gültige Ziel-E-Mail-Adresse eintragen.', 422);
}

$settingsStmt = $pdo->query('SELECT * FROM mailbox_settings WHERE id = 1');
$settings = $settingsStmt->fetch();

if (!$settings || $settings['host'] === '' || $settings['username'] === '' || ($settings['password_encrypted'] ?? '') === '') {
    json_error('Bitte zuerst das E-Mail-Versand-Konto unter Einstellungen > E-Mails einrichten.', 422);
}

$publicUrl = base_url() . '/offer.php?token=' . $offer['token'];
$validUntil = (new DateTimeImmutable($offer['expires_at'], new DateTimeZone('UTC')))->format('d.m.Y');
$validityDays = (int) ($offer['validity_days'] ?? 14);
$contactName = $offer['c_salutation'] . ' ' . $offer['c_contact_last_name'];

$companyName = trim((string) $offer['c_name']);
$subject = 'Angebot CleanTeam für Firma ' . $companyName;

$bodyContent = '<p style="margin:0 0 14px 0;">Guten Tag ' . email_h($contactName) . ',</p>'
    . '<p>mein Name ist Frau Seidler, ich bin Ihre Ansprechpartnerin bei CleanTeam. Vielen Dank für Ihr Interesse an unserem Angebot für ' . email_h($companyName) . '.</p>'
    . '<p>Ihr Vertrag wird komplett online abgeschlossen. Sobald Sie unterschrieben haben, erhalten Sie den fertigen Vertrag automatisch als PDF per E-Mail.</p>'
    . '<h2 style="margin:24px 0 10px 0;color:#08325f;font-size:17px;">Online-Prozess</h2>'
    . '<p>Alle Informationen zum weiteren Ablauf finden Sie im Online-Prozess. Klicken Sie dazu einfach auf den folgenden Button:</p>'
    . email_button_html($publicUrl, 'Jetzt Vertrag online abschließen')
    . '<p style="color:#51657d;font-size:13px;">Der Button ist aus Datenschutzgründen nur einmal nutzbar und ' . $validityDays . ' Tage lang gültig, also bis zum ' . email_h($validUntil) . '. Danach verfällt er automatisch. Wurde er versehentlich schon einmal geöffnet, muss er erst wieder von uns freigegeben werden, bevor er erneut funktioniert.</p>'
    . '<img src="' . email_h(base_url() . '/api/track-open.php?token=' . $offer['token']) . '" width="1" height="1" alt="" style="display:none;width:1px;height:1px;border:0;" />';
$message = render_email_template_message($pdo, $bodyContent, [
    'title' => 'Ihr Angebot von CleanTeam',
    'preheader' => 'Bitte schließen Sie Ihren Vertrag jetzt online ab.',
    'fromName' => $settings['from_name'] ?? 'CleanTeam',
    'signatureText' => $settings['signature'] ?? '',
    'signatureContext' => 'offer',
]);
$body = $message['html'];

try {
    $mailer = new SmtpMailer(
        $settings['host'],
        (int) $settings['smtp_port'],
        $settings['smtp_encryption'],
        $settings['username'],
        decrypt_secret($settings['password_encrypted'])
    );

    $mailer->send(
        $settings['username'],
        $settings['from_name'],
        $toEmail,
        $offer['c_name'],
        $subject,
        $body,
        true,
        $message['inlineImages']
    );
} catch (Throwable $exception) {
    json_error('E-Mail-Versand fehlgeschlagen: ' . $exception->getMessage(), 502);
}

$pdo->prepare('UPDATE offers SET sent_at = UTC_TIMESTAMP() WHERE id = :id')->execute(['id' => $offerId]);

// Damit der Vertrag ab dem Versand in der Vertragsliste sichtbar ist (mit Zustellungsstatus),
// statt erst, wenn der Kunde den Link zum ersten Mal oeffnet.
ensure_contract_for_offer($pdo, $offer);

json_response(['ok' => true, 'sentAt' => now_iso(), 'sentTo' => $toEmail]);
