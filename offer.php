<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/offer_template.php';

$pdo = db();
$offerId = trim((string) ($_GET['offerId'] ?? ''));
$token = trim((string) ($_GET['token'] ?? ''));

if ($token !== '') {
    // Oeffentlicher Zugriff ueber den Kostenvoranschlags-Token, z. B. aus der Kostenvoranschlags-E-Mail
    // oder dem "Link kopieren"-Button. Derselbe Token, der auch fuer o.php verwendet wird.
    $stmt = $pdo->prepare('SELECT * FROM offers WHERE token = :token');
    $stmt->execute(['token' => $token]);
    $offer = $stmt->fetch();

    if (!$offer) {
        http_response_code(404);
        echo 'Kostenvoranschlag nicht gefunden.';
        exit;
    }
} else {
    if (current_user_id() === null) {
        header('Location: /index.html');
        exit;
    }

    if ($offerId === '') {
        http_response_code(400);
        echo 'Kostenvoranschlag fehlt.';
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM offers WHERE id = :id');
    $stmt->execute(['id' => $offerId]);
    $offer = $stmt->fetch();

    if (!$offer) {
        http_response_code(404);
        echo 'Kostenvoranschlag nicht gefunden.';
        exit;
    }
}

$stmt = $pdo->prepare('SELECT * FROM customers WHERE id = :id');
$stmt->execute(['id' => $offer['customer_id']]);
$customer = $stmt->fetch();

if (!$customer) {
    http_response_code(404);
    echo 'Kunde nicht gefunden.';
    exit;
}

echo render_offer_document($offer, $customer);
