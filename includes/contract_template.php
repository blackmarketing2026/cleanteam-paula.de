<?php

const CONTRACTOR = [
    'legal_name' => 'Clean Team Group SRLS',
    'trade_description' => 'Meisterbetrieb Gebäudereinigung',
    'managing_directors' => ['Riccardo Cuccaro', 'Thomas Mündlein'],
    'street' => 'Via Dorsale 11',
    'postal_code' => '54100',
    'city' => 'Massa',
    'country' => 'Italien',
    'service_point_street' => 'Ober der Mühle 30',
    'service_point_postal_code' => '42699',
    'service_point_city' => 'Solingen',
    'website' => 'https://cleanteam-group.com',
    'complaint_portal_url' => 'https://cleanteam-group.com/kundenbereich',
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

function render_contract_document(array $offer, array $customer, ?array $contract): string
{
    $netPrice = (float) $offer['price'];
    $vatAmount = round($netPrice * VAT_RATE / 100, 2);
    $grossPrice = round($netPrice + $vatAmount, 2);

    $contractNumber = h($contract['number'] ?? 'Entwurf');
    $effectiveDate = contract_format_date($offer['start_date'] ?? $offer['created_at']);
    $createdAt = contract_format_date($offer['created_at']);
    $customerCity = h($customer['city']);

    $authorized = $contract['authorized'] ?? null;
    $representationNote = $contract['representation_note'] ?? null;
    $authorityText = $authorized === false && $representationNote
        ? h($representationNote)
        : 'ohne gesonderte Vertretungsangabe';

    $isSigned = $contract !== null && $contract['status'] === 'signiert';
    $signedAt = $isSigned ? contract_format_date($contract['signed_at']) : '–';
    $signatureImage = $isSigned && !empty($contract['signature_data'])
        ? '<img src="' . h($contract['signature_data']) . '" alt="Unterschrift" style="max-height:70px;">'
        : '<span class="sign-placeholder">noch nicht unterschrieben</span>';

    $managingDirectors = h(implode(', ', CONTRACTOR['managing_directors']));
    $contractorLegalName = h(CONTRACTOR['legal_name']);
    $contractorTrade = h(CONTRACTOR['trade_description']);
    $contractorStreet = h(CONTRACTOR['street']);
    $contractorZipCity = h(CONTRACTOR['postal_code'] . ' ' . CONTRACTOR['city'] . ', ' . CONTRACTOR['country']);
    $contractorServicePoint = h(CONTRACTOR['service_point_street'] . ', ' . CONTRACTOR['service_point_postal_code'] . ' ' . CONTRACTOR['service_point_city']);
    $contractorServicePointCity = h(CONTRACTOR['service_point_city']);
    $contractorPortal = h(CONTRACTOR['complaint_portal_url']);
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

    $netPriceFormatted = contract_format_money($netPrice);
    $grossPriceFormatted = contract_format_money($grossPrice);
    $vatRateFormatted = h(rtrim(rtrim(number_format(VAT_RATE, 1, ',', '.'), '0'), ','));

    $statusLabel = $contract === null
        ? 'Entwurf (noch nicht gestartet)'
        : h(ucfirst(str_replace('_', ' ', (string) $contract['status'])));

    $paymentDueDays = PAYMENT_DUE_DAYS;
    $defaultAfterDays = DEFAULT_AFTER_DAYS;
    $noticeMonths = PRICE_ADJUSTMENT_NOTICE_MONTHS;

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
  .meta-bar { font-size: 13px; color: #555; margin-bottom: 24px; }
  .parties { display: flex; gap: 40px; margin: 24px 0; }
  .party { flex: 1; }
  .party small { color: #666; text-transform: uppercase; letter-spacing: 0.04em; }
  ul { padding-left: 20px; }
  .sign-block { display: flex; gap: 40px; margin-top: 40px; }
  .sign-col { flex: 1; border-top: 1px solid #333; padding-top: 8px; }
  .sign-placeholder { color: #999; font-style: italic; }
  @media print { body { margin: 0; } }
</style>
</head>
<body>

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
      Geschäftsführung: {$managingDirectors}<br>
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
  Der Vertrag zur Gebäudereinigung tritt am <strong>{$effectiveDate}</strong> in Kraft.
  Für sämtliche Rechtsbeziehungen der Parteien gilt ausschließlich das Recht der Bundesrepublik Deutschland.
  Die Vertragssprache ist Deutsch.
</p>

<h2>§ 2 Vertragsgegenstand und Objekt</h2>
<p>
  Vertragsgegenstand sind die nachfolgend genannten Reinigungsarbeiten für das Objekt des Auftraggebers:<br>
  {$customerAddress}, {$customerZipCity}
</p>

<h2>§ 3 Art, Umfang und Intervalle der Reinigung</h2>
<p>Die regelmäßige Reinigung findet <strong>{$offerInterval}</strong> statt.</p>
<ul>
  <li>{$serviceLine}</li>
</ul>
{$notesBlock}
<p>Leistungen, die nicht in diesem Vertrag aufgeführt sind, bedürfen einer gesonderten Beauftragung und werden zusätzlich berechnet.</p>

<h2>§ 4 Vergütung und Zahlungsbedingungen</h2>
<p>
  Die monatliche Pauschalvergütung beträgt <strong>{$netPriceFormatted} netto</strong> zuzüglich der jeweils geltenden Umsatzsteuer
  (aktuell {$vatRateFormatted}&nbsp;%). Der Bruttobetrag beträgt zum Zeitpunkt der Vertragserstellung <strong>{$grossPriceFormatted}</strong>.<br>
  Rechnungen sind innerhalb von {$paymentDueDays} Arbeitstagen nach Zugang ohne Abzug zu zahlen.
  Nach {$defaultAfterDays} Tagen fallen Verzugszinsen nach der gesetzlichen Regelung an.
</p>
<p>
  Bei einer Tariferhöhung in der Gebäudereinigung gibt der Auftragnehmer die entsprechenden Lohnsteigerungen an seine Kunden weiter.
  Der Auftraggeber wird mindestens {$noticeMonths} Monat(e) vor Wirksamwerden der Anpassung informiert.
</p>

<h2>§ 5 Pflichten und Mitwirkung des Auftraggebers</h2>
<p>
  Reklamationen sind über das Reklamationsportal des Auftragnehmers unter <strong>{$contractorPortal}</strong> einzureichen.
  Andere Kommunikationswege werden nicht anerkannt und nicht bearbeitet. Der Auftraggeber ist verpflichtet, die erbrachte Leistung
  unmittelbar nach Öffnung des Objekts zu kontrollieren und erkennbare Mängel unverzüglich über das Reklamationsportal anzuzeigen.
</p>
<p>
  Fällt ein gesetzlicher Feiertag auf einen regulären Reinigungstag, findet an diesem Tag keine Arbeitsleistung statt.
  Eine Nachholung erfolgt nicht. Die vereinbarte Pauschalvergütung bleibt davon unberührt.
</p>

<h2>§ 6 Vertraulichkeit</h2>
<p>
  Die Parteien verpflichten sich, während der Laufzeit dieses Vertrags die Geschäfts- und Betriebsgeheimnisse der jeweils anderen
  Partei sowie den Inhalt dieses Vertrags vertraulich zu behandeln und nicht an Dritte weiterzugeben.
</p>

<h2>§ 7 Erfüllungsort, Gerichtsstand und Schlussbestimmungen</h2>
<p>
  Ausschließlicher Gerichtsstand für Streitigkeiten im Zusammenhang mit dem Vertragsverhältnis ist <strong>{$jurisdictionCity}</strong>,
  soweit eine solche Gerichtsstandsvereinbarung rechtlich zulässig ist. Sollte eine Bestimmung dieses Vertrags unwirksam sein oder
  werden, wird die Wirksamkeit der übrigen Bestimmungen hiervon nicht berührt.
</p>

<h2>§ 8 Einbeziehung der Allgemeinen Geschäftsbedingungen</h2>
<p>
  Zusätzlich gelten die Allgemeinen Geschäftsbedingungen des Auftragnehmers in der Fassung <strong>{$agbVersion}</strong>,
  abrufbar unter <strong>{$agbUrl}</strong>.
</p>

<h2>§ 9 Ausfertigungen und elektronische Dokumentation</h2>
<p>
  Beide Parteien erhalten eine Ausfertigung dieses Gebäudereinigungsvertrags. Bei elektronischer Unterzeichnung wird jeder Partei
  nach Abschluss des Signaturvorgangs eine Ausfertigung zur Verfügung gestellt.
</p>

<div class="sign-block">
  <div class="sign-col">
    <div>Ort: {$contractorServicePointCity} &nbsp;·&nbsp; Datum: {$createdAt}</div>
    <div style="margin-top:12px;">{$contractorLegalName}</div>
    <div style="color:#999;font-style:italic;">wird durch CleanTeam gegengezeichnet</div>
  </div>
  <div class="sign-col">
    <div>Ort: {$customerCity} &nbsp;·&nbsp; Datum: {$signedAt}</div>
    <div style="margin-top:12px;">{$signatoryName}</div>
    {$signatureImage}
  </div>
</div>

</body>
</html>
HTML;
}
