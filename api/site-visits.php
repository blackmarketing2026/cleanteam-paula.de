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

function normalize_cleaning_type(string $cleaning): string
{
    if ($cleaning === 'Gesaugt') {
        return 'Nur gesaugt';
    }
    if ($cleaning === 'Gewischt') {
        return 'Nur gewischt';
    }

    $allowedCleaning = ['Gesaugt und gewischt', 'Nur gesaugt', 'Nur gewischt'];
    return in_array($cleaning, $allowedCleaning, true) ? $cleaning : 'Gesaugt und gewischt';
}

function normalize_floor_condition(string $condition): string
{
    $allowedConditions = ['Teppich', 'Fliesen', 'Laminat', 'Parkett', 'Anderer Boden'];
    return in_array($condition, $allowedConditions, true) ? $condition : 'Teppich';
}

function normalize_cleaning_frequency(string $frequency): string
{
    $allowedFrequencies = ['Täglich', 'Alle 2 Tage', 'Wöchentlich', '14-täglich', '30-täglich', 'Individuell'];
    return in_array($frequency, $allowedFrequencies, true) ? $frequency : 'Täglich';
}

function normalize_floor_cleaning_method(string $method): string
{
    if ($method === 'Nur gesaugt') {
        return 'Gesaugt';
    }
    if ($method === 'Nur gewischt') {
        return 'Gewischt';
    }

    $allowedMethods = ['Gesaugt', 'Gewischt', 'Gesaugt und gewischt'];
    return in_array($method, $allowedMethods, true) ? $method : 'Gesaugt und gewischt';
}

function normalize_trash_bag_mode(string $mode): string
{
    $allowedModes = ['Mit Mülltüte', 'Ohne Mülltüte'];
    return in_array($mode, $allowedModes, true) ? $mode : 'Mit Mülltüte';
}

function cleaning_task_label(string $key): string
{
    $labels = [
        'washbasin' => 'Waschbecken',
        'toilet' => 'WC',
        'mirror' => 'Spiegel',
        'floor' => 'Boden',
        'door' => 'Tür',
        'desk' => 'Schreibtische',
        'window' => 'Fensterbänke',
        'surface' => 'Oberflächen',
        'trash' => 'Mülleimer-Entleerung',
        'kitchen' => 'Küchenflächen',
        'handrail' => 'Handlauf / Geländer',
        'treatmentDesk' => 'Schreibtisch',
        'treatmentChair' => 'Behandlungsstühle',
        'treatmentTable' => 'Behandlungstisch',
        'disinfection' => 'Desinfektion',
    ];

    return $labels[$key] ?? $key;
}

function normalize_cleaning_item(array $item): ?array
{
    $key = trim((string) ($item['key'] ?? ($item['type'] ?? '')));
    if ($key === '') {
        return null;
    }

    $frequency = normalize_cleaning_frequency(trim((string) ($item['frequency'] ?? '')));
    $method = trim((string) ($item['method'] ?? ($item['cleaningMethod'] ?? '')));

    return [
        'key' => $key,
        'label' => cleaning_task_label($key),
        'frequency' => $frequency,
        'customFrequency' => $frequency === 'Individuell' ? trim((string) ($item['customFrequency'] ?? '')) : '',
        'method' => $key === 'floor' && $method !== '' ? normalize_floor_cleaning_method($method) : '',
        'bagMode' => $key === 'trash' ? normalize_trash_bag_mode(trim((string) ($item['bagMode'] ?? ($item['trashBagMode'] ?? '')))) : '',
    ];
}

function legacy_cleaning_items_from_room(array $room): array
{
    $items = [];
    if (visit_int($room['sinks'] ?? 0) > 0) {
        $items[] = ['key' => 'washbasin', 'label' => 'Waschbecken', 'frequency' => 'Täglich', 'customFrequency' => ''];
    }
    if (visit_int($room['toilets'] ?? 0) > 0) {
        $items[] = ['key' => 'toilet', 'label' => 'WC', 'frequency' => 'Täglich', 'customFrequency' => ''];
    }
    if (visit_int($room['mirrors'] ?? 0) > 0) {
        $items[] = ['key' => 'mirror', 'label' => 'Spiegel', 'frequency' => 'Täglich', 'customFrequency' => ''];
    }
    if (visit_int($room['desks'] ?? 0) > 0) {
        $items[] = ['key' => 'desk', 'label' => 'Schreibtische', 'frequency' => 'Wöchentlich', 'customFrequency' => ''];
    }
    if (visit_int($room['windows'] ?? 0) > 0) {
        $items[] = ['key' => 'window', 'label' => 'Fensterbänke', 'frequency' => '30-täglich', 'customFrequency' => ''];
    }
    if (trim((string) ($room['cleaningType'] ?? '')) !== '') {
        $items[] = ['key' => 'floor', 'label' => 'Boden', 'frequency' => 'Täglich', 'customFrequency' => '', 'method' => normalize_floor_cleaning_method(trim((string) ($room['cleaningType'] ?? '')))];
    }

    return $items;
}

