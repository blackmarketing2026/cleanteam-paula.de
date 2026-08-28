<?php

// Reiner Lese-Zugriff auf den FTP-Export-Ordner ("Cleanteam Verträge"), damit Admins die
// abgelegten Vertragsdokumente ansehen/herunterladen koennen. Bewusst OHNE Loeschfunktion -
// dieser Endpunkt bietet ausschliesslich "list" und "download".

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ftp_export.php';

require_admin();
require_method('GET');

$pdo = db();
$settings = load_ftp_settings($pdo);

if (!ftp_export_is_allowed($settings)) {
    json_error('FTP ist nicht konfiguriert oder deaktiviert.', 422);
}

$action = (string) ($_GET['action'] ?? 'list');
$relativePath = (string) ($_GET['path'] ?? '');

try {
    $relativePath = ftp_browse_sanitize_relative_path($relativePath);
} catch (Throwable $exception) {
    json_error('Ungültiger Pfad.', 422);
}

$rootFolder = ftp_export_root_folder($settings);
$absolutePath = $rootFolder . ($relativePath !== '' ? '/' . $relativePath : '');

try {
    $connection = ftp_export_connect($settings);
} catch (Throwable $exception) {
    json_error('FTP-Verbindung fehlgeschlagen: ' . $exception->getMessage(), 502);
}

try {
    if ($action === 'download') {
        if ($relativePath === '') {
            json_error('Keine Datei angegeben.', 422);
        }

        $content = ftp_browse_download_to_string($connection, $absolutePath);
        $filename = basename($relativePath);
        $mode = ($_GET['mode'] ?? '') === 'attachment' ? 'attachment' : 'inline';

        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . $mode . '; filename="' . str_replace('"', '', $filename) . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    $items = ftp_browse_list($connection, $absolutePath);
    json_response([
        'path' => $relativePath,
        'items' => $items,
    ]);
} catch (Throwable $exception) {
    json_error('Fehler beim Zugriff auf den FTP-Ordner: ' . $exception->getMessage(), 502);
} finally {
    ftp_close($connection);
}
