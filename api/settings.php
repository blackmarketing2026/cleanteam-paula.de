<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/crypto.php';
require_once __DIR__ . '/../includes/SmtpMailer.php';
require_once __DIR__ . '/../includes/email_template.php';
require_once __DIR__ . '/../includes/email_settings.php';

require_admin();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

function load_smtp_settings(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM smtp_settings WHERE id = 1');
    $row = $stmt->fetch();

    return $row ?: [
        'host' => '', 'port' => 587, 'encryption' => 'tls', 'username' => '',
        'password_encrypted' => '', 'from_name' => 'CleanTeam', 'from_email' => '', 'updated_at' => null,
    ];
}

if ($method === 'GET') {
    $settings = load_smtp_settings($pdo);
    json_response([
        'host' => $settings['host'],
        'port' => (int) $settings['port'],
        'encryption' => $settings['encryption'],
        'username' => $settings['username'],
        'hasPassword' => ($settings['password_encrypted'] ?? '') !== '',
        'fromName' => $settings['from_name'],
        'fromEmail' => $settings['from_email'],
        'updatedAt' => to_iso($settings['updated_at']),
    ]);
}

if ($method === 'POST' && ($_GET['action'] ?? '') === 'test') {
    email_delivery_assert_allowed($pdo, 'test');

    $settings = load_smtp_settings($pdo);
    if ($settings['host'] === '' || $settings['from_email'] === '') {
        json_error('Bitte zuerst die SMTP-Einstellungen speichern.', 422);
    }

    $body = read_json_body();
    $to = trim((string) ($body['to'] ?? $settings['from_email']));
    if ($to === '') {
        json_error('Ziel-E-Mail-Adresse fehlt.', 422);
    }

    try {
        $mailer = new SmtpMailer(
            $settings['host'],
            (int) $settings['port'],
            $settings['encryption'],
            $settings['username'],
            decrypt_secret($settings['password_encrypted'])
        );
        $htmlBody = render_email_template(
            $pdo,
            '<p style="margin:0;">Diese Test-E-Mail bestätigt, dass Ihre SMTP-Einstellungen im CleanTeam Dashboard funktionieren.</p>',
            [
                'title' => 'CleanTeam Dashboard – Test-E-Mail',
                'preheader' => 'Ihre SMTP-Einstellungen funktionieren.',
                'fromName' => $settings['from_name'],
            ]
        );
        $mailer->send(
            $settings['from_email'],
            $settings['from_name'],
            $to,
            $to,
            'CleanTeam Dashboard – Test-E-Mail',
            $htmlBody,
            true
        );
    } catch (Throwable $exception) {
        json_error('Test-E-Mail fehlgeschlagen: ' . $exception->getMessage(), 502);
    }

    json_response(['ok' => true]);
}

if ($method === 'POST') {
    $body = read_json_body();
    $host = trim((string) ($body['host'] ?? ''));
    $port = (int) ($body['port'] ?? 587);
    $encryption = (string) ($body['encryption'] ?? 'tls');
    $username = trim((string) ($body['username'] ?? ''));
    $password = (string) ($body['password'] ?? '');
    $fromName = trim((string) ($body['fromName'] ?? 'CleanTeam'));
    $fromEmail = trim((string) ($body['fromEmail'] ?? ''));

    if (!in_array($encryption, ['none', 'ssl', 'tls'], true)) {
        json_error('Ungültige Verschlüsselung.', 422);
    }

    if ($host === '' || $fromEmail === '' || $port <= 0) {
        json_error('Host, Port und Absender-E-Mail sind erforderlich.', 422);
    }

    $current = load_smtp_settings($pdo);
    try {
        $passwordEncrypted = $password !== '' ? encrypt_secret($password) : ($current['password_encrypted'] ?? '');
    } catch (Throwable $exception) {
        json_error('Serverkonfiguration unvollständig: ' . $exception->getMessage(), 500);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO smtp_settings (id, host, port, encryption, username, password_encrypted, from_name, from_email, updated_at)
         VALUES (1, :host, :port, :encryption, :username, :password_encrypted, :from_name, :from_email, UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE host = :host2, port = :port2, encryption = :encryption2, username = :username2,
            password_encrypted = :password_encrypted2, from_name = :from_name2, from_email = :from_email2, updated_at = UTC_TIMESTAMP()'
    );
    $stmt->execute([
        'host' => $host, 'port' => $port, 'encryption' => $encryption, 'username' => $username,
        'password_encrypted' => $passwordEncrypted, 'from_name' => $fromName, 'from_email' => $fromEmail,
        'host2' => $host, 'port2' => $port, 'encryption2' => $encryption, 'username2' => $username,
        'password_encrypted2' => $passwordEncrypted, 'from_name2' => $fromName, 'from_email2' => $fromEmail,
    ]);

    json_response(['ok' => true]);
}

json_error('Methode nicht erlaubt.', 405);
