<?php

set_exception_handler(function (Throwable $exception): void {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Es ist ein unerwarteter Fehler aufgetreten.']);
});

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(string $message, int $status = 400): void
{
    json_response(['error' => $message], $status);
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function generate_token(): string
{
    return bin2hex(random_bytes(32));
}

function generate_id(string $prefix): string
{
    return $prefix . '-' . bin2hex(random_bytes(12));
}

function ensure_contract_number_settings_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS contract_number_settings (
            id TINYINT UNSIGNED NOT NULL,
            year SMALLINT UNSIGNED NOT NULL DEFAULT ' . (int) gmdate('Y') . ',
            next_number INT UNSIGNED NOT NULL DEFAULT 1,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $stmt = $pdo->query("SHOW COLUMNS FROM contract_number_settings LIKE 'year'");
    if (!$stmt->fetch()) {
        $pdo->exec('ALTER TABLE contract_number_settings ADD COLUMN year SMALLINT UNSIGNED NOT NULL DEFAULT ' . (int) gmdate('Y') . ' AFTER id');
    }

    $pdo->exec('INSERT IGNORE INTO contract_number_settings (id, year, next_number) VALUES (1, ' . (int) gmdate('Y') . ', 1)');
}

function format_contract_number(int $year, int $sequence): string
{
    return sprintf('CT-%d-%03d', $year, $sequence);
}

function ensure_offers_interval_label_length(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM offers LIKE 'interval_label'");
    $column = $stmt->fetch();
    if ($column && stripos((string) $column['Type'], 'varchar(40)') !== false) {
        $pdo->exec('ALTER TABLE offers MODIFY COLUMN interval_label VARCHAR(190) NOT NULL');
    }
}

function ensure_offers_vat_column(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM offers LIKE 'vat_applicable'");
    if (!$stmt->fetch()) {
        $pdo->exec('ALTER TABLE offers ADD COLUMN vat_applicable TINYINT(1) NOT NULL DEFAULT 1 AFTER price');
    }
}

function ensure_offers_link_opened_column(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM offers LIKE 'link_opened_at'");
    if (!$stmt->fetch()) {
        $pdo->exec('ALTER TABLE offers ADD COLUMN link_opened_at DATETIME NULL AFTER expires_at');
    }
}

function ensure_offers_customer_obligations_column(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM offers LIKE 'customer_obligations_note'");
    if (!$stmt->fetch()) {
        $pdo->exec('ALTER TABLE offers ADD COLUMN customer_obligations_note LONGTEXT NULL AFTER notes');
    }
}

function format_service_text(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $lines = array_map(function (string $line): string {
        $line = preg_replace('/[ \t]+/', ' ', trim($line));
        $line = preg_replace('/\s+([.,;:!?])/', '$1', $line);

        // Jeden Satz im Absatz groß beginnen lassen.
        $line = preg_replace_callback(
            '/(^|[.!?]\s+)([a-zäöüß])/u',
            fn (array $m): string => $m[1] . mb_strtoupper($m[2], 'UTF-8'),
            $line
        );

        if ($line !== '' && !preg_match('/[.!?:]$/', $line)) {
            $line .= '.';
        }

        return $line;
    }, explode("\n", $text));

    $lines = array_filter($lines, fn (string $line): bool => $line !== '');

    return implode("\n", $lines);
}

function now_iso(): string
{
    return gmdate('Y-m-d\TH:i:s.000\Z');
}

function to_iso(?string $mysqlDateTime): ?string
{
    if ($mysqlDateTime === null || $mysqlDateTime === '') {
        return null;
    }

    $timestamp = strtotime($mysqlDateTime . ' UTC');
    if ($timestamp === false) {
        return null;
    }

    return gmdate('Y-m-d\TH:i:s.000\Z', $timestamp);
}

function require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        json_error('Methode nicht erlaubt.', 405);
    }
}

function base_url(): string
{
    return rtrim(config()['base_url'] ?? '', '/');
}

function branding_default_logo_relative_path(): string
{
    return 'assets/cleanteam-logo.webp';
}

function branding_default_logo_disk_path(): string
{
    return __DIR__ . '/../' . branding_default_logo_relative_path();
}

function is_https_request(): bool
{
    $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));

    return (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443'
        || $forwardedProto === 'https';
}

function request_origin(): ?string
{
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '' || !preg_match('/^[A-Za-z0-9.-]+(?::[0-9]+)?$/', $host)) {
        return null;
    }

    $scheme = is_https_request() ? 'https' : 'http';

    return $scheme . '://' . $host;
}

function email_asset_base_url(): string
{
    $config = config();
    $configured = trim((string) ($config['email_asset_base_url'] ?? $config['asset_base_url'] ?? ''));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $baseUrl = base_url();
    $origin = request_origin();
    $baseHost = parse_url($baseUrl, PHP_URL_HOST);
    $originHost = $origin !== null ? parse_url($origin, PHP_URL_HOST) : null;
    $normalizedBaseHost = $baseHost !== null ? preg_replace('/^www\./i', '', (string) $baseHost) : null;
    $normalizedOriginHost = $originHost !== null ? preg_replace('/^www\./i', '', (string) $originHost) : null;

    if ($origin !== null && $normalizedBaseHost !== null && strcasecmp($normalizedBaseHost, (string) $normalizedOriginHost) === 0) {
        return rtrim($origin, '/');
    }

    return $baseUrl;
}

const OFFER_INTERVAL_FACTORS = [
    'Einmalig' => 1,
    'Wöchentlich' => 4.33,
    '14-tägig' => 2.16,
    'Monatlich' => 1,
    'Quartalsweise' => 0.33,
];

const OFFER_SERVICE_RATES = [
    'Unterhaltsreinigung' => 1.95,
    'Büroreinigung' => 2.1,
    'Treppenhausreinigung' => 2.45,
    'Grundreinigung' => 3.8,
    'Glasreinigung' => 3.2,
];

function calculate_offer_price(float $squareMeters, string $interval, string $service): float
{
    $factor = OFFER_INTERVAL_FACTORS[$interval] ?? 1;
    $rate = OFFER_SERVICE_RATES[$service] ?? OFFER_SERVICE_RATES['Unterhaltsreinigung'];
    $setup = $interval === 'Einmalig' ? 65 : 35;

    return max(0, round($squareMeters * $rate * $factor + $setup, 2));
}
