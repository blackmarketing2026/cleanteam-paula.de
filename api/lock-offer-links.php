<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/link_access.php';

// Sperrt alle Kundenlinks eines Vertragsentwurfs oder gibt sie wieder frei.
// Beim Freigeben darf der Kunde den Link wieder genau einmal öffnen.
require_login(); require_method('POST');
$pdo = db(); ensure_offer_link_access_columns($pdo);
$offerId = (string) ($_GET['id'] ?? '');
if ($offerId === '') json_error('Vertragsentwurf fehlt.', 422);
$body = read_json_body();
if (!array_key_exists('locked', $body) || !is_bool($body['locked'])) json_error('Bitte sperren oder freigeben wählen.', 422);
$stmt = $pdo->prepare('SELECT id FROM offers WHERE id = :id');
$stmt->execute(['id' => $offerId]);
if (!$stmt->fetch()) json_error('Vertragsentwurf wurde nicht gefunden.', 404);

if ($body['locked']) {
    $pdo->prepare('UPDATE offers SET link_locked = 1, link_locked_at = UTC_TIMESTAMP() WHERE id = :id')
        ->execute(['id' => $offerId]);
} else {
    $pdo->prepare('UPDATE offers SET link_locked = 0, link_locked_at = NULL, link_opened_at = NULL, link_session_hash = NULL WHERE id = :id')
        ->execute(['id' => $offerId]);
}

json_response(['ok' => true, 'locked' => $body['locked']]);
