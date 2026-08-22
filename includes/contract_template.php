<?php

const CONTRACTOR = [
    'legal_name' => 'Clean Team Group SRLS',
    'trade_description' => 'Meisterbetrieb Gebäudereinigung',
    'managing_directors' => ['Thomas Mündlein', 'Riccardo Cuccaro'],
    'street' => 'Via Dorsale 11',
    'postal_code' => '54100',
    'city' => 'Massa',
    'country' => 'Italien',
    'service_point_street' => 'Ober der Mühle 30',
    'service_point_postal_code' => '42699',
    'service_point_city' => 'Solingen',
    'website' => 'https://cleanteam-group.com',
];

const LEGAL = [
    'jurisdiction_city' => 'Solingen',
    'agb_version' => 'Stand 28.07.2020',
    'agb_url' => 'https://cleanteam-solingen.de/agb/',
];

const VAT_RATE = 19.0;
const PAYMENT_DUE_DAYS = 5;
const DEFAULT_AFTER_DAYS = 30;
const PRICE_ADJUSTMENT_NOTICE_MONTHS = 1;
const WAGE_INCREASE_MIN_PERCENT = 5;
const WAGE_INCREASE_MAX_PERCENT = 10;

// Holt den aktuellen Textinhalt der AGB-Seite als Nachweis, was zum Zeitpunkt der Vertragserstellung
// dort stand (falls die Seite sich spaeter aendert). Wird einmalig bei Vertragserstellung aufgerufen
// und mit dem Angebot gespeichert - kein Live-Abruf bei jedem Dokumentenaufruf.
function fetch_agb_text_snapshot(): ?string
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 12,
            'ignore_errors' => true,
            'header' => "User-Agent: CleanTeam-Vertragsgenerator\r\n",
        ],
    ]);

    $html = @file_get_contents(LEGAL['agb_url'], false, $context);
    if ($html === false || trim($html) === '') {
        return null;
    }

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);

    // Navigation, Header, Footer und Skripte sind bei den meisten Seiten reine Menue-/Kontakt-
    // Wiederholungen und keine AGB-Klauseln - vor der Extraktion entfernen.
    foreach ($xpath->query('//script|//style|//nav|//header|//footer|//form|//svg|//noscript') as $node) {
        $node->parentNode?->removeChild($node);
    }

    // Die aktuelle AGB-Seite ist mit dem Divi-Page-Builder gebaut: Menue/Kontakt-CTAs werden dort
    // direkt im Seiteninhalt dupliziert (nicht in <nav>/<header>), waehrend der eigentliche
    // Rechtstext in "et_pb_text_inner"-Textmodulen steckt. Kurze Module (Buttons, Links,
    // Copyright-Zeile) sind eindeutig kein Vertragstext - nur laengere Bloecke uebernehmen.
    $textModuleHtml = '';
    $textModules = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' et_pb_text_inner ')]");
    if ($textModules !== false) {
        foreach ($textModules as $node) {
            if (mb_strlen(trim($node->textContent)) > 150) {
                $textModuleHtml .= ($doc->saveHTML($node) ?: '') . "\n";
            }
        }
    }

    if (trim($textModuleHtml) !== '') {
        $rawHtml = $textModuleHtml;
    } else {
        // Fallback fuer den Fall, dass die Seite nicht (mehr) mit Divi gebaut ist: den
        // erkennbaren Hauptinhaltsbereich nehmen, sonst den ganzen (bereits bereinigten) Body.
        $contentNode = null;
        foreach (['//main', "//*[@role='main']", "//article", "//*[contains(@class,'entry-content')]", "//*[@id='content']"] as $query) {
            $nodes = $xpath->query($query);
            if ($nodes !== false && $nodes->length > 0) {
                $contentNode = $nodes->item(0);
                break;
            }
        }
        $contentNode ??= $doc->getElementsByTagName('body')->item(0);
        if ($contentNode === null) {
            return null;
        }
        $rawHtml = $doc->saveHTML($contentNode) ?: '';
    }

    $rawHtml = preg_replace('#<(br|p|div|li|h[1-6])\b[^>]*>#i', "\n", $rawHtml) ?? $rawHtml;
    $text = html_entity_decode(strip_tags($rawHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Icon-Font-Zeichen (Private-Use-Area, z. B. Telefon-/Mail-Symbole) landen sonst als "?" im Text.
    $text = preg_replace('/[\x{E000}-\x{F8FF}\x{F0000}-\x{FFFFD}\x{100000}-\x{10FFFD}]/u', '', $text) ?? $text;
    $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

    // Direkt wiederholte identische Zeilen (z. B. doppelt eingebundenes Mobil-/Desktop-Menue) zusammenfassen.
    $lines = array_map('trim', explode("\n", $text));
    $deduped = [];
    foreach ($lines as $line) {
        if ($line === '' || (end($deduped) !== false && end($deduped) === $line)) {
            if ($line === '') {
                $deduped[] = '';
            }
            continue;
        }
        $deduped[] = $line;
    }
    $text = trim((string) preg_replace('/\n{3,}/', "\n\n", implode("\n", $deduped)));

    return $text !== '' ? $text : null;
}

// Pflichten des Auftraggebers (nicht kundenspezifisch), aus der Vertragsvorlage übernommen und
// durchgehend nummeriert (im Ausgangsdokument war die Nummerierung fehlerhaft/doppelt).
const CUSTOMER_OBLIGATIONS = [
    'Reklamationen sind ausschließlich über das Reklamationsportal auf unserer Website www.cleanteam-group.com im Kundenbereich unter Verwendung des Formulars „Reklamationen für Kunden“ einzureichen. Reklamationen über andere Kommunikationswege, insbesondere telefonisch, per E-Mail, WhatsApp oder sonstige Nachrichtendienste, werden nicht anerkannt und nicht bearbeitet. Der Auftraggeber ist verpflichtet, die erbrachte Leistung unmittelbar nach Öffnung bzw. Inbetriebnahme des Objekts zu kontrollieren und erkennbare Mängel unverzüglich über das Reklamationsportal anzuzeigen. Später eingehende Reklamationen sind ausgeschlossen, da eine ordnungsgemäße Überprüfung und Nachvollziehbarkeit der beanstandeten Leistung nach Ablauf einer gewissen Zeit regelmäßig nicht mehr möglich ist.',
    'Sollte ein gesetzlicher Feiertag auf einen regulären Arbeitstag fallen, findet an diesem Tag keine Arbeitsleistung statt und es erfolgt keine Nachholung. Die Pauschalvergütung bleibt davon unberührt.',
    'Um einen reibungslosen Ablauf der Reinigung zu gewährleisten, bitten wir Sie, Kartonagen ordnungsgemäß zu zerkleinern und in Müllsäcken zu entsorgen. Nur so kann eine fachgerechte Entsorgung erfolgen. Karton und Kartonagen werden nur dann von unserem Reinigungspersonal entsorgt, wenn sie zuvor zerkleinert und ordnungsgemäß in einem Müllsack bereitgestellt wurden.',
    'Wenn der Auftraggeber keine kostenfreie Parkmöglichkeit bereitstellt, berechnen wir anfallende Parkgebühren gesondert. Diese werden transparent auf der Rechnung ausgewiesen.',
    'Der Auftraggeber ist verpflichtet, sämtliche beweglichen Gegenstände, die eine ordnungsgemäße Reinigung beeinträchtigen oder einen unverhältnismäßigen Zeitaufwand verursachen, vor Beginn der Reinigungsarbeiten entsprechend vorzubereiten. Hierzu zählen insbesondere das Hochstellen von Stühlen, Spielzeugkisten sowie sonstigen beweglichen Einrichtungsgegenständen. Erfolgt dies nicht, wird die Reinigung ausschließlich im frei zugänglichen Bereich durchgeführt. Ein Anspruch auf Nachreinigung oder Reklamation hinsichtlich nicht zugänglicher Flächen ist in diesem Fall ausgeschlossen.',
    'Die Reinigungsleistungen werden im laufenden Betrieb erbracht. Für Verschmutzungen, die nach Abschluss der Reinigung durch Mitarbeiter, Kunden oder Dritte entstehen, übernimmt der Auftragnehmer keine Haftung; eine Nachreinigung ist nicht Bestandteil der vereinbarten Leistung.',
    'Die Unterhaltsreinigung umfasst keine Garantie zur vollständigen Entfernung hartnäckiger oder eingetrockneter Flecken. Soweit diese im Rahmen der vereinbarten Feuchtreinigung nicht beseitigt werden können, ist eine Haftung ausgeschlossen.',
    'Die Reinigung der WC-Fliesenwände ist nicht Bestandteil der vereinbarten Unterhaltsreinigung und bedarf einer gesonderten Beauftragung.',
    'Elektronische Geräte und Anlagen werden ausschließlich trocken mittels Staubwedel oder vergleichbarer trockener Reinigungsutensilien entstaubt. Eine Feuchtreinigung elektronischer Geräte erfolgt aus Haftungsgründen nicht. Für Schäden, die durch eine vom Auftraggeber gewünschte oder eigenmächtig veranlasste Feuchtreinigung entstehen, wird keine Haftung übernommen.',
    'Toilettenpapier, Handtücher und Papier sind nicht in der Pauschale enthalten. Gerne stellen wir diese Utensilien bereit und berechnen sie Ihnen direkt an. Gegenstände auf dem Boden oder auf Tischen werden von uns nicht entfernt. Es liegt in der Verantwortung des Auftraggebers, die betreffenden Bereiche freizuräumen, damit eine gründliche Reinigung durchgeführt werden kann. Andernfalls reinigen wir lediglich um die vorhandenen Gegenstände herum.',
];

function contract_customer_display_name(array $customer): string
{
    return trim((string) $customer['name']);
}

function contract_signatory_display(array $customer): string
{
    $salutation = trim((string) ($customer['salutation'] ?? ''));
    $lastName = trim((string) ($customer['contact_last_name'] ?? ''));

    return trim($salutation . ' ' . $lastName);
}

function contract_contractor_signature_name(): string
{
    return (string) (CONTRACTOR['managing_directors'][0] ?? 'Thomas Mündlein');
}

function contract_format_date(?string $isoOrMysqlDate): string
{
    if ($isoOrMysqlDate === null || $isoOrMysqlDate === '') {
        return '–';
    }

    $timestamp = strtotime($isoOrMysqlDate);
    if ($timestamp === false) {
        return '–';
    }

    return gmdate('d.m.Y', $timestamp);
}

function contract_format_datetime(?string $isoOrMysqlDate): string
{
    if ($isoOrMysqlDate === null || $isoOrMysqlDate === '') {
        return '–';
    }

    $endsWithZulu = substr($isoOrMysqlDate, -1) === 'Z';
    $timestamp = strtotime($isoOrMysqlDate . ($endsWithZulu ? '' : ' UTC'));
    if ($timestamp === false) {
        return '–';
    }

    $date = new DateTimeImmutable('@' . $timestamp);
    $date = $date->setTimezone(new DateTimeZone('Europe/Berlin'));

    return $date->format('d.m.Y H:i') . ' Uhr';
}

function contract_format_money(float $amount): string
{
    return number_format($amount, 2, ',', '.') . ' €';
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Absolute URL, damit das Logo auch im per E-Mail verschickten HTML-Anhang lädt (dort gibt es
// keinen Server-Kontext für einen relativen Pfad).
function contract_logo_html(): string
{
    $row = db()->query('SELECT logo_filename FROM branding_settings WHERE id = 1')->fetch();
    $filename = trim((string) ($row['logo_filename'] ?? ''));

    if ($filename !== '' && is_file(__DIR__ . '/../uploads/' . $filename)) {
        $url = h(base_url() . '/uploads/' . rawurlencode($filename));
        return '<img src="' . $url . '" alt="Logo" class="doc-logo">';
    }

    if (is_file(branding_default_logo_disk_path())) {
        $url = h(base_url() . '/' . branding_default_logo_relative_path());
        return '<img src="' . $url . '" alt="Logo" class="doc-logo">';
    }

    return '';
}

function render_obligations_html(): string
{
    $html = '<ol class="obligations">';
    foreach (CUSTOMER_OBLIGATIONS as $item) {
        $html .= '<li>' . h($item) . '</li>';
    }
    $html .= '</ol>';

    return $html;
}

function render_signature_protocol_html(array $offer, array $customer, ?array $contract): string
{
    $isSigned = $contract !== null && $contract['status'] === 'signiert';
    $signedAt = $contract['signed_at'] ?? null;
    $termsAcceptedAt = $contract['terms_accepted_at'] ?? null;
    $termsAccepted = $termsAcceptedAt !== null || $isSigned;
    $termsAcceptedDisplay = $termsAccepted ? 'Ja, Zustimmung erteilt' : 'Noch nicht bestätigt';
    $termsAcceptedTime = contract_format_datetime($termsAcceptedAt ?: ($isSigned ? $signedAt : null));
    $privacyAcceptedAt = $contract['privacy_accepted_at'] ?? null;
    $privacyAcceptedDisplay = $privacyAcceptedAt !== null ? 'Ja, Zustimmung erteilt' : 'Noch nicht bestätigt';
    $privacyAcceptedTime = contract_format_datetime($privacyAcceptedAt);
    $signedAtDisplay = contract_format_datetime($signedAt);
    $contractNumber = h($contract['number'] ?? 'Entwurf');
    $customerName = h(contract_customer_display_name($customer));
    $signatoryName = h(contract_signatory_display($customer));
    $offerCreatedAt = contract_format_datetime($offer['created_at'] ?? null);
    $contractCreatedAt = contract_format_datetime($contract['created_at'] ?? null);
    $agbVersion = h(LEGAL['agb_version']);
    $agbUrl = h(LEGAL['agb_url']);
    $authorizationGrantor = trim((string) ($contract['authorization_grantor_name'] ?? ''));
    $authorizationAddress = trim((string) ($contract['authorization_company_address'] ?? ''));
    $authorizationRows = '';
    if (($contract['authorized'] ?? null) !== null && (int) $contract['authorized'] === 0 && $authorizationGrantor !== '' && $authorizationAddress !== '') {
        $authorizationRows = '<dt>Vollmachtgeber</dt><dd>' . h($authorizationGrantor) . '</dd>'
            . '<dt>Vollmacht-Adresse</dt><dd>' . h($authorizationAddress) . '</dd>';
    }

    $agbSnapshotBlock = '';
    $agbSnapshotText = trim((string) ($offer['agb_snapshot_text'] ?? ''));
    if ($agbSnapshotText !== '') {
        $agbSnapshotAt = contract_format_datetime($offer['agb_snapshot_captured_at'] ?? null);
        $agbSnapshotBlock = '<div class="agb-snapshot">'
            . '<h3>AGB-Inhalt zum Zeitpunkt der Vertragserstellung</h3>'
            . '<p class="muted">Automatisch von ' . $agbUrl . ' übernommener Textinhalt, abgerufen am ' . h($agbSnapshotAt)
            . ', als Nachweis für CleanTeam, falls sich die AGB später ändern sollten.</p>'
            . '<pre class="agb-snapshot-text">' . h($agbSnapshotText) . '</pre>'
            . '</div>';
    }

    return <<<HTML
<section class="signature-protocol">
  <h2>Signaturprotokoll / Nachweis für CleanTeam</h2>
  <p>
    Dieses Protokoll dokumentiert die elektronische Unterzeichnung der CleanTeam-Ausfertigung
    sowie die Zustimmung zu den Vertragsbedingungen und Allgemeinen Geschäftsbedingungen.
  </p>
  <dl class="protocol-grid">
    <dt>Vertragsnummer</dt><dd>{$contractNumber}</dd>
    <dt>Kunde</dt><dd>{$customerName}</dd>
    <dt>Unterzeichner</dt><dd>{$signatoryName}</dd>
    <dt>Vertragsentwurf erstellt</dt><dd>{$offerCreatedAt}</dd>
    <dt>Vertrag erstellt</dt><dd>{$contractCreatedAt}</dd>
    <dt>Vertrag elektronisch signiert</dt><dd>{$signedAtDisplay}</dd>
    <dt>Datenschutz-Zustimmung erteilt</dt><dd>{$privacyAcceptedDisplay}</dd>
    <dt>Zeitpunkt der Datenschutz-Zustimmung</dt><dd>{$privacyAcceptedTime}</dd>
    <dt>AGB / Vertragsbedingungen zugestimmt</dt><dd>{$termsAcceptedDisplay}</dd>
    <dt>Zeitpunkt der Zustimmung</dt><dd>{$termsAcceptedTime}</dd>
    <dt>AGB-Fassung</dt><dd>{$agbVersion}</dd>
    <dt>AGB-Quelle</dt><dd>{$agbUrl}</dd>
    {$authorizationRows}
  </dl>
  {$agbSnapshotBlock}
</section>
HTML;
}

function ensure_contract_template_settings_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS contract_template_settings (
            id TINYINT UNSIGNED NOT NULL,
            template_html LONGTEXT NOT NULL,
            contractor_signature_data LONGTEXT NULL,
            contractor_signature_updated_at DATETIME NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $columns = [
        'contractor_signature_data' => 'ALTER TABLE contract_template_settings ADD COLUMN contractor_signature_data LONGTEXT NULL AFTER template_html',
        'contractor_signature_updated_at' => 'ALTER TABLE contract_template_settings ADD COLUMN contractor_signature_updated_at DATETIME NULL AFTER contractor_signature_data',
    ];

    foreach ($columns as $column => $sql) {
        $stmt = $pdo->query("SHOW COLUMNS FROM contract_template_settings LIKE '{$column}'");
        if (!$stmt->fetch()) {
            $pdo->exec($sql);
        }
    }
}

// Startwert des Mustervertrags: der bisherige, fest verdrahtete Vertragstext (§ 1 - § 9),
// jetzt mit {{platzhalter}}-Tokens statt PHP-Interpolation. Wird beim ersten Aufruf in
// contract_template_settings gespeichert und ist danach ueber die Einstellungen editierbar.
function default_contract_template_html(): string
{
    $obligationsHtml = render_obligations_html();

    return <<<HTML
<h2>§ 1 Beginn, Rechtswahl, Vertragssprache</h2>
<p>
  1. Der Vertrag zur Gebäudereinigung tritt am <strong>{{beginn_datum}}</strong> in Kraft.<br>
  2. Für sämtliche Rechtsbeziehungen der Parteien gilt ausschließlich das Recht der Bundesrepublik Deutschland unter Ausschluss
  aller kollisionsrechtlichen Bestimmungen, die in eine andere Rechtsordnung verweisen. Die Vertragssprache ist Deutsch.
</p>

<h2>§ 2 Vertragsgegenstand und Objekt</h2>
<p>
  Vertragsgegenstand sind die in § 3 genannten Reinigungsarbeiten für das nachfolgend näher bezeichnete Objekt:
</p>
<p>
  <strong>Leistungsort:</strong><br>
  Ort: {{leistungsort_ort}}<br>
  Postleitzahl: {{leistungsort_plz}}<br>
  Straße: {{leistungsort_strasse}}
</p>

<h2>§ 3 Art und Umfang der Reinigung</h2>
<p>Die Reinigung findet <strong>{{intervall}}</strong> statt.</p>
<p>Gebuchte Leistung: {{leistung}}</p>
{{zusatzhinweis_block}}
<p>Leistungen, die nicht in diesem Vertrag aufgeführt sind, bedürfen einer gesonderten Beauftragung und werden zusätzlich berechnet.</p>

<h2>§ 4 Vergütung</h2>
<p>
  Die monatliche Pauschalvergütung beträgt <strong>{{preis_netto}} netto</strong> zuzüglich der jeweils geltenden Umsatzsteuer
  (aktuell {{ust_satz}}&nbsp;%). Der Bruttobetrag beträgt zum Zeitpunkt der Vertragserstellung <strong>{{preis_brutto}}</strong>.
  Leistungen außerhalb von § 3 sind separat zu beauftragen und werden zusätzlich berechnet. Rechnungen sind binnen
  {{zahlungsfrist_tage}} Arbeitstagen zu zahlen; nach {{verzug_tage}} Tagen fallen automatisch Verzugszinsen an.
</p>
<p>
  Bei einer Tariferhöhung in der Gebäudereinigung geben wir die entsprechenden Lohnsteigerungen an unser Personal und
  unsere Kunden weiter. Die Lohnkosten werden voraussichtlich um {{lohn_min}} bis {{lohn_max}} Prozent steigen. Unsere Kunden
  informieren wir mindestens {{ankuendigung_monate}} Monat(e) vorher und teilen den exakten Differenzbetrag mit, der aus Lohn-,
  Betriebs- und Materialkosten berechnet wird.
</p>
<p>
  <strong>Zahlungsverzug / Zurückbehaltungsrecht:</strong> Die vereinbarte Vergütung ist innerhalb von {{zahlungsfrist_tage}}
  Arbeitstagen nach Zugang der Rechnung ohne Abzug auf das von uns benannte Konto zu überweisen. Geht innerhalb dieser
  Frist kein vollständiger Zahlungseingang ein und erfolgt keine vorherige Mitteilung oder anderweitige Vereinbarung durch
  den Auftraggeber, sind wir berechtigt, von unserem Zurückbehaltungsrecht gemäß § 320 BGB Gebrauch zu machen und die
  vertraglich geschuldeten Reinigungsleistungen bis zum vollständigen Ausgleich der offenen Forderung auszusetzen.
  Die während der Ausübung des Zurückbehaltungsrechts ausfallenden Reinigungstermine gelten als vertraglich geschuldet
  und werden dem Auftraggeber vergütungsrechtlich berechnet. Ein Anspruch auf Nachholung der während dieses Zeitraums
  ausgefallenen Reinigungsleistungen besteht nicht.
</p>

<h2>§ 5 Pflichten des Auftraggebers</h2>
{$obligationsHtml}

<h2>§ 6 Vertraulichkeit</h2>
<p>
  Die Parteien vereinbaren, während der Laufzeit dieses Vertrags über die Geschäfts- und Betriebsgeheimnisse der jeweils
  anderen Partei, einschließlich dieses Vertrags, über jedes Know-how, jegliches von einer Partei ausgehändigte Material
  und sämtliche Informationen, die eine Partei über das Geschäft der jeweils anderen erhält ("vertrauliche Informationen"),
  vertraulich zu behandeln und nicht an Dritte weiterzugeben.
</p>

<h2>§ 7 Erfüllungsort, Gerichtsstand und Schlussbestimmungen</h2>
<p>
  1. Der Erfüllungsort richtet sich nach dem Sitz des Auftraggebers.<br>
  2. Ausschließlicher Gerichtsstand für Streitigkeiten im Zusammenhang mit dem Vertragsverhältnis ist <strong>{{gerichtsstand}}</strong>.<br>
  3. Für sämtliche Rechtsbeziehungen der Parteien gilt ausschließlich das Recht der Bundesrepublik Deutschland unter
  Ausschluss aller kollisionsrechtlichen Bestimmungen, die in eine andere Rechtsordnung verweisen.<br>
  4. Sollte eine Bestimmung dieses Vertrags unwirksam sein, wird die Wirksamkeit der übrigen Bestimmungen hiervon nicht
  berührt. Die Parteien werden über eine die unwirksame Bestimmung ersetzende Regelung verhandeln, die dem Inhalt der
  ursprünglichen Bestimmung möglichst nahekommt. Gleiches gilt für mögliche Vertragslücken.
</p>

<h2>§ 8 Einbeziehung der Allgemeinen Geschäftsbedingungen</h2>
<p>
  Es gelten zusätzlich die Allgemeinen Geschäftsbedingungen des Auftragnehmers. Die gültige Fassung mit
  <strong>{{agb_version}}</strong> ist auf der Website <strong>{{agb_url}}</strong> einsehbar. Auf Wunsch kann dem Auftraggeber
  eine Ausfertigung der Allgemeinen Geschäftsbedingungen auch postalisch zugesendet werden.
</p>

<h2>§ 9 Ausfertigungen und elektronische Dokumentation</h2>
<p>
  Beide Parteien erhalten eine Ausfertigung dieses Gebäudereinigungsvertrags. Bei elektronischer Unterzeichnung wird jeder
  Partei nach Abschluss des Signaturvorgangs eine Ausfertigung zur Verfügung gestellt.
</p>
HTML;
}

// Der Vertragstext ist nicht mehr ueber die Einstellungen anpassbar - es gibt nur noch den
// fest im Code hinterlegten Standardtext, damit er nicht unbemerkt vom aktuellen Stand abweicht.
function get_contract_template_html(PDO $pdo): string
{
    return default_contract_template_html();
}

function get_contract_template_contractor_signature_data(PDO $pdo): ?string
{
    ensure_contract_template_settings_table($pdo);
    $stmt = $pdo->query('SELECT contractor_signature_data FROM contract_template_settings WHERE id = 1');
    $row = $stmt->fetch();
    $signature = $row ? trim((string) ($row['contractor_signature_data'] ?? '')) : '';

    return $signature !== '' ? $signature : null;
}

function save_contract_template_contractor_signature_data(PDO $pdo, ?string $signatureDataUrl): void
{
    ensure_contract_template_settings_table($pdo);
    $templateHtml = get_contract_template_html($pdo);

    if ($signatureDataUrl === null) {
        $stmt = $pdo->prepare(
            'INSERT INTO contract_template_settings (id, template_html, contractor_signature_data, contractor_signature_updated_at, updated_at)
             VALUES (1, :html, NULL, NULL, UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE contractor_signature_data = NULL, contractor_signature_updated_at = NULL, updated_at = UTC_TIMESTAMP()'
        );
        $stmt->execute(['html' => $templateHtml]);
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO contract_template_settings (id, template_html, contractor_signature_data, contractor_signature_updated_at, updated_at)
         VALUES (1, :html, :signature, UTC_TIMESTAMP(), UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE contractor_signature_data = :signature2, contractor_signature_updated_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()'
    );
    $stmt->execute([
        'html' => $templateHtml,
        'signature' => $signatureDataUrl,
        'signature2' => $signatureDataUrl,
    ]);
}

function contract_template_placeholder_map(array $offer, array $customer, ?array $contract, bool $forPdf = false): array
{
    $netPrice = (float) $offer['price'];
    $vatAmount = round($netPrice * VAT_RATE / 100, 2);
    $grossPrice = round($netPrice + $vatAmount, 2);
    $effectiveDate = contract_format_date($offer['start_date'] ?? $offer['created_at']);
    $customerAddress = trim((string) $customer['address'] . ' ' . (string) $customer['house_number']);
    $customerZipCity = trim((string) $customer['zip'] . ' ' . (string) $customer['city']);
    $customerFullAddress = trim($customerAddress . ', ' . $customerZipCity, ', ');
    $offerNotes = trim((string) ($offer['notes'] ?? ''));

    $authorized = isset($contract['authorized']) && $contract['authorized'] !== null ? (bool) $contract['authorized'] : null;
    $representationNote = $contract['representation_note'] ?? null;
    $authorityText = $authorized === false && $representationNote
        ? (string) $representationNote
        : 'ohne gesonderte Vertretungsangabe';

    $values = [
        'vertragsnummer' => (string) ($contract['number'] ?? 'Entwurf'),
        'beginn_datum' => $effectiveDate,
        'leistungsort' => $customerFullAddress,
        'leistungsort_strasse' => $customerAddress,
        'leistungsort_plz' => (string) $customer['zip'],
        'leistungsort_ort' => (string) $customer['city'],
        'intervall' => (string) $offer['interval_label'],
        'leistung' => (int) $offer['square_meters'] > 0
            ? (string) $offer['service'] . ' (' . (int) $offer['square_meters'] . ' m² Reinigungsfläche)'
            : (string) $offer['service'],
        'preis_netto' => contract_format_money($netPrice),
        'preis_brutto' => contract_format_money($grossPrice),
        'ust_satz' => rtrim(rtrim(number_format(VAT_RATE, 1, ',', '.'), '0'), ','),
        'zahlungsfrist_tage' => (string) PAYMENT_DUE_DAYS,
        'verzug_tage' => (string) DEFAULT_AFTER_DAYS,
        'lohn_min' => (string) WAGE_INCREASE_MIN_PERCENT,
        'lohn_max' => (string) WAGE_INCREASE_MAX_PERCENT,
        'ankuendigung_monate' => (string) PRICE_ADJUSTMENT_NOTICE_MONTHS,
        'gerichtsstand' => LEGAL['jurisdiction_city'],
        'agb_version' => LEGAL['agb_version'],
        'agb_url' => LEGAL['agb_url'],
        'kunde_name' => contract_customer_display_name($customer),
        'kunde_adresse' => $customerAddress,
        'kunde_ort' => $customerZipCity,
        'unterzeichner' => contract_signatory_display($customer),
        'vertretung' => $authorityText,
    ];

    foreach ($values as $key => $value) {
        $values[$key] = h((string) $value);
    }

    $values['logo'] = $forPdf ? '' : contract_logo_html();
    $values['zusatzhinweis_block'] = $offerNotes !== ''
        ? '<p>' . ($forPdf ? '' : '<strong>Zusatzhinweis:</strong> ') . h($offerNotes) . '</p>'
        : '';

    return $values;
}

function render_contract_template_body(string $templateHtml, array $placeholders): string
{
    $map = [];
    foreach ($placeholders as $key => $value) {
        $map['{{' . $key . '}}'] = $value;
    }

    return strtr($templateHtml, $map);
}

// Wird sowohl vom echten Vertragsdokument als auch von der Mustervertrag-Vorschau in den
// Einstellungen verwendet, damit beide identisch aussehen.
function contract_document_style_css(): string
{
    return <<<CSS
  body { font-family: Calibri, Candara, Segoe, "Segoe UI", Optima, Arial, sans-serif; font-size: 14px; max-width: 720px; margin: 32px auto; padding: 0 32px; color: #1a1a1a; line-height: 1.4; }
  h1 { font-size: 15px; font-weight: 700; text-align: center; margin: 36px 0; }
  h2 { font-size: 14px; font-weight: 700; margin: 20px 0 8px; }
  h4 { font-size: 14px; font-weight: 700; margin: 16px 0 4px; }
  .doc-logo { max-height: 64px; max-width: 260px; margin-bottom: 16px; }
  .doc-meta { font-size: 11px; color: #777; margin-bottom: 8px; }
  .parties-intro { text-align: center; margin: 32px 0; }
  .party-text { margin: 28px 0 4px; }
  .party-designation { text-align: right; font-size: 13px; font-style: italic; margin: 0 0 32px; }
  ul, ol { padding-left: 20px; }
  ul li { margin-bottom: 4px; }
  ol.obligations li { margin-bottom: 14px; }
  .sign-block { display: flex; gap: 40px; margin-top: 40px; }
  .sign-col { flex: 1; }
  .sign-placeholder { color: #999; font-style: italic; }
  .signature-protocol { page-break-before: always; margin-top: 48px; padding-top: 20px; border-top: 2px solid #333; }
  .signature-protocol p { max-width: 680px; }
  .protocol-grid { display: grid; grid-template-columns: 260px 1fr; column-gap: 28px; row-gap: 10px; margin-top: 18px; font-family: Arial, sans-serif; font-size: 13px; line-height: 1.45; align-items: start; }
  .protocol-grid dt { font-weight: 700; color: #333; }
  .protocol-grid dd { margin: 0; overflow-wrap: anywhere; }
  .agb-snapshot { margin-top: 28px; page-break-inside: avoid; }
  .agb-snapshot h3 { font-size: 14px; margin: 0 0 4px; }
  .agb-snapshot-text { white-space: pre-wrap; font-family: Calibri, Arial, sans-serif; font-size: 11px; line-height: 1.5; border: 1px solid #ccc; border-radius: 4px; padding: 12px; margin-top: 8px; max-height: 900px; overflow: auto; }
  @media print { body { margin: 0; } }
CSS;
}

function render_contract_document(array $offer, array $customer, ?array $contract, array $options = []): string
{
    $audience = ($options['audience'] ?? 'customer') === 'cleanteam' ? 'cleanteam' : 'customer';
    $isCleanTeamCopy = $audience === 'cleanteam';

    $contractNumber = h($contract['number'] ?? 'Entwurf');
    $createdAt = contract_format_date($offer['created_at']);
    $customerCity = h($customer['city']);

    $authorized = isset($contract['authorized']) && $contract['authorized'] !== null ? (bool) $contract['authorized'] : null;
    $representationNote = $contract['representation_note'] ?? null;
    $authorityText = $authorized === false && $representationNote
        ? h($representationNote)
        : 'ohne gesonderte Vertretungsangabe';

    $isSigned = $contract !== null && $contract['status'] === 'signiert';
    $signedAt = $isSigned ? contract_format_date($contract['signed_at']) : '–';
    $signatureImage = $isSigned && !empty($contract['signature_data'])
        ? '<img src="' . h($contract['signature_data']) . '" alt="Unterschrift" style="max-height:70px;">'
        : '<span class="sign-placeholder">noch nicht unterschrieben</span>';
    $contractorSignatureName = h(contract_contractor_signature_name());
    $contractorSignatureDataUrl = get_contract_template_contractor_signature_data(db());
    $contractorSignatureImage = $contractorSignatureDataUrl !== null
        ? '<img src="' . h($contractorSignatureDataUrl) . '" alt="Unterschrift Thomas Mündlein" style="max-height:70px;">'
        : '<span class="sign-placeholder">CleanTeam-Unterschrift noch nicht hinterlegt</span>';

    $managingDirectorsInline = h(implode(', ', CONTRACTOR['managing_directors']));
    $contractorLegalName = h(CONTRACTOR['legal_name']);
    $contractorTrade = h(CONTRACTOR['trade_description']);
    $contractorStreetZipCity = h(CONTRACTOR['street'] . ' ' . CONTRACTOR['postal_code'] . ' ' . CONTRACTOR['city']);
    $contractorServicePoint = h(CONTRACTOR['service_point_street'] . ', ' . CONTRACTOR['service_point_postal_code'] . ' ' . CONTRACTOR['service_point_city']);
    $contractorServicePointCity = h(CONTRACTOR['service_point_city']);

    $customerName = h(contract_customer_display_name($customer));
    $signatoryName = h(contract_signatory_display($customer));
    $customerAddress = h(trim($customer['address'] . ' ' . $customer['house_number']));
    $customerZip = trim((string) $customer['zip']);
    $customerZipCity = h(($customerZip !== '' ? 'D-' . $customerZip : '') . ' ' . $customer['city']);
    $customerFullAddressInline = trim($customerAddress . ($customerAddress !== '' ? ', ' : '') . $customerZipCity, ', ');
    $authorityInline = $authorized === false && $representationNote ? ' (' . $authorityText . ')' : '';

    $statusLabel = $contract === null
        ? 'Entwurf (noch nicht gestartet)'
        : h(ucfirst(str_replace('_', ' ', (string) $contract['status'])));
    $documentLabel = $isCleanTeamCopy ? 'CleanTeam-Ausfertigung' : 'Kundenausfertigung';
    $protocolHtml = $isCleanTeamCopy ? render_signature_protocol_html($offer, $customer, $contract) : '';
    $logoHtml = contract_logo_html();

    $templateHtml = get_contract_template_html(db());
    $placeholders = contract_template_placeholder_map($offer, $customer, $contract, false);
    $templateBody = render_contract_template_body($templateHtml, $placeholders);
    $styleCss = contract_document_style_css();

    return <<<HTML
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Gebäudereinigungsvertrag {$contractNumber}</title>
<style>
{$styleCss}
</style>
</head>
<body>

{$logoHtml}
<div class="doc-meta">{$documentLabel} &nbsp;·&nbsp; Vertragsnummer {$contractNumber} &nbsp;·&nbsp; Status: {$statusLabel} &nbsp;·&nbsp; Erstellt: {$createdAt}</div>

<h1>Gebäudereinigungsvertrag</h1>

<p class="parties-intro">zwischen</p>

<p class="party-text">
  der <strong>{$contractorLegalName}</strong> {$contractorTrade}, Geschäftsführer: {$managingDirectorsInline}<br>
  {$contractorStreetZipCity}/Service Point: {$contractorServicePoint}
</p>
<p class="party-designation">- im Folgenden Auftragnehmer genannt -</p>

<p class="party-text">
  Die <strong>{$customerName}</strong>, {$customerFullAddressInline}, Vertragsunterzeichnung durch: {$signatoryName}{$authorityInline}
</p>
<p class="party-designation">- im Folgenden Auftraggeber genannt -</p>

<p>Der folgende Vertrag zur Gebäudereinigung wird abgeschlossen:</p>

{$templateBody}

<div class="sign-block">
  <div class="sign-col">
    <div>{$contractorServicePointCity}, {$createdAt}</div>
    <div style="margin-top:12px;">Im Namen von CleanTeam:<br>{$contractorSignatureName}</div>
    {$contractorSignatureImage}
  </div>
  <div class="sign-col">
    <div>{$customerCity}, {$signedAt}</div>
    <div style="margin-top:12px;">Vertragsunterzeichnung durch:<br>{$signatoryName}</div>
    {$signatureImage}
  </div>
</div>

{$protocolHtml}

</body>
</html>
HTML;
}
