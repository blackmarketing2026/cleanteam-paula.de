<?php

require_once __DIR__ . '/contract_pdf.php';

function ensure_quote_documents_table(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS quote_documents (
        id VARCHAR(64) NOT NULL,
        offer_id VARCHAR(64) NOT NULL,
        filename VARCHAR(190) NOT NULL,
        mime_type VARCHAR(80) NOT NULL DEFAULT \'application/pdf\',
        content LONGBLOB NOT NULL,
        sha256 CHAR(64) NOT NULL,
        generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id), UNIQUE KEY uniq_quote_documents_offer (offer_id),
        CONSTRAINT fk_quote_documents_offer FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

function quote_pdf_filename(string $customerName): string
{
    $name = preg_replace('/[^a-zA-Z0-9äöüÄÖÜß._ -]+/u', '_', $customerName) ?: 'Kunde';
    return 'CleanTeam Kostenvoranschlag - ' . trim($name) . '.pdf';
}

function render_quote_pdf(array $offer, array $customer): string
{
    $pdf = new SimplePdfDocument();
    $pdf->meta('Erstellt am: ' . contract_format_date($offer['created_at']));
    $pdf->title('Kostenvoranschlag');
    $pdf->paragraph(CONTRACTOR['legal_name'] . ' - ' . CONTRACTOR['trade_description']);
    $pdf->heading('Kunde und Objekt');
    $pdf->keyValue('Firma', contract_customer_display_name($customer));
    $pdf->keyValue('Ansprechpartner', contract_signatory_display($customer));
    $pdf->keyValue('Objekt', trim($customer['address'] . ' ' . $customer['house_number'] . ', ' . $customer['zip'] . ' ' . $customer['city'], ' ,'));
    $pdf->heading('Kalkulation');
    $pdf->keyValue('Leistungsbeginn', contract_format_date($offer['start_date']));
    $pdf->keyValue('Reinigungsintervall', (string) $offer['interval_label']);
    if ((int) $offer['square_meters'] > 0) $pdf->keyValue('Fläche', (int) $offer['square_meters'] . ' m²');
    $net = (float) $offer['price'];
    $pdf->keyValue('Monatlicher Preis netto', contract_format_money($net));
    if (!isset($offer['vat_applicable']) || (int) $offer['vat_applicable'] === 1) {
        $pdf->keyValue('Umsatzsteuer', contract_format_money($net * VAT_RATE / 100));
        $pdf->keyValue('Monatlicher Preis brutto', contract_format_money($net * (1 + VAT_RATE / 100)));
    }
    $pdf->keyValue('Gültig bis', contract_format_datetime($offer['expires_at']));
    $pdf->heading('Leistungsbeschreibung');
    $pdf->paragraph((string) $offer['notes']);
    if (trim((string) ($offer['customer_obligations_note'] ?? '')) !== '') {
        $pdf->heading('Pflichten des Auftraggebers');
        $pdf->paragraph((string) $offer['customer_obligations_note']);
    }
    $pdf->paragraph('Bitte nehmen Sie diesen Kostenvoranschlag über den Ihnen zugesandten Link an. Nach Ihrer Annahme erhalten Sie automatisch die Auftragsbestätigung.', 9.5);
    return $pdf->output();
}

function save_quote_pdf(PDO $pdo, array $offer, array $customer): array
{
    ensure_quote_documents_table($pdo);
    $stmt = $pdo->prepare('SELECT * FROM quote_documents WHERE offer_id = :id');
    $stmt->execute(['id' => $offer['id']]);
    $existing = $stmt->fetch();
    if ($existing) return $existing;
    $content = render_quote_pdf($offer, $customer);
    $stmt = $pdo->prepare('INSERT INTO quote_documents (id, offer_id, filename, mime_type, content, sha256, generated_at) VALUES (:id,:offer_id,:filename,\'application/pdf\',:content,:sha256,UTC_TIMESTAMP())');
    $stmt->execute(['id' => generate_id('quote-document'), 'offer_id' => $offer['id'], 'filename' => quote_pdf_filename(contract_customer_display_name($customer)), 'content' => $content, 'sha256' => hash('sha256', $content)]);
    $stmt = $pdo->prepare('SELECT * FROM quote_documents WHERE offer_id = :id'); $stmt->execute(['id' => $offer['id']]);
    return $stmt->fetch();
}
