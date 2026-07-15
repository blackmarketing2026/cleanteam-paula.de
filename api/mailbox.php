<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/crypto.php';
require_once __DIR__ . '/../includes/SmtpMailer.php';

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

function mailbox_base_string(array $settings): string
{
    $flag = $settings['imap_encryption'] === 'ssl' ? '/ssl/novalidate-cert' : ($settings['imap_encryption'] === 'tls' ? '/tls/novalidate-cert' : '/notls');

    return '{' . $settings['host'] . ':' . $settings['imap_port'] . '/imap' . $flag . '}';
}

function mailbox_connect(array $settings)
{
    if ($settings['host'] === '' || $settings['username'] === '') {
        json_error('Postfach ist noch nicht eingerichtet.', 422);
    }

    $password = decrypt_secret($settings['password_encrypted']);
    $conn = @imap_open(mailbox_base_string($settings) . 'INBOX', $settings['username'], $password);

    if ($conn === false) {
        json_error('IMAP-Verbindung fehlgeschlagen: ' . imap_last_error(), 502);
    }

    return $conn;
}

// Sucht den tatsächlichen Namen des Postausgang-Ordners (Server-abhängig, z. B. "Sent",
// "Sent Items" oder "Gesendet") und legt ihn beim ersten Senden an, falls er noch fehlt.
function mailbox_resolve_sent_folder($conn, string $base): string
{
    $candidates = ['sent', 'gesendet'];
    $list = @imap_list($conn, $base, '*');

    if ($list !== false) {
        foreach ($list as $full) {
            $short = substr($full, strlen($base));
            $decoded = @imap_utf7_decode($short);
            $decoded = $decoded !== false ? $decoded : $short;
            foreach ($candidates as $needle) {
                if (stripos($decoded, $needle) !== false) {
                    return $short;
                }
            }
        }
    }

    @imap_createmailbox($conn, $base . 'Sent');

    return 'Sent';
}

function mailbox_open_folder(array $settings, string $folder)
{
    $conn = mailbox_connect($settings);

    if ($folder === 'sent') {
        $base = mailbox_base_string($settings);
        $sentFolder = mailbox_resolve_sent_folder($conn, $base);
        if (!@imap_reopen($conn, $base . $sentFolder)) {
            imap_close($conn);
            json_error('Postausgang-Ordner konnte nicht geöffnet werden.', 502);
        }
    }

    return $conn;
}

function mailbox_decode_header(?string $value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    $decoded = @imap_mime_header_decode($value);
    if ($decoded === false || !is_array($decoded)) {
        return $value;
    }

    $result = '';
    foreach ($decoded as $part) {
        $charset = strtoupper($part->charset ?? 'default') === 'DEFAULT' ? 'ISO-8859-1' : $part->charset;
        $text = $part->text;
        if (strtoupper($charset) !== 'UTF-8') {
            $converted = @mb_convert_encoding($text, 'UTF-8', $charset);
            $text = $converted !== false ? $converted : $text;
        }
        $result .= $text;
    }

    return $result;
}

function mailbox_decode_body(string $body, int $encoding): string
{
    switch ($encoding) {
        case 3:
            return base64_decode($body) ?: $body;
        case 4:
            return quoted_printable_decode($body);
        default:
            return $body;
    }
}

function mailbox_part_charset($structure): string
{
    if (!isset($structure->parameters)) {
        return 'UTF-8';
    }

    foreach ($structure->parameters as $param) {
        if (strtoupper($param->attribute) === 'CHARSET') {
            return $param->value;
        }
    }

    return 'UTF-8';
}

function mailbox_find_body_parts($conn, int $msgNo, $structure, string $prefix, array &$found): void
{
    if (isset($structure->parts) && count($structure->parts)) {
        foreach ($structure->parts as $index => $part) {
            $partNumber = $prefix === '' ? (string) ($index + 1) : $prefix . '.' . ($index + 1);
            mailbox_find_body_parts($conn, $msgNo, $part, $partNumber, $found);
        }
        return;
    }

    $subtype = strtoupper($structure->subtype ?? '');
    if ((int) $structure->type !== 0 || ($subtype !== 'HTML' && $subtype !== 'PLAIN')) {
        return;
    }

    $partNumber = $prefix === '' ? '1' : $prefix;
    $raw = imap_fetchbody($conn, $msgNo, $partNumber);
    $decoded = mailbox_decode_body($raw, (int) ($structure->encoding ?? 0));

    $charset = mailbox_part_charset($structure);
    if (strtoupper($charset) !== 'UTF-8') {
        $converted = @mb_convert_encoding($decoded, 'UTF-8', $charset);
        $decoded = $converted !== false ? $converted : $decoded;
    }

    $found[$subtype === 'HTML' ? 'html' : 'plain'] = $decoded;
}

