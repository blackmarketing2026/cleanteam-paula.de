<?php

// ALL-INKL: call every five minutes. Uses the existing config.php cron_secret.
require_once __DIR__ . '/../includes/recurring_email_runner.php';
$secret = trim((string) (config()['cron_secret'] ?? ''));
$provided = (string) ($_SERVER['HTTP_X_CRON_KEY'] ?? $_GET['key'] ?? '');
if ($secret === '' || !hash_equals($secret, $provided)) {
    json_error('Nicht autorisiert.', 403);
}
set_time_limit(90);
json_response(run_recurring_emails(db()));
