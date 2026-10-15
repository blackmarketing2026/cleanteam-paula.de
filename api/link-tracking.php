<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/link_access.php';

function ensure_link_visits_table(PDO $pdo): void
{
    if ($pdo->query("SHOW TABLES LIKE 'offer_link_visits'")->fetch()) return;
    $pdo->exec('CREATE TABLE IF NOT EXISTS offer_link_visits (
    id VARCHAR(64) NOT NULL PRIMARY KEY,
    offer_id VARCHAR(64) NOT NULL,
    link_kind VARCHAR(20) NOT NULL,
    document_kind VARCHAR(30) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    opened_at DATETIME NOT NULL,
    last_seen_at DATETIME NOT NULL,
    closed_at DATETIME NULL,
    close_reason VARCHAR(20) NULL,
    KEY idx_link_visits_offer_opened (offer_id, opened_at),
    CONSTRAINT fk_link_visits_offer FOREIGN KEY (offer_id) REFERENCES offers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require_login();
    ensure_link_visits_table($pdo);
    $offerId = trim((string) ($_GET['offerId'] ?? ''));
    if ($offerId === '') json_error('Angebot fehlt.', 400);
    $stmt = $pdo->prepare('SELECT id, link_kind, document_kind, ip_address, opened_at, last_seen_at, closed_at, close_reason,
        TIMESTAMPDIFF(SECOND, opened_at, COALESCE(closed_at, IF(last_seen_at < UTC_TIMESTAMP() - INTERVAL 45 SECOND, last_seen_at, UTC_TIMESTAMP()))) AS duration_seconds
        FROM offer_link_visits WHERE offer_id = :offer_id ORDER BY opened_at ASC, id ASC');
    $stmt->execute(['offer_id' => $offerId]);
    $visits = [];
    foreach ($stmt->fetchAll() as $row) {
        $timedOut = $row['closed_at'] === null && strtotime($row['last_seen_at'] . ' UTC') < time() - 45;
        $visits[] = [
            'linkKind' => $row['link_kind'],
            'documentKind' => $row['document_kind'],
            'ipAddress' => $row['ip_address'],
            'openedAt' => to_iso($row['opened_at']),
            'closedAt' => to_iso($row['closed_at'] ?? ($timedOut ? $row['last_seen_at'] : null)),
            'closeReason' => $row['close_reason'] ?? ($timedOut ? 'timeout' : null),
            'durationSeconds' => max(0, (int) $row['duration_seconds']),
        ];
    }
    json_response(['visits' => $visits]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Methode nicht erlaubt.', 405);
$token = trim((string) ($_GET['token'] ?? ''));
if ($token === '') json_error('Link fehlt.', 404);
$stmt = $pdo->prepare('SELECT id, token, quote_token FROM offers WHERE token = :token OR quote_token = :quote_token');
$stmt->execute(['token' => $token, 'quote_token' => $token]);
$offer = $stmt->fetch();
if (!$offer) json_error('Link ungültig.', 404);
require_offer_link_access_json($pdo, $offer);
ensure_link_visits_table($pdo);

$body = read_json_body();
$action = (string) ($body['action'] ?? '');
$linkKind = !empty($offer['quote_token']) && hash_equals($offer['quote_token'], $token) ? 'quote' : 'contract';
$documentKind = (string) ($body['documentKind'] ?? '');
if (!in_array($documentKind, ['kostenvoranschlag', 'vertrag', 'abschluss', 'sonstiges'], true)) $documentKind = 'sonstiges';

if ($action === 'open') {
    // Eigene Vorschauen im angemeldeten Adminbereich sind keine Kundenaufrufe.
    if (current_user_id() !== null) json_response(['visitId' => null]);
    $id = generate_id('visit');
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $pdo->prepare('INSERT INTO offer_link_visits (id, offer_id, link_kind, document_kind, ip_address, opened_at, last_seen_at)
        VALUES (:id, :offer_id, :link_kind, :document_kind, :ip, UTC_TIMESTAMP(), UTC_TIMESTAMP())')
        ->execute(['id' => $id, 'offer_id' => $offer['id'], 'link_kind' => $linkKind, 'document_kind' => $documentKind, 'ip' => substr($ip, 0, 45)]);
    if ($documentKind === 'vertrag') {
        require_once __DIR__ . '/../includes/contract_notify.php';
        notify_contract_opened($pdo, $offer['id'], $linkKind);
    }
    json_response(['visitId' => $id]);
}

$visitId = (string) ($body['visitId'] ?? '');
if ($visitId === '') json_error('Besuch fehlt.', 400);
if ($action === 'heartbeat') {
    $pdo->prepare('UPDATE offer_link_visits SET last_seen_at = UTC_TIMESTAMP(), document_kind = :document_kind
        WHERE id = :id AND offer_id = :offer_id AND link_kind = :link_kind AND closed_at IS NULL')
        ->execute(['document_kind' => $documentKind, 'id' => $visitId, 'offer_id' => $offer['id'], 'link_kind' => $linkKind]);
    json_response(['ok' => true]);
}
if ($action === 'close') {
    $pdo->prepare('UPDATE offer_link_visits SET last_seen_at = UTC_TIMESTAMP(), closed_at = UTC_TIMESTAMP(), close_reason = :reason,
        document_kind = :document_kind WHERE id = :id AND offer_id = :offer_id AND link_kind = :link_kind AND closed_at IS NULL')
        ->execute(['reason' => 'closed', 'document_kind' => $documentKind, 'id' => $visitId, 'offer_id' => $offer['id'], 'link_kind' => $linkKind]);
    json_response(['ok' => true]);
}
json_error('Aktion unbekannt.', 400);
