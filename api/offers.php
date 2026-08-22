<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

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

ensure_offers_pricing_columns($pdo);

if ($method === 'GET') {
    $rows = $pdo->query(OFFER_SELECT . ' ORDER BY o.created_at DESC')->fetchAll();
    json_response(array_map('offer_row_to_json', $rows));
}

if ($method === 'POST') {
    $body = read_json_body();
    $customerName = trim((string) ($body['customerName'] ?? ''));
    $contactPerson = trim((string) ($body['contactPerson'] ?? ''));
    $phone = trim((string) ($body['phone'] ?? ''));
    $email = trim((string) ($body['email'] ?? ''));
    $price = round((float) ($body['price'] ?? 0), 2);
    $serviceText = trim((string) ($body['serviceText'] ?? ''));

    if ($customerName === '' || $contactPerson === '' || $phone === '' || $email === '' || $serviceText === '') {
        json_error('Name, Ansprechpartner, Telefonnummer, E-Mail und Leistungsbeschreibung sind erforderlich.', 422);
    }

    if ($price <= 0) {
        json_error('Bitte den monatlichen Preis eintragen.', 422);
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
        'phone' => $phone,
        'salutation' => '',
        'contact_last_name' => $contactPerson,
        'address' => '',
        'house_number' => '',
        'zip' => '',
        'city' => '',
    ]);

    $id = generate_id('offer');
    $token = generate_token();

    $stmt = $pdo->prepare(
        'INSERT INTO offers (id, customer_id, square_meters, interval_label, service, start_date, notes, base_price, price_adjustment, price_adjustment_note, price, token, created_at, expires_at)
         VALUES (:id, :customer_id, 0, :interval_label, :service, NULL, :notes, :base_price, 0, NULL, :price, :token, UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 14 DAY))'
    );
    $stmt->execute([
        'id' => $id,
        'customer_id' => $customerId,
        'interval_label' => 'Monatlich',
        'service' => 'Individuelle Leistung',
        'notes' => format_service_text($serviceText),
        'base_price' => $price,
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
        json_error('Vertrags-ID fehlt.', 422);
    }

    $stmt = $pdo->prepare('DELETE FROM offers WHERE id = :id');
    $stmt->execute(['id' => $id]);
    json_response(['ok' => true]);
}

json_error('Methode nicht erlaubt.', 405);
