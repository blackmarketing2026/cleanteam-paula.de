<?php

require_once __DIR__ . '/../includes/helpers.php';
restore_exception_handler();

// Kein echter DB-/Config-Zugriff im Unit-Test: contract_logo_html() (nur im forPdf=false-Zweig
// aufgerufen) fragt lediglich das Logo ab, das für diesen Test irrelevant ist.
if (!function_exists('config')) {
    function config(): array
    {
        return ['base_url' => 'https://example.test'];
    }
}
if (!function_exists('db')) {
    function db(): object
    {
        return new class {
            public function query(string $sql): object
            {
                return new class {
                    public function fetch(): array
                    {
                        return [];
                    }
                };
            }
        };
    }
}

require_once __DIR__ . '/../includes/contract_template.php';

$index = file_get_contents(__DIR__ . '/../index.html');
$app = file_get_contents(__DIR__ . '/../app.js');
$publicJs = file_get_contents(__DIR__ . '/../public.js');
$createApi = file_get_contents(__DIR__ . '/../api/existing-offers.php');
$updateApi = file_get_contents(__DIR__ . '/../api/offers.php');

foreach (['existing-offer-original-start', 'existing-offer-effective-start', 'offer-edit-original-start', 'offer-edit-effective-start'] as $fieldId) {
    if (!str_contains($index, 'id="' . $fieldId . '" type="date"')) {
        throw new RuntimeException('Tag/Monat/Jahr-Feld für Bestandsverträge fehlt: ' . $fieldId);
    }
}
foreach (['existing-offer-start-month', 'existing-offer-start-year', 'offer-edit-original-start-month', 'offer-edit-original-start-year'] as $removedFieldId) {
    if (str_contains($index, $removedFieldId) || str_contains($app, $removedFieldId)) {
        throw new RuntimeException('Altes getrenntes Monats-/Jahresfeld ist noch vorhanden: ' . $removedFieldId);
    }
}

if (offer_month_start_date('2018-04') !== '2018-04-01'
    || offer_month_start_date('2027-11-19') !== '2027-11-19'
    || offer_month_start_date('2027-02-30') !== null
    || offer_month_start_date('2027-13') !== null) {
    throw new RuntimeException('Tag/Monat/Jahr-Werte werden nicht zuverlässig normalisiert.');
}

if (!str_contains($createApi, 'offer_month_start_date($originalStartInput)')
    || !str_contains($createApi, 'offer_month_start_date($effectiveStartInput)')
    || !str_contains($updateApi, 'offer_month_start_date($originalStartInput)')
    || !str_contains($updateApi, 'offer_month_start_date($startDate)')) {
    throw new RuntimeException('Ein Speicherweg validiert die beiden Vertragsdaten nicht.');
}
if (!str_contains($createApi, '$effectiveStartDate')
    || !str_contains($updateApi, '$isExistingContract ? $effectiveStartDate : $startDate')) {
    throw new RuntimeException('Das Inkrafttreten des neuen Vertrags wird nicht gespeichert.');
}
if (!str_contains($publicJs, 'Neuer Vertrag in Kraft ab') || !str_contains($app, 'Neuer Vertrag ab')) {
    throw new RuntimeException('Die beiden Vertragsdaten werden nicht verständlich angezeigt.');
}

$existingContractOffer = [
    'price' => 500.0,
    'vat_applicable' => 1,
    'is_existing_contract' => 1,
    'original_start_date' => '2018-04-15',
    'start_date' => '2027-11-19',
    'created_at' => '2026-09-15 10:00:00',
    'interval_label' => 'Wöchentlich',
    'square_meters' => 0,
    'service' => 'Individuelle Leistung',
    'notes' => '',
    'customer_obligations_note' => '',
];
$existingContractCustomer = [
    'name' => 'Test GmbH',
    'address' => 'Musterstraße',
    'house_number' => '1',
    'zip' => '12345',
    'city' => 'Musterstadt',
    'salutation' => '',
    'contact_last_name' => 'Max Mustermann',
];

$previewPlaceholders = contract_template_placeholder_map($existingContractOffer, $existingContractCustomer, null, false);

if ($previewPlaceholders['beginn_datum'] !== '19.11.2027') {
    throw new RuntimeException('Im neuen Bestandsvertrag wird nicht das neue Inkrafttreten mit Tag verwendet.');
}
if (!str_contains($previewPlaceholders['beginn_block'], 'Der ursprüngliche Vertrag besteht seit')
    || !str_contains($previewPlaceholders['beginn_block'], '15.04.2018')
    || !str_contains($previewPlaceholders['beginn_block'], 'Der neue Vertrag tritt am')
    || !str_contains($previewPlaceholders['beginn_block'], '19.11.2027')) {
    throw new RuntimeException('Die Kundenübersicht nennt ursprünglichen Vertrag und neues Inkrafttreten nicht vollständig mit Tag.');
}
if ($previewPlaceholders['ausfertigung_satz'] !== 'Beide Parteien erhalten eine Ausfertigung dieses Vertrags.') {
    throw new RuntimeException('Der Bestandskundenvertrag wird in der Ausfertigungsklausel nicht als Vertrag bezeichnet.');
}

$pdfPlaceholders = contract_template_placeholder_map($existingContractOffer, $existingContractCustomer, null, true);

if ($pdfPlaceholders['beginn_datum'] !== '19.11.2027') {
    throw new RuntimeException('Im finalen PDF wird nicht das neue Inkrafttreten mit Tag verwendet.');
}
if (str_contains($pdfPlaceholders['beginn_block'], 'Der ursprüngliche Vertrag besteht seit')
    || str_contains($pdfPlaceholders['beginn_block'], '15.04.2018')) {
    throw new RuntimeException('Das finale PDF nennt noch das ursprüngliche Vertragsdatum, obwohl nur das neue Inkrafttreten stehen soll.');
}
if (!str_contains($pdfPlaceholders['beginn_block'], '19.11.2027')) {
    throw new RuntimeException('Das finale PDF nennt das neue Inkrafttreten nicht mit Tag.');
}

echo "PASS: preview shows both contract dates, final PDF shows only the new effective date\n";
