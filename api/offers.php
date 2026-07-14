<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

function ensure_offers_site_visit_id_column(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM offers LIKE 'site_visit_id'");
    if ($stmt->fetch()) {
        return;
    }

    $pdo->exec('ALTER TABLE offers ADD COLUMN site_visit_id VARCHAR(64) NULL AFTER customer_id');
}

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

function offer_row_to_json(array $row): array
{
    $price = (float) $row['price'];
    $basePrice = isset($row['base_price']) && (float) $row['base_price'] > 0
        ? (float) $row['base_price']
        : $price;

    return [
        'id' => $row['id'],
        'customerId' => $row['customer_id'],
        'siteVisitId' => $row['site_visit_id'] ?? null,
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
        'token' => $row['token'],
        'publicUrl' => base_url() . '/offer.php?token=' . $row['token'],
        'createdAt' => to_iso($row['created_at']),
        'expiresAt' => to_iso($row['expires_at']),
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

ensure_offers_site_visit_id_column($pdo);
ensure_offers_pricing_columns($pdo);

if ($method === 'GET') {
    $rows = $pdo->query(OFFER_SELECT . ' ORDER BY o.created_at DESC')->fetchAll();
    json_response(array_map('offer_row_to_json', $rows));
}

if ($method === 'POST') {
    $body = read_json_body();
    $customerId = trim((string) ($body['customerId'] ?? ''));
    $squareMeters = (float) ($body['squareMeters'] ?? 0);
    $interval = trim((string) ($body['interval'] ?? ''));
    $service = trim((string) ($body['service'] ?? ''));
    $siteVisitId = trim((string) ($body['siteVisitId'] ?? ''));
    $priceAdjustment = round((float) ($body['priceAdjustment'] ?? 0), 2);
    $priceAdjustmentNote = trim((string) ($body['priceAdjustmentNote'] ?? ''));

    if ($siteVisitId === '') {
        json_error('Bitte zuerst eine offene Begehung auswählen.', 422);
    }

    if ($customerId === '' || $squareMeters <= 0 || $interval === '' || $service === '') {
        json_error('Begehung, Kunde, Quadratmeter, Intervall und Leistung sind erforderlich.', 422);
    }

    $customerStmt = $pdo->prepare('SELECT id FROM customers WHERE id = :id');
    $customerStmt->execute(['id' => $customerId]);
    if (!$customerStmt->fetch()) {
        json_error('Kunde wurde nicht gefunden.', 404);
    }

    $visitStmt = $pdo->prepare('SELECT id FROM site_visits WHERE id = :id');
    $visitStmt->execute(['id' => $siteVisitId]);
    if (!$visitStmt->fetch()) {
        json_error('Begehung wurde nicht gefunden.', 404);
    }

    $processedStmt = $pdo->prepare('SELECT id FROM offers WHERE site_visit_id = :site_visit_id LIMIT 1');
    $processedStmt->execute(['site_visit_id' => $siteVisitId]);
    if ($processedStmt->fetch()) {
        json_error('Diese Begehung wurde bereits abgearbeitet.', 409);
    }

    $basePrice = calculate_offer_price($squareMeters, $interval, $service);
    $price = max(0, round($basePrice + $priceAdjustment, 2));
    $id = generate_id('offer');
    $token = generate_token();
    $startDate = trim((string) ($body['startDate'] ?? ''));

    $stmt = $pdo->prepare(
        'INSERT INTO offers (id, customer_id, site_visit_id, square_meters, interval_label, service, start_date, notes, base_price, price_adjustment, price_adjustment_note, price, token, created_at, expires_at)
         VALUES (:id, :customer_id, :site_visit_id, :square_meters, :interval_label, :service, :start_date, :notes, :base_price, :price_adjustment, :price_adjustment_note, :price, :token, UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 14 DAY))'
    );
    $stmt->execute([
        'id' => $id,
        'customer_id' => $customerId,
        'site_visit_id' => $siteVisitId !== '' ? $siteVisitId : null,
        'square_meters' => (int) $squareMeters,
        'interval_label' => $interval,
        'service' => $service,
        'start_date' => $startDate !== '' ? $startDate : null,
        'notes' => trim((string) ($body['notes'] ?? '')),
        'base_price' => $basePrice,
        'price_adjustment' => $priceAdjustment,
        'price_adjustment_note' => $priceAdjustmentNote !== '' ? $priceAdjustmentNote : null,
        'price' => $price,
        'token' => $token,
    ]);

    $stmt = $pdo->prepare(OFFER_SELECT . ' WHERE o.id = :id');
    $stmt->execute(['id' => $id]);
    json_response(offer_row_to_json($stmt->fetch()), 201);
}

if ($method === 'DELETE') {
    $id = (string) ($_GET['id'] ?? '');
    if ($id === '') {
        json_error('Kostenvoranschlags-ID fehlt.', 422);
    }

    $stmt = $pdo->prepare('DELETE FROM offers WHERE id = :id');
    $stmt->execute(['id' => $id]);
    json_response(['ok' => true]);
}

json_error('Methode nicht erlaubt.', 405);
