<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/contract_template.php';

require_login();
require_method('POST');

$pdo = db();
ensure_offers_discount_column($pdo);
ensure_offers_interval_label_length($pdo);
ensure_offers_vat_column($pdo);
ensure_offers_customer_obligations_column($pdo);
ensure_offers_existing_contract_columns($pdo);

$body = read_json_body();
$customerName = trim((string) ($body['customerName'] ?? ''));
$contactPerson = trim((string) ($body['contactPerson'] ?? ''));
$email = trim((string) ($body['email'] ?? ''));
$address = trim((string) ($body['address'] ?? ''));
$zip = trim((string) ($body['zip'] ?? ''));
$city = trim((string) ($body['city'] ?? ''));
$squareMeters = max(0, (int) ($body['squareMeters'] ?? 0));
$interval = trim((string) ($body['interval'] ?? ''));
$basePrice = round((float) ($body['basePrice'] ?? $body['price'] ?? 0), 2);
$discountPercent = round((float) ($body['discountPercent'] ?? 0), 2);
$price = offer_discounted_price($basePrice, $discountPercent);
$vatApplicable = (bool) ($body['vatApplicable'] ?? true);
$startMonth = (int) ($body['originalStartMonth'] ?? 0);
$startYear = (int) ($body['originalStartYear'] ?? 0);
$serviceText = trim((string) ($body['serviceText'] ?? ''));
$customerObligationsNote = trim((string) ($body['customerObligationsNote'] ?? ''));

if ($customerName === '' || $contactPerson === '' || $email === '' || $address === ''
    || $zip === '' || $city === '' || $interval === '' || $serviceText === '') {
    json_error('Name, Geschäftsführer/Inhaber, E-Mail, Objektadresse, Reinigungsintervall und Leistungsbeschreibung sind erforderlich.', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_error('Bitte eine gültige E-Mail-Adresse eintragen.', 422);
}
if ($basePrice <= 0) {
    json_error('Bitte den monatlichen Gesamtpreis eintragen.', 422);
}
if ($discountPercent < 0 || $discountPercent >= 100) {
    json_error('Der Rabatt muss zwischen 0 und 99,99 Prozent liegen.', 422);
}
$currentYear = (int) gmdate('Y');
if ($startMonth < 1 || $startMonth > 12 || $startYear < 1900 || $startYear > $currentYear) {
    json_error('Bitte den ursprünglichen Vertragsbeginn mit einem gültigen Monat und Jahr eintragen.', 422);
}
$originalStartDate = sprintf('%04d-%02d-01', $startYear, $startMonth);

try {
    $pdo->beginTransaction();
    $customerId = generate_id('customer');
    $pdo->prepare(
        'INSERT INTO customers (id, name, email, phone, salutation, contact_last_name, address, house_number, zip, city, created_at)
         VALUES (:id, :name, :email, \'\', \'\', :contact, :address, \'\', :zip, :city, UTC_TIMESTAMP())'
    )->execute(['id' => $customerId, 'name' => $customerName, 'email' => $email, 'contact' => $contactPerson,
        'address' => $address, 'zip' => $zip, 'city' => $city]);

    $id = generate_id('offer');
    $pdo->prepare(
        'INSERT INTO offers (id, customer_id, is_existing_contract, square_meters, interval_label, service, start_date,
            original_start_date, notes, customer_obligations_note, base_price, discount_percent, price_adjustment, price_adjustment_note,
            price, vat_applicable, token, created_at, expires_at, validity_days, validity_hours)
         VALUES (:id, :customer_id, 1, :square_meters, :interval_label, :service, NULL, :original_start_date,
            :notes, :obligations, :base_price, :discount_percent, 0, NULL, :price, :vat, :token, UTC_TIMESTAMP(), \'2099-12-31 23:59:59\', 0, 0)'
    )->execute([
        'id' => $id, 'customer_id' => $customerId, 'square_meters' => $squareMeters,
        'interval_label' => $interval, 'service' => 'Individuelle Leistung', 'original_start_date' => $originalStartDate,
        'notes' => format_service_text($serviceText),
        'obligations' => $customerObligationsNote !== '' ? format_service_text($customerObligationsNote) : null,
        'base_price' => $basePrice, 'discount_percent' => $discountPercent,
        'price' => $price, 'vat' => $vatApplicable ? 1 : 0, 'token' => generate_token(),
    ]);

    $agbSnapshotText = fetch_agb_text_snapshot();
    if ($agbSnapshotText !== null) {
        $pdo->prepare('UPDATE offers SET agb_snapshot_text = :text, agb_snapshot_captured_at = UTC_TIMESTAMP() WHERE id = :id')
            ->execute(['text' => $agbSnapshotText, 'id' => $id]);
    }
    $pdo->commit();
    json_response(['id' => $id], 201);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}
