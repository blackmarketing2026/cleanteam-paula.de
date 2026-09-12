<?php
require_once __DIR__ . '/../includes/helpers.php';
restore_exception_handler();
require_once __DIR__ . '/../includes/contract_template.php';

if (offer_discounted_price(400.0, 8.0) !== 368.0) {
    throw new RuntimeException('8 Prozent Rabatt auf 400,00 Euro wurden falsch berechnet.');
}
if (offer_discounted_price(123.45, 0.0) !== 123.45) {
    throw new RuntimeException('Ein Preis ohne Rabatt darf sich nicht verändern.');
}
if (offer_discounted_price(199.99, 12.5) !== 174.99) {
    throw new RuntimeException('Die Rabattberechnung rundet nicht korrekt auf Cent.');
}

$contractPlaceholders = contract_template_placeholder_map([
    'price' => 368.0, 'vat_applicable' => 1, 'is_existing_contract' => 0,
    'start_date' => '2026-10-01', 'created_at' => '2026-09-12 10:00:00',
    'interval_label' => 'Wöchentlich', 'square_meters' => 100, 'service' => 'Reinigung',
    'notes' => '', 'customer_obligations_note' => '',
], [
    'name' => 'Test GmbH', 'address' => 'Musterstraße', 'house_number' => '8',
    'zip' => '55555', 'city' => 'Musterstadt', 'salutation' => '',
    'contact_last_name' => 'Max Mustermann',
], null, true);
if ($contractPlaceholders['preis_netto'] !== '368,00 €'
    || !str_contains($contractPlaceholders['ust_block'], '368,00 €')
    || str_contains($contractPlaceholders['ust_block'], '400,00 €')) {
    throw new RuntimeException('Im Vertrag wird nicht ausschließlich der rabattierte Endpreis verwendet.');
}

$schema = file_get_contents(__DIR__ . '/../sql/schema.sql');
$helpers = file_get_contents(__DIR__ . '/../includes/helpers.php');
$offersApi = file_get_contents(__DIR__ . '/../api/offers.php');
$existingOffersApi = file_get_contents(__DIR__ . '/../api/existing-offers.php');
$contractsApi = file_get_contents(__DIR__ . '/../api/contracts.php');
$app = file_get_contents(__DIR__ . '/../app.js');
$index = file_get_contents(__DIR__ . '/../index.html');
$publicApi = file_get_contents(__DIR__ . '/../api/public.php');
$publicJs = file_get_contents(__DIR__ . '/../public.js');

foreach ([$schema, $helpers, $offersApi, $existingOffersApi, $contractsApi] as $source) {
    if (!str_contains($source, 'discount_percent')) {
        throw new RuntimeException('Die Rabattspalte ist nicht in allen Datenbankpfaden enthalten.');
    }
}
foreach ([$offersApi, $existingOffersApi, $contractsApi] as $source) {
    if (!str_contains($source, 'offer_discounted_price($basePrice, $discountPercent)')) {
        throw new RuntimeException('Ein Speicherpfad berechnet den rabattierten Endpreis nicht serverseitig.');
    }
}
foreach (['offer-discount', 'existing-offer-discount', 'contract-correction-discount', 'offer-edit-discount'] as $fieldId) {
    if (!str_contains($index, 'id="' . $fieldId . '"')) {
        throw new RuntimeException('Rabattfeld fehlt in einer Vertragsmaske: ' . $fieldId);
    }
}
if (!str_contains($app, 'updateDiscountPricePreview') || !str_contains($index, 'Preis nach Rabatt')) {
    throw new RuntimeException('Die Live-Anzeige für Ausgangspreis, Rabatt und Endpreis fehlt.');
}
if (!str_contains($publicApi, "'discountPercent'") || !str_contains($publicJs, 'Preis nach Rabatt netto')) {
    throw new RuntimeException('Der Rabatt wird im öffentlichen KVA-Prozess nicht angezeigt.');
}
foreach ([$offersApi, $contractsApi] as $source) {
    if (!str_contains($source, "['signiert', 'bestaetigt']")) {
        throw new RuntimeException('Signierte Auftragsbestätigungen sind nicht gegen Preisänderungen geschützt.');
    }
}

echo "PASS: discount pricing, forms, persistence, public display and signed-contract protection\n";