function mailbox_extract_body($conn, int $msgNo): array
{
    $structure = imap_fetchstructure($conn, $msgNo);
    $found = ['html' => null, 'plain' => null];
    mailbox_find_body_parts($conn, $msgNo, $structure, '', $found);

    return $found;
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
            'imapPort' => 993,
            'imapEncryption' => 'ssl',
            'smtpPort' => 587,
            'smtpEncryption' => 'tls',
            'hasPassword' => false,
            'fromName' => '',
        ]);
    }

    json_response($response + [
        'host' => $settings['host'],
        'imapPort' => (int) $settings['imap_port'],
        'imapEncryption' => $settings['imap_encryption'],
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
    $imapPort = (int) ($body['imapPort'] ?? 993);
    $imapEncryption = (string) ($body['imapEncryption'] ?? 'ssl');
    $smtpPort = (int) ($body['smtpPort'] ?? 587);
    $smtpEncryption = (string) ($body['smtpEncryption'] ?? 'tls');
    $username = trim((string) ($body['username'] ?? ''));
    $password = (string) ($body['password'] ?? '');
    $fromName = trim((string) ($body['fromName'] ?? 'CleanTeam'));
    $signature = (string) ($body['signature'] ?? '');

    if (!in_array($imapEncryption, ['none', 'ssl', 'tls'], true) || !in_array($smtpEncryption, ['none', 'ssl', 'tls'], true)) {
        json_error('Ungültige Verschlüsselung.', 422);
    }

    if ($host === '' || $username === '' || $imapPort <= 0 || $smtpPort <= 0) {
        json_error('Host, Benutzername und Ports sind erforderlich.', 422);
    }

    $current = load_mailbox_settings($pdo);
    try {
        $passwordEncrypted = $password !== '' ? encrypt_secret($password) : ($current['password_encrypted'] ?? '');
    } catch (Throwable $exception) {
        json_error('Serverkonfiguration unvollständig: ' . $exception->getMessage(), 500);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO mailbox_settings (id, host, imap_port, imap_encryption, smtp_port, smtp_encryption, username, password_encrypted, from_name, signature, updated_at)
         VALUES (1, :host, :imap_port, :imap_encryption, :smtp_port, :smtp_encryption, :username, :password_encrypted, :from_name, :signature, UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE host = :host2, imap_port = :imap_port2, imap_encryption = :imap_encryption2,
            smtp_port = :smtp_port2, smtp_encryption = :smtp_encryption2, username = :username2,
            password_encrypted = :password_encrypted2, from_name = :from_name2, signature = :signature2, updated_at = UTC_TIMESTAMP()'
    );
    $stmt->execute([
        'host' => $host, 'imap_port' => $imapPort, 'imap_encryption' => $imapEncryption,
        'smtp_port' => $smtpPort, 'smtp_encryption' => $smtpEncryption, 'username' => $username,
        'password_encrypted' => $passwordEncrypted, 'from_name' => $fromName, 'signature' => $signature,
        'host2' => $host, 'imap_port2' => $imapPort, 'imap_encryption2' => $imapEncryption,
        'smtp_port2' => $smtpPort, 'smtp_encryption2' => $smtpEncryption, 'username2' => $username,
        'password_encrypted2' => $passwordEncrypted, 'from_name2' => $fromName, 'signature2' => $signature,
    ]);

    json_response(['ok' => true]);
}

if ($method === 'GET' && $action === 'inbox') {
    $folder = (string) ($_GET['folder'] ?? 'inbox') === 'sent' ? 'sent' : 'inbox';
    $settings = load_mailbox_settings($pdo);
    $conn = mailbox_open_folder($settings, $folder);

    $total = imap_num_msg($conn);
    if ($total === 0) {
        imap_close($conn);
        json_response(['messages' => [], 'total' => 0]);
    }

    $limit = 50;
    $start = max(1, $total - $limit + 1);
    $overview = imap_fetch_overview($conn, $start . ':' . $total);
    usort($overview, fn($a, $b) => $b->msgno <=> $a->msgno);

    $messages = array_map(function ($item) use ($conn) {
        return [
            'uid' => imap_uid($conn, $item->msgno),
            'from' => mailbox_decode_header($item->from ?? ''),
            'subject' => mailbox_decode_header($item->subject ?? '') ?: '(kein Betreff)',
            'date' => isset($item->date) ? to_iso(date('Y-m-d H:i:s', strtotime($item->date))) : null,
            'seen' => !empty($item->seen),
        ];
    }, $overview);

    imap_close($conn);
    json_response(['messages' => $messages, 'total' => $total]);
}

if ($method === 'GET' && $action === 'message') {
    $uid = (int) ($_GET['uid'] ?? 0);
    if ($uid <= 0) {
        json_error('Nachrichten-ID fehlt.', 422);
    }

    $folder = (string) ($_GET['folder'] ?? 'inbox') === 'sent' ? 'sent' : 'inbox';
    $settings = load_mailbox_settings($pdo);
    $conn = mailbox_open_folder($settings, $folder);

    $msgNo = @imap_msgno($conn, $uid);
    if (!$msgNo) {
        imap_close($conn);
        json_error('Nachricht wurde nicht gefunden.', 404);
    }

    $header = imap_headerinfo($conn, $msgNo);
    $body = mailbox_extract_body($conn, $msgNo);

    $result = [
        'uid' => $uid,
        'from' => mailbox_decode_header($header->fromaddress ?? ''),
        'to' => mailbox_decode_header($header->toaddress ?? ''),
        'subject' => mailbox_decode_header($header->subject ?? '') ?: '(kein Betreff)',
        'date' => isset($header->date) ? to_iso(date('Y-m-d H:i:s', strtotime($header->date))) : null,
        'html' => $body['html'],
        'plain' => $body['plain'],
    ];

    imap_close($conn);
    json_response($result);
}

function mailbox_save_to_sent(array $settings, string $fromEmail, string $fromName, string $to, string $subject, string $htmlBody): void
{
    $password = decrypt_secret($settings['password_encrypted']);
    $base = mailbox_base_string($settings);
    $conn = @imap_open($base . 'INBOX', $settings['username'], $password);

    if ($conn === false) {
        return;
    }

    $sentFolder = mailbox_resolve_sent_folder($conn, $base);
    $headers = implode("\r\n", [
        'Date: ' . date('r'),
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'To: ' . $to,
        'Subject: ' . $subject,
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ]);
    $raw = $headers . "\r\n\r\n" . $htmlBody;

    @imap_append($conn, $base . $sentFolder, $raw, '\\Seen');
    imap_close($conn);
}

if ($method === 'POST' && $action === 'send') {
    $settings = load_mailbox_settings($pdo);
    if ($settings['host'] === '' || ($settings['password_encrypted'] ?? '') === '') {
        json_error('Bitte zuerst das Postfach einrichten.', 422);
    }

    $body = read_json_body();
    $to = trim((string) ($body['to'] ?? ''));
    $subject = trim((string) ($body['subject'] ?? ''));
    $message = (string) ($body['body'] ?? '');

    if ($to === '' || $subject === '' || trim($message) === '') {
        json_error('Empfänger, Betreff und Text sind erforderlich.', 422);
    }

    $htmlBody = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

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
            $to,
            $to,
            $subject,
            $htmlBody,
            true
        );
    } catch (Throwable $exception) {
        json_error('E-Mail konnte nicht gesendet werden: ' . $exception->getMessage(), 502);
    }

    // Best-effort: Kopie im Postausgang ablegen. Ueber die reine SMTP-Submission legt der
    // Server sonst keine Kopie an, ein Fehlschlag hier soll den erfolgreichen Versand aber
    // nicht als Fehler an den Nutzer melden.
    mailbox_save_to_sent($settings, $settings['username'], $settings['from_name'], $to, $subject, $htmlBody);

    json_response(['ok' => true]);
}

json_error('Unbekannte Aktion.', 404);
