<?php

// Exportiert nachtraeglich alle bereits signierten Vertraege per FTP, fuer die es noch keinen
// automatischen Export gab (z. B. weil sie vor Einfuehrung des FTP-Exports unterschrieben wurden).
// Admin-only, manuell ausgeloest ueber die FTP-Einstellungen.

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

$stmt = $pdo->query("SELECT id FROM contracts WHERE status = 'signiert' ORDER BY signed_at ASC");
$contractIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

$result = ['total' => count($contractIds), 'exported' => 0, 'failed' => 0, 'errors' => []];

foreach ($contractIds as $contractId) {
    try {
        export_contract_to_ftp_or_throw($pdo, (string) $contractId);
        $result['exported']++;
    } catch (Throwable $exception) {
        $result['failed']++;
        $result['errors'][] = $contractId . ': ' . $exception->getMessage();
    }
}

json_response($result);
