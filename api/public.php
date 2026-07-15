<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/contract_notify.php';
require_once __DIR__ . '/../includes/contract_pdf.php';

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = (string) ($_GET['action'] ?? '');
$token = trim((string) ($_GET['token'] ?? ''));

if ($token === '') {
    json_error('Kein Kostenvoranschlags-Link angegeben.', 404);
}

const STEP_ORDER = ['daten', 'intervall', 'vollmacht', 'vertragspartner', 'leistung', 'bedingungen', 'signatur', 'fertig'];
const TERMINAL_STATUSES = ['daten_abgelehnt', 'intervall_abgelehnt'];

function load_offer(PDO $pdo, string $token): array
{
    $stmt = $pdo->prepare(
        'SELECT o.*, c.name AS c_name, c.email AS c_email, c.phone AS c_phone, c.salutation AS c_salutation,
            c.contact_last_name AS c_contact_last_name, c.address AS c_address, c.house_number AS c_house_number,
            c.zip AS c_zip, c.city AS c_city, sv.address AS sv_address
         FROM offers o
         INNER JOIN customers c ON c.id = o.customer_id
         LEFT JOIN site_visits sv ON sv.id = o.site_visit_id
         WHERE o.token = :token'
    );
    $stmt->execute(['token' => $token]);
    $offer = $stmt->fetch();

    if (!$offer) {
        json_error('Dieser Kostenvoranschlags-Link ist ungültig.', 404);
    }

    return $offer;
}

