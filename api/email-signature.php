<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/email_signature.php';

require_admin();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = (string) ($_GET['action'] ?? '');

const ALLOWED_EMAIL_SIGNATURE_MIMES = [
    'image/png' => 'png',
    'image/jpeg' => 'jpg',
    'image/webp' => 'webp',
];
const MAX_EMAIL_SIGNATURE_IMAGE_BYTES = 2 * 1024 * 1024;

function current_email_signature_image_filename(PDO $pdo): ?string
{
    ensure_email_signature_settings_table($pdo);
    $stmt = $pdo->query('SELECT image_filename FROM email_signature_settings WHERE id = 1');
    $row = $stmt->fetch();
    $filename = is_array($row) ? trim((string) ($row['image_filename'] ?? '')) : '';
    return $filename !== '' ? $filename : null;
}

function delete_email_signature_image_file(?string $filename): void
{
    if ($filename === null || trim($filename) === '') {
        return;
    }

    $path = EMAIL_SIGNATURE_UPLOADS_DIR . '/' . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

if ($method === 'GET') {
    json_response(load_email_signature_settings($pdo));
}

if ($method === 'POST' && $action === 'image') {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        json_error('Keine Datei hochgeladen.', 422);
    }

    $file = $_FILES['image'];
    if ($file['size'] > MAX_EMAIL_SIGNATURE_IMAGE_BYTES) {
        json_error('Datei ist zu gross (max. 2 MB).', 422);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset(ALLOWED_EMAIL_SIGNATURE_MIMES[$mime])) {
        json_error('Nur PNG, JPG oder WEBP sind erlaubt.', 422);
    }

    if (!is_dir(EMAIL_SIGNATURE_UPLOADS_DIR) && !mkdir(EMAIL_SIGNATURE_UPLOADS_DIR, 0755, true) && !is_dir(EMAIL_SIGNATURE_UPLOADS_DIR)) {
        json_error('Upload-Verzeichnis konnte nicht angelegt werden.', 500);
    }

    $extension = ALLOWED_EMAIL_SIGNATURE_MIMES[$mime];
    $filename = 'email-signature-' . bin2hex(random_bytes(8)) . '.' . $extension;
    $destination = EMAIL_SIGNATURE_UPLOADS_DIR . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        json_error('Datei konnte nicht gespeichert werden.', 500);
    }

    $oldFilename = current_email_signature_image_filename($pdo);

    $stmt = $pdo->prepare(
        'INSERT INTO email_signature_settings (id, image_filename, updated_at)
         VALUES (1, :filename, UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE image_filename = :filename2, updated_at = UTC_TIMESTAMP()'
    );
    $stmt->execute(['filename' => $filename, 'filename2' => $filename]);

    delete_email_signature_image_file($oldFilename);

    json_response(load_email_signature_settings($pdo));
}

if ($method === 'DELETE' && $action === 'image') {
    $oldFilename = current_email_signature_image_filename($pdo);
    delete_email_signature_image_file($oldFilename);
    $pdo->exec('UPDATE email_signature_settings SET image_filename = NULL, updated_at = UTC_TIMESTAMP() WHERE id = 1');
    json_response(load_email_signature_settings($pdo));
}

if ($method === 'POST') {
    $body = read_json_body();
    json_response(save_email_signature_settings($pdo, $body));
}

json_error('Methode nicht erlaubt.', 405);

