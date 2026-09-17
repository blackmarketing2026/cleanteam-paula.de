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
$originalStartInput = trim((string) ($body['originalStartDate'] ?? ''));
$effectiveStartInput = trim((string) ($body['startDate'] ?? ''));
$signingLocation = normalize_contract_signing_location($body['signingLocation'] ?? null);
$validityDays = (int) ($body['validityDays'] ?? 14);
$validityHours = (int) ($body['validityHours'] ?? 0);
$legacyStartMonth = (int) ($body['originalStartMonth'] ?? 0);
$legacyStartYear = (int) ($body['originalStartYear'] ?? 0);
if ($originalStartInput === '' && $legacyStartMonth > 0 && $legacyStartYear > 0) {
    $originalStartInput = sprintf('%04d-%02d', $legacyStartYear, $legacyStartMonth);
}
$serviceText = str_replace(["\r\n", "\r"], "\n", (string) ($body['serviceText'] ?? ''));
$customerObligationsNote = str_replace(["\r\n", "\r"], "\n", (string) ($body['customerObligationsNote'] ?? ''));

if ($customerName === '' || $contactPerson === '' || $email === '' || $address === ''
    || $zip === '' || $city === '' || $interval === '' || trim($serviceText) === '') {
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
$originalStartDate = offer_month_start_date($originalStartInput);
$effectiveStartDate = offer_month_start_date($effectiveStartInput);
$currentYear = (int) gmdate('Y');
if ($originalStartDate === null || (int) substr($originalStartDate, 0, 4) < 1900
    || (int) substr($originalStartDate, 0, 4) > $currentYear) {
    json_error('Bitte den ursprünglichen Vertragsbeginn mit einem gültigen Monat und Jahr eintragen.', 422);
}
if ($effectiveStartDate === null || (int) substr($effectiveStartDate, 0, 4) < 1900) {
    json_error('Bitte eintragen, ab wann der neue Vertrag mit einem gültigen Monat und Jahr in Kraft tritt.', 422);
}
if ($signingLocation === null) {
    json_error('Bitte einen gültigen Signaturort auswählen.', 422);
}
if ($validityDays < 0 || $validityHours < 0 || $validityHours > 24
    || ($validityDays === 0 && $validityHours === 0)) {
    json_error('Bitte mindestens einen Tag oder eine Stunde als Gültigkeitsdauer auswählen.', 422);
}

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
            original_start_date, signing_location, notes, customer_obligations_note, base_price, discount_percent, price_adjustment, price_adjustment_note,
            price, vat_applicable, token, created_at, expires_at, validity_days, validity_hours)
         VALUES (:id, :customer_id, 1, :square_meters, :interval_label, :service, :start_date, :original_start_date, :signing_location,
            :notes, :obligations, :base_price, :discount_percent, 0, NULL, :price, :vat, :token, UTC_TIMESTAMP(),
            DATE_ADD(DATE_ADD(UTC_TIMESTAMP(), INTERVAL :validity_days DAY), INTERVAL :validity_hours HOUR), :validity_days2, :validity_hours2)'
    )->execute([
        'id' => $id, 'customer_id' => $customerId, 'square_meters' => $squareMeters,
        'interval_label' => $interval, 'service' => 'Individuelle Leistung', 'start_date' => $effectiveStartDate,
        'original_start_date' => $originalStartDate,
        'signing_location' => $signingLocation,
        'notes' => $serviceText,
        'obligations' => $customerObligationsNote !== '' ? $customerObligationsNote : null,
        'base_price' => $basePrice, 'discount_percent' => $discountPercent,
        'price' => $price, 'vat' => $vatApplicable ? 1 : 0, 'token' => generate_token(),
        'validity_days' => $validityDays, 'validity_days2' => $validityDays,
        'validity_hours' => $validityHours, 'validity_hours2' => $validityHours,
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
