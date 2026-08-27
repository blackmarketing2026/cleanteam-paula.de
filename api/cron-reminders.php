<?php

// Oeffentlicher Endpunkt (kein Login) fuer die automatischen Vertrags-Erinnerungen.
// Wird per Cronjob (z. B. All-Inkl KAS) zweimal taeglich aufgerufen, einmal pro Stufe:
//   .../api/cron-reminders.php?key=CRON_SECRET&stage=1   -> taeglich 08:00 Uhr
//   .../api/cron-reminders.php?key=CRON_SECRET&stage=2   -> taeglich 08:15 Uhr
//
// Ablauf: Angebot wird an Tag 0 verschickt. Ist es an einem der Folgetage um 08:00 Uhr
// immer noch nicht unterschrieben, geht Stufe-1-Erinnerung raus. Ist es danach an einem
// der Folgetage um 08:15 Uhr immer noch nicht unterschrieben, geht Stufe-2-Erinnerung raus.
// Die Kette stoppt endgueltig bei Unterschrift, expliziter Ablehnung durch den Kunden
// oder abgelaufenem Link - danach kommt keine weitere Erinnerung mehr.
//
// Geschuetzt ueber ein Secret in config.php ('cron_secret'), nicht ueber Login, da der
// Aufruf von aussen (Cronjob) ohne Session kommt.

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/crypto.php';
require_once __DIR__ . '/../includes/SmtpMailer.php';
require_once __DIR__ . '/../includes/email_template.php';
require_once __DIR__ . '/../includes/email_settings.php';

const REMINDER_STOP_STATUSES = ['signiert', 'daten_abgelehnt', 'intervall_abgelehnt', 'datenschutz_abgelehnt', 'berechtigung_abgelehnt'];

$configuredSecret = trim((string) (config()['cron_secret'] ?? ''));
$providedSecret = trim((string) ($_GET['key'] ?? ''));

if ($configuredSecret === '' || !hash_equals($configuredSecret, $providedSecret)) {
    json_error('Nicht autorisiert.', 403);
}

$stage = (string) ($_GET['stage'] ?? '');
if (!in_array($stage, ['1', '2'], true)) {
    json_error('Ungueltige Stufe. ?stage=1 oder ?stage=2 erwartet.', 422);
}

$pdo = db();
ensure_offers_reminder_columns($pdo);

$stopPlaceholders = implode(',', array_fill(0, count(REMINDER_STOP_STATUSES), '?'));

if ($stage === '1') {
    $sql = "SELECT o.*, c.name AS c_name, c.email AS c_email, c.salutation AS c_salutation, c.contact_last_name AS c_contact_last_name
            FROM offers o
            INNER JOIN customers c ON c.id = o.customer_id
            LEFT JOIN contracts ct ON ct.offer_id = o.id
            WHERE o.sent_at IS NOT NULL
              AND o.reminder1_sent_at IS NULL
              AND o.expires_at > UTC_TIMESTAMP()
              AND DATE(o.sent_at) < CURDATE()
              AND (ct.id IS NULL OR ct.status NOT IN ($stopPlaceholders))";
} else {
    $sql = "SELECT o.*, c.name AS c_name, c.email AS c_email, c.salutation AS c_salutation, c.contact_last_name AS c_contact_last_name
            FROM offers o
            INNER JOIN customers c ON c.id = o.customer_id
            LEFT JOIN contracts ct ON ct.offer_id = o.id
            WHERE o.sent_at IS NOT NULL
              AND o.reminder1_sent_at IS NOT NULL
              AND o.reminder2_sent_at IS NULL
              AND o.expires_at > UTC_TIMESTAMP()
              AND DATE(o.reminder1_sent_at) < CURDATE()
              AND (ct.id IS NULL OR ct.status NOT IN ($stopPlaceholders))";
}

$stmt = $pdo->prepare($sql);
$stmt->execute(REMINDER_STOP_STATUSES);
$offers = $stmt->fetchAll();

$result = ['stage' => (int) $stage, 'candidates' => count($offers), 'sent' => 0, 'skipped' => 0, 'errors' => []];

