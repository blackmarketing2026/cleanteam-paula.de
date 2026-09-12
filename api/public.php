<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/contract_notify.php';
require_once __DIR__ . '/../includes/contract_pdf.php';
require_once __DIR__ . '/../includes/ftp_export.php';

require_once __DIR__ . '/../includes/contract_signers.php';
require_once __DIR__ . '/../includes/quote_pdf.php';

$pdo = db();
ensure_offers_existing_contract_columns($pdo);
ensure_contracts_second_signer_columns($pdo);
$method = $_SERVER['REQUEST_METHOD'];
$action = (string) ($_GET['action'] ?? '');
$token = trim((string) ($_GET['token'] ?? ''));

if ($token === '') {
    json_error('Kein Vertragslink angegeben.', 404);
}

const STEP_ORDER = ['datenschutz', 'signatur', 'fertig'];
const TERMINAL_STATUSES = ['daten_abgelehnt', 'intervall_abgelehnt', 'datenschutz_abgelehnt', 'berechtigung_abgelehnt'];

// Alte, inzwischen entfernte Schrittnamen (Vollmacht-Funktion) auf den naechsten
// noch gueltigen Schritt abbilden, damit laengst laufende, noch nicht unterschriebene
// Vertraege nicht in einem unbekannten Schritt haengen bleiben.
function normalize_current_step(string $step): string
{
    if (in_array($step, ['daten', 'intervall', 'leistung', 'vollmacht', 'vertragspartner', 'bedingungen', 'identitaet'], true)) {
        return 'signatur';
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
         WHERE o.token = :token OR o.quote_token = :token2'
    );
    $stmt->execute(['token' => $token, 'token2' => $token]);
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
    if (!empty($offer['is_existing_contract'])) {
        return false;
    }
    return strtotime($offer['expires_at'] . ' UTC') < time();
}

