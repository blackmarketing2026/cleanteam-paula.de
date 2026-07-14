<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

const CONTRACT_SELECT = 'SELECT ct.*, o.square_meters, o.interval_label, o.service, o.start_date, o.notes AS offer_notes,
    o.price, o.created_at AS offer_created_at, o.token,
    c.name AS c_name, c.email AS c_email, c.phone AS c_phone, c.salutation AS c_salutation,
    c.contact_last_name AS c_contact_last_name, c.address AS c_address, c.house_number AS c_house_number,
    c.zip AS c_zip, c.city AS c_city
    FROM contracts ct
    INNER JOIN offers o ON o.id = ct.offer_id
    INNER JOIN customers c ON c.id = ct.customer_id';

function contract_row_to_json(array $row): array
{
    return [
        'id' => $row['id'],
        'offerId' => $row['offer_id'],
        'number' => $row['number'],
        'status' => $row['status'],
        'currentStep' => $row['current_step'],
        'dataConfirmed' => (bool) $row['data_confirmed'],
        'intervalConfirmed' => (bool) $row['interval_confirmed'],
        'authorized' => $row['authorized'] === null ? null : (bool) $row['authorized'],
        'representationNote' => $row['representation_note'],
        'signedAt' => to_iso($row['signed_at']),
        'signatureDataUrl' => $row['signature_data'],
        'createdAt' => to_iso($row['created_at']),
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
        'offer' => [
            'id' => $row['offer_id'],
            'squareMeters' => (int) $row['square_meters'],
            'interval' => $row['interval_label'],
            'service' => $row['service'],
            'startDate' => $row['start_date'],
            'notes' => $row['offer_notes'],
            'price' => (float) $row['price'],
            'createdAt' => to_iso($row['offer_created_at']),
        ],
    ];
}

if ($method === 'GET') {
    $rows = $pdo->query(CONTRACT_SELECT . ' ORDER BY ct.created_at DESC')->fetchAll();
    json_response(array_map('contract_row_to_json', $rows));
}

if ($method === 'POST') {
    $body = read_json_body();
    $offerId = trim((string) ($body['offerId'] ?? ''));

    if ($offerId === '') {
        json_error('Angebots-ID fehlt.', 422);
    }

    $offerStmt = $pdo->prepare('SELECT id, customer_id FROM offers WHERE id = :id');
    $offerStmt->execute(['id' => $offerId]);
    $offer = $offerStmt->fetch();

    if (!$offer) {
        json_error('Angebot wurde nicht gefunden.', 404);
    }

    $existingStmt = $pdo->prepare('SELECT id FROM contracts WHERE offer_id = :offer_id');
    $existingStmt->execute(['offer_id' => $offerId]);
    $existing = $existingStmt->fetch();

    if (!$existing) {
        $year = gmdate('Y');
        $count = (int) $pdo->query('SELECT COUNT(*) FROM contracts')->fetchColumn();
        $number = sprintf('CT-%s-%03d', $year, $count + 1);

        $id = generate_id('contract');
        $stmt = $pdo->prepare(
            'INSERT INTO contracts (id, offer_id, customer_id, number, status, current_step, created_at)
             VALUES (:id, :offer_id, :customer_id, :number, :status, :current_step, UTC_TIMESTAMP())'
        );
        $stmt->execute([
            'id' => $id,
            'offer_id' => $offerId,
            'customer_id' => $offer['customer_id'],
            'number' => $number,
            'status' => 'entwurf',
            'current_step' => 'daten',
        ]);
        $contractId = $id;
    } else {
        $contractId = $existing['id'];
    }

    $stmt = $pdo->prepare(CONTRACT_SELECT . ' WHERE ct.id = :id');
    $stmt->execute(['id' => $contractId]);
    json_response(contract_row_to_json($stmt->fetch()), 201);
}

if ($method === 'DELETE') {
    $id = (string) ($_GET['id'] ?? '');
    if ($id === '') {
        json_error('Vertrags-ID fehlt.', 422);
    }

    $stmt = $pdo->prepare('DELETE FROM contracts WHERE id = :id');
    $stmt->execute(['id' => $id]);
    json_response(['ok' => true]);
}

json_error('Methode nicht erlaubt.', 405);
