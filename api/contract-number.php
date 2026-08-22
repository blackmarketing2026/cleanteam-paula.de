<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

ensure_contract_number_settings_table($pdo);

if ($method === 'GET') {
    $nextNumber = (int) $pdo->query('SELECT next_number FROM contract_number_settings WHERE id = 1')->fetchColumn();
    json_response([
        'nextNumber' => $nextNumber,
        'nextNumberPreview' => format_contract_number($nextNumber),
    ]);
}

if ($method === 'POST') {
    require_admin();

    $body = read_json_body();
    $nextNumber = (int) ($body['nextNumber'] ?? 0);

    if ($nextNumber <= 0) {
        json_error('Bitte eine gültige Startnummer (größer als 0) angeben.', 422);
    }

    $pdo->prepare('UPDATE contract_number_settings SET next_number = :next_number, updated_at = UTC_TIMESTAMP() WHERE id = 1')
        ->execute(['next_number' => $nextNumber]);

    json_response([
        'nextNumber' => $nextNumber,
        'nextNumberPreview' => format_contract_number($nextNumber),
    ]);
}

json_error('Methode nicht erlaubt.', 405);
