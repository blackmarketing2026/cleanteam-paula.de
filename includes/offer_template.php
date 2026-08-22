<?php

require_once __DIR__ . '/contract_template.php';

function render_offer_document(array $offer, array $customer): string
{
    $logoHtml = contract_logo_html();
    $contractorLegalName = h(CONTRACTOR['legal_name']);
    $contractorTrade = h(CONTRACTOR['trade_description']);

    $customerName = h(contract_customer_display_name($customer));
    $signatoryName = h(contract_signatory_display($customer));
    $customerEmail = h((string) $customer['email']);
    $customerPhone = h((string) $customer['phone']);
    $customerAddress = h(trim($customer['address'] . ' ' . $customer['house_number']));
    $customerZipCity = h(trim($customer['zip'] . ' ' . $customer['city']));
    $addressRow = $customerAddress !== '' || $customerZipCity !== ''
        ? '<dt>Objekt / Anschrift</dt><dd>' . trim($customerAddress . ', ' . $customerZipCity, ', ') . '</dd>'
        : '';

    $squareMeters = (int) $offer['square_meters'];
    $areaRow = $squareMeters > 0 ? "<dt>Fläche</dt><dd>{$squareMeters} m²</dd>" : '';
    $startDate = $offer['start_date'] !== null ? contract_format_date($offer['start_date']) : 'Nach Absprache';
    $validUntil = contract_format_date($offer['expires_at']);
    $price = (float) $offer['price'];
    $priceFormatted = contract_format_money($price);

    $offerNotes = trim((string) ($offer['notes'] ?? ''));
    $displayOfferNotes = preg_replace('/^Leistungsbeschreibung \/ Dienstleistung\r?\n\r?\n?/', '', $offerNotes) ?? $offerNotes;
    $notesBlock = $offerNotes !== ''
        ? '<h2>Leistungsbeschreibung / Dienstleistung</h2><div class="offer-service-text">' . h(trim($displayOfferNotes)) . '</div>'
        : '';
    $floorPlanHtml = '';

    $signUrl = h(base_url() . '/o.php?token=' . $offer['token']);

    return <<<HTML
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Ihr Vertrag von {$contractorLegalName}</title>
<style>
  body { font-family: Georgia, "Times New Roman", serif; max-width: 700px; margin: 40px auto; padding: 0 24px; color: #1a1a1a; line-height: 1.55; }
  .doc-logo { max-height: 64px; max-width: 260px; margin-bottom: 16px; }
  h1 { font-size: 24px; margin-bottom: 4px; }
  h2 { font-size: 16px; margin-top: 32px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
  dl { display: grid; grid-template-columns: 180px minmax(0, 1fr); gap: 9px 18px; margin: 12px 0; }
  dt { color: #666; font-weight: 700; }
  dd { margin: 0; overflow-wrap: anywhere; }
  .cta { text-align: center; margin: 44px 0 24px; }
  .cta a { display: inline-block; padding: 16px 32px; background: #1a6de0; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 16px; }
  .floor-plan { display: grid; gap: 14px; }
  .floor-plan-floor { border: 1px solid #ddd; border-radius: 8px; padding: 14px; }
  .floor-plan-floor h3 { margin: 0 0 8px; font-size: 15px; }
  .floor-plan-floor ul { margin: 0; padding-left: 20px; }
  .floor-plan-floor li { margin-bottom: 10px; }
  .floor-plan-floor li strong,
  .floor-plan-floor li span { display: block; }
  .offer-service-text { white-space: pre-wrap; border: 1px solid #ddd; border-radius: 8px; padding: 14px; font-family: Arial, sans-serif; font-size: 13px; line-height: 1.55; }
  .muted { color: #666; font-size: 13px; }
  @media print { .cta { display: none; } body { margin: 0; } }
</style>
</head>
<body>

{$logoHtml}
<h1>Ihr individueller Reinigungsvertrag</h1>
<p class="muted">{$contractorLegalName} – {$contractorTrade}</p>

<h2>Kunde</h2>
<dl>
  <dt>Firma</dt><dd>{$customerName}</dd>
  <dt>Ansprechpartner</dt><dd>{$signatoryName}</dd>
  <dt>E-Mail</dt><dd>{$customerEmail}</dd>
  <dt>Telefon</dt><dd>{$customerPhone}</dd>
  {$addressRow}
</dl>

<h2>Vertragsdaten</h2>
<dl>
  {$areaRow}
  <dt>Startdatum</dt><dd>{$startDate}</dd>
  <dt>Monatlicher Preis</dt><dd>{$priceFormatted} netto monatlich</dd>
  <dt>Gültig bis</dt><dd>{$validUntil}</dd>
</dl>
{$notesBlock}
{$floorPlanHtml}

<div class="cta">
  <a href="{$signUrl}">Jetzt Vertrag abschließen</a>
</div>

</body>
</html>
HTML;
}
