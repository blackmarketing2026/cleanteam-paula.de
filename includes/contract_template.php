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

function render_contract_document(array $offer, array $customer, ?array $contract): string
{
    $netPrice = (float) $offer['price'];
    $vatAmount = round($netPrice * VAT_RATE / 100, 2);
    $grossPrice = round($netPrice + $vatAmount, 2);

    $contractNumber = h($contract['number'] ?? 'Entwurf');
    $effectiveDate = contract_format_date($offer['start_date'] ?? $offer['created_at']);
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
    $jurisdictionCity = h(LEGAL['jurisdiction_city']);
    $agbVersion = h(LEGAL['agb_version']);
    $agbUrl = h(LEGAL['agb_url']);

    $customerName = h(contract_customer_display_name($customer));
    $signatoryName = h(contract_signatory_display($customer));
    $customerAddress = h($customer['address'] . ' ' . $customer['house_number']);
    $customerZipCity = h($customer['zip'] . ' ' . $customer['city']);

    $offerInterval = h((string) $offer['interval_label']);
    $serviceLine = h((string) $offer['service']) . ' (' . (int) $offer['square_meters'] . ' m² Reinigungsfläche)';
    $offerNotes = trim((string) ($offer['notes'] ?? ''));
    $notesBlock = $offerNotes !== '' ? '<p><strong>Zusatzhinweis:</strong> ' . h($offerNotes) . '</p>' : '';
    $serviceCatalogHtml = render_service_catalog_html();
    $obligationsHtml = render_obligations_html();

    $netPriceFormatted = contract_format_money($netPrice);
    $grossPriceFormatted = contract_format_money($grossPrice);
    $vatRateFormatted = h(rtrim(rtrim(number_format(VAT_RATE, 1, ',', '.'), '0'), ','));

    $statusLabel = $contract === null
        ? 'Entwurf (noch nicht gestartet)'
        : h(ucfirst(str_replace('_', ' ', (string) $contract['status'])));

    $paymentDueDays = PAYMENT_DUE_DAYS;
    $defaultAfterDays = DEFAULT_AFTER_DAYS;
    $noticeMonths = PRICE_ADJUSTMENT_NOTICE_MONTHS;
    $wageMin = WAGE_INCREASE_MIN_PERCENT;
    $wageMax = WAGE_INCREASE_MAX_PERCENT;
    $logoHtml = contract_logo_html();

    return <<<HTML
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Gebäudereinigungsvertrag {$contractNumber}</title>
<style>
  body { font-family: Georgia, "Times New Roman", serif; max-width: 780px; margin: 40px auto; padding: 0 24px; color: #1a1a1a; line-height: 1.55; }
  h1 { font-size: 24px; margin-bottom: 4px; }
  h2 { font-size: 16px; margin-top: 32px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
  h4 { font-size: 14px; margin: 18px 0 4px; }
  .doc-logo { max-height: 64px; max-width: 260px; margin-bottom: 16px; }
  .meta-bar { font-size: 13px; color: #555; margin-bottom: 24px; }
  .parties { display: flex; gap: 40px; margin: 24px 0; }
  .party { flex: 1; }
  .party small { color: #666; text-transform: uppercase; letter-spacing: 0.04em; }
  ul, ol { padding-left: 20px; }
  ul li, ol.obligations li { margin-bottom: 4px; }
  .sign-block { display: flex; gap: 40px; margin-top: 40px; }
  .sign-col { flex: 1; border-top: 1px solid #333; padding-top: 8px; }
  .sign-placeholder { color: #999; font-style: italic; }
  @media print { body { margin: 0; } }
</style>
</head>
<body>

{$logoHtml}
<h1>Gebäudereinigungsvertrag</h1>
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

<h2>§ 1 Beginn, Rechtswahl und Vertragssprache</h2>
<p>
  1. Der Vertrag zur Gebäudereinigung tritt am <strong>{$effectiveDate}</strong> in Kraft.<br>
  2. Für sämtliche Rechtsbeziehungen der Parteien gilt ausschließlich das Recht der Bundesrepublik Deutschland unter Ausschluss
  aller kollisionsrechtlichen Bestimmungen, die in eine andere Rechtsordnung verweisen. Die Vertragssprache ist Deutsch.
</p>

<h2>§ 2 Vertragsgegenstand und Objekt</h2>
<p>
  Vertragsgegenstand sind die in § 3 genannten Reinigungsarbeiten für das nachfolgend näher bezeichnete Objekt:<br>
  Leistungsort: {$customerAddress}, {$customerZipCity}
</p>

<h2>§ 3 Art und Umfang der Reinigung</h2>
<p>Die Reinigung findet <strong>{$offerInterval}</strong> statt.</p>
<p>Gebuchte Leistung: {$serviceLine}</p>
{$notesBlock}
{$serviceCatalogHtml}
<p>Leistungen, die nicht in diesem Vertrag aufgeführt sind, bedürfen einer gesonderten Beauftragung und werden zusätzlich berechnet.</p>

<h2>§ 4 Vergütung</h2>
<p>
  Die monatliche Pauschalvergütung beträgt <strong>{$netPriceFormatted} netto</strong> zuzüglich der jeweils geltenden Umsatzsteuer
  (aktuell {$vatRateFormatted}&nbsp;%). Der Bruttobetrag beträgt zum Zeitpunkt der Vertragserstellung <strong>{$grossPriceFormatted}</strong>.
  Leistungen außerhalb von § 3 sind separat zu beauftragen und werden zusätzlich berechnet. Rechnungen sind binnen
  {$paymentDueDays} Arbeitstagen zu zahlen; nach {$defaultAfterDays} Tagen fallen automatisch Verzugszinsen an.
</p>
<p>
  Bei einer Tariferhöhung in der Gebäudereinigung geben wir die entsprechenden Lohnsteigerungen an unser Personal und
  unsere Kunden weiter. Die Lohnkosten werden voraussichtlich um {$wageMin} bis {$wageMax} Prozent steigen. Unsere Kunden
  informieren wir mindestens {$noticeMonths} Monat(e) vorher und teilen den exakten Differenzbetrag mit, der aus Lohn-,
  Betriebs- und Materialkosten berechnet wird.
</p>
<p>
  <strong>Zahlungsverzug / Zurückbehaltungsrecht:</strong> Die vereinbarte Vergütung ist innerhalb von {$paymentDueDays}
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
  2. Ausschließlicher Gerichtsstand für Streitigkeiten im Zusammenhang mit dem Vertragsverhältnis ist <strong>{$jurisdictionCity}</strong>.<br>
  3. Für sämtliche Rechtsbeziehungen der Parteien gilt ausschließlich das Recht der Bundesrepublik Deutschland unter
  Ausschluss aller kollisionsrechtlichen Bestimmungen, die in eine andere Rechtsordnung verweisen.<br>
  4. Sollte eine Bestimmung dieses Vertrags unwirksam sein, wird die Wirksamkeit der übrigen Bestimmungen hiervon nicht
  berührt. Die Parteien werden über eine die unwirksame Bestimmung ersetzende Regelung verhandeln, die dem Inhalt der
  ursprünglichen Bestimmung möglichst nahekommt. Gleiches gilt für mögliche Vertragslücken.
</p>

<h2>§ 8 Einbeziehung der Allgemeinen Geschäftsbedingungen</h2>
<p>
  Es gelten zusätzlich die Allgemeinen Geschäftsbedingungen des Auftragnehmers. Die gültige Fassung mit
  <strong>{$agbVersion}</strong> ist auf der Website <strong>{$agbUrl}</strong> einsehbar. Auf Wunsch kann dem Auftraggeber
  eine Ausfertigung der Allgemeinen Geschäftsbedingungen auch postalisch zugesendet werden.
</p>

<h2>§ 9 Ausfertigungen und elektronische Dokumentation</h2>
<p>
  Beide Parteien erhalten eine Ausfertigung dieses Gebäudereinigungsvertrags. Bei elektronischer Unterzeichnung wird jeder
  Partei nach Abschluss des Signaturvorgangs eine Ausfertigung zur Verfügung gestellt.
</p>

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

</body>
</html>
HTML;
}
