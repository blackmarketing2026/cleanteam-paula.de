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
