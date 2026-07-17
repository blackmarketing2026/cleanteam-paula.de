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

function contract_template_sample_context(string $templateHtml): array
{
    $offer = [
        'price' => 1300.0,
        'start_date' => gmdate('Y-m-d'),
        'created_at' => gmdate('Y-m-d H:i:s'),
        'interval_label' => 'wöchentlich',
        'service' => 'Unterhaltsreinigung',
        'square_meters' => 450,
        'notes' => 'Beispieltext für die Vorschau.',
    ];
    $customer = [
        'name' => 'Musterfirma GmbH',
        'salutation' => 'Frau',
        'contact_last_name' => 'Musterfrau',
        'address' => 'Musterstraße',
        'house_number' => '12a',
        'zip' => '70173',
        'city' => 'Stuttgart',
    ];
    $contract = [
        'number' => 'CT-VORSCHAU',
        'status' => 'entwurf',
        'authorized' => null,
        'representation_note' => null,
        'signed_at' => null,
        'signature_data' => null,
    ];

    $placeholders = contract_template_placeholder_map($offer, $customer, $contract, false);
    $body = render_contract_template_body($templateHtml, $placeholders);
    $styleCss = contract_document_style_css();

    $html = <<<HTML
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<style>
{$styleCss}
</style>
</head>
<body>
<div class="doc-label">Vorschau mit Beispieldaten</div>
{$body}
</body>
</html>
HTML;

    return ['html' => $html];
}

if ($method === 'GET') {
    if ($action === 'default') {
        json_response(['templateHtml' => default_contract_template_html()]);
    }

    ensure_contract_template_settings_table($pdo);
    $row = $pdo->query(
        'SELECT template_html, contractor_signature_data, contractor_signature_updated_at, updated_at
         FROM contract_template_settings WHERE id = 1'
    )->fetch();

    json_response([
        'templateHtml' => get_contract_template_html($pdo),
        'updatedAt' => $row ? to_iso($row['updated_at']) : null,
        'contractorSignatureName' => contract_contractor_signature_name(),
        'contractorSignatureDataUrl' => get_contract_template_contractor_signature_data($pdo),
        'contractorSignatureUpdatedAt' => $row ? to_iso($row['contractor_signature_updated_at'] ?? null) : null,
        'placeholders' => contract_template_placeholder_definitions(),
    ]);
}

if ($method === 'POST') {
    $body = read_json_body();
    $templateHtml = (string) ($body['templateHtml'] ?? '');

    if ($action === 'preview') {
        json_response(contract_template_sample_context($templateHtml));
    }

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

    if (trim($templateHtml) === '') {
        json_error('Der Vertragstext darf nicht leer sein.', 422);
    }

    if (preg_match('/<\s*script/i', $templateHtml) === 1) {
        json_error('Script-Tags sind im Vertragstext nicht erlaubt.', 422);
    }

    save_contract_template_html($pdo, $templateHtml);

    json_response(['ok' => true]);
}

json_error('Methode nicht erlaubt.', 405);
