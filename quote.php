<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/quote_pdf.php';

$pdo = db(); ensure_quote_workflow_columns($pdo);
$offerId = trim((string) ($_GET['offerId'] ?? '')); $token = trim((string) ($_GET['token'] ?? ''));
if ($token === '' && current_user_id() === null) { header('Location: /index.html'); exit; }
if ($offerId === '' && $token === '') { http_response_code(400); echo 'Kostenvoranschlag fehlt.'; exit; }
$stmt = $pdo->prepare($token !== '' ? 'SELECT * FROM offers WHERE token = :value' : 'SELECT * FROM offers WHERE id = :value');
$stmt->execute(['value' => $token !== '' ? $token : $offerId]); $offer = $stmt->fetch();
if (!$offer) { http_response_code(404); echo 'Kostenvoranschlag nicht gefunden.'; exit; }
$customer = $pdo->prepare('SELECT * FROM customers WHERE id = :id'); $customer->execute(['id' => $offer['customer_id']]);
$customer = $customer->fetch();
if (!$customer) { http_response_code(404); echo 'Kunde nicht gefunden.'; exit; }
if (($offer['quote_status'] ?? 'entwurf') === 'entwurf') {
    $quote = ['filename' => quote_pdf_filename(contract_customer_display_name($customer)), 'content' => render_quote_pdf($offer, $customer)];
} else {
    $quote = save_quote_pdf($pdo, $offer, $customer);
}
header('Content-Type: application/pdf'); header('Content-Length: ' . strlen($quote['content'])); header('Content-Disposition: inline; filename="' . str_replace('"', '', $quote['filename']) . '"'); echo $quote['content'];
