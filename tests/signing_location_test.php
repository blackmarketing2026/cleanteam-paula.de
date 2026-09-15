<?php

require_once __DIR__ . '/../includes/helpers.php';
restore_exception_handler();
require_once __DIR__ . '/../includes/contract_template.php';

$index = file_get_contents(__DIR__ . '/../index.html');
$app = file_get_contents(__DIR__ . '/../app.js');
$offersApi = file_get_contents(__DIR__ . '/../api/offers.php');
$existingOffersApi = file_get_contents(__DIR__ . '/../api/existing-offers.php');
$template = file_get_contents(__DIR__ . '/../includes/contract_template.php');
$pdf = file_get_contents(__DIR__ . '/../includes/contract_pdf.php');
$schema = file_get_contents(__DIR__ . '/../sql/schema.sql');

foreach (['offer-signing-location', 'existing-offer-signing-location', 'offer-edit-signing-location'] as $fieldId) {
    if (!str_contains($index, 'id="' . $fieldId . '"')) {
        throw new RuntimeException('Auswahl für den Signaturort fehlt: ' . $fieldId);
    }
}
foreach (['Linz, &Ouml;sterreich' => 'Linz, Österreich', 'Solingen, Deutschland' => 'Solingen, Deutschland'] as $htmlLocation => $location) {
    if (!str_contains($index, 'value="' . $htmlLocation . '"')) {
        throw new RuntimeException('Signaturort fehlt in den Auswahlboxen: ' . $location);
    }
}
if (normalize_contract_signing_location('Linz, Österreich') !== 'Linz, Österreich'
    || normalize_contract_signing_location('Solingen, Deutschland') !== 'Solingen, Deutschland'
    || normalize_contract_signing_location('Musterstadt') !== null) {
    throw new RuntimeException('Signaturorte werden nicht zuverlässig validiert.');
}
if (contract_offer_signing_location([]) !== SIGNING_LOCATION
    || contract_offer_signing_location(['signing_location' => 'Solingen, Deutschland']) !== 'Solingen, Deutschland') {
    throw new RuntimeException('Gespeicherter Signaturort oder Rückfallwert ist fehlerhaft.');
}
if (!str_contains($app, 'signingLocation')
    || !str_contains($offersApi, 'signing_location')
    || !str_contains($existingOffersApi, 'signing_location')
    || !str_contains($schema, 'signing_location VARCHAR(80)')) {
    throw new RuntimeException('Der Signaturort wird nicht durchgängig gespeichert.');
}
if (!str_contains($template, 'contract_offer_signing_location($offer)')
    || !str_contains($pdf, 'contract_offer_signing_location($offer)')) {
    throw new RuntimeException('Der ausgewählte Signaturort fehlt im HTML- oder PDF-Vertrag.');
}

echo "PASS: signing location is selectable, persisted and rendered for all contract types\n";
