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
            . '<p>mein Name ist Frau Seidler, ich bin Ihre Ansprechpartnerin bei CleanTeam. Vielen Dank für Ihr Interesse an unserem Angebot für ' . email_h($sampleName) . '.</p>'
            . '<p>Ihr Vertrag wird komplett online abgeschlossen. Sobald Sie unterschrieben haben, erhalten Sie den fertigen Vertrag automatisch als PDF per E-Mail.</p>'
            . '<h2 style="margin:24px 0 10px 0;color:#08325f;font-size:17px;">Online-Prozess</h2>'
            . '<p>Alle Informationen zum weiteren Ablauf finden Sie im Online-Prozess. Klicken Sie dazu einfach auf den folgenden Button:</p>'
            . email_button_html($sampleLink, 'Jetzt Vertrag online abschließen')
            . '<p style="color:#51657d;font-size:13px;">Der Button ist aus Datenschutzgründen nur einmal nutzbar und 14 Tage lang gültig, also bis zum ' . email_h($sampleValidUntil) . '. Danach verfällt er automatisch. Wurde er versehentlich schon einmal geöffnet, muss er erst wieder von uns freigegeben werden, bevor er erneut funktioniert.</p>';
        $title = 'Ihr Angebot von CleanTeam';
        $preheader = 'Bitte schließen Sie Ihren Vertrag jetzt online ab.';
        $signatureContext = 'offer';
        break;

    case 'reminder1':
        $bodyContent = '<p style="margin:0 0 14px 0;">Guten Tag ' . email_h($sampleContact) . ',</p>'
            . '<p>Ihr Vertrag mit CleanTeam für <strong>' . email_h($sampleName) . '</strong> liegt bereit, wurde bisher aber noch nicht digital unterschrieben. Wir würden uns freuen, wenn Sie den Vertragsabschluss zeitnah nachholen:</p>'
            . email_button_html($sampleLink, 'Jetzt Vertrag online abschließen')
            . '<p>Der Link ist noch bis zum ' . email_h($sampleValidUntil) . ' gültig.</p>';
        $title = 'Erinnerung an Ihren Vertrag';
        $preheader = 'Bitte schließen Sie Ihren Vertrag jetzt online ab.';
        $signatureContext = 'offer';
        break;

    case 'reminder2':
        $bodyContent = '<p style="margin:0 0 14px 0;">Guten Tag ' . email_h($sampleContact) . ',</p>'
            . '<p>Wir möchten Sie freundlich daran erinnern, dass der Vertrag für <strong>' . email_h($sampleName) . '</strong> noch auf Ihre digitale Unterschrift wartet. Damit alles reibungslos weitergeht, bitten wir Sie, den Abschluss zeitnah vorzunehmen:</p>'
            . email_button_html($sampleLink, 'Jetzt Vertrag online abschließen')
            . '<p>Der Link ist noch bis zum ' . email_h($sampleValidUntil) . ' gültig, danach verfällt er automatisch.</p>';
        $title = 'Erinnerung an Ihren Vertrag';
        $preheader = 'Bitte schließen Sie Ihren Vertrag jetzt online ab.';
        $signatureContext = 'offer';
        break;

    case 'reminder3':
        $bodyContent = '<p style="margin:0 0 14px 0;">Guten Tag ' . email_h($sampleContact) . ',</p>'
            . '<p>Dies ist unsere letzte Erinnerung: Der Vertrag für <strong>' . email_h($sampleName) . '</strong> kann nur noch bis zum ' . email_h($sampleValidUntil) . ' digital unterschrieben werden. Bitte schließen Sie ihn bis dahin ab, damit der Link nicht verfällt:</p>'
            . email_button_html($sampleLink, 'Jetzt Vertrag online abschließen')
            . '<p>Gültig letztmalig bis zum ' . email_h($sampleValidUntil) . '.</p>';
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
