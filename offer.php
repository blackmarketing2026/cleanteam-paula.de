<?php

header('Cache-Control: no-store, private');
$token = trim((string) ($_GET['token'] ?? ''));
if ($token !== '') {
    // Alte, bereits versendete Links ueberspringen die nicht mehr benoetigte
    // Vorschaltseite und fuehren direkt in den Vertragsabschluss.
    header('Location: /o.php?token=' . rawurlencode($token), true, 302);
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

$pdo = db();
$offerId = trim((string) ($_GET['offerId'] ?? ''));

if (current_user_id() === null) {
    header('Location: /index.html');
    exit;
}

if ($offerId === '') {
    http_response_code(400);
    echo 'Vertrag fehlt.';
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM offers WHERE id = :id');
$stmt->execute(['id' => $offerId]);
$offer = $stmt->fetch();

if (!$offer) {
    http_response_code(404);
    echo 'Vertrag nicht gefunden.';
    exit;
}

// Auch der Einstieg aus der Vertragsliste fuehrt direkt zur ersten Frage.
header('Location: /o.php?token=' . rawurlencode($offer['token']), true, 302);
exit;
