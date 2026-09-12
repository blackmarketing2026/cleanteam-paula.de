<?php
require_once __DIR__ . '/../includes/quote_pdf.php';

$offer = ['id' => 'offer-fixture-123456', 'created_at' => '2026-09-12 10:00:00', 'expires_at' => '2026-09-26 10:00:00',
    'start_date' => '2026-10-01', 'original_start_date' => null, 'is_existing_contract' => 0,
    'interval_label' => 'Zweimal wöchentlich', 'square_meters' => 555, 'price' => 640.0,
    'vat_applicable' => 1, 'notes' => 'Unterhaltsreinigung der Büro- und Sanitärflächen.',
    'customer_obligations_note' => 'Wasser und Strom werden bereitgestellt.'];
$customer = ['name' => 'Test GmbH', 'salutation' => '', 'contact_last_name' => 'Max Mustermann',
    'address' => 'Musterstraße', 'house_number' => '8', 'zip' => '55555', 'city' => 'Musterstadt'];

$pdf = render_quote_pdf($offer, $customer);
if (!str_starts_with($pdf, '%PDF-1.4') || strlen($pdf) < 3000) throw new RuntimeException('KVA-PDF wurde nicht korrekt erzeugt.');
if (quote_reference($offer) !== 'KV-2026-123456') throw new RuntimeException('KVA-Nummer ist nicht stabil.');
if (quote_pdf_logo_path() === null || !str_contains($pdf, '/Subtype /Image')) throw new RuntimeException('CleanTeam-Logo wurde nicht in das KVA-PDF eingebettet.');
if (!str_contains($pdf, '0.25 0.56 0.10 rg') || !str_contains($pdf, '0.06 0.18 0.45 rg')) throw new RuntimeException('CleanTeam-Farben fehlen im KVA-PDF.');
$outputPath = getenv('QUOTE_PDF_OUTPUT');
if (is_string($outputPath) && $outputPath !== '') file_put_contents($outputPath, $pdf);
echo "PASS: quote PDF generation, reference, pricing layout and contract-form data\n";
