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

function ensure_contracts_privacy_accepted_at_column(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM contracts LIKE 'privacy_accepted_at'");
    if ($stmt->fetch()) {
        return;
    }

    $pdo->exec('ALTER TABLE contracts ADD COLUMN privacy_accepted_at DATETIME NULL AFTER terms_accepted_at');
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

const CONTRACT_SELECT = 'SELECT ct.*, o.square_meters, o.interval_label, o.service, o.start_date, o.notes AS offer_notes,
    o.price, o.vat_applicable, o.created_at AS offer_created_at, o.token,
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
            'squareMeters' => (int) $row['square_meters'],
            'interval' => $row['interval_label'],
            'service' => $row['service'],
            'startDate' => $row['start_date'],
            'notes' => $row['offer_notes'],
            'price' => (float) $row['price'],
            'vatApplicable' => !isset($row['vat_applicable']) || (int) $row['vat_applicable'] === 1,
            'createdAt' => to_iso($row['offer_created_at']),
        ],
    ];
}

ensure_contracts_terms_accepted_at_column($pdo);
ensure_contracts_privacy_accepted_at_column($pdo);
ensure_contracts_authorization_columns($pdo);
ensure_offers_interval_label_length($pdo);
ensure_offers_vat_column($pdo);

function contract_documents_table_exists(PDO $pdo): bool
{
    $stmt = $pdo->query("SHOW TABLES LIKE 'contract_documents'");
    return (bool) $stmt->fetch();
}

if ($method === 'GET') {
    $rows = $pdo->query(CONTRACT_SELECT . ' ORDER BY c.name ASC, ct.created_at DESC')->fetchAll();
    json_response(array_map('contract_row_to_json', $rows));
}

if ($method === 'POST') {
    json_error('Verträge werden nur über den Vertragslink erstellt.', 405);
}

if ($method === 'PATCH') {
    $id = (string) ($_GET['id'] ?? '');
    if ($id === '') {
        json_error('Vertrags-ID fehlt.', 422);
    }

    $stmt = $pdo->prepare(CONTRACT_SELECT . ' WHERE ct.id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        json_error('Vertrag wurde nicht gefunden.', 404);
    }

    $body = read_json_body();
    $action = (string) ($body['action'] ?? '');

    if ($action === 'release') {
        $restartStepByStatus = [
            'daten_abgelehnt' => 'daten',
            'intervall_abgelehnt' => 'daten',
            'datenschutz_abgelehnt' => 'datenschutz',
        ];
        $status = (string) $row['status'];
        if (!isset($restartStepByStatus[$status])) {
            json_error('Dieser Vertrag hat keine offene Rückfrage.', 422);
        }

        $pdo->prepare("UPDATE contracts SET status = 'entwurf', current_step = :step WHERE id = :id")
            ->execute(['step' => $restartStepByStatus[$status], 'id' => $id]);
    } elseif ($action === 'update-contact') {
        $customerName = trim((string) ($body['customerName'] ?? ''));
        $contactPerson = trim((string) ($body['contactPerson'] ?? ''));
        $email = trim((string) ($body['email'] ?? ''));
        $address = trim((string) ($body['address'] ?? ''));
        $zip = trim((string) ($body['zip'] ?? ''));
        $city = trim((string) ($body['city'] ?? ''));
        $squareMeters = (int) ($body['squareMeters'] ?? 0);
        $interval = trim((string) ($body['interval'] ?? ''));
        $price = round((float) ($body['price'] ?? 0), 2);
        $vatApplicable = (bool) ($body['vatApplicable'] ?? true);
        $startDate = trim((string) ($body['startDate'] ?? ''));
        $serviceText = trim((string) ($body['serviceText'] ?? ''));

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
            'id' => $row['customer_id'],
        ]);

        $pdo->prepare(
            'UPDATE offers SET square_meters = :square_meters, interval_label = :interval_label, start_date = :start_date, price = :price, base_price = :price, vat_applicable = :vat_applicable, notes = :notes WHERE id = :id'
        )->execute([
                'square_meters' => $squareMeters,
                'interval_label' => $intervalLabel,
                'start_date' => $startDate,
                'price' => $price,
                'vat_applicable' => $vatApplicable ? 1 : 0,
                'notes' => format_service_text($serviceText),
                'id' => $row['offer_id'],
            ]);
    } else {
        json_error('Unbekannte Aktion.', 422);
    }

    $stmt = $pdo->prepare(CONTRACT_SELECT . ' WHERE ct.id = :id');
    $stmt->execute(['id' => $id]);
    json_response(contract_row_to_json($stmt->fetch()));
}

if ($method === 'DELETE') {
    $id = (string) ($_GET['id'] ?? '');
    if ($id === '') {
        json_error('Vertrags-ID fehlt.', 422);
    }

    $stmt = $pdo->prepare('SELECT id FROM contracts WHERE id = :id');
    $stmt->execute(['id' => $id]);
    if (!$stmt->fetch()) {
        json_error('Vertrag wurde nicht gefunden.', 404);
    }

    $pdo->beginTransaction();
    try {
        if (contract_documents_table_exists($pdo)) {
            $stmt = $pdo->prepare('DELETE FROM contract_documents WHERE contract_id = :id');
            $stmt->execute(['id' => $id]);
        }

        $stmt = $pdo->prepare('DELETE FROM contracts WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    json_response(['ok' => true]);
}

json_error('Methode nicht erlaubt.', 405);
