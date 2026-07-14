<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

function offer_row_to_json(array $row): array
{
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
        'price' => (float) $row['price'],
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

    if ($customerId === '' || $squareMeters <= 0 || $interval === '' || $service === '') {
        json_error('Kunde, Quadratmeter, Intervall und Leistung sind erforderlich.', 422);
    }

    $customerStmt = $pdo->prepare('SELECT id FROM customers WHERE id = :id');
    $customerStmt->execute(['id' => $customerId]);
    if (!$customerStmt->fetch()) {
        json_error('Kunde wurde nicht gefunden.', 404);
    }

    $price = calculate_offer_price($squareMeters, $interval, $service);
    $id = generate_id('offer');
    $token = generate_token();
    $startDate = trim((string) ($body['startDate'] ?? ''));

    $stmt = $pdo->prepare(
        'INSERT INTO offers (id, customer_id, square_meters, interval_label, service, start_date, notes, price, token, created_at, expires_at)
         VALUES (:id, :customer_id, :square_meters, :interval_label, :service, :start_date, :notes, :price, :token, UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 14 DAY))'
    );
    $stmt->execute([
        'id' => $id,
        'customer_id' => $customerId,
        'square_meters' => (int) $squareMeters,
        'interval_label' => $interval,
        'service' => $service,
        'start_date' => $startDate !== '' ? $startDate : null,
        'notes' => trim((string) ($body['notes'] ?? '')),
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
        json_error('Angebots-ID fehlt.', 422);
    }

    $stmt = $pdo->prepare('DELETE FROM offers WHERE id = :id');
    $stmt->execute(['id' => $id]);
    json_response(['ok' => true]);
}

json_error('Methode nicht erlaubt.', 405);
