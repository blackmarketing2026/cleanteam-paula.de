<?php

// Exportiert einen einzelnen (bereits signierten) Vertrag manuell per FTP - ausgeloest
// ueber den "Backup"-Button in der Vertragsliste.

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ftp_export.php';

require_admin();
require_method('POST');

$pdo = db();
$settings = load_ftp_settings($pdo);

if (!ftp_export_is_allowed($settings)) {
    json_error('FTP ist nicht konfiguriert oder deaktiviert.', 422);
}

$contractId = trim((string) ($_GET['id'] ?? ''));
if ($contractId === '') {
    json_error('Vertrags-ID fehlt.', 422);
}

$stmt = $pdo->prepare('SELECT status FROM contracts WHERE id = :id');
$stmt->execute(['id' => $contractId]);
$status = $stmt->fetchColumn();

if ($status === false) {
    json_error('Vertrag wurde nicht gefunden.', 404);
}
if ($status !== 'signiert') {
    json_error('Nur unterschriebene Verträge können per FTP gesichert werden.', 422);
}

try {
    export_contract_to_ftp_or_throw($pdo, $contractId);
} catch (Throwable $exception) {
    json_error('FTP-Backup fehlgeschlagen: ' . $exception->getMessage(), 502);
}

json_response(['ok' => true]);
