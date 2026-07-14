<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/contract_notify.php';

require_login();

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $settings = load_contract_notification_settings($pdo);
    json_response([
        'enabled' => (bool) $settings['enabled'],
        'recipients' => contract_notification_recipients((string) ($settings['recipients'] ?? '')),
        'updatedAt' => to_iso($settings['updated_at']),
    ]);
}

if ($method === 'POST') {
    $body = read_json_body();
    $enabled = (bool) ($body['enabled'] ?? false);
    $recipientsInput = $body['recipients'] ?? [];
    $recipientsInput = is_array($recipientsInput) ? $recipientsInput : [];

    $emails = [];
    foreach ($recipientsInput as $entry) {
        $email = trim((string) $entry);
        if ($email === '') {
            continue;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_error('Ungültige E-Mail-Adresse: ' . $email, 422);
        }
        $emails[] = $email;
    }
    $emails = array_values(array_unique($emails));

    if ($enabled && $emails === []) {
        json_error('Bitte mindestens eine E-Mail-Adresse angeben.', 422);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO contract_notifications (id, enabled, recipients, updated_at)
         VALUES (1, :enabled, :recipients, UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE enabled = :enabled2, recipients = :recipients2, updated_at = UTC_TIMESTAMP()'
    );
    $stmt->execute([
        'enabled' => $enabled ? 1 : 0,
        'recipients' => implode("\n", $emails),
        'enabled2' => $enabled ? 1 : 0,
        'recipients2' => implode("\n", $emails),
    ]);

    json_response(['ok' => true]);
}

json_error('Methode nicht erlaubt.', 405);