function load_contract(PDO $pdo, string $offerId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM contracts WHERE offer_id = :offer_id');
    $stmt->execute(['offer_id' => $offerId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

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

function authorization_address_options(array $offer): array
{
    $options = [];
    $seen = [];
    $customerAddress = trim((string) ($offer['c_address'] ?? '') . ' ' . (string) ($offer['c_house_number'] ?? ''));
    $customerZipCity = trim((string) ($offer['c_zip'] ?? '') . ' ' . (string) ($offer['c_city'] ?? ''));
    $customerFullAddress = trim($customerAddress . ($customerZipCity !== '' ? ', ' . $customerZipCity : ''));
    $siteVisitAddress = trim((string) ($offer['sv_address'] ?? ''));

    foreach ([
        ['label' => 'Firmenadresse', 'value' => $customerFullAddress],
        ['label' => 'Objektadresse', 'value' => $siteVisitAddress],
    ] as $option) {
        if ($option['value'] === '' || isset($seen[$option['value']])) {
            continue;
        }

        $seen[$option['value']] = true;
        $options[] = $option;
    }

    return $options;
}

function authorization_note(string $grantorName, string $companyAddress): string
{
    return 'Vollmacht durch ' . $grantorName . ', Firmenadresse: ' . $companyAddress;
}

function offer_is_expired(array $offer): bool
{
    return strtotime($offer['expires_at'] . ' UTC') < time();
}

function public_state(array $offer, ?array $contract): array
{
    return [
        'offer' => [
            'squareMeters' => (int) $offer['square_meters'],
            'interval' => $offer['interval_label'],
            'service' => $offer['service'],
            'startDate' => $offer['start_date'],
            'notes' => $offer['notes'],
            'basePrice' => isset($offer['base_price']) && (float) $offer['base_price'] > 0
                ? (float) $offer['base_price']
                : (float) $offer['price'],
            'priceAdjustment' => (float) ($offer['price_adjustment'] ?? 0),
            'priceAdjustmentNote' => $offer['price_adjustment_note'] ?? null,
            'price' => (float) $offer['price'],
            'expiresAt' => to_iso($offer['expires_at']),
            'expired' => offer_is_expired($offer),
            'customer' => [
                'name' => $offer['c_name'],
                'email' => $offer['c_email'],
                'phone' => $offer['c_phone'],
                'salutation' => $offer['c_salutation'],
                'contactLastName' => $offer['c_contact_last_name'],
                'address' => $offer['c_address'],
                'houseNumber' => $offer['c_house_number'],
                'zip' => $offer['c_zip'],
                'city' => $offer['c_city'],
            ],
            'authorizationAddressOptions' => authorization_address_options($offer),
        ],
        'contract' => $contract === null ? null : [
            'status' => $contract['status'],
            'currentStep' => $contract['current_step'],
            'dataConfirmed' => (bool) $contract['data_confirmed'],
            'intervalConfirmed' => (bool) $contract['interval_confirmed'],
            'authorized' => $contract['authorized'] === null ? null : (bool) $contract['authorized'],
            'representationNote' => $contract['representation_note'],
            'authorizationGrantorName' => $contract['authorization_grantor_name'] ?? null,
            'authorizationCompanyAddress' => $contract['authorization_company_address'] ?? null,
            'hasAuthorizationDocument' => !empty($contract['authorization_grantor_name'])
                && !empty($contract['authorization_company_address'])
                && isset($contract['authorized'])
                && (int) $contract['authorized'] === 0,
            'number' => $contract['number'],
            'signedAt' => to_iso($contract['signed_at']),
            'signatureDataUrl' => $contract['signature_data'],
        ],
    ];
}

function next_contract_number(PDO $pdo): string
{
    $year = gmdate('Y');
    $count = (int) $pdo->query('SELECT COUNT(*) FROM contracts')->fetchColumn();
    return sprintf('CT-%s-%03d', $year, $count + 1);
}

function require_active_contract(?array $contract): array
{
    if ($contract === null) {
        json_error('Der Vertrag wurde noch nicht gestartet.', 409);
    }

    if (in_array($contract['status'], TERMINAL_STATUSES, true)) {
        json_error('Für diesen Vertrag wurde eine Rückfrage vermerkt. Bitte kontaktieren Sie CleanTeam.', 409);
    }

    if ($contract['status'] === 'signiert') {
        json_error('Dieser Vertrag wurde bereits unterschrieben.', 409);
    }

    return $contract;
}

ensure_offers_site_visit_id_column($pdo);
$offer = load_offer($pdo, $token);
ensure_contracts_terms_accepted_at_column($pdo);
ensure_contracts_authorization_columns($pdo);

if ($method === 'GET' && $action === 'offer') {
    $contract = load_contract($pdo, $offer['id']);
    json_response(public_state($offer, $contract));
}

if (offer_is_expired($offer) && $method === 'POST') {
    json_error('Dieser Kostenvoranschlag ist abgelaufen. Bitte kontaktieren Sie CleanTeam für einen neuen Kostenvoranschlag.', 410);
}

if ($method === 'POST' && $action === 'start') {
    $contract = load_contract($pdo, $offer['id']);

    if ($contract === null) {
        $id = generate_id('contract');
        $stmt = $pdo->prepare(
            'INSERT INTO contracts (id, offer_id, customer_id, number, status, current_step, created_at)
             VALUES (:id, :offer_id, :customer_id, :number, :status, :current_step, UTC_TIMESTAMP())'
        );
        $stmt->execute([
            'id' => $id,
            'offer_id' => $offer['id'],
            'customer_id' => $offer['customer_id'],
            'number' => next_contract_number($pdo),
            'status' => 'entwurf',
            'current_step' => 'daten',
        ]);
        notify_contract_created($pdo, $id);
        $contract = load_contract($pdo, $offer['id']);
    }

    json_response(public_state($offer, $contract));
}

if ($method === 'POST' && $action === 'confirm-data') {
    $contract = require_active_contract(load_contract($pdo, $offer['id']));
    $body = read_json_body();
    $confirmed = (bool) ($body['confirmed'] ?? false);

    if ($confirmed) {
        $pdo->prepare("UPDATE contracts SET data_confirmed = 1, current_step = 'intervall' WHERE id = :id")
            ->execute(['id' => $contract['id']]);
    } else {
        $pdo->prepare("UPDATE contracts SET status = 'daten_abgelehnt' WHERE id = :id")
            ->execute(['id' => $contract['id']]);
    }

    json_response(public_state($offer, load_contract($pdo, $offer['id'])));
}

if ($method === 'POST' && $action === 'confirm-interval') {
    $contract = require_active_contract(load_contract($pdo, $offer['id']));
    $body = read_json_body();
    $confirmed = (bool) ($body['confirmed'] ?? false);

    if ($confirmed) {
        $pdo->prepare("UPDATE contracts SET interval_confirmed = 1, current_step = 'vollmacht' WHERE id = :id")
            ->execute(['id' => $contract['id']]);
    } else {
        $pdo->prepare("UPDATE contracts SET status = 'intervall_abgelehnt' WHERE id = :id")
            ->execute(['id' => $contract['id']]);
    }

    json_response(public_state($offer, load_contract($pdo, $offer['id'])));
}

if ($method === 'POST' && $action === 'authorization') {
    $contract = require_active_contract(load_contract($pdo, $offer['id']));
    $body = read_json_body();
    $authorized = (bool) ($body['authorized'] ?? false);
    $grantorName = trim((string) ($body['authorizationGrantorName'] ?? ''));
    $companyAddress = trim((string) ($body['authorizationCompanyAddress'] ?? ''));

    if (!$authorized) {
        if ($grantorName === '') {
            json_error('Bitte geben Sie den Ansprechpartner an, der die Vollmacht erteilt.', 422);
        }

        if ($companyAddress === '') {
            json_error('Bitte wählen Sie die Firmenadresse aus.', 422);
        }

        $allowedAddresses = [];
        foreach (authorization_address_options($offer) as $option) {
            $allowedAddresses[] = (string) $option['value'];
        }
        if ($allowedAddresses !== [] && !in_array($companyAddress, $allowedAddresses, true)) {
            json_error('Bitte wählen Sie eine gültige Firmenadresse aus.', 422);
        }
    }

    $stmt = $pdo->prepare(
        "UPDATE contracts SET authorized = :authorized, representation_note = :note,
            authorization_grantor_name = :grantor_name, authorization_company_address = :company_address,
            current_step = 'vertragspartner' WHERE id = :id"
    );
    $stmt->execute([
        'authorized' => $authorized ? 1 : 0,
        'note' => $authorized ? null : authorization_note($grantorName, $companyAddress),
        'grantor_name' => $authorized ? null : $grantorName,
        'company_address' => $authorized ? null : $companyAddress,
        'id' => $contract['id'],
    ]);

    if (!$authorized) {
        save_contract_pdf($pdo, $contract['id'], 'authorization', true);
    }

    json_response(public_state($offer, load_contract($pdo, $offer['id'])));
}

if ($method === 'POST' && $action === 'advance') {
    $contract = require_active_contract(load_contract($pdo, $offer['id']));
    $body = read_json_body();
    $targetStep = (string) ($body['step'] ?? '');

    $currentIndex = array_search($contract['current_step'], STEP_ORDER, true);
    $targetIndex = array_search($targetStep, STEP_ORDER, true);

    if ($targetIndex === false || $currentIndex === false || $targetIndex < $currentIndex || $targetIndex > $currentIndex + 1) {
        json_error('Ungültiger Schrittwechsel.', 422);
    }

    if ($contract['current_step'] === 'bedingungen' && $targetStep === 'signatur') {
        $pdo->prepare('UPDATE contracts SET current_step = :step, terms_accepted_at = UTC_TIMESTAMP() WHERE id = :id')
            ->execute(['step' => $targetStep, 'id' => $contract['id']]);
    } else {
        $pdo->prepare('UPDATE contracts SET current_step = :step WHERE id = :id')
            ->execute(['step' => $targetStep, 'id' => $contract['id']]);
    }

    json_response(public_state($offer, load_contract($pdo, $offer['id'])));
}

if ($method === 'POST' && $action === 'sign') {
    $contract = require_active_contract(load_contract($pdo, $offer['id']));
    $body = read_json_body();
    $signatureDataUrl = (string) ($body['signatureDataUrl'] ?? '');

    if (strpos($signatureDataUrl, 'data:image/png;base64,') !== 0) {
        json_error('Ungültige Signatur.', 422);
    }

    $stmt = $pdo->prepare(
        "UPDATE contracts SET status = 'signiert', signed_at = UTC_TIMESTAMP(), terms_accepted_at = COALESCE(terms_accepted_at, UTC_TIMESTAMP()), signature_data = :signature, current_step = 'fertig' WHERE id = :id"
    );
    $stmt->execute(['signature' => $signatureDataUrl, 'id' => $contract['id']]);
    save_contract_pdfs($pdo, $contract['id'], true);
    notify_contract_created($pdo, $contract['id']);
    notify_customer_contract_signed($pdo, $contract['id']);

    json_response(public_state($offer, load_contract($pdo, $offer['id'])));
}

json_error('Unbekannte Aktion.', 404);
