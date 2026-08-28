<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/crypto.php';
require_once __DIR__ . '/contract_pdf.php';
require_once __DIR__ . '/contract_notify.php';

function ensure_ftp_settings_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS ftp_settings (
          id TINYINT UNSIGNED NOT NULL,
          enabled TINYINT(1) NOT NULL DEFAULT 0,
          host VARCHAR(255) NOT NULL DEFAULT \'\',
          port INT NOT NULL DEFAULT 21,
          use_ssl TINYINT(1) NOT NULL DEFAULT 0,
          username VARCHAR(255) NOT NULL DEFAULT \'\',
          password_encrypted TEXT NULL,
          base_path VARCHAR(500) NOT NULL DEFAULT \'\',
          passive_mode TINYINT(1) NOT NULL DEFAULT 1,
          updated_at DATETIME NULL,
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function load_ftp_settings(PDO $pdo): array
{
    ensure_ftp_settings_table($pdo);
    $stmt = $pdo->query('SELECT * FROM ftp_settings WHERE id = 1');
    $row = $stmt->fetch();

    return $row ?: [
        'enabled' => 0, 'host' => '', 'port' => 21, 'use_ssl' => 0, 'username' => '',
        'password_encrypted' => '', 'base_path' => '', 'passive_mode' => 1, 'updated_at' => null,
    ];
}

function ftp_settings_row_to_response(array $row): array
{
    return [
        'enabled' => (bool) $row['enabled'],
        'host' => (string) $row['host'],
        'port' => (int) $row['port'],
        'useSsl' => (bool) $row['use_ssl'],
        'username' => (string) $row['username'],
        'hasPassword' => (string) ($row['password_encrypted'] ?? '') !== '',
        'basePath' => (string) $row['base_path'],
        'passiveMode' => (bool) $row['passive_mode'],
        'updatedAt' => to_iso($row['updated_at'] ?? null),
    ];
}

function save_ftp_settings(PDO $pdo, array $input): array
{
    $current = load_ftp_settings($pdo);

    $enabled = (bool) ($input['enabled'] ?? false);
    $host = trim((string) ($input['host'] ?? ''));
    $port = (int) ($input['port'] ?? 21);
    $useSsl = (bool) ($input['useSsl'] ?? false);
    $username = trim((string) ($input['username'] ?? ''));
    $password = (string) ($input['password'] ?? '');
    $basePath = trim((string) ($input['basePath'] ?? ''), '/');
    $passiveMode = (bool) ($input['passiveMode'] ?? true);

    if ($enabled && ($host === '' || $username === '')) {
        json_error('Bitte Host und Benutzername angeben.', 422);
    }
    if ($port <= 0) {
        $port = 21;
    }

    $passwordEncrypted = $password !== '' ? encrypt_secret($password) : ($current['password_encrypted'] ?? '');

    $stmt = $pdo->prepare(
        'INSERT INTO ftp_settings (id, enabled, host, port, use_ssl, username, password_encrypted, base_path, passive_mode, updated_at)
         VALUES (1, :enabled, :host, :port, :use_ssl, :username, :password_encrypted, :base_path, :passive_mode, UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE
            enabled = :enabled2, host = :host2, port = :port2, use_ssl = :use_ssl2, username = :username2,
            password_encrypted = :password_encrypted2, base_path = :base_path2, passive_mode = :passive_mode2,
            updated_at = UTC_TIMESTAMP()'
    );
    $stmt->execute([
        'enabled' => $enabled ? 1 : 0, 'host' => $host, 'port' => $port, 'use_ssl' => $useSsl ? 1 : 0,
        'username' => $username, 'password_encrypted' => $passwordEncrypted, 'base_path' => $basePath,
        'passive_mode' => $passiveMode ? 1 : 0,
        'enabled2' => $enabled ? 1 : 0, 'host2' => $host, 'port2' => $port, 'use_ssl2' => $useSsl ? 1 : 0,
        'username2' => $username, 'password_encrypted2' => $passwordEncrypted, 'base_path2' => $basePath,
        'passive_mode2' => $passiveMode ? 1 : 0,
    ]);

    return load_ftp_settings($pdo);
}

function ftp_export_is_allowed(array $settings): bool
{
    return (bool) $settings['enabled']
        && trim((string) $settings['host']) !== ''
        && trim((string) $settings['username']) !== ''
        && trim((string) ($settings['password_encrypted'] ?? '')) !== '';
}

/**
 * @return resource|\FTP\Connection
 */
function ftp_export_connect(array $settings)
{
    $host = (string) $settings['host'];
    $port = (int) $settings['port'];

    $connection = $settings['use_ssl']
        ? @ftp_ssl_connect($host, $port, 10)
        : @ftp_connect($host, $port, 10);

    if ($connection === false) {
        throw new RuntimeException('FTP-Verbindung zu ' . $host . ' fehlgeschlagen.');
    }

    $loggedIn = @ftp_login($connection, (string) $settings['username'], decrypt_secret($settings['password_encrypted']));
    if (!$loggedIn) {
        throw new RuntimeException('FTP-Login fehlgeschlagen.');
    }

    if ($settings['passive_mode']) {
        ftp_pasv($connection, true);
    }

    return $connection;
}

/**
 * @param resource|\FTP\Connection $connection
 */
function ftp_export_dir_exists($connection, string $path): bool
{
    $current = @ftp_pwd($connection);
    $exists = @ftp_chdir($connection, $path);
    if ($current !== false) {
        @ftp_chdir($connection, $current);
    }

    return $exists !== false;
}

/**
 * @param resource|\FTP\Connection $connection
 */
function ftp_export_ensure_dir($connection, string $path): void
{
    $segments = array_values(array_filter(explode('/', $path), fn($segment) => $segment !== ''));
    $current = '';
    foreach ($segments as $segment) {
        $current .= '/' . $segment;
        if (!@ftp_chdir($connection, $current)) {
            @ftp_mkdir($connection, $current);
        }
    }
}

/**
 * @param resource|\FTP\Connection $connection
 */
function ftp_export_upload_string($connection, string $remotePath, string $content): void
{
    $stream = fopen('php://temp', 'r+b');
    fwrite($stream, $content);
    rewind($stream);
    $ok = ftp_fput($connection, $remotePath, $stream, FTP_BINARY);
    fclose($stream);

    if (!$ok) {
        throw new RuntimeException('Upload von ' . $remotePath . ' fehlgeschlagen.');
    }
}

function ftp_export_sanitize_path_segment(string $name): string
{
    $safe = preg_replace('/[\/\\\\:*?"<>|]+/', '-', $name);
    $safe = trim((string) $safe);

    return $safe !== '' ? $safe : 'Kunde';
}

// Exportiert den unterschriebenen Vertrag (CleanTeam-Ausfertigung inkl. Anhang und
// Kundenausfertigung) zusaetzlich per FTP in die Ordnerstruktur
// "Cleanteam Vertraege / <Firmenname> / ...". Best effort - Fehler duerfen den
// eigentlichen Signaturvorgang nicht abbrechen.
function export_contract_to_ftp(PDO $pdo, string $contractId): void
{
    try {
        $settings = load_ftp_settings($pdo);
        if (!ftp_export_is_allowed($settings)) {
            return;
        }

        $context = load_contract_context($pdo, $contractId);
        if ($context === null) {
            return;
        }

        $cleanteamPdf = save_contract_pdf($pdo, $contractId, 'cleanteam', false);
        $customerPdf = save_contract_pdf($pdo, $contractId, 'customer', false);

        $companyName = ftp_export_sanitize_path_segment(trim((string) ($context['customer']['name'] ?? '')));
        $rootFolder = trim((string) $settings['base_path'], '/');
        $rootFolder = ($rootFolder !== '' ? '/' . $rootFolder : '') . '/Cleanteam Verträge';

        $connection = ftp_export_connect($settings);
        try {
            ftp_export_ensure_dir($connection, $rootFolder);

            $companyFolder = $rootFolder . '/' . $companyName;
            if (ftp_export_dir_exists($connection, $companyFolder)) {
                $companyFolder = $rootFolder . '/' . $companyName . ' ' . date('Y-m-d');
            }
            ftp_export_ensure_dir($connection, $companyFolder);

            ftp_export_upload_string($connection, $companyFolder . '/CleanTeam Vertrag mit Anhang.pdf', (string) $cleanteamPdf['content']);
            ftp_export_upload_string($connection, $companyFolder . '/Kundenvertrag.pdf', (string) $customerPdf['content']);
        } finally {
            ftp_close($connection);
        }
    } catch (Throwable $exception) {
        error_log('FTP-Vertragsexport fehlgeschlagen (' . $contractId . '): ' . $exception->getMessage());
    }
}
