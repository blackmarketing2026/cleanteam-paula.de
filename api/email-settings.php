<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/email_settings.php';

require_admin();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    json_response(load_email_delivery_settings($pdo));
}

if ($method === 'POST') {
    $body = read_json_body();
    json_response(save_email_delivery_settings($pdo, $body));
}

json_error('Methode nicht erlaubt.', 405);
