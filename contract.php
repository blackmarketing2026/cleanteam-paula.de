<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/contract_template.php';

if (current_user_id() === null) {
    header('Location: /index.html');
    exit;
}

$pdo = db();
$contractId = trim((string) ($_GET['contractId'] ?? ''));
$offerId = trim((string) ($_GET['offerId'] ?? ''));

if ($contractId !== '') {
    $stmt = $pdo->prepare('SELECT * FROM contracts WHERE id = :id');
    $stmt->execute(['id' => $contractId]);
    $contract = $stmt->fetch();

    if (!$contract) {
        http_response_code(404);
        echo 'Vertrag nicht gefunden.';
        exit;
    }

    $offerId = $contract['offer_id'];
} else {
    $contract = null;
}

if ($offerId === '') {
    http_response_code(400);
    echo 'Angebot fehlt.';
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM offers WHERE id = :id');
$stmt->execute(['id' => $offerId]);
$offer = $stmt->fetch();

if (!$offer) {
    http_response_code(404);
    echo 'Angebot nicht gefunden.';
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM customers WHERE id = :id');
$stmt->execute(['id' => $offer['customer_id']]);
$customer = $stmt->fetch();

if (!$customer) {
    http_response_code(404);
    echo 'Kunde nicht gefunden.';
    exit;
}

if ($contract === null) {
    $stmt = $pdo->prepare('SELECT * FROM contracts WHERE offer_id = :offer_id');
    $stmt->execute(['offer_id' => $offerId]);
    $contract = $stmt->fetch() ?: null;
}

echo render_contract_document($offer, $customer, $contract);
