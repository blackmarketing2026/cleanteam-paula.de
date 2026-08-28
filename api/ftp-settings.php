<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ftp_export.php';

require_admin();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    json_response(ftp_settings_row_to_response(load_ftp_settings($pdo)));
}

if ($method === 'POST' && ($_GET['action'] ?? '') === 'test') {
    $settings = load_ftp_settings($pdo);
    if (!ftp_export_is_allowed($settings)) {
        json_error('Bitte zuerst Host, Benutzername und Passwort speichern und aktivieren.', 422);
    }

    try {
        $connection = ftp_export_connect($settings);
        ftp_close($connection);
    } catch (Throwable $exception) {
        json_error('FTP-Verbindung fehlgeschlagen: ' . $exception->getMessage(), 502);
    }

    json_response(['ok' => true]);
}

if ($method === 'POST') {
    $body = read_json_body();
    $settings = save_ftp_settings($pdo, $body);
    json_response(ftp_settings_row_to_response($settings));
}

json_error('Methode nicht erlaubt.', 405);
