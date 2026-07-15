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
    'agb_url' => 'https://cleanteam-group.com/agb/',
];

const VAT_RATE = 19.0;
const PAYMENT_DUE_DAYS = 5;
const DEFAULT_AFTER_DAYS = 30;
const PRICE_ADJUSTMENT_NOTICE_MONTHS = 1;
const WAGE_INCREASE_MIN_PERCENT = 5;
const WAGE_INCREASE_MAX_PERCENT = 10;

// Standard-Leistungsverzeichnis aus der bestehenden Vertragsvorlage (nicht kundenspezifisch,
// wird unverändert in jeden generierten Vertrag übernommen).
const STANDARD_SERVICE_CATALOG = [
    'Eingangsbereich und Glasflächen' => [
        'Saugen der Bodenflächen im Eingangsbereich.',
        'Kontrolle des Eingangsbereichs auf Spinnweben und umgehende Beseitigung.',
        'Entfernung von Fingerabdrücken und Verschmutzungen an den Glastüren im Eingangsbereich.',
    ],
    'Büro- und Allgemeinflächen' => [
        'Entstaubung aller frei zugänglichen Einrichtungsgegenstände, soweit diese ohne Verrücken von Gegenständen erreichbar sind.',
        'Entleerung sämtlicher Papierkörbe und Abfallbehälter in den Büroräumen sowie Einsetzen neuer Müllbeutel.',
        'Entstaubung frei zugänglicher Schreibtischflächen. Befinden sich Arbeitsunterlagen auf den Flächen oder wird der Arbeitsplatz während der Reinigung genutzt, erfolgt dort keine Reinigung.',
        'Gründliches Saugen sämtlicher Bodenflächen sowie Feuchtreinigung aller Hartbodenflächen ohne Teppichbelag.',
        'Entstaubung sämtlicher Türen und Türrahmen.',
        'Reinigung und Entstaubung frei zugänglicher Fensterbänke. Bei belegten Fensterbänken werden nur erreichbare Bereiche gereinigt; vollständig belegte Fensterbänke bleiben unberücksichtigt.',
        'Entstaubung von Regalen nach vorheriger Abstimmung bzw. auf Anweisung.',
        'Entstaubung der Stuhlgestelle und Stuhlfüße im 14-tägigen Turnus.',
    ],
    'Gästezimmer, Sanitärbereiche und Verbrauchsmaterial' => [
        'Kontrolle der Gästezimmer nach Bedarf einschließlich Entstaubung, Bodenreinigung und Feuchtwischen der Bodenflächen.',
        'Hygienische Reinigung sämtlicher Sanitäranlagen gemäß den geltenden Hygienevorschriften.',
        'Nachfüllen von Handtuchpapier, Toilettenpapier und Seifenspendern, sofern das Verbrauchsmaterial vor Ort bereitgestellt wird.',
    ],
    'Küchen, Pausenraum und Kantine' => [
        'Reinigung der Küchenzeilen ausschließlich von außen, einschließlich Arbeitsflächen und Spüle.',
        'Reinigung sämtlicher Tische im Pausenraum.',
        'Reinigung der Teeküche im Pausenraum einschließlich Spüle sowie Entfernung von Kaffee- und sonstigen Gebrauchsspuren an den Außenflächen der Küchenmöbel.',
        'Saugen und Feuchtwischen des Bodens im Pausenraum.',
        'Reinigung der Demonstrationsküche in der Kantine nach vorheriger Abstimmung und entsprechend den vom Objektleiter festgelegten Bereichen, einschließlich Küchenflächen sowie Saugen und Feuchtwischen des Bodens.',
    ],
    'Aufzug, Treppenhaus und Sonderbereiche' => [
        'Kontrolle des Aufzugs auf Spinnweben und deren Beseitigung; Reinigung des Aufzugbodens durch Saugen und Feuchtwischen.',
        'Wöchentliche Reinigung des Treppenhauses einschließlich Flure durch Kehren bzw. Saugen sowie anschließende Feuchtreinigung.',
        'Laufende Kontrolle sämtlicher Bereiche auf Spinnweben und deren sofortige Beseitigung.',
        'Wöchentliche gründliche Reinigung des Technik- bzw. Elektroraums in der Produktion, soweit die Bereiche zugänglich sind.',
    ],
    'Geschäftsführerbüro' => [
        'Saugen der Bodenflächen.',
        'Reinigung des Glastisches und der frei zugänglichen Schreibtischflächen.',
        'Entfernung von Fingerabdrücken auf Glasflächen.',
        'Entstaubung der Fensterbänke.',
        'Beseitigung vorhandener Spinnweben.',
        'Reinigung der Glastüren einschließlich Entfernung von Fingerabdrücken.',
    ],
];

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
    $filename = $row['logo_filename'] ?? null;
    if ($filename === null || $filename === '') {
        return '';
    }

    $url = h(base_url() . '/uploads/' . rawurlencode($filename));

    return '<img src="' . $url . '" alt="Logo" class="doc-logo">';
}

