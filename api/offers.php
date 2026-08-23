<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/contract_template.php';

require_login();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

function ensure_offers_pricing_columns(PDO $pdo): void
{
    $columns = [
        'base_price' => 'ALTER TABLE offers ADD COLUMN base_price DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER notes',
        'price_adjustment' => 'ALTER TABLE offers ADD COLUMN price_adjustment DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER base_price',
        'price_adjustment_note' => 'ALTER TABLE offers ADD COLUMN price_adjustment_note VARCHAR(255) NULL AFTER price_adjustment',
    ];

    foreach ($columns as $column => $sql) {
        $stmt = $pdo->query("SHOW COLUMNS FROM offers LIKE '{$column}'");
        if (!$stmt->fetch()) {
            $pdo->exec($sql);
        }
    }
}

function ensure_offers_agb_snapshot_columns(PDO $pdo): void
{
    $columns = [
        'agb_snapshot_text' => 'ALTER TABLE offers ADD COLUMN agb_snapshot_text LONGTEXT NULL AFTER notes',
        'agb_snapshot_captured_at' => 'ALTER TABLE offers ADD COLUMN agb_snapshot_captured_at DATETIME NULL AFTER agb_snapshot_text',
    ];

    foreach ($columns as $column => $sql) {
        $stmt = $pdo->query("SHOW COLUMNS FROM offers LIKE '{$column}'");
        if (!$stmt->fetch()) {
            $pdo->exec($sql);
        }
    }
}

function ensure_offers_validity_days_column(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM offers LIKE 'validity_days'");
    if (!$stmt->fetch()) {
        $pdo->exec('ALTER TABLE offers ADD COLUMN validity_days SMALLINT UNSIGNED NOT NULL DEFAULT 14 AFTER expires_at');
    }
}

function offer_row_to_json(array $row): array
{
    $price = (float) $row['price'];
    $basePrice = isset($row['base_price']) && (float) $row['base_price'] > 0
        ? (float) $row['base_price']
        : $price;

    return [
        'id' => $row['id'],
        'customerId' => $row['customer_id'],
        'customer' => [
            'id' => $row['customer_id'],
            'name' => $row['c_name'],
            'email' => $row['c_email'],
            'phone' => $row['c_phone'],
            'salutation' => $row['c_salutation'],
            'contactLastName' => $row['c_contact_last_name'],
            'address' => $row['c_address'],
            'houseNumber' => $row['c_house_number'],
            'zip' => $row['c_zip'],
            'city' => $row['c_city'],
        ],
        'squareMeters' => (int) $row['square_meters'],
        'interval' => $row['interval_label'],
        'service' => $row['service'],
        'startDate' => $row['start_date'],
        'notes' => $row['notes'],
        'basePrice' => $basePrice,
        'priceAdjustment' => (float) ($row['price_adjustment'] ?? 0),
        'priceAdjustmentNote' => $row['price_adjustment_note'] ?? null,
        'price' => $price,
        'vatApplicable' => !isset($row['vat_applicable']) || (int) $row['vat_applicable'] === 1,
        'token' => $row['token'],
        'publicUrl' => base_url() . '/offer.php?token=' . $row['token'],
        'createdAt' => to_iso($row['created_at']),
        'expiresAt' => to_iso($row['expires_at']),
        'validityDays' => (int) ($row['validity_days'] ?? 14),
        'linkOpenedAt' => to_iso($row['link_opened_at'] ?? null),
        'sentAt' => to_iso($row['sent_at']),
        'contractId' => $row['contract_id'],
        'contractStatus' => $row['contract_status'],
    ];
}

const OFFER_SELECT = 'SELECT o.*, c.name AS c_name, c.email AS c_email, c.phone AS c_phone,
    c.salutation AS c_salutation, c.contact_last_name AS c_contact_last_name, c.address AS c_address,
    c.house_number AS c_house_number, c.zip AS c_zip, c.city AS c_city,
    ct.id AS contract_id, ct.status AS contract_status
    FROM offers o
    INNER JOIN customers c ON c.id = o.customer_id
    LEFT JOIN contracts ct ON ct.offer_id = o.id';

ensure_offers_pricing_columns($pdo);
ensure_offers_interval_label_length($pdo);
ensure_offers_vat_column($pdo);
ensure_offers_agb_snapshot_columns($pdo);
ensure_offers_validity_days_column($pdo);
ensure_offers_link_opened_column($pdo);

if ($method === 'GET') {
    $rows = $pdo->query(OFFER_SELECT . ' ORDER BY o.created_at DESC')->fetchAll();
    json_response(array_map('offer_row_to_json', $rows));
}

