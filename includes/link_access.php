<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

// Zugriffsschutz fuer die oeffentlichen Kundenlinks (Vertrags- und KVA-Link) eines
// Vertragsentwurfs:
// - "Link sperren" im Adminbereich sperrt alle Links des Entwurfs sofort.
// - Jeder Link laesst sich nur einmal oeffnen. Der erste Aufruf bindet den Link an
//   diesen Browser (Sitzungs-Cookie); Neuladen und Folgeschritte in derselben Sitzung
//   funktionieren weiter, jeder spaetere Aufruf zeigt den Sperrbildschirm.
// Angemeldete CleanTeam-Nutzer verbrauchen den einmaligen Aufruf nicht.

const LINK_LOCKED_MESSAGE = 'Link ist gesperrt. Bitte melden Sie sich bei CleanTeam.';

function ensure_offer_link_access_columns(PDO $pdo): void
{
    $columns = [
        'link_locked' => 'ALTER TABLE offers ADD COLUMN link_locked TINYINT(1) NOT NULL DEFAULT 0',
        'link_locked_at' => 'ALTER TABLE offers ADD COLUMN link_locked_at DATETIME NULL',
        'link_opened_at' => 'ALTER TABLE offers ADD COLUMN link_opened_at DATETIME NULL',
        'link_session_hash' => 'ALTER TABLE offers ADD COLUMN link_session_hash CHAR(64) NULL',
    ];
    foreach ($columns as $column => $sql) {
        $stmt = $pdo->query("SHOW COLUMNS FROM offers LIKE '{$column}'");
        if (!$stmt->fetch()) {
            $pdo->exec($sql);
        }
    }
}

function offer_link_cookie_name(array $offer): string
{
    return 'ct_link_' . substr(hash('sha256', (string) $offer['id']), 0, 16);
}

// Liefert true, wenn der aktuelle Aufruf den Link nutzen darf. Ein noch nie
// geoeffneter Link wird dabei fuer diesen Browser beansprucht.
function offer_link_access_allowed(PDO $pdo, array $offer): bool
{
    ensure_offer_link_access_columns($pdo);
    $stmt = $pdo->prepare('SELECT link_locked, link_opened_at, link_session_hash FROM offers WHERE id = :id');
    $stmt->execute(['id' => $offer['id']]);
    $access = $stmt->fetch();
    if (!$access || (int) $access['link_locked'] === 1) {
        return false;
    }
    if (current_user_id() !== null) {
        return true;
    }

    $cookieName = offer_link_cookie_name($offer);
    $cookie = (string) ($_COOKIE[$cookieName] ?? '');
    if ($access['link_opened_at'] !== null) {
        return $cookie !== '' && !empty($access['link_session_hash'])
            && hash_equals((string) $access['link_session_hash'], hash('sha256', $cookie));
    }

    $secret = bin2hex(random_bytes(32));
    $claim = $pdo->prepare(
        'UPDATE offers SET link_opened_at = UTC_TIMESTAMP(), link_session_hash = :hash
         WHERE id = :id AND link_opened_at IS NULL AND link_locked = 0'
    );
    $claim->execute(['hash' => hash('sha256', $secret), 'id' => $offer['id']]);
    if ($claim->rowCount() !== 1) {
        // Ein paralleler Aufruf war schneller.
        return false;
    }
    setcookie($cookieName, $secret, [
        'expires' => 0,
        'path' => '/',
        'samesite' => 'Lax',
        'secure' => is_https_request(),
        'httponly' => true,
    ]);
    $_COOKIE[$cookieName] = $secret;
    return true;
}

function require_offer_link_access_json(PDO $pdo, array $offer): void
{
    if (!offer_link_access_allowed($pdo, $offer)) {
        json_response(['error' => LINK_LOCKED_MESSAGE, 'code' => 'link_locked'], 423);
    }
}

function require_offer_link_access_page(PDO $pdo, array $offer): void
{
    if (offer_link_access_allowed($pdo, $offer)) {
        return;
    }
    http_response_code(423);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, private');
    echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>Link gesperrt</title>'
        . '<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f4f7fb;font-family:Inter,Arial,sans-serif;color:#10264d;text-align:center;padding:24px;box-sizing:border-box}svg{width:64px;height:64px;color:#b42318}h1{font-size:24px;margin:16px 0 8px}p{color:#5b6b82;margin:0}</style></head><body><main>'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>'
        . '<h1>Link ist gesperrt</h1><p>Bitte melden Sie sich bei CleanTeam.</p></main></body></html>';
    exit;
}