if ($offers === []) {
    json_response($result);
}

if (!email_delivery_is_allowed($pdo, 'offer')) {
    $result['skipped'] = count($offers);
    json_response($result);
}

$settings = $pdo->query('SELECT * FROM mailbox_settings WHERE id = 1')->fetch();
if (!$settings || $settings['host'] === '' || $settings['username'] === '' || ($settings['password_encrypted'] ?? '') === '') {
    $result['errors'][] = 'Kein E-Mail-Versand-Konto konfiguriert.';
    json_response($result);
}

$mailer = new SmtpMailer(
    $settings['host'],
    (int) $settings['smtp_port'],
    $settings['smtp_encryption'],
    $settings['username'],
    decrypt_secret($settings['password_encrypted'])
);

foreach ($offers as $offer) {
    $toEmail = trim((string) $offer['c_email']);
    if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        $result['skipped']++;
        continue;
    }

    $publicUrl = base_url() . '/offer.php?token=' . $offer['token'];
    $validUntil = (new DateTimeImmutable($offer['expires_at'], new DateTimeZone('UTC')))->format('d.m.Y');
    $contactName = trim($offer['c_salutation'] . ' ' . $offer['c_contact_last_name']);

    if ($stage === '1') {
        $intro = '<p>Sie haben Ihren Vertrag von CleanTeam vor Kurzem erhalten – bisher wurde er noch nicht unterschrieben. Falls Sie das noch nicht geschafft haben, holen Sie es gern jetzt nach:</p>';
        $subject = 'Erinnerung: Ihr Vertrag wartet noch auf Ihre Unterschrift';
        $title = 'Erinnerung an Ihren Vertrag';
    } else {
        $intro = '<p>Ihr Vertrag von CleanTeam wartet weiterhin auf Ihre Unterschrift. Dies ist unsere letzte automatische Erinnerung – bitte schließen Sie den Vertrag zeitnah ab, damit der Link nicht verfällt:</p>';
        $subject = 'Letzte Erinnerung: Ihr Vertrag wartet noch auf Ihre Unterschrift';
        $title = 'Letzte Erinnerung an Ihren Vertrag';
    }

    $bodyContent = '<p style="margin:0 0 14px 0;">Guten Tag ' . email_h($contactName) . ',</p>'
        . $intro
        . '<p style="margin:18px 0;"><a href="' . email_h($publicUrl) . '" style="display:inline-block;padding:12px 20px;background:#0a4f91;color:#ffffff;text-decoration:none;border-radius:7px;font-weight:700;">Jetzt Vertrag online abschließen</a></p>'
        . '<p>Der Link ist gültig bis zum ' . email_h($validUntil) . '.</p>'
        . '<img src="' . email_h(base_url() . '/api/track-open.php?token=' . $offer['token']) . '" width="1" height="1" alt="" style="display:none;width:1px;height:1px;border:0;" />';

    $message = render_email_template_message($pdo, $bodyContent, [
        'title' => $title,
        'preheader' => 'Bitte schließen Sie Ihren Vertrag jetzt online ab.',
        'fromName' => $settings['from_name'] ?? 'CleanTeam',
        'signatureText' => $settings['signature'] ?? '',
        'signatureContext' => 'offer',
    ]);

    try {
        $mailer->send(
            $settings['username'],
            $settings['from_name'],
            $toEmail,
            $offer['c_name'],
            $subject,
            $message['html'],
            true,
            $message['inlineImages']
        );

        $column = $stage === '1' ? 'reminder1_sent_at' : 'reminder2_sent_at';
        $pdo->prepare("UPDATE offers SET {$column} = UTC_TIMESTAMP() WHERE id = :id")
            ->execute(['id' => $offer['id']]);

        $result['sent']++;
    } catch (Throwable $exception) {
        $result['errors'][] = $offer['id'] . ': ' . $exception->getMessage();
        error_log('Vertrags-Erinnerung fehlgeschlagen (' . $offer['id'] . '): ' . $exception->getMessage());
    }
}

json_response($result);