function render_service_catalog_html(): string
{
    $html = '';
    foreach (STANDARD_SERVICE_CATALOG as $category => $items) {
        $html .= '<h4>' . h($category) . '</h4><ul>';
        foreach ($items as $item) {
            $html .= '<li>' . h($item) . '</li>';
        }
        $html .= '</ul>';
    }

    return $html;
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
    <dt>Kostenvoranschlag erstellt</dt><dd>{$offerCreatedAt}</dd>
    <dt>Vertrag erstellt</dt><dd>{$contractCreatedAt}</dd>
    <dt>Vertrag elektronisch signiert</dt><dd>{$signedAtDisplay}</dd>
    <dt>AGB / Vertragsbedingungen zugestimmt</dt><dd>{$termsAcceptedDisplay}</dd>
    <dt>Zeitpunkt der Zustimmung</dt><dd>{$termsAcceptedTime}</dd>
    <dt>AGB-Fassung</dt><dd>{$agbVersion}</dd>
    <dt>AGB-Quelle</dt><dd>{$agbUrl}</dd>
    {$authorizationRows}
  </dl>
</section>
HTML;
}

function ensure_contract_template_settings_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS contract_template_settings (
            id TINYINT UNSIGNED NOT NULL,
            template_html LONGTEXT NOT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

// Startwert des Mustervertrags: der bisherige, fest verdrahtete Vertragstext (§ 1 - § 9),
// jetzt mit {{platzhalter}}-Tokens statt PHP-Interpolation. Wird beim ersten Aufruf in
// contract_template_settings gespeichert und ist danach ueber die Einstellungen editierbar.
function default_contract_template_html(): string
{
    $serviceCatalogHtml = render_service_catalog_html();
    $obligationsHtml = render_obligations_html();

    return <<<HTML
<h2>§ 1 Beginn, Rechtswahl und Vertragssprache</h2>
<p>
  1. Der Vertrag zur Gebäudereinigung tritt am <strong>{{beginn_datum}}</strong> in Kraft.<br>
  2. Für sämtliche Rechtsbeziehungen der Parteien gilt ausschließlich das Recht der Bundesrepublik Deutschland unter Ausschluss
  aller kollisionsrechtlichen Bestimmungen, die in eine andere Rechtsordnung verweisen. Die Vertragssprache ist Deutsch.
</p>

<h2>§ 2 Vertragsgegenstand und Objekt</h2>
<p>
  Vertragsgegenstand sind die in § 3 genannten Reinigungsarbeiten für das nachfolgend näher bezeichnete Objekt:<br>
  Leistungsort: {{leistungsort}}
</p>

<h2>§ 3 Art und Umfang der Reinigung</h2>
<p>Die Reinigung findet <strong>{{intervall}}</strong> statt.</p>
<p>Gebuchte Leistung: {{leistung}}</p>
{{zusatzhinweis_block}}
{$serviceCatalogHtml}
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

function get_contract_template_html(PDO $pdo): string
{
    ensure_contract_template_settings_table($pdo);
    $stmt = $pdo->query('SELECT template_html FROM contract_template_settings WHERE id = 1');
    $row = $stmt->fetch();

    return $row && trim((string) $row['template_html']) !== '' ? (string) $row['template_html'] : default_contract_template_html();
}

function save_contract_template_html(PDO $pdo, string $html): void
{
    ensure_contract_template_settings_table($pdo);
    $stmt = $pdo->prepare(
        'INSERT INTO contract_template_settings (id, template_html, updated_at)
         VALUES (1, :html, UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE template_html = :html2, updated_at = UTC_TIMESTAMP()'
    );
    $stmt->execute(['html' => $html, 'html2' => $html]);
}

// Fuer die Platzhalter-Palette in den Einstellungen: Gruppe, Token, Beschreibung.
function contract_template_placeholder_definitions(): array
{
    return [
        'Firma / Kunde' => [
            'kunde_name' => 'Name des Auftraggebers',
            'kunde_adresse' => 'Straße und Hausnummer des Kunden',
            'kunde_ort' => 'PLZ und Ort des Kunden',
            'unterzeichner' => 'Name des Vertragsunterzeichners',
            'vertretung' => 'Vertretungshinweis',
            'logo' => 'Firmenlogo (nur in der HTML-Ansicht sichtbar)',
        ],
        'Leistung' => [
            'leistungsort' => 'Adresse des zu reinigenden Objekts',
            'intervall' => 'Reinigungsintervall',
            'leistung' => 'Leistungsbeschreibung inkl. Quadratmeter',
            'zusatzhinweis_block' => 'Zusatzhinweis aus dem Kostenvoranschlag (falls vorhanden)',
            'beginn_datum' => 'Vertragsbeginn',
        ],
        'Preis' => [
            'preis_netto' => 'Monatspreis netto',
            'preis_brutto' => 'Monatspreis brutto',
            'ust_satz' => 'Umsatzsteuersatz in Prozent',
            'zahlungsfrist_tage' => 'Zahlungsfrist in Arbeitstagen',
            'verzug_tage' => 'Tage bis Verzugszinsen',
            'lohn_min' => 'Untere Lohnsteigerung in Prozent',
            'lohn_max' => 'Obere Lohnsteigerung in Prozent',
            'ankuendigung_monate' => 'Ankündigungsfrist für Preisanpassungen in Monaten',
        ],
        'Rechtliches' => [
            'vertragsnummer' => 'Vertragsnummer',
            'gerichtsstand' => 'Gerichtsstand',
            'agb_version' => 'AGB-Version',
            'agb_url' => 'AGB-Link',
        ],
    ];
}

function contract_template_placeholder_map(array $offer, array $customer, ?array $contract, bool $forPdf = false): array
{
    $netPrice = (float) $offer['price'];
    $vatAmount = round($netPrice * VAT_RATE / 100, 2);
    $grossPrice = round($netPrice + $vatAmount, 2);
    $effectiveDate = contract_format_date($offer['start_date'] ?? $offer['created_at']);
    $customerAddress = trim((string) $customer['address'] . ' ' . (string) $customer['house_number']);
    $customerZipCity = trim((string) $customer['zip'] . ' ' . (string) $customer['city']);
    $offerNotes = trim((string) ($offer['notes'] ?? ''));

    $authorized = isset($contract['authorized']) && $contract['authorized'] !== null ? (bool) $contract['authorized'] : null;
    $representationNote = $contract['representation_note'] ?? null;
    $authorityText = $authorized === false && $representationNote
        ? (string) $representationNote
        : 'ohne gesonderte Vertretungsangabe';

    $values = [
        'vertragsnummer' => (string) ($contract['number'] ?? 'Entwurf'),
        'beginn_datum' => $effectiveDate,
        'leistungsort' => $customerAddress . ', ' . $customerZipCity,
        'intervall' => (string) $offer['interval_label'],
        'leistung' => (string) $offer['service'] . ' (' . (int) $offer['square_meters'] . ' m² Reinigungsfläche)',
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
  body { font-family: Georgia, "Times New Roman", serif; max-width: 780px; margin: 40px auto; padding: 0 24px; color: #1a1a1a; line-height: 1.55; }
  h1 { font-size: 24px; margin-bottom: 4px; }
  h2 { font-size: 16px; margin-top: 32px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
  h4 { font-size: 14px; margin: 18px 0 4px; }
  .doc-logo { max-height: 64px; max-width: 260px; margin-bottom: 16px; }
  .meta-bar { font-size: 13px; color: #555; margin-bottom: 24px; }
  .doc-label { display: inline-block; margin: 8px 0 0; padding: 3px 8px; border: 1px solid #bbb; border-radius: 4px; color: #444; font-size: 12px; font-family: Arial, sans-serif; }
  .parties { display: flex; gap: 40px; margin: 24px 0; }
  .party { flex: 1; }
  .party small { color: #666; text-transform: uppercase; letter-spacing: 0.04em; }
  ul, ol { padding-left: 20px; }
  ul li, ol.obligations li { margin-bottom: 4px; }
  .sign-block { display: flex; gap: 40px; margin-top: 40px; }
  .sign-col { flex: 1; border-top: 1px solid #333; padding-top: 8px; }
  .sign-placeholder { color: #999; font-style: italic; }
  .signature-protocol { page-break-before: always; margin-top: 48px; padding-top: 20px; border-top: 2px solid #333; }
  .protocol-grid { display: grid; grid-template-columns: 220px 1fr; gap: 8px 18px; margin-top: 16px; font-family: Arial, sans-serif; font-size: 13px; }
  .protocol-grid dt { font-weight: 700; color: #333; }
  .protocol-grid dd { margin: 0; }
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

    $managingDirectorsHtml = implode('<br>', array_map('h', CONTRACTOR['managing_directors']));
    $contractorLegalName = h(CONTRACTOR['legal_name']);
    $contractorTrade = h(CONTRACTOR['trade_description']);
    $contractorStreet = h(CONTRACTOR['street']);
    $contractorZipCity = h(CONTRACTOR['postal_code'] . ' ' . CONTRACTOR['city'] . ', ' . CONTRACTOR['country']);
    $contractorServicePoint = h(CONTRACTOR['service_point_street'] . ', ' . CONTRACTOR['service_point_postal_code'] . ' ' . CONTRACTOR['service_point_city']);
    $contractorServicePointCity = h(CONTRACTOR['service_point_city']);

    $customerName = h(contract_customer_display_name($customer));
    $signatoryName = h(contract_signatory_display($customer));
    $customerAddress = h($customer['address'] . ' ' . $customer['house_number']);
    $customerZipCity = h($customer['zip'] . ' ' . $customer['city']);

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
<h1>Gebäudereinigungsvertrag</h1>
<div class="doc-label">{$documentLabel}</div>
<div class="meta-bar">
  Vertragsnummer: <strong>{$contractNumber}</strong> &nbsp;·&nbsp;
  Status: <strong>{$statusLabel}</strong> &nbsp;·&nbsp;
  Erstellt: {$createdAt}
</div>

<div class="parties">
  <div class="party">
    <small>Auftragnehmer</small>
    <p>
      <strong>{$contractorLegalName}</strong><br>
      {$contractorTrade}<br>
      Geschäftsführung: {$managingDirectorsHtml}<br>
      {$contractorStreet}, {$contractorZipCity}<br>
      Service Point: {$contractorServicePoint}
    </p>
  </div>
  <div class="party">
    <small>Auftraggeber</small>
    <p>
      <strong>{$customerName}</strong><br>
      {$customerAddress}<br>
      {$customerZipCity}<br>
      Vertragsunterzeichnung durch: {$signatoryName} ({$authorityText})
    </p>
  </div>
</div>

{$templateBody}

<div class="sign-block">
  <div class="sign-col">
    <div>{$contractorServicePointCity}, {$createdAt}</div>
    <div style="margin-top:12px;">Geschäftsführer:<br>{$managingDirectorsHtml}</div>
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