if ($method === 'POST') {
    $body = read_json_body();
    $customerName = trim((string) ($body['customerName'] ?? ''));
    $contactPerson = trim((string) ($body['contactPerson'] ?? ''));
    $email = trim((string) ($body['email'] ?? ''));
    $address = trim((string) ($body['address'] ?? ''));
    $zip = trim((string) ($body['zip'] ?? ''));
    $city = trim((string) ($body['city'] ?? ''));
    $squareMeters = (int) ($body['squareMeters'] ?? 0);
    $price = round((float) ($body['price'] ?? 0), 2);
    $vatApplicable = (bool) ($body['vatApplicable'] ?? true);
    $startDate = trim((string) ($body['startDate'] ?? ''));
    $serviceText = trim((string) ($body['serviceText'] ?? ''));
    $interval = trim((string) ($body['interval'] ?? ''));
    $validityDays = (int) ($body['validityDays'] ?? 14);

    $allowedIntervals = ['Täglich', 'Einmal wöchentlich', 'Zweimal wöchentlich', 'Dreimal wöchentlich', 'Viermal wöchentlich', '14-tägig'];
    if (!in_array($interval, $allowedIntervals, true)) {
        json_error('Bitte ein gültiges Reinigungsintervall auswählen.', 422);
    }
    $intervalLabel = $interval;

    if ($customerName === '' || $contactPerson === '' || $email === ''
        || $address === '' || $zip === '' || $city === '' || $serviceText === '') {
        json_error('Name, Geschäftsführer/Inhaber, E-Mail, Objektadresse und Leistungsbeschreibung sind erforderlich.', 422);
    }

    if ($price <= 0) {
        json_error('Bitte den monatlichen Preis eintragen.', 422);
    }

    if ($startDate === '' || strtotime($startDate) === false) {
        json_error('Bitte den Beginn der Dienstleistung eintragen.', 422);
    }

    if ($validityDays <= 0) {
        json_error('Bitte eine gültige Anzahl an Tagen für die Gültigkeitsdauer eintragen.', 422);
    }

    $customerId = generate_id('customer');
    $customerStmt = $pdo->prepare(
        'INSERT INTO customers (id, name, email, phone, salutation, contact_last_name, address, house_number, zip, city, created_at)
         VALUES (:id, :name, :email, :phone, :salutation, :contact_last_name, :address, :house_number, :zip, :city, UTC_TIMESTAMP())'
    );
    $customerStmt->execute([
        'id' => $customerId,
        'name' => $customerName,
        'email' => $email,
        'phone' => '',
        'salutation' => '',
        'contact_last_name' => $contactPerson,
        'address' => $address,
        'house_number' => '',
        'zip' => $zip,
        'city' => $city,
    ]);

    $id = generate_id('offer');
    $token = generate_token();

    $stmt = $pdo->prepare(
        'INSERT INTO offers (id, customer_id, square_meters, interval_label, service, start_date, notes, base_price, price_adjustment, price_adjustment_note, price, vat_applicable, token, created_at, expires_at, validity_days)
         VALUES (:id, :customer_id, :square_meters, :interval_label, :service, :start_date, :notes, :base_price, 0, NULL, :price, :vat_applicable, :token, UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL :validity_days DAY), :validity_days2)'
    );
    $stmt->execute([
        'id' => $id,
        'customer_id' => $customerId,
        'square_meters' => $squareMeters,
        'interval_label' => $intervalLabel,
        'service' => 'Individuelle Leistung',
        'start_date' => $startDate,
        'notes' => format_service_text($serviceText),
        'base_price' => $price,
        'price' => $price,
        'vat_applicable' => $vatApplicable ? 1 : 0,
        'token' => $token,
        'validity_days' => $validityDays,
        'validity_days2' => $validityDays,
    ]);

    $agbSnapshotText = fetch_agb_text_snapshot();
    if ($agbSnapshotText !== null) {
        $pdo->prepare('UPDATE offers SET agb_snapshot_text = :text, agb_snapshot_captured_at = UTC_TIMESTAMP() WHERE id = :id')
            ->execute(['text' => $agbSnapshotText, 'id' => $id]);
    }

    $stmt = $pdo->prepare(OFFER_SELECT . ' WHERE o.id = :id');
    $stmt->execute(['id' => $id]);
    json_response(offer_row_to_json($stmt->fetch()), 201);
}

