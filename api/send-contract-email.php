<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/email_settings.php';
require_once __DIR__ . '/../includes/contract_notify.php';

require_login();
require_method('POST');

$pdo = db();
email_delivery_assert_allowed($pdo, 'contract_customer');

$contractId = (string) ($_GET['id'] ?? '');
if ($contractId === '') {
    json_error('Vertrag fehlt.', 422);
}

$context = load_contract_context($pdo, $contractId);
if ($context === null) {
    json_error('Vertrag wurde nicht gefunden.', 404);
}

if (!in_array($context['contract']['status'] ?? '', ['signiert', 'bestaetigt'], true)) {
    json_error('Der Vertrag ist noch nicht unterschrieben.', 422);
}

$input = read_json_body();
$toEmail = trim((string) ($input['toEmail'] ?? $context['customer']['email']));
if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
    json_error('Bitte eine gültige E-Mail-Adresse eintragen.', 422);
}

try {
    send_signed_contract_email_to_customer($pdo, $context, $toEmail);
} catch (Throwable $exception) {
    json_error('E-Mail-Versand fehlgeschlagen: ' . $exception->getMessage(), 502);
}

json_response(['ok' => true, 'sentTo' => $toEmail]);
