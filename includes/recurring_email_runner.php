<?php

require_once __DIR__ . '/recurring_email.php';
require_once __DIR__ . '/recurring_email_sender.php';
require_once __DIR__ . '/SmtpMailer.php';
require_once __DIR__ . '/email_template.php';
require_once __DIR__ . '/email_settings.php';

function run_recurring_emails(PDO $pdo): array
{
    ensure_recurring_email_tables($pdo);
    $pdo->exec('INSERT INTO customer_email_scheduler (id, last_run_at) VALUES (1, UTC_TIMESTAMP())
        ON DUPLICATE KEY UPDATE last_run_at = UTC_TIMESTAMP()');
    $result = ['sent' => 0, 'paused' => 0, 'skipped' => 0];
    // Recover interrupted deliveries conservatively: SMTP may have accepted the message.
    // Never retry an uncertain delivery automatically.
    $candidates = $pdo->query("SELECT customer_id FROM customer_email_series
        WHERE enabled = 1 AND next_run_at <= UTC_TIMESTAMP()
        UNION SELECT customer_id FROM customer_email_series_runs
        WHERE status = 'sending' AND started_at < UTC_TIMESTAMP() - INTERVAL 15 MINUTE LIMIT 25")->fetchAll(PDO::FETCH_COLUMN);
    if ($candidates === []) {
        return $result;
    }
    $delivery = load_email_delivery_settings($pdo);
    $smtp = $pdo->query('SELECT * FROM mailbox_settings WHERE id = 1')->fetch();
    $started = microtime(true);
    foreach ($candidates as $id) {
        if (microtime(true) - $started > 45) {
            break;
        }
        if (!recurring_email_lock($pdo, $id)) {
            $result['skipped']++;
            continue;
        }
        $runId = null;
        try {
            $customer = recurring_email_customer($pdo, $id);
            $stmt = $pdo->prepare('SELECT * FROM customer_email_series WHERE customer_id = ?');
            $stmt->execute([$id]);
            $series = $stmt->fetch();
            if (!$series) {
                continue;
            }
            $uncertain = $pdo->prepare("SELECT id FROM customer_email_series_runs WHERE customer_id = ? AND status = 'sending' LIMIT 1");
            $uncertain->execute([$id]);
            if ($uncertain->fetch()) {
                $pdo->prepare("UPDATE customer_email_series_runs SET status = 'uncertain', completed_at = UTC_TIMESTAMP(),
                    error_message = 'Versand wurde unterbrochen. Bitte Zustellung pruefen; keine automatische Wiederholung.'
                    WHERE customer_id = ? AND status = 'sending'")->execute([$id]);
                throw new RuntimeException('Vorheriger Versand wurde unterbrochen. Zustellung vor erneutem Aktivieren pruefen.');
            }
            if (!$series['enabled'] || !$series['next_run_at'] || $series['next_run_at'] > gmdate('Y-m-d H:i:s')) {
                continue;
            }
            if (!$customer || $customer['deleted_at'] !== null || !$customer['eligible']) {
                throw new RuntimeException('Kunde ist nicht mehr aktiv oder hat keinen unterschriebenen Vertrag.');
            }
            if (!$delivery['customerEmailsEnabled'] || !$delivery['contractEmailsEnabled']) {
                $result['skipped']++;
                continue;
            }
            if (!$smtp || empty($smtp['host']) || empty($smtp['username']) || empty($smtp['password_encrypted'])) {
                throw new RuntimeException('Bitte das E-Mail-Versandkonto in den Einstellungen einrichten.');
            }
            $recipient = recurring_email_recipient($series, $customer);
            $message = recurring_email_message($pdo, $smtp, $series);
            $mailer = new SmtpMailer($smtp['host'], (int) $smtp['smtp_port'], $smtp['smtp_encryption'],
                $smtp['username'], decrypt_secret($smtp['password_encrypted']));
            $next = recurring_email_next_run($series['frequency'], (int) $series['schedule_day'], $series['send_time'],
                new DateTimeImmutable('now', new DateTimeZone('UTC')));
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO customer_email_series_runs
                (customer_id, scheduled_at, started_at, status, recipient, subject, attachment_name)
                VALUES (?, ?, UTC_TIMESTAMP(), 'sending', ?, ?, ?)")
                ->execute([$id, $series['next_run_at'], $recipient, $series['subject'], $series['attachment_name']]);
            $runId = $pdo->lastInsertId();
            $pdo->prepare('UPDATE customer_email_series SET next_run_at = ? WHERE customer_id = ?')->execute([$next, $id]);
            $pdo->commit();
            recurring_email_deliver($mailer, $smtp, $customer, $series, $message, $recipient);
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE customer_email_series_runs SET status = 'sent', completed_at = UTC_TIMESTAMP() WHERE id = ?")->execute([$runId]);
            $pdo->prepare('UPDATE customer_email_series SET last_sent_at = UTC_TIMESTAMP(), last_error = NULL WHERE customer_id = ?')->execute([$id]);
            $pdo->commit();
            $result['sent']++;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $runId !== null
                ? 'Versand nicht sicher bestaetigt. Bitte Zustellung pruefen. Serie pausiert; keine automatische Wiederholung.'
                : 'Versand nicht moeglich. Bitte Kundenstatus, Versandkonto und Einstellungen pruefen.';
            error_log('E-Mail-Serie ' . $id . ': ' . $exception->getMessage());
            if ($runId !== null) {
                $pdo->prepare("UPDATE customer_email_series_runs SET status = 'uncertain', completed_at = UTC_TIMESTAMP(), error_message = ? WHERE id = ?")
                    ->execute([$error, $runId]);
            }
            $pdo->prepare('UPDATE customer_email_series SET enabled = 0, next_run_at = NULL, last_error = ?, revision = revision + 1 WHERE customer_id = ?')
                ->execute([$error, $id]);
            $result['paused']++;
        } finally {
            recurring_email_unlock($pdo, $id);
        }
    }
    return $result;
}
