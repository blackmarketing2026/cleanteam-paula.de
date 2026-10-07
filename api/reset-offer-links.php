<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

// Setzt den Kundenfortschritt von Vertrags- und KVA-Link zurück, damit beide wieder
// wie beim ersten Öffnen starten. Links und Gültigkeit bleiben unverändert.
require_login(); require_method('POST');
$pdo = db(); ensure_quote_workflow_columns($pdo);
$offerId = (string) ($_GET['id'] ?? '');
if ($offerId === '') json_error('Vertragsentwurf fehlt.', 422);
$stmt = $pdo->prepare('SELECT * FROM offers WHERE id = :id');
$stmt->execute(['id' => $offerId]); $offer = $stmt->fetch();
if (!$offer) json_error('Vertragsentwurf wurde nicht gefunden.', 404);

$stmt = $pdo->prepare('SELECT id, status FROM contracts WHERE offer_id = :offer_id');
$stmt->execute(['offer_id' => $offerId]); $contract = $stmt->fetch();
if ($contract && in_array($contract['status'], ['signiert', 'bestaetigt'], true)) {
    json_error('Der Vertrag ist bereits unterschrieben. Die Links können nicht mehr zurückgesetzt werden.', 409);
}

$pdo->beginTransaction();
try {
    if ($contract) {
        $pdo->prepare(
            "UPDATE contracts SET status = 'entwurf', current_step = 'datenschutz', signed_at = NULL,
                terms_accepted_at = NULL, privacy_accepted_at = NULL, data_confirmed = 0,
                interval_confirmed = 0, signature_data = NULL WHERE id = :id"
        )->execute(['id' => $contract['id']]);
    }
    $pdo->prepare(
        'UPDATE offers SET quote_status = :quote_status, quote_accepted_at = NULL,
            quote_accepted_ip = NULL, quote_accepted_user_agent = NULL WHERE id = :id'
    )->execute(['quote_status' => empty($offer['quote_token']) ? 'entwurf' : 'sent', 'id' => $offerId]);
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    throw $exception;
}

json_response(['ok' => true]);
