<?php

require_once __DIR__ . '/../includes/contract_template.php';

$index = file_get_contents(__DIR__ . '/../index.html');
$app = file_get_contents(__DIR__ . '/../app.js');
$offersApi = file_get_contents(__DIR__ . '/../api/offers.php');
$existingOffersApi = file_get_contents(__DIR__ . '/../api/existing-offers.php');
$contractsApi = file_get_contents(__DIR__ . '/../api/contracts.php');

foreach ([$index, $app] as $source) {
    if (str_contains($source, 'Text korrigieren') || str_contains($source, 'offer-review')) {
        throw new RuntimeException('Der Korrektur-Zwischenschritt ist noch vorhanden.');
    }
}
if (str_contains($app, 'api/format-text.php')) {
    throw new RuntimeException('Die automatische Textkorrektur wird weiterhin aufgerufen.');
}
foreach ([$offersApi, $existingOffersApi, $contractsApi] as $source) {
    if (str_contains($source, 'format_service_text')) {
        throw new RuntimeException('Ein Speicherweg verändert weiterhin die Leistungsbeschreibung.');
    }
}

$formattedText = "1. Empfang\n   - Boden wischen\n\n2. Büro\n   • Tische reinigen";
$placeholders = contract_template_placeholder_map([
    'price' => 100.0,
    'vat_applicable' => 1,
    'is_existing_contract' => 0,
    'start_date' => '2026-10-01',
    'created_at' => '2026-09-14 10:00:00',
    'interval_label' => 'Wöchentlich',
    'square_meters' => 0,
    'service' => 'Individuelle Leistung',
    'notes' => $formattedText,
    'customer_obligations_note' => '',
], [
    'name' => 'Test GmbH',
    'address' => 'Musterstraße',
    'house_number' => '1',
    'zip' => '12345',
    'city' => 'Musterstadt',
    'salutation' => '',
    'contact_last_name' => 'Max Mustermann',
], null, true);

if (!str_contains($placeholders['zusatzhinweis_block'], htmlspecialchars($formattedText, ENT_QUOTES, 'UTF-8'))) {
    throw new RuntimeException('Zeilenfolge oder Einrückung geht beim Vertragsrendering verloren.');
}

echo "PASS: correction step removed and pasted service formatting is preserved\n";
