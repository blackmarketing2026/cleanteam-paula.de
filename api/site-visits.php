<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

function ensure_site_visits_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS site_visits (
            id VARCHAR(64) NOT NULL,
            customer_name VARCHAR(190) NOT NULL,
            email VARCHAR(190) NOT NULL,
            phone VARCHAR(60) NOT NULL,
            address VARCHAR(255) NOT NULL,
            onsite_contact VARCHAR(190) NOT NULL,
            square_meters INT UNSIGNED NOT NULL,
            floors_json LONGTEXT NOT NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function visit_int($value): int
{
    return max(0, (int) $value);
}

function normalize_visit_floor(array $floor, int $index): array
{
    $cleaning = trim((string) ($floor['cleaningType'] ?? ''));
    $condition = trim((string) ($floor['floorCondition'] ?? ''));

    $allowedCleaning = ['Gesaugt', 'Gewischt', 'Gesaugt und gewischt'];
    $allowedConditions = ['Teppich', 'Fliesen', 'Laminat', 'Parkett', 'Anderer Boden'];

    return [
        'name' => trim((string) ($floor['name'] ?? '')) ?: 'Etage ' . ($index + 1),
        'sanitaryRooms' => visit_int($floor['sanitaryRooms'] ?? 0),
        'sinks' => visit_int($floor['sinks'] ?? 0),
        'mirrors' => visit_int($floor['mirrors'] ?? 0),
        'toilets' => visit_int($floor['toilets'] ?? 0),
        'officeRooms' => visit_int($floor['officeRooms'] ?? 0),
        'desks' => visit_int($floor['desks'] ?? 0),
        'windows' => visit_int($floor['windows'] ?? 0),
        'cleaningType' => in_array($cleaning, $allowedCleaning, true) ? $cleaning : 'Gesaugt',
        'floorCondition' => in_array($condition, $allowedConditions, true) ? $condition : 'Teppich',
        'notes' => trim((string) ($floor['notes'] ?? '')),
    ];
}

function site_visit_to_json(array $row): array
{
    $floors = json_decode($row['floors_json'] ?? '[]', true);
    if (!is_array($floors)) {
        $floors = [];
    }

    return [
        'id' => $row['id'],
        'customerName' => $row['customer_name'],
        'email' => $row['email'],
        'phone' => $row['phone'],
        'address' => $row['address'],
        'onsiteContact' => $row['onsite_contact'],
        'squareMeters' => (int) $row['square_meters'],
        'floors' => $floors,
        'notes' => $row['notes'],
        'createdAt' => to_iso($row['created_at']),
    ];
}

ensure_site_visits_table($pdo);

if ($method === 'GET') {
    $rows = $pdo->query('SELECT * FROM site_visits ORDER BY created_at DESC')->fetchAll();
    json_response(array_map('site_visit_to_json', $rows));
}

if ($method === 'POST') {
    $body = read_json_body();
    $required = ['customerName', 'email', 'phone', 'address', 'onsiteContact'];

    foreach ($required as $field) {
        if (trim((string) ($body[$field] ?? '')) === '') {
            json_error("Feld \"{$field}\" ist erforderlich.", 422);
        }
    }

    $squareMeters = visit_int($body['squareMeters'] ?? 0);
    $floors = $body['floors'] ?? [];

    if ($squareMeters <= 0) {
        json_error('Die Objektgröße in Quadratmetern ist erforderlich.', 422);
    }

    if (!is_array($floors) || count($floors) === 0) {
        json_error('Bitte mindestens eine Etage öffnen und ausfüllen.', 422);
    }

    $normalizedFloors = [];
    foreach ($floors as $index => $floor) {
        if (!is_array($floor)) {
            continue;
        }
        $normalizedFloors[] = normalize_visit_floor($floor, (int) $index);
    }

    if (count($normalizedFloors) === 0) {
        json_error('Bitte mindestens eine Etage öffnen und ausfüllen.', 422);
    }

    $id = generate_id('visit');
    $floorsJson = json_encode($normalizedFloors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($floorsJson === false) {
        json_error('Etagen konnten nicht gespeichert werden.', 422);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO site_visits (id, customer_name, email, phone, address, onsite_contact, square_meters, floors_json, notes, created_at)
         VALUES (:id, :customer_name, :email, :phone, :address, :onsite_contact, :square_meters, :floors_json, :notes, UTC_TIMESTAMP())'
    );
    $stmt->execute([
        'id' => $id,
        'customer_name' => trim((string) $body['customerName']),
        'email' => trim((string) $body['email']),
        'phone' => trim((string) $body['phone']),
        'address' => trim((string) $body['address']),
        'onsite_contact' => trim((string) $body['onsiteContact']),
        'square_meters' => $squareMeters,
        'floors_json' => $floorsJson,
        'notes' => trim((string) ($body['notes'] ?? '')),
    ]);

    $stmt = $pdo->prepare('SELECT * FROM site_visits WHERE id = :id');
    $stmt->execute(['id' => $id]);
    json_response(site_visit_to_json($stmt->fetch()), 201);
}

if ($method === 'DELETE') {
    $id = (string) ($_GET['id'] ?? '');
    if ($id === '') {
        json_error('Begehungs-ID fehlt.', 422);
    }

    $stmt = $pdo->prepare('DELETE FROM site_visits WHERE id = :id');
    $stmt->execute(['id' => $id]);
    json_response(['ok' => true]);
}

json_error('Methode nicht erlaubt.', 405);