function public_state(array $offer, ?array $contract): array
{
    global $token;
    $currentStep = $contract['current_step'] ?? null;
    if ($currentStep !== null) {
        $currentStep = normalize_current_step($currentStep);
    }

    return [
        'serverNow' => gmdate('Y-m-d\TH:i:s\Z'),
        'accessMode' => !empty($offer['quote_token']) && hash_equals((string) $offer['quote_token'], $token) ? 'quote' : 'contract',
        'offer' => [
            'squareMeters' => (int) $offer['square_meters'],
            'interval' => $offer['interval_label'],
            'service' => $offer['service'],
            'startDate' => $offer['start_date'],
            'isExistingContract' => (bool) ($offer['is_existing_contract'] ?? false),
            'originalStartDate' => $offer['original_start_date'] ?? null,
            'notes' => $offer['notes'],
            'basePrice' => isset($offer['base_price']) && (float) $offer['base_price'] > 0
                ? (float) $offer['base_price']
                : (float) $offer['price'],
            'priceAdjustment' => (float) ($offer['price_adjustment'] ?? 0),
            'priceAdjustmentNote' => $offer['price_adjustment_note'] ?? null,
            'price' => (float) $offer['price'],
            'vatApplicable' => !isset($offer['vat_applicable']) || (int) $offer['vat_applicable'] === 1,
            'expiresAt' => to_iso($offer['expires_at']),
            'expired' => offer_is_expired($offer),
            'quoteStatus' => $offer['quote_status'] ?? 'entwurf',
            'quoteAcceptedAt' => to_iso($offer['quote_accepted_at'] ?? null),
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

function migrate_legacy_quote_confirmation(PDO $pdo, array $offer, array $contract): array
{
    if (($offer['quote_status'] ?? '') !== 'accepted'
        || ($contract['status'] ?? '') !== 'bestaetigt'
        || !empty($contract['signature_data'])) {
        return $contract;
    }

    ensure_contract_documents_table($pdo);
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM contract_documents WHERE contract_id = :contract_id')
            ->execute(['contract_id' => $contract['id']]);
        $pdo->prepare(
            "UPDATE contracts SET status = 'entwurf', current_step = 'datenschutz', signed_at = NULL,
                terms_accepted_at = NULL, privacy_accepted_at = NULL, data_confirmed = 0,
                interval_confirmed = 0, signature_data = NULL WHERE id = :id"
        )->execute(['id' => $contract['id']]);
        $pdo->prepare(
            "UPDATE offers SET quote_status = 'signing', quote_accepted_at = NULL,
                quote_accepted_ip = NULL, quote_accepted_user_agent = NULL WHERE id = :id"
        )->execute(['id' => $offer['id']]);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    return load_contract($pdo, $offer['id']);
}

ensure_quote_workflow_columns($pdo);
$offer = load_offer($pdo, $token);
$isQuoteAccess = !empty($offer['quote_token']) && hash_equals((string) $offer['quote_token'], $token);
ensure_contracts_terms_accepted_at_column($pdo);
ensure_contracts_privacy_accepted_at_column($pdo);
ensure_contracts_authorization_columns($pdo);
ensure_contracts_authorized_signer_columns($pdo);

if ($method === 'GET' && $action === 'offer') {
    $contract = load_contract($pdo, $offer['id']);
    if ($isQuoteAccess && $contract !== null) {
        $contract = migrate_legacy_quote_confirmation($pdo, $offer, $contract);
        $offer = load_offer($pdo, $token);
    }
    if (($offer['quote_status'] ?? '') === 'accepted') {
        $fallbackStatus = $contract !== null && ($contract['status'] ?? '') !== 'signiert'
            ? 'signing'
            : 'sent';
        $pdo->prepare(
            'UPDATE offers SET quote_status = :quote_status, quote_accepted_at = NULL,
                quote_accepted_ip = NULL, quote_accepted_user_agent = NULL WHERE id = :id'
        )->execute(['quote_status' => $fallbackStatus, 'id' => $offer['id']]);
        $offer = load_offer($pdo, $token);
    }
    if ($contract !== null && normalize_current_step((string) $contract['current_step']) === 'signatur'
        && $contract['current_step'] !== 'signatur') {
        $nextStep = empty($contract['privacy_accepted_at']) ? 'datenschutz' : 'signatur';
        $pdo->prepare("UPDATE contracts SET current_step = :step, data_confirmed = CASE WHEN :step_confirmed = 'signatur' THEN 1 ELSE data_confirmed END, interval_confirmed = CASE WHEN :step_confirmed_2 = 'signatur' THEN 1 ELSE interval_confirmed END WHERE id = :id")
            ->execute([
                'step' => $nextStep,
                'step_confirmed' => $nextStep,
                'step_confirmed_2' => $nextStep,
                'id' => $contract['id'],
            ]);
        $contract = load_contract($pdo, $offer['id']);
    }
    json_response(public_state($offer, $contract));
}

if (offer_is_expired($offer) && $method === 'POST') {
    json_error('Vertragslink abgelaufen. Bitte kontaktieren Sie das Clean-Team.', 410);
}

if ($method === 'POST' && $action === 'accept-quote') {
    if (!$isQuoteAccess) json_error('Diese Aktion ist nur über den Kostenvoranschlagslink möglich.', 409);
    if (($offer['quote_status'] ?? 'entwurf') === 'signing') {
        json_response(public_state($offer, load_contract($pdo, $offer['id'])));
    }
    if (!in_array(($offer['quote_status'] ?? 'entwurf'), ['entwurf', 'sent'], true)) {
        json_error('Der Kostenvoranschlag wurde noch nicht versendet.', 409);
    }
    if (empty($offer['agb_snapshot_text'])) {
        refresh_offer_agb_snapshot($pdo, $offer['id']);
        $offer = load_offer($pdo, $token);
    }
    $contract = ensure_contract_for_offer($pdo, $offer);
    if (($contract['status'] ?? '') !== 'signiert') {
        $pdo->prepare("UPDATE offers SET quote_status = 'signing', quote_accepted_at = NULL, quote_accepted_ip = NULL, quote_accepted_user_agent = NULL WHERE id = :id")
            ->execute(['id' => $offer['id']]);
    }
    $offer = load_offer($pdo, $token);
    json_response(public_state($offer, load_contract($pdo, $offer['id'])));
}

if ($isQuoteAccess && $method === 'POST' && ($offer['quote_status'] ?? '') !== 'signing') {
    json_error('Bitte starten Sie den Online-Vertragsabschluss über den Kostenvoranschlag.', 409);
}

if ($method === 'POST' && $action === 'start') {
    if (empty($offer['agb_snapshot_text'])) {
        refresh_offer_agb_snapshot($pdo, $offer['id']);
        $offer = load_offer($pdo, $token);
    }
    $contract = ensure_contract_for_offer($pdo, $offer);
    json_response(public_state($offer, $contract));
}

if ($method === 'POST' && $action === 'confirm-privacy') {
    $contract = require_active_contract(load_contract($pdo, $offer['id']));
    $body = read_json_body();
    $confirmed = (bool) ($body['confirmed'] ?? false);

    if ($confirmed) {
        $pdo->prepare("UPDATE contracts SET current_step = 'signatur', data_confirmed = 1, interval_confirmed = 1, privacy_accepted_at = UTC_TIMESTAMP() WHERE id = :id")
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

    if (!validate_contract_signature($signatureDataUrl)) {
        json_error('Ungültige Signatur.', 422);
    }

    if (normalize_current_step($contract['current_step']) !== 'signatur'
        || empty($contract['privacy_accepted_at']) || empty($contract['data_confirmed'])) {
        json_error('Bitte schliessen Sie zuerst die vorherigen Schritte ab.', 409);
    }
    // Accept already-open clients from the previous two-person form as well.
    $inputSigners = $body['additionalSigners'] ?? [];
    if (!array_key_exists('additionalSigners', $body) && ($body['twoSigners'] ?? false) === true) {
        $inputSigners = [['name' => $body['secondSignerName'] ?? '',
            'signatureDataUrl' => $body['secondSignatureDataUrl'] ?? '',
            'confirmed' => $body['secondSignerConfirmed'] ?? false]];
    }
    try {
        $signers = validate_additional_signers($inputSigners);
    } catch (InvalidArgumentException $exception) {
        json_error($exception->getMessage(), 422);
    }
    $secondName = $signers[0]['name'] ?? null;
    $secondSignature = $signers[0]['signatureDataUrl'] ?? null;

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "UPDATE contracts SET additional_signers = :additional_signers, second_signer_name = :second_name, second_signature_data = :second_signature,
            second_signed_at = CASE WHEN :two_signers = 1 THEN UTC_TIMESTAMP() ELSE NULL END, status = 'signiert', signed_at = UTC_TIMESTAMP(), terms_accepted_at = COALESCE(terms_accepted_at, UTC_TIMESTAMP()), signature_data = :signature, current_step = 'fertig' WHERE id = :id AND status <> 'signiert' AND current_step = 'signatur'"
        );
        $stmt->execute(['signature' => $signatureDataUrl, 'id' => $contract['id'],
            'second_name' => $secondName, 'second_signature' => $secondSignature, 'two_signers' => (int) (count($signers) > 0),
            'additional_signers' => json_encode($signers, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
        if ($stmt->rowCount() !== 1) {
            $pdo->rollBack();
            json_error('Der Vertrag wurde bereits abgeschlossen oder der Schritt hat sich geaendert.', 409);
        }
        $pdo->prepare(
            "UPDATE offers SET quote_status = CASE WHEN quote_status = 'signing' THEN 'sent' ELSE quote_status END,
                quote_accepted_at = NULL, quote_accepted_ip = NULL, quote_accepted_user_agent = NULL WHERE id = :id"
        )->execute(['id' => $offer['id']]);
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }
    save_contract_pdfs($pdo, $contract['id'], true);
    notify_contract_created($pdo, $contract['id']);
    if (empty($offer['is_existing_contract'])) {
        notify_customer_contract_signed($pdo, $contract['id']);
    }
    export_contract_to_ftp($pdo, $contract['id']);

    $offer = load_offer($pdo, $token);
    json_response(public_state($offer, load_contract($pdo, $offer['id'])));
}

json_error('Unbekannte Aktion.', 404);