function cleaning_items_from_room(array $room): array
{
    $items = [];
    $rawItems = $room['cleaningItems'] ?? [];
    if (is_array($rawItems)) {
        foreach ($rawItems as $item) {
            if (is_array($item)) {
                $normalizedItem = normalize_cleaning_item($item);
                if ($normalizedItem !== null) {
                    $items[] = $normalizedItem;
                }
            }
        }
    }

    return $items !== [] ? $items : legacy_cleaning_items_from_room($room);
}

function normalize_visit_room(array $room, int $index): array
{
    $roomType = trim((string) ($room['roomType'] ?? ''));
    $allowedTypes = ['Büro', 'Behandlungsräume', 'Sanitär', 'Küche', 'Flur', 'Treppenhaus', 'Empfang', 'Lager', 'Sonstiger Raum'];
    $notes = trim((string) ($room['notes'] ?? ($room['areaNotes'] ?? '')));

    return [
        'name' => trim((string) ($room['name'] ?? '')) ?: 'Raum ' . ($index + 1),
        'roomType' => in_array($roomType, $allowedTypes, true) ? $roomType : 'Büro',
        'quantity' => max(1, visit_int($room['quantity'] ?? 1)),
        'squareMeters' => visit_int($room['squareMeters'] ?? 0),
        'cleaningItems' => cleaning_items_from_room($room),
        'sinks' => visit_int($room['sinks'] ?? 0),
        'mirrors' => visit_int($room['mirrors'] ?? 0),
        'toilets' => visit_int($room['toilets'] ?? 0),
        'desks' => visit_int($room['desks'] ?? 0),
        'windows' => visit_int($room['windows'] ?? 0),
        'cleaningType' => normalize_cleaning_type(trim((string) ($room['cleaningType'] ?? ''))),
        'floorCondition' => normalize_floor_condition(trim((string) ($room['floorCondition'] ?? ''))),
        'extraAgreements' => trim((string) ($room['extraAgreements'] ?? '')),
        'notes' => $notes,
    ];
}

function legacy_rooms_from_floor(array $floor): array
{
    $rooms = [];
    $areaName = trim((string) ($floor['areaName'] ?? ''));
    $areaNotes = trim((string) ($floor['areaNotes'] ?? ($floor['notes'] ?? '')));
    $extraAgreements = trim((string) ($floor['extraAgreements'] ?? ''));
    $cleaningType = normalize_cleaning_type(trim((string) ($floor['cleaningType'] ?? '')));
    $floorCondition = normalize_floor_condition(trim((string) ($floor['floorCondition'] ?? '')));
    $sanitaryRooms = visit_int($floor['sanitaryRooms'] ?? 0);
    $officeRooms = visit_int($floor['officeRooms'] ?? 0);

    if ($sanitaryRooms > 0) {
        $rooms[] = [
            'name' => $areaName !== '' && $officeRooms === 0 ? $areaName : 'Sanitärbereich',
            'roomType' => 'Sanitär',
            'quantity' => $sanitaryRooms,
            'squareMeters' => 0,
            'sinks' => visit_int($floor['sinks'] ?? 0),
            'mirrors' => visit_int($floor['mirrors'] ?? 0),
            'toilets' => visit_int($floor['toilets'] ?? 0),
            'desks' => 0,
            'windows' => 0,
            'cleaningType' => $cleaningType,
            'floorCondition' => $floorCondition,
            'extraAgreements' => $officeRooms === 0 ? $extraAgreements : '',
            'notes' => $officeRooms === 0 ? $areaNotes : '',
        ];
    }

    if ($officeRooms > 0) {
        $rooms[] = [
            'name' => $areaName !== '' && $sanitaryRooms === 0 ? $areaName : 'Bürobereich',
            'roomType' => 'Büro',
            'quantity' => $officeRooms,
            'squareMeters' => 0,
            'sinks' => 0,
            'mirrors' => 0,
            'toilets' => 0,
            'desks' => visit_int($floor['desks'] ?? 0),
            'windows' => visit_int($floor['windows'] ?? 0),
            'cleaningType' => $cleaningType,
            'floorCondition' => $floorCondition,
            'extraAgreements' => $extraAgreements,
            'notes' => $areaNotes,
        ];
    }

    if ($rooms === [] && ($areaName !== '' || $areaNotes !== '' || $extraAgreements !== '')) {
        $rooms[] = [
            'name' => $areaName !== '' ? $areaName : 'Bereich',
            'roomType' => 'Sonstiger Raum',
            'quantity' => 1,
            'squareMeters' => 0,
            'sinks' => 0,
            'mirrors' => 0,
            'toilets' => 0,
            'desks' => 0,
            'windows' => 0,
            'cleaningType' => $cleaningType,
            'floorCondition' => $floorCondition,
            'extraAgreements' => $extraAgreements,
            'notes' => $areaNotes,
        ];
    }

    return $rooms;
}

