<?php
require_once __DIR__ . '/../includes/quote_pdf.php';

$offer = ['id' => 'offer-fixture-123456', 'created_at' => '2026-09-12 10:00:00', 'expires_at' => '2026-09-26 10:00:00',
    'start_date' => '2026-10-01', 'original_start_date' => null, 'is_existing_contract' => 0,
    'interval_label' => 'Zweimal wöchentlich', 'square_meters' => 555, 'price' => 640.0,
    'vat_applicable' => 1, 'notes' => implode("\n", [
        'Sanitäranlagen und WCs werden hygienisch gereinigt und desinfiziert.',
        'Freistehende Schreibtische werden entstaubt.',
        'Spinnweben werden monatlich entfernt.',
        'Der Teppichboden wird fachgerecht abgesaugt.',
        'Die Küche wird außen gereinigt und der Mülleimer geleert.',
        'Alle freien Flächen werden entstaubt.',
        'Türen und Türrahmen werden monatlich entstaubt.',
        'In allen Bereichen werden die Mülleimer geleert.',
        'Bildschirme werden alle zwei Wochen mit einem Staubwedel entstaubt.',
        'Fensterbänke werden monatlich entstaubt.',
    ]),
    'customer_obligations_note' => 'Wasser und Strom werden bereitgestellt.'];
$customer = ['name' => 'Test GmbH', 'salutation' => '', 'contact_last_name' => 'Max Mustermann',
    'address' => 'Musterstraße', 'house_number' => '8', 'zip' => '55555', 'city' => 'Musterstadt'];

$pdf = render_quote_pdf($offer, $customer);
if (!str_starts_with($pdf, '%PDF-1.4') || strlen($pdf) < 3000) throw new RuntimeException('KVA-PDF wurde nicht korrekt erzeugt.');
if (!str_contains($pdf, '8680') || !str_contains($pdf, 'K2130')) throw new RuntimeException('Die festen KVA- und Kundennummern fehlen.');
if (quote_pdf_logo_path() === null || !str_contains($pdf, '/Subtype /Image')) throw new RuntimeException('CleanTeam-Logo wurde nicht in das KVA-PDF eingebettet.');
if (substr_count($pdf, '/Subtype /Image') !== 1 || !str_contains($pdf, 'Thomas')) throw new RuntimeException('Das KVA muss das Logo und Thomas als Geschäftsführer ohne Unterschriftsbild enthalten.');
if (!str_contains($pdf, '0.25 0.56 0.10 rg') || !str_contains($pdf, '0.06 0.18 0.45 rg')) throw new RuntimeException('CleanTeam-Farben fehlen im KVA-PDF.');
foreach (['Hinweis: Dieser Kostenvoranschlag', 'Bitte nehmen Sie den Kostenvoranschlag'] as $omittedSentence) {
    if (str_contains($pdf, $omittedSentence)) throw new RuntimeException('Entfernter KVA-Hinweis wird weiterhin ausgegeben.');
}
if (!str_contains($pdf, 'Leistungsbeschreibung')) throw new RuntimeException('Leistungsbeschreibung fehlt im KVA-PDF.');
foreach (['Pflichten des Auftraggebers', 'Zahlungsbedingungen', 'Preisgrundlage'] as $omittedText) {
    if (str_contains($pdf, $omittedText)) throw new RuntimeException($omittedText . ' darf im KVA-PDF nicht ausgegeben werden.');
}
foreach (['Clean Team Wuppertal', 'CleanTeam Haan', 'info@cleanteam-group.com', 'ISO 14001:2015', 'Fabio Donato'] as $letterheadText) {
    if (!str_contains($pdf, $letterheadText)) throw new RuntimeException('Angabe im neuen KVA-Briefkopf fehlt: ' . $letterheadText);
}
foreach (['Alle genannten Preise', 'Reinigungsmitteln', 'Anfahrtskosten', 'Toilettenpapier', 'Auftraggeber'] as $remarkText) {
    if (!str_contains($pdf, $remarkText)) throw new RuntimeException('Bemerkung im neuen KVA fehlt: ' . $remarkText);
}
if (str_contains($pdf, 'Gültig bis:') || str_contains($pdf, 'KOSTENVORANSCHLAG')
    || str_contains($pdf, 'Voraussichtlicher Gesamtbetrag') || str_contains($pdf, '761,60')
    || str_contains($pdf, 'IBAN')) {
    throw new RuntimeException('Das KVA enthält weiterhin Gültigkeit oder hochgerechnete Bruttopreise.');
}
$outputPath = getenv('QUOTE_PDF_OUTPUT');
if (is_string($outputPath) && $outputPath !== '') file_put_contents($outputPath, $pdf);
echo "PASS: quote PDF generation, reference, pricing layout and contract-form data\n";
