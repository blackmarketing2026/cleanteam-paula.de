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
    json_error('Kein Vertragslink angegeben.', 404);
}

const STEP_ORDER = ['datenschutz', 'daten', 'leistung', 'identitaet', 'signatur', 'fertig'];
const TERMINAL_STATUSES = ['daten_abgelehnt', 'intervall_abgelehnt', 'datenschutz_abgelehnt', 'berechtigung_abgelehnt'];

// Alte, inzwischen entfernte Schrittnamen (Vollmacht-Funktion) auf den naechsten
// noch gueltigen Schritt abbilden, damit laengst laufende, noch nicht unterschriebene
// Vertraege nicht in einem unbekannten Schritt haengen bleiben.
function normalize_current_step(string $step): string
{
    if (in_array($step, ['intervall', 'vollmacht', 'vertragspartner'], true)) {
        return 'leistung';
    }

    if ($step === 'bedingungen') {
        return 'identitaet';
    }

    return $step;
}

function load_offer(PDO $pdo, string $token): array
{
    $stmt = $pdo->prepare(
        'SELECT o.*, c.name AS c_name, c.email AS c_email, c.phone AS c_phone, c.salutation AS c_salutation,
            c.contact_last_name AS c_contact_last_name, c.address AS c_address, c.house_number AS c_house_number,
            c.zip AS c_zip, c.city AS c_city
         FROM offers o
         INNER JOIN customers c ON c.id = o.customer_id
         WHERE o.token = :token'
    );
    $stmt->execute(['token' => $token]);
    $offer = $stmt->fetch();

    if (!$offer) {
        json_error('Dieser Vertragslink ist ungültig.', 404);
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

function ensure_contracts_authorized_signer_columns(PDO $pdo): void
{
    $columns = [
        'authorized' => 'ALTER TABLE contracts ADD COLUMN authorized TINYINT(1) NULL AFTER interval_confirmed',
        'representation_note' => 'ALTER TABLE contracts ADD COLUMN representation_note TEXT NULL AFTER authorized',
    ];

    foreach ($columns as $column => $sql) {
        $stmt = $pdo->query("SHOW COLUMNS FROM contracts LIKE '{$column}'");
        if (!$stmt->fetch()) {
            $pdo->exec($sql);
        }
    }
}

function offer_is_expired(array $offer): bool
{
    return strtotime($offer['expires_at'] . ' UTC') < time();
}

function public_state(array $offer, ?array $contract): array
{
    $currentStep = $contract['current_step'] ?? null;
    if ($currentStep !== null) {
        $currentStep = normalize_current_step($currentStep);
    }

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
        ],
        'contract' => $contract === null ? null : [
            'status' => $contract['status'],
            'currentStep' => $currentStep,
            'dataConfirmed' => (bool) $contract['data_confirmed'],
            'intervalConfirmed' => (bool) $contract['interval_confirmed'],
            'signedAt' => to_iso($contract['signed_at']),
            'signatureDataUrl' => $contract['signature_data'],
        ],
    ];
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

$offer = load_offer($pdo, $token);
ensure_contracts_terms_accepted_at_column($pdo);
ensure_contracts_privacy_accepted_at_column($pdo);
ensure_contracts_authorization_columns($pdo);
ensure_contracts_authorized_signer_columns($pdo);
ensure_offers_link_opened_column($pdo);

if ($method === 'GET' && $action === 'offer') {
    $contract = load_contract($pdo, $offer['id']);
    $isCompleted = $contract !== null && $contract['status'] === 'signiert';

    if (!$isCompleted && $offer['link_opened_at'] !== null) {
        json_error(
            'Dieser Link wurde bereits geöffnet und ist aus Datenschutzgründen gesperrt. Bitte kontaktieren Sie CleanTeam, damit der Link wieder freigegeben wird.',
            410
        );
    }

    $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($offer['link_opened_at'] === null && !is_automated_email_scanner($userAgent)) {
        $pdo->prepare('UPDATE offers SET link_opened_at = UTC_TIMESTAMP() WHERE id = :id')->execute(['id' => $offer['id']]);
    }

    json_response(public_state($offer, $contract));
}

if (offer_is_expired($offer) && $method === 'POST') {
    json_error('Dieser Vertrag ist abgelaufen. Bitte kontaktieren Sie CleanTeam für einen neuen Vertrag.', 410);
}

if ($method === 'POST' && $action === 'start') {
    $contract = ensure_contract_for_offer($pdo, $offer);
    json_response(public_state($offer, $contract));
}

if ($method === 'POST' && $action === 'confirm-privacy') {
    $contract = require_active_contract(load_contract($pdo, $offer['id']));
    $body = read_json_body();
    $confirmed = (bool) ($body['confirmed'] ?? false);

    if ($confirmed) {
        $pdo->prepare("UPDATE contracts SET current_step = 'daten', privacy_accepted_at = UTC_TIMESTAMP() WHERE id = :id")
            ->execute(['id' => $contract['id']]);
    } else {
        $pdo->prepare("UPDATE contracts SET status = 'datenschutz_abgelehnt' WHERE id = :id")
            ->execute(['id' => $contract['id']]);
    }

    json_response(public_state($offer, load_contract($pdo, $offer['id'])));
}

if ($method === 'POST' && $action === 'confirm-data') {
    $contract = require_active_contract(load_contract($pdo, $offer['id']));
    $body = read_json_body();
    $confirmed = (bool) ($body['confirmed'] ?? false);

    if ($confirmed) {
        $pdo->prepare("UPDATE contracts SET data_confirmed = 1, interval_confirmed = 1, current_step = 'leistung' WHERE id = :id")
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
        $pdo->prepare("UPDATE contracts SET interval_confirmed = 1, current_step = 'leistung' WHERE id = :id")
            ->execute(['id' => $contract['id']]);
    } else {
        $pdo->prepare("UPDATE contracts SET status = 'intervall_abgelehnt' WHERE id = :id")
            ->execute(['id' => $contract['id']]);
    }

    json_response(public_state($offer, load_contract($pdo, $offer['id'])));
}

if ($method === 'POST' && $action === 'advance') {
    $contract = require_active_contract(load_contract($pdo, $offer['id']));
    $body = read_json_body();
    $targetStep = (string) ($body['step'] ?? '');
    $currentStep = normalize_current_step((string) $contract['current_step']);

    $currentIndex = array_search($currentStep, STEP_ORDER, true);
    $targetIndex = array_search($targetStep, STEP_ORDER, true);

    if ($targetIndex === false || $currentIndex === false || $targetIndex < $currentIndex || $targetIndex > $currentIndex + 1) {
        json_error('Ungültiger Schrittwechsel.', 422);
    }

    if ($currentStep === 'leistung' && $targetStep === 'identitaet') {
        if (empty($body['termsAccepted'])) {
            json_error('Bitte bestaetigen Sie zuerst den Auftrag.', 422);
        }
        $pdo->prepare('UPDATE contracts SET current_step = :step, terms_accepted_at = UTC_TIMESTAMP() WHERE id = :id')
            ->execute(['step' => $targetStep, 'id' => $contract['id']]);
    } else {
        $pdo->prepare('UPDATE contracts SET current_step = :step WHERE id = :id')
            ->execute(['step' => $targetStep, 'id' => $contract['id']]);
    }

    json_response(public_state($offer, load_contract($pdo, $offer['id'])));
}

if ($method === 'POST' && $action === 'confirm-identity') {
    $contract = require_active_contract(load_contract($pdo, $offer['id']));
    $body = read_json_body();
    $confirmed = (bool) ($body['confirmed'] ?? false);

    if ($confirmed) {
        $pdo->prepare("UPDATE contracts SET current_step = 'signatur' WHERE id = :id")
            ->execute(['id' => $contract['id']]);
    } elseif (!empty($body['authorized'])) {
        $representationNote = trim((string) ($body['representationNote'] ?? ''));
        if ($representationNote === '') {
            json_error('Bitte tragen Sie Ihren Namen ein.', 422);
        }
        $pdo->prepare("UPDATE contracts SET current_step = 'signatur', authorized = 0, representation_note = :note WHERE id = :id")
            ->execute(['note' => $representationNote, 'id' => $contract['id']]);
    } else {
        $pdo->prepare("UPDATE contracts SET status = 'berechtigung_abgelehnt' WHERE id = :id")
            ->execute(['id' => $contract['id']]);
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