function normalize_visit_floor(array $floor, int $index): array
{
    $areaNotes = trim((string) ($floor['areaNotes'] ?? ($floor['notes'] ?? '')));
    $rooms = [];
    $rawRooms = $floor['rooms'] ?? [];
    if (is_array($rawRooms) && count($rawRooms) > 0) {
        foreach ($rawRooms as $roomIndex => $room) {
            if (is_array($room)) {
                $rooms[] = normalize_visit_room($room, (int) $roomIndex);
            }
        }
    }

    if ($rooms === []) {
        $rooms = legacy_rooms_from_floor($floor);
    }

    $sanitaryRooms = 0;
    $officeRooms = 0;
    $sinks = 0;
    $mirrors = 0;
    $toilets = 0;
    $desks = 0;
    $windows = 0;
    foreach ($rooms as $room) {
        $quantity = max(1, visit_int($room['quantity'] ?? 1));
        if (($room['roomType'] ?? '') === 'Sanitär') {
            $sanitaryRooms += $quantity;
        }
        if (($room['roomType'] ?? '') === 'Büro') {
            $officeRooms += $quantity;
        }
        $sinks += visit_int($room['sinks'] ?? 0);
        $mirrors += visit_int($room['mirrors'] ?? 0);
        $toilets += visit_int($room['toilets'] ?? 0);
        $desks += visit_int($room['desks'] ?? 0);
        $windows += visit_int($room['windows'] ?? 0);
    }

    return [
        'name' => trim((string) ($floor['name'] ?? '')) ?: 'Etage ' . ($index + 1),
        'rooms' => $rooms,
        'sanitaryRooms' => $sanitaryRooms,
        'sinks' => $sinks,
        'mirrors' => $mirrors,
        'toilets' => $toilets,
        'officeRooms' => $officeRooms,
        'desks' => $desks,
        'windows' => $windows,
        'cleaningType' => normalize_cleaning_type(trim((string) ($floor['cleaningType'] ?? ''))),
        'floorCondition' => normalize_floor_condition(trim((string) ($floor['floorCondition'] ?? ''))),
        'areaName' => trim((string) ($floor['areaName'] ?? '')),
        'extraAgreements' => trim((string) ($floor['extraAgreements'] ?? '')),
        'areaNotes' => $areaNotes,
        'notes' => $areaNotes,
    ];
}

function site_visit_to_json(array $row): array
{
    $floors = json_decode($row['floors_json'] ?? '[]', true);
    if (!is_array($floors)) {
        $floors = [];
    }
    $normalizedFloors = [];
    foreach ($floors as $index => $floor) {
        if (is_array($floor)) {
            $normalizedFloors[] = normalize_visit_floor($floor, (int) $index);
        }
    }

    return [
        'id' => $row['id'],
        'companyName' => $row['customer_name'],
        'customerName' => $row['customer_name'],
        'email' => $row['email'],
        'phone' => $row['phone'],
        'address' => $row['address'],
        'onsiteContact' => $row['onsite_contact'],
        'squareMeters' => (int) $row['square_meters'],
        'floors' => $normalizedFloors,
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
    $companyName = trim((string) ($body['companyName'] ?? ($body['customerName'] ?? '')));
    if ($companyName === '') {
        json_error('Bitte den Firmennamen eintragen.', 422);
    }

    $required = ['email', 'phone', 'address', 'onsiteContact'];

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
        json_error('Bitte mindestens eine Etage hinzufügen und ausfüllen.', 422);
    }

    $normalizedFloors = [];
    foreach ($floors as $index => $floor) {
        if (!is_array($floor)) {
            continue;
        }
        $normalizedFloor = normalize_visit_floor($floor, (int) $index);
        if (count($normalizedFloor['rooms']) === 0) {
            json_error('Bitte pro Etage mindestens einen Raum hinzufügen.', 422);
        }
        foreach ($normalizedFloor['rooms'] as $room) {
            if (!isset($room['cleaningItems']) || !is_array($room['cleaningItems']) || count($room['cleaningItems']) === 0) {
                json_error('Bitte pro Raum mindestens einen Reinigungspunkt auswählen.', 422);
            }
            foreach ($room['cleaningItems'] as $cleaningItem) {
                if (($cleaningItem['frequency'] ?? '') === 'Individuell' && trim((string) ($cleaningItem['customFrequency'] ?? '')) === '') {
                    json_error('Bitte bei individuellem Rhythmus eine Angabe eintragen.', 422);
                }
            }
        }
        $normalizedFloors[] = $normalizedFloor;
    }

    if (count($normalizedFloors) === 0) {
        json_error('Bitte mindestens eine Etage hinzufügen und ausfüllen.', 422);
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
        'customer_name' => $companyName,
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
