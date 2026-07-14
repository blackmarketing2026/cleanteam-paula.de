<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

function ensure_customers_deleted_at_column(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM customers LIKE 'deleted_at'");
    if ($stmt->fetch()) {
        return;
    }

    $pdo->exec('ALTER TABLE customers ADD COLUMN deleted_at DATETIME NULL AFTER created_at');
}

function customer_to_json(array $row): array
{
    return [
        'id' => $row['id'],
        'name' => $row['name'],
        'email' => $row['email'],
        'phone' => $row['phone'],
        'salutation' => $row['salutation'],
        'contactLastName' => $row['contact_last_name'],
        'address' => $row['address'],
        'houseNumber' => $row['house_number'],
        'zip' => $row['zip'],
        'city' => $row['city'],
        'createdAt' => to_iso($row['created_at']),
    ];
}

ensure_customers_deleted_at_column($pdo);

if ($method === 'GET') {
    $rows = $pdo->query('SELECT * FROM customers WHERE deleted_at IS NULL ORDER BY name ASC')->fetchAll();
    json_response(array_map('customer_to_json', $rows));
}

if ($method === 'POST') {
    $body = read_json_body();
    $required = ['name', 'email', 'phone', 'salutation', 'contactLastName', 'address', 'houseNumber', 'zip', 'city'];
    foreach ($required as $field) {
        if (trim((string) ($body[$field] ?? '')) === '') {
            json_error("Feld \"{$field}\" ist erforderlich.", 422);
        }
    }

    $id = generate_id('customer');
    $stmt = $pdo->prepare(
        'INSERT INTO customers (id, name, email, phone, salutation, contact_last_name, address, house_number, zip, city, created_at)
         VALUES (:id, :name, :email, :phone, :salutation, :contact_last_name, :address, :house_number, :zip, :city, UTC_TIMESTAMP())'
    );
    $stmt->execute([
        'id' => $id,
        'name' => trim($body['name']),
        'email' => trim($body['email']),
        'phone' => trim($body['phone']),
        'salutation' => trim($body['salutation']),
        'contact_last_name' => trim($body['contactLastName']),
        'address' => trim($body['address']),
        'house_number' => trim($body['houseNumber']),
        'zip' => trim($body['zip']),
        'city' => trim($body['city']),
    ]);

    $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = :id');
    $stmt->execute(['id' => $id]);
    json_response(customer_to_json($stmt->fetch()), 201);
}

if ($method === 'PUT') {
    $id = (string) ($_GET['id'] ?? '');
    if ($id === '') {
        json_error('Kunden-ID fehlt.', 422);
    }

    $exists = $pdo->prepare('SELECT id FROM customers WHERE id = :id AND deleted_at IS NULL');
    $exists->execute(['id' => $id]);
    if (!$exists->fetch()) {
        json_error('Kunde wurde nicht gefunden.', 404);
    }

    $body = read_json_body();
    $required = ['name', 'email', 'phone', 'salutation', 'contactLastName', 'address', 'houseNumber', 'zip', 'city'];
    foreach ($required as $field) {
        if (trim((string) ($body[$field] ?? '')) === '') {
            json_error("Feld \"{$field}\" ist erforderlich.", 422);
        }
    }

    $stmt = $pdo->prepare(
        'UPDATE customers SET name = :name, email = :email, phone = :phone, salutation = :salutation,
         contact_last_name = :contact_last_name, address = :address, house_number = :house_number,
         zip = :zip, city = :city WHERE id = :id'
    );
    $stmt->execute([
        'id' => $id,
        'name' => trim($body['name']),
        'email' => trim($body['email']),
        'phone' => trim($body['phone']),
        'salutation' => trim($body['salutation']),
        'contact_last_name' => trim($body['contactLastName']),
        'address' => trim($body['address']),
        'house_number' => trim($body['houseNumber']),
        'zip' => trim($body['zip']),
        'city' => trim($body['city']),
    ]);

    $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = :id AND deleted_at IS NULL');
    $stmt->execute(['id' => $id]);
    json_response(customer_to_json($stmt->fetch()));
}

if ($method === 'DELETE') {
    $id = (string) ($_GET['id'] ?? '');
    if ($id === '') {
        json_error('Kunden-ID fehlt.', 422);
    }

    $stmt = $pdo->prepare('UPDATE customers SET deleted_at = UTC_TIMESTAMP() WHERE id = :id AND deleted_at IS NULL');
    $stmt->execute(['id' => $id]);

    if ($stmt->rowCount() === 0) {
        json_error('Kunde wurde nicht gefunden oder ist bereits gelöscht.', 404);
    }

    json_response(['ok' => true]);
}

json_error('Methode nicht erlaubt.', 405);
