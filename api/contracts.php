<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

function ensure_contracts_terms_accepted_at_column(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM contracts LIKE 'terms_accepted_at'");
    if ($stmt->fetch()) {
        return;
    }

    $pdo->exec('ALTER TABLE contracts ADD COLUMN terms_accepted_at DATETIME NULL AFTER representation_note');
}

function ensure_contracts_authorization_columns(PDO $pdo): void
{
    $columns = [
        'authorization_grantor_name' => 'ALTER TABLE contracts ADD COLUMN authorization_grantor_name VARCHAR(190) NULL AFTER representation_note',
        'authorization_company_address' => 'ALTER TABLE contracts ADD COLUMN authorization_company_address VARCHAR(255) NULL AFTER authorization_grantor_name',
    ];

    foreach ($columns as $column => $sql) {
        $stmt = $pdo->query("SHOW COLUMNS FROM contracts LIKE '{$column}'");
        if (!$stmt->fetch()) {
            $pdo->exec($sql);
        }
    }
}

function ensure_offers_site_visit_id_column(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM offers LIKE 'site_visit_id'");
    if ($stmt->fetch()) {
        return;
    }

    $pdo->exec('ALTER TABLE offers ADD COLUMN site_visit_id VARCHAR(64) NULL AFTER customer_id');
}

const CONTRACT_SELECT = 'SELECT ct.*, o.square_meters, o.interval_label, o.service, o.start_date, o.notes AS offer_notes,
    o.price, o.created_at AS offer_created_at, o.token, o.site_visit_id,
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
        'authorizationGrantorName' => $row['authorization_grantor_name'] ?? null,
        'authorizationCompanyAddress' => $row['authorization_company_address'] ?? null,
        'hasAuthorizationDocument' => !empty($row['authorization_grantor_name'])
            && !empty($row['authorization_company_address'])
            && isset($row['authorized'])
            && (int) $row['authorized'] === 0,
        'termsAcceptedAt' => to_iso($row['terms_accepted_at'] ?? null),
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
            'siteVisitId' => $row['site_visit_id'] ?? null,
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

ensure_contracts_terms_accepted_at_column($pdo);
ensure_contracts_authorization_columns($pdo);
ensure_offers_site_visit_id_column($pdo);

if ($method === 'GET') {
    $rows = $pdo->query(CONTRACT_SELECT . ' ORDER BY c.name ASC, ct.created_at DESC')->fetchAll();
    json_response(array_map('contract_row_to_json', $rows));
}

if ($method === 'POST') {
    json_error('Verträge werden nur über den Kostenvoranschlags-Link erstellt.', 405);
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
