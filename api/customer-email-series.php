<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/recurring_email.php';
require_once __DIR__ . '/../includes/recurring_email_sender.php';

require_login();
$_SESSION['email_series_csrf'] ??= bin2hex(random_bytes(32));
$pdo = db();
ensure_recurring_email_tables($pdo);
$id = trim((string) ($_GET['customerId'] ?? ''));
$customer = recurring_email_customer($pdo, $id);
if (!$customer) {
    json_error('Kunde nicht gefunden.', 404);
}
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    if (($_GET['attachment'] ?? '') === '1') {
        $stmt = $pdo->prepare('SELECT attachment_name, attachment_mime, attachment_content FROM customer_email_series WHERE customer_id = ?');
        $stmt->execute([$id]);
        $file = $stmt->fetch();
        if (empty($file['attachment_name'])) {
            json_error('Kein Anhang vorhanden.', 404);
        }
        header('Content-Type: ' . $file['attachment_mime']);
        header("Content-Disposition: attachment; filename=attachment; filename*=UTF-8''" . rawurlencode($file['attachment_name']));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        echo $file['attachment_content'];
        exit;
    }
    json_response(recurring_email_state($pdo, $customer) + ['csrfToken' => $_SESSION['email_series_csrf']]);
}
if ($method !== 'POST') {
    json_error('Methode nicht erlaubt.', 405);
}
if (!hash_equals($_SESSION['email_series_csrf'], (string) ($_POST['csrfToken'] ?? ''))) {
    json_error('Die Sitzung ist abgelaufen oder der Upload ueberschreitet das Serverlimit. Bitte Fenster neu oeffnen und erneut versuchen.', 403);
}
if (!recurring_email_lock($pdo, $id)) {
    json_error('Die Serie wird gerade verarbeitet. Bitte gleich erneut versuchen.', 409);
}
$result = null;
$error = null;
$code = 422;
try {
    $action = (string) ($_POST['action'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM customer_email_series WHERE customer_id = ?');
    $stmt->execute([$id]);
    $current = $stmt->fetch() ?: null;
    if ($action === 'pause') {
        $pdo->prepare('UPDATE customer_email_series SET enabled = 0, next_run_at = NULL, revision = revision + 1, updated_at = UTC_TIMESTAMP() WHERE customer_id = ?')->execute([$id]);
    } else {
        if (!in_array($action, ['draft', 'activate', 'test'], true)) {
            throw new InvalidArgumentException('Bitte die Serie speichern oder aktivieren. Ein Upload kann das Serverlimit ueberschritten haben.');
        }
        if ($customer['deleted_at'] !== null || !$customer['eligible']) {
            throw new InvalidArgumentException('E-Mail-Serien sind nur fuer aktive Kunden mit unterschriebenem Vertrag verfuegbar.');
        }
        if ((int) ($_POST['revision'] ?? 0) !== (int) ($current['revision'] ?? 0)) {
            $code = 409;
            throw new InvalidArgumentException('Die Serie wurde zwischenzeitlich geaendert. Bitte das Fenster erneut oeffnen.');
        }
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $frequency = (string) ($_POST['frequency'] ?? 'monthly');
        $day = filter_var($_POST['scheduleDay'] ?? '', FILTER_VALIDATE_INT);
        $time = (string) ($_POST['sendTime'] ?? '09:00');
        if (mb_strlen($subject) > 190 || preg_match('/[\r\n]/', $subject) || strlen($body) > 50000) {
            throw new InvalidArgumentException('Betreff: maximal 190 Zeichen ohne Zeilenumbruch. Inhalt: maximal 50 KB.');
        }
        $recipientMode = (string) ($_POST['recipientMode'] ?? 'contract');
        $recipientEmail = $recipientMode === 'manual' ? trim((string) ($_POST['recipientEmail'] ?? '')) : null;
        $recipient = recurring_email_recipient(['recipient_mode' => $recipientMode, 'recipient_email' => $recipientEmail], $customer);
        $next = $action === 'test' ? null : recurring_email_next_run($frequency, (int) $day, $time, new DateTimeImmutable('now', new DateTimeZone('UTC')));
        $enabled = $action === 'activate';
        if (($enabled || $action === 'test') && ($subject === '' || $body === '')) {
            throw new InvalidArgumentException('Bitte Betreff und Inhalt angeben.');
        }
        $file = ['name' => $current['attachment_name'] ?? null, 'mime' => $current['attachment_mime'] ?? null,
            'content' => $current['attachment_content'] ?? null];
        if (($_POST['removeAttachment'] ?? '') === '1') {
            $file = ['name' => null, 'mime' => null, 'content' => null];
        }
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = recurring_email_attachment($_FILES['attachment']);
        }
        if ($action === 'test') {
            if (time() - (int) ($_SESSION['series_test_at'][$id] ?? 0) < 30) {
                throw new InvalidArgumentException('Bitte vor einer weiteren Test-E-Mail 30 Sekunden warten.');
            }
            $smtp = $pdo->query('SELECT * FROM mailbox_settings WHERE id = 1')->fetch();
            if (!$smtp || empty($smtp['host']) || empty($smtp['username']) || empty($smtp['password_encrypted'])) {
                throw new InvalidArgumentException('Bitte zuerst das E-Mail-Versandkonto einrichten.');
            }
            $testSeries = ['subject' => $subject, 'body' => $body, 'attachment_name' => $file['name'],
                'attachment_mime' => $file['mime'], 'attachment_content' => $file['content']];
            $_SESSION['series_test_at'][$id] = time();
            try {
                $message = recurring_email_message($pdo, $smtp, $testSeries);
                $mailer = new SmtpMailer($smtp['host'], (int) $smtp['smtp_port'], $smtp['smtp_encryption'],
                    $smtp['username'], decrypt_secret($smtp['password_encrypted']));
                recurring_email_deliver($mailer, $smtp, $customer, $testSeries, $message, $recipient, true);
            } catch (Throwable $exception) {
                error_log('Test-E-Mail-Serie ' . $id . ': ' . $exception->getMessage());
                throw new InvalidArgumentException('Testversand nicht sicher bestaetigt. Bitte Postfach und SMTP-Einstellungen pruefen, bevor Sie erneut senden.');
            }
            $result = ['testSent' => true, 'recipient' => $recipient];
        } else {
            // Editing content does not move an already-active due date into the future.
            if ($enabled && !empty($current['enabled']) && $frequency === $current['frequency']
                && (int) $day === (int) $current['schedule_day'] && $time === $current['send_time'] && $current['next_run_at']) {
                $next = $current['next_run_at'];
            }
            $params = [$subject, $body, $frequency, (int) $day, $time, (int) $enabled, $enabled ? $next : null,
                $file['name'], $file['mime'], $file['content'], $recipientMode, $recipientEmail, $id];
            if ($current) {
                $pdo->prepare('UPDATE customer_email_series SET subject = ?, body = ?, frequency = ?, schedule_day = ?, send_time = ?,
                    enabled = ?, next_run_at = ?, attachment_name = ?, attachment_mime = ?, attachment_content = ?, recipient_mode = ?, recipient_email = ?,
                    last_error = NULL, revision = revision + 1, updated_at = UTC_TIMESTAMP() WHERE customer_id = ?')->execute($params);
            } else {
                $pdo->prepare('INSERT INTO customer_email_series (subject, body, frequency, schedule_day, send_time, enabled,
                    next_run_at, attachment_name, attachment_mime, attachment_content, recipient_mode, recipient_email, customer_id, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())')->execute($params);
            }
        }
    }
    $result ??= recurring_email_state($pdo, $customer);
} catch (InvalidArgumentException $exception) {
    $error = $exception->getMessage();
} finally {
    recurring_email_unlock($pdo, $id);
}
if ($error !== null) {
    json_error($error, $code);
}
json_response($result + ['csrfToken' => $_SESSION['email_series_csrf']]);
