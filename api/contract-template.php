<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/contract_template.php';

require_admin();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = (string) ($_GET['action'] ?? '');

function normalize_contractor_signature_data_url(string $signatureDataUrl): string
{
    $signatureDataUrl = trim($signatureDataUrl);
    if (strlen($signatureDataUrl) > 2500000) {
        json_error('Die Unterschrift ist zu groß.', 422);
    }

    if (strpos($signatureDataUrl, 'data:image/png;base64,') !== 0) {
        json_error('Bitte eine gültige PNG-Unterschrift speichern.', 422);
    }

    $binary = base64_decode(substr($signatureDataUrl, 22), true);
    if ($binary === false || substr($binary, 0, 8) !== "\x89PNG\r\n\x1a\n") {
        json_error('Bitte eine gültige PNG-Unterschrift speichern.', 422);
    }

    return $signatureDataUrl;
}

function invalidate_contract_pdf_cache(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW TABLES LIKE 'contract_documents'");
    if ($stmt->fetch()) {
        $pdo->exec("DELETE FROM contract_documents WHERE audience IN ('cleanteam', 'customer')");
    }
}

if ($method === 'GET') {
    ensure_contract_template_settings_table($pdo);
    $row = $pdo->query(
        'SELECT contractor_signature_data, contractor_signature_updated_at, updated_at
         FROM contract_template_settings WHERE id = 1'
    )->fetch();

    json_response([
        'updatedAt' => $row ? to_iso($row['updated_at']) : null,
        'contractorSignatureName' => contract_contractor_signature_name(),
        'contractorSignatureDataUrl' => get_contract_template_contractor_signature_data($pdo),
        'contractorSignatureUpdatedAt' => $row ? to_iso($row['contractor_signature_updated_at'] ?? null) : null,
    ]);
}

if ($method === 'POST') {
    $body = read_json_body();

    if ($action === 'signature') {
        $signatureDataUrl = normalize_contractor_signature_data_url((string) ($body['signatureDataUrl'] ?? ''));
        save_contract_template_contractor_signature_data($pdo, $signatureDataUrl);
        invalidate_contract_pdf_cache($pdo);

        json_response([
            'ok' => true,
            'contractorSignatureName' => contract_contractor_signature_name(),
            'contractorSignatureDataUrl' => $signatureDataUrl,
        ]);
    }

    if ($action === 'remove-signature') {
        save_contract_template_contractor_signature_data($pdo, null);
        invalidate_contract_pdf_cache($pdo);

        json_response(['ok' => true]);
    }

    json_error('Unbekannte Aktion.', 422);
}

json_error('Methode nicht erlaubt.', 405);