if ($method === 'PUT') {
    $id = (string) ($_GET['id'] ?? '');
    if ($id === '') {
        json_error('Vertrags-ID fehlt.', 422);
    }

    $stmt = $pdo->prepare('SELECT id, customer_id, created_at FROM offers WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        json_error('Vertragsentwurf wurde nicht gefunden.', 404);
    }

    $body = read_json_body();
    $customerName = trim((string) ($body['customerName'] ?? ''));
    $contactPerson = trim((string) ($body['contactPerson'] ?? ''));
    $email = trim((string) ($body['email'] ?? ''));
    $address = trim((string) ($body['address'] ?? ''));
    $zip = trim((string) ($body['zip'] ?? ''));
    $city = trim((string) ($body['city'] ?? ''));
    $squareMeters = (int) ($body['squareMeters'] ?? 0);
    $price = round((float) ($body['price'] ?? 0), 2);
    $vatApplicable = (bool) ($body['vatApplicable'] ?? true);
    $startDate = trim((string) ($body['startDate'] ?? ''));
    $serviceText = trim((string) ($body['serviceText'] ?? ''));
    $interval = trim((string) ($body['interval'] ?? ''));
    $validityDays = (int) ($body['validityDays'] ?? 14);

    $allowedIntervals = ['Täglich', 'Einmal wöchentlich', 'Zweimal wöchentlich', 'Dreimal wöchentlich', 'Viermal wöchentlich', '14-tägig'];
    if (!in_array($interval, $allowedIntervals, true)) {
        json_error('Bitte ein gültiges Reinigungsintervall auswählen.', 422);
    }
    $intervalLabel = $interval;

    if ($customerName === '' || $contactPerson === '' || $email === ''
        || $address === '' || $zip === '' || $city === '' || $serviceText === '') {
        json_error('Name, Geschäftsführer/Inhaber, E-Mail, Objektadresse und Leistungsbeschreibung sind erforderlich.', 422);
    }
    if ($price <= 0) {
        json_error('Bitte den monatlichen Preis eintragen.', 422);
    }
    if ($startDate === '' || strtotime($startDate) === false) {
        json_error('Bitte den Beginn der Dienstleistung eintragen.', 422);
    }
    if ($validityDays <= 0) {
        json_error('Bitte eine gültige Anzahl an Tagen für die Gültigkeitsdauer eintragen.', 422);
    }

    $pdo->prepare(
        'UPDATE customers SET name = :name, contact_last_name = :contact, email = :email,
            address = :address, zip = :zip, city = :city WHERE id = :id'
    )->execute([
        'name' => $customerName,
        'contact' => $contactPerson,
        'email' => $email,
        'address' => $address,
        'zip' => $zip,
        'city' => $city,
        'id' => $existing['customer_id'],
    ]);

    $pdo->prepare(
        'UPDATE offers SET square_meters = :square_meters, interval_label = :interval_label, start_date = :start_date,
            price = :price, base_price = :price, vat_applicable = :vat_applicable, notes = :notes,
            validity_days = :validity_days, expires_at = DATE_ADD(:created_at, INTERVAL :validity_days2 DAY) WHERE id = :id'
    )->execute([
        'square_meters' => $squareMeters,
        'interval_label' => $intervalLabel,
        'start_date' => $startDate,
        'price' => $price,
        'vat_applicable' => $vatApplicable ? 1 : 0,
        'notes' => format_service_text($serviceText),
        'validity_days' => $validityDays,
        'validity_days2' => $validityDays,
        'created_at' => $existing['created_at'],
        'id' => $id,
    ]);

    $stmt = $pdo->prepare(OFFER_SELECT . ' WHERE o.id = :id');
    $stmt->execute(['id' => $id]);
    json_response(offer_row_to_json($stmt->fetch()));
}

if ($method === 'PATCH') {
    $id = (string) ($_GET['id'] ?? '');
    if ($id === '') {
        json_error('Vertrags-ID fehlt.', 422);
    }

    $body = read_json_body();
    $action = (string) ($body['action'] ?? '');

    if ($action !== 'reset-link') {
        json_error('Unbekannte Aktion.', 422);
    }

    $stmt = $pdo->prepare('SELECT id FROM offers WHERE id = :id');
    $stmt->execute(['id' => $id]);
    if (!$stmt->fetch()) {
        json_error('Vertragsentwurf wurde nicht gefunden.', 404);
    }

    $pdo->prepare('UPDATE offers SET link_opened_at = NULL WHERE id = :id')->execute(['id' => $id]);

    $stmt = $pdo->prepare(OFFER_SELECT . ' WHERE o.id = :id');
    $stmt->execute(['id' => $id]);
    json_response(offer_row_to_json($stmt->fetch()));
}

if ($method === 'DELETE') {
    $id = (string) ($_GET['id'] ?? '');
    if ($id === '') {
        json_error('Vertrags-ID fehlt.', 422);
    }

    $stmt = $pdo->prepare('DELETE FROM offers WHERE id = :id');
    $stmt->execute(['id' => $id]);
    json_response(['ok' => true]);
}

json_error('Methode nicht erlaubt.', 405);
