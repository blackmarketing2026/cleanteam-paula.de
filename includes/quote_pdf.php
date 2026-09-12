<?php
require_once __DIR__ . '/contract_pdf.php';

function ensure_quote_documents_table(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS quote_documents (
        id VARCHAR(64) NOT NULL, offer_id VARCHAR(64) NOT NULL, filename VARCHAR(190) NOT NULL,
        mime_type VARCHAR(80) NOT NULL DEFAULT \'application/pdf\', content LONGBLOB NOT NULL,
        sha256 CHAR(64) NOT NULL, generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id), UNIQUE KEY uniq_quote_documents_offer (offer_id),
        CONSTRAINT fk_quote_documents_offer FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

function quote_pdf_filename(string $customerName): string
{
    $name = trim(preg_replace('/[\/\\\\:*?"<>|]+/', '-', $customerName) ?: '') ?: 'Kunde';
    return 'CleanTeam Kostenvoranschlag - ' . $name . '.pdf';
}

function quote_pdf_logo_path(): ?string
{
    $path = __DIR__ . '/../assets/cleanteam-logo.jpg';
    return is_file($path) ? $path : null;
}

function render_quote_pdf(array $offer, array $customer): string
{
    $pdf = new SimplePdfDocument();
    $pdf->quoteHeader([
        CONTRACTOR['legal_name'], CONTRACTOR['trade_description'],
        CONTRACTOR['service_point_street'] . ', ' . CONTRACTOR['service_point_postal_code'] . ' ' . CONTRACTOR['service_point_city'],
        CONTRACTOR['website'],
    ], contract_format_date($offer['created_at']), contract_format_date($offer['expires_at']), quote_pdf_logo_path());

    $pdf->quoteSectionHeading('Empfänger');
    $pdf->keyValue('Firma', contract_customer_display_name($customer));
    $pdf->keyValue('Ansprechpartner', contract_signatory_display($customer));
    $pdf->keyValue('Objekt', trim($customer['address'] . ' ' . $customer['house_number'] . ', ' . $customer['zip'] . ' ' . $customer['city'], ' ,'));
    $pdf->keyValue('Projekt', 'Regelmäßige Gebäudereinigung');
    $pdf->keyValue('Reinigungsintervall', (string) $offer['interval_label']);
    if ((int) $offer['square_meters'] > 0) $pdf->keyValue('Reinigungsfläche', (int) $offer['square_meters'] . ' m²');

    $net = (float) $offer['price'];
    $vatApplicable = !isset($offer['vat_applicable']) || (int) $offer['vat_applicable'] === 1;
    $vat = $vatApplicable ? round($net * VAT_RATE / 100, 2) : 0.0;
    $gross = $net + $vat;
    $pdf->spacer(8.0);
    $pdf->quotePriceTable('Monatliche Gebäudereinigung', '1 Monat', contract_format_money($net), contract_format_money($net));
    $pdf->quoteTotal(contract_format_money($net), $vatApplicable ? contract_format_money($vat) : null, contract_format_money($gross));

    $pdf->quoteSectionHeading('Leistungsbeschreibung');
    $pdf->paragraph((string) $offer['notes']);

    $startDate = !empty($offer['start_date']) ? contract_format_date($offer['start_date']) : 'nach Absprache';
    if (!empty($offer['is_existing_contract']) && !empty($offer['original_start_date'])) {
        $startDate = 'Bestandsleistung, ursprünglicher Beginn ' . contract_format_date($offer['original_start_date']);
    }
    $pdf->quoteSectionHeading('Rahmenbedingungen');
    $pdf->keyValue('Ausführungsbeginn', $startDate);
    $pdf->paragraph('Hinweis: Dieser Kostenvoranschlag basiert auf den im Vertragsentwurf erfassten Leistungsdaten. Änderungen oder zusätzliche Leistungen werden vor Ausführung abgestimmt.', 9.5);
    $pdf->paragraph('Bitte nehmen Sie den Kostenvoranschlag über den zugesandten Link an. Danach erhalten Sie automatisch Ihre Auftragsbestätigung.', 9.5);
    $pdf->spacer(8.0);
    $pdf->paragraph("Freundliche Grüße\n" . CONTRACTOR['legal_name']);
    return $pdf->output();
}

function save_quote_pdf(PDO $pdo, array $offer, array $customer): array
{
    ensure_quote_documents_table($pdo);
    $content = render_quote_pdf($offer, $customer);
    $filename = quote_pdf_filename(contract_customer_display_name($customer));
    $stmt = $pdo->prepare(
        'INSERT INTO quote_documents (id, offer_id, filename, mime_type, content, sha256, generated_at)
         VALUES (:id,:offer_id,:filename,\'application/pdf\',:content,:sha256,UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE filename = :updated_filename, mime_type = \'application/pdf\', content = :updated_content,
            sha256 = :updated_sha256, generated_at = UTC_TIMESTAMP()'
    );
    $stmt->execute([
        'id' => generate_id('quote-document'),
        'offer_id' => $offer['id'],
        'filename' => $filename,
        'content' => $content,
        'sha256' => hash('sha256', $content),
        'updated_filename' => $filename,
        'updated_content' => $content,
        'updated_sha256' => hash('sha256', $content),
    ]);
    $stmt = $pdo->prepare('SELECT * FROM quote_documents WHERE offer_id = :id'); $stmt->execute(['id' => $offer['id']]);
    return $stmt->fetch();
}
