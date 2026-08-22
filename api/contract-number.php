<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

ensure_contract_number_settings_table($pdo);

if ($method === 'GET') {
    $row = $pdo->query('SELECT year, next_number FROM contract_number_settings WHERE id = 1')->fetch();
    $year = (int) $row['year'];
    $nextNumber = (int) $row['next_number'];

    json_response([
        'year' => $year,
        'nextNumber' => $nextNumber,
        'nextNumberPreview' => format_contract_number($year, $nextNumber),
    ]);
}

if ($method === 'POST') {
    require_admin();

    $body = read_json_body();
    $year = (int) ($body['year'] ?? 0);
    $nextNumber = (int) ($body['nextNumber'] ?? 0);

    if ($year < 2000 || $year > 2100) {
        json_error('Bitte ein gültiges Jahr angeben.', 422);
    }
    if ($nextNumber <= 0) {
        json_error('Bitte eine gültige Startnummer (größer als 0) angeben.', 422);
    }

    $pdo->prepare('UPDATE contract_number_settings SET year = :year, next_number = :next_number, updated_at = UTC_TIMESTAMP() WHERE id = 1')
        ->execute(['year' => $year, 'next_number' => $nextNumber]);

    json_response([
        'year' => $year,
        'nextNumber' => $nextNumber,
        'nextNumberPreview' => format_contract_number($year, $nextNumber),
    ]);
}

json_error('Methode nicht erlaubt.', 405);
