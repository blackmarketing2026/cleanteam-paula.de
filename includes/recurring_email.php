<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/recurring_email_placeholders.php';

function ensure_recurring_email_tables(PDO $pdo): void
{
    $column = $pdo->query("SHOW COLUMNS FROM customers LIKE 'deleted_at'");
    if (!$column->fetch()) {
        $pdo->exec('ALTER TABLE customers ADD COLUMN deleted_at DATETIME NULL');
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_email_series (
        customer_id VARCHAR(64) NOT NULL PRIMARY KEY,
        subject VARCHAR(190) NOT NULL DEFAULT '', body TEXT NOT NULL,
        frequency VARCHAR(10) NOT NULL DEFAULT 'monthly', schedule_day TINYINT UNSIGNED NOT NULL DEFAULT 1,
        send_time CHAR(5) NOT NULL DEFAULT '09:00', enabled TINYINT(1) NOT NULL DEFAULT 0,
        next_run_at DATETIME NULL, last_sent_at DATETIME NULL, last_error VARCHAR(500) NULL,
        attachment_name VARCHAR(190) NULL, attachment_mime VARCHAR(100) NULL, attachment_content MEDIUMBLOB NULL,
        revision INT UNSIGNED NOT NULL DEFAULT 1, updated_at DATETIME NOT NULL,
        KEY idx_series_due (enabled, next_run_at),
        CONSTRAINT fk_series_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    foreach (['recipient_mode' => "VARCHAR(10) NOT NULL DEFAULT 'contract'", 'recipient_email' => 'VARCHAR(190) NULL'] as $name => $definition) {
        if (!$pdo->query("SHOW COLUMNS FROM customer_email_series LIKE '{$name}'")->fetch()) {
            $pdo->exec("ALTER TABLE customer_email_series ADD COLUMN {$name} {$definition}");
        }
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_email_series_runs (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, customer_id VARCHAR(64) NOT NULL,
        scheduled_at DATETIME NOT NULL, started_at DATETIME NOT NULL, completed_at DATETIME NULL,
        status VARCHAR(20) NOT NULL, recipient VARCHAR(190) NOT NULL, subject VARCHAR(190) NOT NULL,
        attachment_name VARCHAR(190) NULL, error_message VARCHAR(500) NULL,
        UNIQUE KEY uniq_series_occurrence (customer_id, scheduled_at),
        CONSTRAINT fk_series_run_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_email_scheduler (
        id TINYINT UNSIGNED NOT NULL PRIMARY KEY, last_run_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function recurring_email_next_run(string $frequency, int $day, string $time, DateTimeImmutable $after): string
{
    if (!in_array($frequency, ['weekly', 'monthly'], true)
        || $day < 1 || $day > ($frequency === 'weekly' ? 7 : 31)
        || !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $time)) {
        throw new InvalidArgumentException('Bitte einen gueltigen Versandplan angeben.');
    }
    $local = $after->setTimezone(new DateTimeZone('Europe/Berlin'));
    [$hour, $minute] = array_map('intval', explode(':', $time));
    if ($frequency === 'weekly') {
        $offset = ($day - (int) $local->format('N') + 7) % 7;
        $next = $local->modify("+{$offset} days")->setTime($hour, $minute);
        if ($next <= $local) {
            $offset += 7;
            $next = $local->modify("+{$offset} days")->setTime($hour, $minute);
        }
    } else {
        $month = $local->modify('first day of this month')->setTime($hour, $minute);
        $next = $month->setDate((int) $month->format('Y'), (int) $month->format('m'), min($day, (int) $month->format('t')));
        if ($next <= $local) {
            $month = $month->modify('first day of next month');
            $next = $month->setDate((int) $month->format('Y'), (int) $month->format('m'), min($day, (int) $month->format('t')));
        }
    }
    return $next->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}

function recurring_email_customer(PDO $pdo, string $id): ?array
{
    $stmt = $pdo->prepare("SELECT c.id, c.name, c.email, c.deleted_at,
        EXISTS(SELECT 1 FROM contracts ct WHERE ct.customer_id = c.id AND ct.status = 'signiert') AS eligible
        FROM customers c WHERE c.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function recurring_email_recipient(array $series, array $customer): string
{
    $mode = $series['recipient_mode'] ?? 'contract';
    if (!in_array($mode, ['contract', 'manual'], true)) {
        throw new InvalidArgumentException('Bitte eine gueltige Empfaengerauswahl treffen.');
    }
    $email = trim((string) ($mode === 'manual' ? ($series['recipient_email'] ?? '') : $customer['email']));
    if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Bitte eine gueltige Empfaenger-E-Mail-Adresse angeben.');
    }
    return $email;
}

function recurring_email_lock(PDO $pdo, string $customerId): bool
{
    $stmt = $pdo->prepare('SELECT GET_LOCK(?, 0)');
    $stmt->execute(['ct-series-' . substr(hash('sha256', $customerId), 0, 48)]);
    return (int) $stmt->fetchColumn() === 1;
}

function recurring_email_unlock(PDO $pdo, string $customerId): void
{
    $pdo->prepare('SELECT RELEASE_LOCK(?)')->execute(['ct-series-' . substr(hash('sha256', $customerId), 0, 48)]);
}

function recurring_email_state(PDO $pdo, array $customer): array
{
    $stmt = $pdo->prepare('SELECT customer_id, subject, body, frequency, schedule_day, send_time, enabled,
        next_run_at, last_sent_at, last_error, attachment_name, attachment_mime,
        OCTET_LENGTH(attachment_content) AS attachment_size, revision, recipient_mode, recipient_email FROM customer_email_series WHERE customer_id = ?');
    $stmt->execute([$customer['id']]);
    $series = $stmt->fetch() ?: null;
    $logs = $pdo->prepare('SELECT scheduled_at, started_at, completed_at, status, recipient, subject, attachment_name, error_message
        FROM customer_email_series_runs WHERE customer_id = ? ORDER BY id DESC LIMIT 10');
    $logs->execute([$customer['id']]);
    return ['customer' => ['id' => $customer['id'], 'name' => $customer['name'], 'email' => $customer['email']],
        'eligible' => $customer['deleted_at'] === null && (bool) $customer['eligible'],
        'series' => $series, 'runs' => $logs->fetchAll(),
        'placeholders' => recurring_email_placeholder_context($pdo, $customer['id']),
        'schedulerLastRunAt' => $pdo->query('SELECT last_run_at FROM customer_email_scheduler WHERE id = 1')->fetchColumn() ?: null];
}

function recurring_email_attachment(array $upload): array
{
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Der Anhang konnte nicht hochgeladen werden. Bitte Dateigroesse und Serverlimit pruefen.');
    }
    if (!is_uploaded_file($upload['tmp_name']) || filesize($upload['tmp_name']) > 5 * 1024 * 1024 || filesize($upload['tmp_name']) === 0) {
        throw new InvalidArgumentException('Der Anhang muss zwischen 1 Byte und 5 MB gross sein.');
    }
    $name = basename(str_replace('\\', '/', (string) $upload['name']));
    $name = preg_replace('/[\x00-\x1f\x7f]/', '', $name);
    if (mb_strlen($name) > 190) {
        throw new InvalidArgumentException('Der Dateiname darf maximal 190 Zeichen enthalten.');
    }
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['pdf' => ['application/pdf'], 'png' => ['image/png'], 'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'],
        'txt' => ['text/plain'], 'csv' => ['text/plain', 'text/csv', 'application/csv'],
        'docx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xlsx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
    if (!isset($allowed[$extension]) || !in_array($mime, $allowed[$extension], true)) {
        throw new InvalidArgumentException('Erlaubt sind PDF, DOCX, XLSX, PNG, JPG, TXT und CSV.');
    }
    if (in_array($extension, ['docx', 'xlsx'], true)) {
        $zip = new ZipArchive();
        if ($zip->open($upload['tmp_name']) !== true) {
            throw new InvalidArgumentException('Ungueltige Office-Datei.');
        }
        $valid = $zip->locateName($extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml') !== false;
        $zip->close();
        if (!$valid) {
            throw new InvalidArgumentException('Ungueltige Office-Datei.');
        }
        $mime = $allowed[$extension][1];
    }
    return ['name' => $name, 'mime' => $mime, 'content' => file_get_contents($upload['tmp_name'])];
}
