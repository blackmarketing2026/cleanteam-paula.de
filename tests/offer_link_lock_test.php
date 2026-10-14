<?php

// Spielt Sperren, einmaliges Öffnen und Freigeben gegen eine SQLite-Datenbank durch.
require_once __DIR__ . '/../includes/link_access.php';

final class LinkLockTestPdo extends PDO
{
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        if (str_starts_with($query, 'SHOW COLUMNS')) {
            return parent::query('SELECT 1');
        }
        return parent::query($query);
    }
}

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}


$_SESSION = [];
$pdo = new LinkLockTestPdo('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->sqliteCreateFunction('UTC_TIMESTAMP', fn () => gmdate('Y-m-d H:i:s'), 0);
$pdo->exec('CREATE TABLE offers (id TEXT PRIMARY KEY, link_locked INTEGER NOT NULL DEFAULT 0, link_locked_at TEXT NULL, link_opened_at TEXT NULL, link_session_hash TEXT NULL)');
$pdo->exec("INSERT INTO offers (id) VALUES ('offer-1')");
$offer = ['id' => 'offer-1'];

// session_start() darf im CLI-Test keine Header senden.
ini_set('session.use_cookies', '0');
ob_start();

assert_true(offer_link_access_allowed($pdo, $offer), 'Der erste Aufruf muss den Link öffnen.');
$cookieName = offer_link_cookie_name($offer);
$browserCookie = $_COOKIE[$cookieName] ?? '';
assert_true($browserCookie !== '', 'Der erste Aufruf bindet den Link nicht an den Browser.');
assert_true(offer_link_access_allowed($pdo, $offer), 'Neuladen im selben Browser muss weiter funktionieren.');

$_COOKIE = [];
assert_true(!offer_link_access_allowed($pdo, $offer), 'Ein zweiter Aufruf in einem anderen Browser muss gesperrt sein.');

$_SESSION['user_id'] = 1;
assert_true(offer_link_access_allowed($pdo, $offer), 'Angemeldete CleanTeam-Nutzer dürfen den Link prüfen.');
unset($_SESSION['user_id']);

$_COOKIE[$cookieName] = $browserCookie;
$pdo->exec("UPDATE offers SET link_locked = 1 WHERE id = 'offer-1'");
assert_true(!offer_link_access_allowed($pdo, $offer), 'Ein manuell gesperrter Link muss auch im ursprünglichen Browser gesperrt sein.');

// Freigeben wie api/lock-offer-links.php: der Link kann wieder genau einmal geöffnet werden.
$pdo->exec("UPDATE offers SET link_locked = 0, link_locked_at = NULL, link_opened_at = NULL, link_session_hash = NULL WHERE id = 'offer-1'");
$_COOKIE = [];
assert_true(offer_link_access_allowed($pdo, $offer), 'Ein freigegebener Link muss wieder geöffnet werden können.');
$_COOKIE = [];
assert_true(!offer_link_access_allowed($pdo, $offer), 'Auch nach der Freigabe darf der Link nur einmal geöffnet werden.');
ob_end_clean();

$publicApi = file_get_contents(__DIR__ . '/../api/public.php');
$publicJs = file_get_contents(__DIR__ . '/../public.js');
$page = file_get_contents(__DIR__ . '/../o.php');
$app = file_get_contents(__DIR__ . '/../app.js');
foreach ([
    [$publicApi, 'require_offer_link_access_json($pdo, $offer);', 'Die Kunden-API prüft die Linksperre nicht.'],
    [file_get_contents(__DIR__ . '/../contract.php'), 'require_offer_link_access_page($pdo, $offer);', 'Die Vertragsansicht prüft die Linksperre nicht.'],
    [file_get_contents(__DIR__ . '/../quote.php'), 'require_offer_link_access_page($pdo, $offer);', 'Der Kostenvoranschlag prüft die Linksperre nicht.'],
    [$page, 'id="screen-gesperrt"', 'Der Sperrbildschirm fehlt.'],
    [$page, 'Bitte melden Sie sich bei CleanTeam.', 'Der Hinweis auf dem Sperrbildschirm fehlt.'],
    [$publicJs, 'error.code === "link_locked"', 'Die Kundenseite zeigt bei gesperrtem Link keinen Sperrbildschirm.'],
    [$app, 'data-action="toggle-offer-link-lock"', 'Der Button „Link sperren“ fehlt.'],
    [$app, 'action === "toggle-offer-link-lock"', 'Der Handler für „Link sperren“ fehlt.'],
    [file_get_contents(__DIR__ . '/../api/reset-offer-links.php'), 'link_opened_at = NULL', 'Zurücksetzen gibt den einmaligen Aufruf nicht wieder frei.'],
] as [$source, $needle, $message]) {
    assert_true(str_contains($source, $needle), $message);
}

echo "PASS: links can be locked, opened only once and released again\n";
