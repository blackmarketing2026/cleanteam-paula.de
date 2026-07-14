<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/crypto.php';
require_once __DIR__ . '/../includes/SmtpMailer.php';

require_login();
require_method('POST');

$pdo = db();
$offerId = (string) ($_GET['id'] ?? '');
if ($offerId === '') {
    json_error('Kostenvoranschlags-ID fehlt.', 422);
}

$stmt = $pdo->prepare(
    'SELECT o.*, c.name AS c_name, c.email AS c_email, c.salutation AS c_salutation, c.contact_last_name AS c_contact_last_name
     FROM offers o INNER JOIN customers c ON c.id = o.customer_id WHERE o.id = :id'
);
$stmt->execute(['id' => $offerId]);
$offer = $stmt->fetch();

if (!$offer) {
    json_error('Kostenvoranschlag wurde nicht gefunden.', 404);
}

$settingsStmt = $pdo->query('SELECT * FROM mailbox_settings WHERE id = 1');
$settings = $settingsStmt->fetch();

if (!$settings || $settings['host'] === '' || $settings['username'] === '' || ($settings['password_encrypted'] ?? '') === '') {
    json_error('Bitte zuerst das Postfach unter "Postfach" einrichten.', 422);
}

$publicUrl = base_url() . '/offer.php?token=' . $offer['token'];
$validUntil = (new DateTimeImmutable($offer['expires_at'], new DateTimeZone('UTC')))->format('d.m.Y');
$contactName = $offer['c_salutation'] . ' ' . $offer['c_contact_last_name'];

$body = '<div style="font-family: Arial, sans-serif; color: #1c2733; line-height: 1.5;">'
    . '<p>Guten Tag ' . htmlspecialchars($contactName, ENT_QUOTES, 'UTF-8') . ',</p>'
    . '<p>vielen Dank für Ihr Interesse an CleanTeam. Ihr individueller Kostenvoranschlag steht ab sofort online bereit:</p>'
    . '<p><a href="' . htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:12px 20px;background:#1a6de0;color:#ffffff;text-decoration:none;border-radius:6px;">Kostenvoranschlag ansehen</a></p>'
    . '<p>Der Link ist bis zum ' . $validUntil . ' gültig.</p>'
    . '<p>Mit freundlichen Grüßen<br>Ihr CleanTeam</p>'
    . '</div>';

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
        $offer['c_email'],
        $offer['c_name'],
        'Ihr Kostenvoranschlag von CleanTeam',
        $body,
        true
    );
} catch (Throwable $exception) {
    json_error('E-Mail-Versand fehlgeschlagen: ' . $exception->getMessage(), 502);
}

$pdo->prepare('UPDATE offers SET sent_at = UTC_TIMESTAMP() WHERE id = :id')->execute(['id' => $offerId]);

json_response(['ok' => true, 'sentAt' => now_iso()]);
