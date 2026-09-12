<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/crypto.php';
require_once __DIR__ . '/../includes/SmtpMailer.php';
require_once __DIR__ . '/../includes/email_template.php';
require_once __DIR__ . '/../includes/email_settings.php';
require_once __DIR__ . '/../includes/quote_pdf.php';

require_login(); require_method('POST');
$pdo = db(); ensure_offers_existing_contract_columns($pdo); ensure_quote_workflow_columns($pdo);
$sendAsQuote = defined('SEND_AS_QUOTE') && SEND_AS_QUOTE === true;
email_delivery_assert_allowed($pdo, 'offer');
$offerId = (string) ($_GET['id'] ?? '');
if ($offerId === '') json_error('Kostenvoranschlag fehlt.', 422);
$stmt = $pdo->prepare('SELECT o.*, c.name AS c_name, c.email AS c_email, c.salutation AS c_salutation, c.contact_last_name AS c_contact_last_name FROM offers o INNER JOIN customers c ON c.id = o.customer_id WHERE o.id = :id');
$stmt->execute(['id' => $offerId]); $offer = $stmt->fetch();
if (!$offer) json_error('Vertragsentwurf wurde nicht gefunden.', 404);
if ($sendAsQuote && ($offer['quote_status'] ?? 'entwurf') === 'accepted') json_error('Der Kostenvoranschlag wurde bereits angenommen.', 409);
if ($sendAsQuote && empty($offer['quote_token'])) {
    $offer['quote_token'] = generate_token();
    $pdo->prepare('UPDATE offers SET quote_token = :token WHERE id = :id')->execute(['token' => $offer['quote_token'], 'id' => $offerId]);
}
$input = read_json_body(); $toEmail = trim((string) ($input['toEmail'] ?? $offer['c_email']));
if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) json_error('Bitte eine gültige Ziel-E-Mail-Adresse eintragen.', 422);
$settings = $pdo->query('SELECT * FROM mailbox_settings WHERE id = 1')->fetch();
if (!$settings || $settings['host'] === '' || $settings['username'] === '' || ($settings['password_encrypted'] ?? '') === '') json_error('Bitte zuerst das E-Mail-Versand-Konto unter Einstellungen > E-Mails einrichten.', 422);
$publicUrl = base_url() . '/o.php?token=' . ($sendAsQuote ? $offer['quote_token'] : $offer['token']);
if ($sendAsQuote) $publicUrl .= '&start=online-contract';
$contact = trim($offer['c_salutation'] . ' ' . $offer['c_contact_last_name']); $company = trim((string) $offer['c_name']);
$validUntil = (new DateTimeImmutable($offer['expires_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Berlin'))->format('d.m.Y H:i');
$bodyContent = $sendAsQuote
    ? '<p>Guten Tag ' . email_h($contact) . ',</p><p>anbei erhalten Sie Ihren Kostenvoranschlag von CleanTeam für ' . email_h($company) . '.</p><p>Über den folgenden Button starten Sie den Online-Vertragsabschluss. Der Kostenvoranschlag gilt erst als angenommen, nachdem der Vertrag online unterschrieben wurde.</p>' . email_button_html($publicUrl, 'Kostenvoranschlag annehmen') . '<p style="color:#51657d;font-size:13px;">Der Link ist bis ' . email_h($validUntil) . ' Uhr gültig.</p>'
    : '<p>Guten Tag ' . email_h($contact) . ',</p><p>vielen Dank für Ihr Interesse an unserem Angebot für ' . email_h($company) . '.</p><p>Über den folgenden Link können Sie Ihren Vertrag wie gewohnt online prüfen und digital abschließen.</p>' . email_button_html($publicUrl, 'Jetzt Vertrag online abschließen') . '<p style="color:#51657d;font-size:13px;">Der Vertragslink ist bis ' . email_h($validUntil) . ' Uhr gültig.</p>';
$bodyContent .= '<img src="' . email_h(base_url() . '/api/track-open.php?token=' . $offer['token']) . '" width="1" height="1" alt="" style="display:none;width:1px;height:1px;border:0;" />';
$message = render_email_template_message($pdo, $bodyContent, ['title' => $sendAsQuote ? 'Ihr Kostenvoranschlag von CleanTeam' : 'Ihr Vertrag von CleanTeam', 'preheader' => $sendAsQuote ? 'Online-Vertrag ansehen und unterschreiben.' : 'Bitte schließen Sie Ihren Vertrag online ab.', 'fromName' => $settings['from_name'] ?? 'CleanTeam', 'signatureText' => $settings['signature'] ?? '', 'signatureContext' => 'offer']);
try {
    $mailer = new SmtpMailer($settings['host'], (int) $settings['smtp_port'], $settings['smtp_encryption'], $settings['username'], decrypt_secret($settings['password_encrypted']));
    if ($sendAsQuote) {
        $customerStmt = $pdo->prepare('SELECT * FROM customers WHERE id = :id'); $customerStmt->execute(['id' => $offer['customer_id']]);
        $quote = save_quote_pdf($pdo, $offer, $customerStmt->fetch());
        $mailer->sendWithAttachment($settings['username'], $settings['from_name'], $toEmail, $company, 'Kostenvoranschlag CleanTeam für Firma ' . $company, $message['html'], $quote['filename'], $quote['content'], 'application/pdf', $message['inlineImages']);
    } else {
        $mailer->send($settings['username'], $settings['from_name'], $toEmail, $company, 'Vertragsunterlagen CleanTeam für Firma ' . $company, $message['html'], true, $message['inlineImages']);
    }
} catch (Throwable $exception) { json_error('E-Mail-Versand fehlgeschlagen: ' . $exception->getMessage(), 502); }
$pdo->prepare($sendAsQuote ? "UPDATE offers SET quote_sent_at = UTC_TIMESTAMP(), quote_status = CASE WHEN quote_status = 'signing' THEN 'signing' ELSE 'sent' END WHERE id = :id" : 'UPDATE offers SET sent_at = UTC_TIMESTAMP() WHERE id = :id')->execute(['id' => $offerId]);
if (!$sendAsQuote) ensure_contract_for_offer($pdo, $offer);
json_response(['ok' => true, 'sentAt' => now_iso(), 'sentTo' => $toEmail]);
