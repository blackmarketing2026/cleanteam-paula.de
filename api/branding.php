<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

const UPLOADS_DIR = __DIR__ . '/../uploads';
const ALLOWED_LOGO_MIMES = [
    'image/png' => 'png',
    'image/jpeg' => 'jpg',
    'image/webp' => 'webp',
];
const MAX_LOGO_BYTES = 2 * 1024 * 1024;

function load_branding_settings(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT * FROM branding_settings WHERE id = 1');
    $row = $stmt->fetch();

    return $row ?: ['logo_filename' => null, 'updated_at' => null];
}

function branding_logo_url(?string $filename): ?string
{
    if ($filename === null || $filename === '') {
        return null;
    }

    return base_url() . '/uploads/' . rawurlencode($filename);
}

// GET ist bewusst oeffentlich (kein require_login), damit die Angebots-/Vertragsseiten fuer
// Kunden dasselbe Logo laden koennen wie das Dashboard.
if ($method === 'GET') {
    $settings = load_branding_settings($pdo);
    json_response([
        'logoUrl' => branding_logo_url($settings['logo_filename']),
        'updatedAt' => to_iso($settings['updated_at']),
    ]);
}

if ($method === 'POST') {
    require_login();

    if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        json_error('Keine Datei hochgeladen.', 422);
    }

    $file = $_FILES['logo'];
    if ($file['size'] > MAX_LOGO_BYTES) {
        json_error('Datei ist zu groß (max. 2 MB).', 422);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset(ALLOWED_LOGO_MIMES[$mime])) {
        json_error('Nur PNG, JPG oder WEBP sind erlaubt.', 422);
    }

    if (!is_dir(UPLOADS_DIR) && !mkdir(UPLOADS_DIR, 0755, true) && !is_dir(UPLOADS_DIR)) {
        json_error('Upload-Verzeichnis konnte nicht angelegt werden.', 500);
    }

    $extension = ALLOWED_LOGO_MIMES[$mime];
    $filename = 'logo-' . bin2hex(random_bytes(8)) . '.' . $extension;
    $destination = UPLOADS_DIR . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        json_error('Datei konnte nicht gespeichert werden.', 500);
    }

    $current = load_branding_settings($pdo);
    $oldFilename = $current['logo_filename'];

    $stmt = $pdo->prepare(
        'INSERT INTO branding_settings (id, logo_filename, updated_at)
         VALUES (1, :filename, UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE logo_filename = :filename2, updated_at = UTC_TIMESTAMP()'
    );
    $stmt->execute(['filename' => $filename, 'filename2' => $filename]);

    if ($oldFilename !== null && $oldFilename !== '' && $oldFilename !== $filename) {
        $oldPath = UPLOADS_DIR . '/' . $oldFilename;
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    json_response(['ok' => true, 'logoUrl' => branding_logo_url($filename)]);
}

if ($method === 'DELETE') {
    require_login();

    $current = load_branding_settings($pdo);
    if ($current['logo_filename'] !== null && $current['logo_filename'] !== '') {
        $oldPath = UPLOADS_DIR . '/' . $current['logo_filename'];
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    $pdo->exec('UPDATE branding_settings SET logo_filename = NULL, updated_at = UTC_TIMESTAMP() WHERE id = 1');

    json_response(['ok' => true]);
}

json_error('Methode nicht erlaubt.', 405);
