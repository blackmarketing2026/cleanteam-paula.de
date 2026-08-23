<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/crypto.php';

require_login();

$pdo = db();
$currentUser = current_user();
if ($currentUser === null) {
    logout_user();
    json_error('Nicht angemeldet.', 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = (string) ($_GET['action'] ?? '');

function load_mailbox_settings(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM mailbox_settings WHERE id = 1');
    $row = $stmt->fetch();

    return $row ?: [
        'host' => '', 'imap_port' => 993, 'imap_encryption' => 'ssl',
        'smtp_port' => 587, 'smtp_encryption' => 'tls', 'username' => '',
        'password_encrypted' => '', 'from_name' => 'CleanTeam', 'signature' => '', 'updated_at' => null,
    ];
}

if ($method === 'GET' && $action === 'settings') {
    $settings = load_mailbox_settings($pdo);
    $configured = $settings['host'] !== '' && $settings['username'] !== '' && ($settings['password_encrypted'] ?? '') !== '';
    $response = [
        'username' => $settings['username'],
        'signature' => $settings['signature'] ?? '',
        'configured' => $configured,
        'canManageSettings' => $currentUser['isAdmin'],
        'updatedAt' => to_iso($settings['updated_at']),
    ];

    if (!$currentUser['isAdmin']) {
        json_response($response + [
            'host' => '',
            'smtpPort' => 587,
            'smtpEncryption' => 'tls',
            'hasPassword' => false,
            'fromName' => '',
        ]);
    }

    json_response($response + [
        'host' => $settings['host'],
        'smtpPort' => (int) $settings['smtp_port'],
        'smtpEncryption' => $settings['smtp_encryption'],
        'hasPassword' => ($settings['password_encrypted'] ?? '') !== '',
        'fromName' => $settings['from_name'],
    ]);
}

if ($method === 'POST' && $action === 'settings') {
    require_admin();

    $body = read_json_body();
    $host = trim((string) ($body['host'] ?? ''));
    $smtpPort = (int) ($body['smtpPort'] ?? 587);
    $smtpEncryption = (string) ($body['smtpEncryption'] ?? 'tls');
    $username = trim((string) ($body['username'] ?? ''));
    $password = (string) ($body['password'] ?? '');
    $fromName = trim((string) ($body['fromName'] ?? 'CleanTeam'));
    $signature = (string) ($body['signature'] ?? '');

    if (!in_array($smtpEncryption, ['none', 'ssl', 'tls'], true)) {
        json_error('Ungültige Verschlüsselung.', 422);
    }

    if ($host === '' || $username === '' || $smtpPort <= 0) {
        json_error('Host, Benutzername und Port sind erforderlich.', 422);
    }

    $current = load_mailbox_settings($pdo);
    try {
        $passwordEncrypted = $password !== '' ? encrypt_secret($password) : ($current['password_encrypted'] ?? '');
    } catch (Throwable $exception) {
        json_error('Serverkonfiguration unvollständig: ' . $exception->getMessage(), 500);
    }

    // imap_port/imap_encryption bleiben unveraendert (Spalten aus der frueheren Postfach-Funktion,
    // werden nicht mehr genutzt, aber ohne Migration in der Tabelle belassen).
    $stmt = $pdo->prepare(
        'INSERT INTO mailbox_settings (id, host, imap_port, imap_encryption, smtp_port, smtp_encryption, username, password_encrypted, from_name, signature, updated_at)
         VALUES (1, :host, :imap_port, :imap_encryption, :smtp_port, :smtp_encryption, :username, :password_encrypted, :from_name, :signature, UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE host = :host2, smtp_port = :smtp_port2, smtp_encryption = :smtp_encryption2,
            username = :username2, password_encrypted = :password_encrypted2, from_name = :from_name2,
            signature = :signature2, updated_at = UTC_TIMESTAMP()'
    );
    $stmt->execute([
        'host' => $host, 'imap_port' => (int) $current['imap_port'], 'imap_encryption' => $current['imap_encryption'],
        'smtp_port' => $smtpPort, 'smtp_encryption' => $smtpEncryption, 'username' => $username,
        'password_encrypted' => $passwordEncrypted, 'from_name' => $fromName, 'signature' => $signature,
        'host2' => $host, 'smtp_port2' => $smtpPort, 'smtp_encryption2' => $smtpEncryption,
        'username2' => $username, 'password_encrypted2' => $passwordEncrypted, 'from_name2' => $fromName,
        'signature2' => $signature,
    ]);

    json_response(['ok' => true]);
}

json_error('Unbekannte Aktion.', 404);
