<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/email_template.php';

require_admin();
require_method('GET');

$pdo = db();

$type = trim((string) ($_GET['type'] ?? ''));

$settingsStmt = $pdo->query('SELECT * FROM mailbox_settings WHERE id = 1');
$smtp = $settingsStmt->fetch() ?: [];
$fromName = trim((string) ($smtp['from_name'] ?? '')) !== '' ? $smtp['from_name'] : 'CleanTeam';
$signatureText = (string) ($smtp['signature'] ?? '');

$sampleName = 'Max Mustermann GmbH';
$sampleContact = 'Herr Max Mustermann';
$sampleLink = '#vorschau-link';
$sampleValidUntil = (new DateTimeImmutable('+14 days'))->format('d.m.Y');

switch ($type) {
    case 'offer':
        $bodyContent = '<p style="margin:0 0 14px 0;">Guten Tag ' . email_h($sampleContact) . ',</p>'
            . '<p>vielen Dank für Ihr Interesse an CleanTeam. Ihr individueller Vertrag steht ab sofort online bereit.</p>'
            . '<p style="color:#51657d;font-size:13px;">Hinweis: Der Link zur Vertragsunterzeichnung kann aus Datenschutzgründen nur einmal verwendet werden. Klicken Sie bitte nur darauf, wenn Sie den Vertrag auch tatsächlich abschließen möchten. Wurde er versehentlich schon einmal geöffnet, muss er erst wieder von uns freigegeben werden, bevor er erneut funktioniert.</p>'
            . '<p>Bitte schließen Sie den Vertrag jetzt online ab – klicken Sie dazu einfach auf den folgenden Button:</p>'
            . email_button_html($sampleLink, 'Jetzt Vertrag online abschließen')
            . '<p>Der Link ist 14 Tage lang gültig, also bis zum ' . email_h($sampleValidUntil) . '. Danach verfällt er automatisch und kann nicht mehr verwendet werden.</p>';
        $title = 'Ihr Vertrag von CleanTeam';
        $preheader = 'Bitte schließen Sie Ihren Vertrag jetzt online ab.';
        $signatureContext = 'offer';
        break;

    case 'reminder1':
        $bodyContent = '<p style="margin:0 0 14px 0;">Guten Tag ' . email_h($sampleContact) . ',</p>'
            . '<p>Sie haben Ihren Vertrag von CleanTeam vor Kurzem erhalten – bisher wurde er noch nicht unterschrieben. Falls Sie das noch nicht geschafft haben, holen Sie es gern jetzt nach:</p>'
            . email_button_html($sampleLink, 'Jetzt Vertrag online abschließen')
            . '<p>Der Link ist gültig bis zum ' . email_h($sampleValidUntil) . '.</p>';
        $title = 'Erinnerung an Ihren Vertrag';
        $preheader = 'Bitte schließen Sie Ihren Vertrag jetzt online ab.';
        $signatureContext = 'offer';
        break;

    case 'reminder2':
        $bodyContent = '<p style="margin:0 0 14px 0;">Guten Tag ' . email_h($sampleContact) . ',</p>'
            . '<p>Ihr Vertrag von CleanTeam wartet weiterhin auf Ihre Unterschrift. Dies ist unsere letzte automatische Erinnerung – bitte schließen Sie den Vertrag zeitnah ab, damit der Link nicht verfällt:</p>'
            . email_button_html($sampleLink, 'Jetzt Vertrag online abschließen')
            . '<p>Der Link ist gültig bis zum ' . email_h($sampleValidUntil) . '.</p>';
        $title = 'Letzte Erinnerung an Ihren Vertrag';
        $preheader = 'Bitte schließen Sie Ihren Vertrag jetzt online ab.';
        $signatureContext = 'offer';
        break;

    case 'contract_customer':
        $bodyContent = '<p style="margin:0 0 14px 0;">Willkommen bei CleanTeam!</p>'
            . '<p>Mein Name ist ' . email_h($fromName) . '. Ich bin Ihr Ansprechpartner f&uuml;r Ihren Vertrag.</p>'
            . '<p>Sie finden Ihren unterschriebenen Vertrag im Anhang dieser E-Mail. Alternativ k&ouml;nnen Sie ihn auch jederzeit &uuml;ber den folgenden Button herunterladen:</p>'
            . email_button_html($sampleLink, 'Vertrag herunterladen')
            . '<p>Bei Fragen stehe ich Ihnen gerne zur Verf&uuml;gung &ndash; per E-Mail oder direkt telefonisch im B&uuml;ro.</p>';
        $title = 'Willkommen bei CleanTeam';
        $preheader = 'Willkommen bei CleanTeam.';
        $signatureContext = 'contract_customer';
        break;

    default:
        http_response_code(404);
        echo 'Unbekannter E-Mail-Typ.';
        exit;
}

$message = render_email_template_message($pdo, $bodyContent, [
    'title' => $title,
    'preheader' => $preheader,
    'fromName' => $fromName,
    'signatureText' => $signatureText,
    'signatureContext' => $signatureContext,
    'preview' => true,
]);

header('Content-Type: text/html; charset=utf-8');
echo $message['html'];
