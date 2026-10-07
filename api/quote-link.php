<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quote_pdf.php';

require_login(); require_method('POST');
$pdo = db(); ensure_quote_workflow_columns($pdo);
$offerId = (string) ($_GET['id'] ?? '');
if ($offerId === '') json_error('Kostenvoranschlag fehlt.', 422);
$stmt = $pdo->prepare('SELECT * FROM offers WHERE id = :id');
$stmt->execute(['id' => $offerId]); $offer = $stmt->fetch();
if (!$offer) json_error('Vertragsentwurf wurde nicht gefunden.', 404);
if (empty($offer['agb_snapshot_text'])) refresh_offer_agb_snapshot($pdo, $offerId);

$token = prepare_quote_link($pdo, $offer);
$pdo->prepare(
    'UPDATE offers SET quote_status = :quote_status,
        quote_accepted_at = NULL, quote_accepted_ip = NULL, quote_accepted_user_agent = NULL
     WHERE id = :id'
)->execute(['quote_status' => quote_status_after_sharing($pdo, $offer), 'id' => $offerId]);

json_response(['ok' => true, 'url' => base_url() . '/o.php?token=' . $token]);
