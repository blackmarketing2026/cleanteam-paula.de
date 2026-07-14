<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/contract_template.php';
require_once __DIR__ . '/includes/contract_pdf.php';

$pdo = db();
$contractId = trim((string) ($_GET['contractId'] ?? ''));
$offerId = trim((string) ($_GET['offerId'] ?? ''));
$token = trim((string) ($_GET['token'] ?? ''));
$document = trim((string) ($_GET['document'] ?? ''));
$format = trim((string) ($_GET['format'] ?? 'html'));
$download = (string) ($_GET['download'] ?? '') === '1';
$isPublicTokenAccess = $token !== '';

if ($isPublicTokenAccess) {
    // Oeffentlicher Zugriff ueber den Kostenvoranschlags-Token (z. B. von der "fertig"-Seite des
    // Kunden-Vertragswizards). Der Token beweist bereits den Zugriff auf genau diesen Kostenvoranschlag,
    // dieselbe Berechtigung wie api/public.php verwendet, daher keine zusaetzliche Admin-Session
    // noetig.
    $stmt = $pdo->prepare('SELECT * FROM offers WHERE token = :token');
    $stmt->execute(['token' => $token]);
    $offer = $stmt->fetch();

    if (!$offer) {
        http_response_code(404);
        echo 'Kostenvoranschlag nicht gefunden.';
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM contracts WHERE offer_id = :offer_id');
    $stmt->execute(['offer_id' => $offer['id']]);
    $contract = $stmt->fetch();

    if (!$contract) {
        http_response_code(404);
        echo 'Vertrag nicht gefunden.';
        exit;
    }
} else {
    if (current_user_id() === null) {
        header('Location: /index.html');
        exit;
    }

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

    if ($contract === null) {
        $stmt = $pdo->prepare('SELECT * FROM contracts WHERE offer_id = :offer_id');
        $stmt->execute(['offer_id' => $offerId]);
        $contract = $stmt->fetch() ?: null;
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

$documentAudience = $isPublicTokenAccess
    ? 'customer'
    : (in_array($document, ['customer', 'cleanteam', 'site_visit'], true) ? $document : 'cleanteam');

if ($format === 'pdf') {
    if ($contract === null) {
        http_response_code(404);
        echo 'Vertrag nicht gefunden.';
        exit;
    }

    $forceRefresh = $documentAudience !== 'site_visit' && ($contract['status'] ?? '') !== 'signiert';
    try {
        $pdf = save_contract_pdf($pdo, $contract['id'], $documentAudience, $forceRefresh);
    } catch (RuntimeException $exception) {
        http_response_code(404);
        echo $exception->getMessage();
        exit;
    }
    output_contract_pdf($pdf, $download);
}

echo render_contract_document($offer, $customer, $contract, ['audience' => $documentAudience]);
