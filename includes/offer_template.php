<?php

require_once __DIR__ . '/contract_template.php';

function offer_int($value): int
{
    return max(0, (int) $value);
}

function offer_cleaning_type(?string $value): string
{
    if ($value === 'Gesaugt') {
        return 'Nur gesaugt';
    }
    if ($value === 'Gewischt') {
        return 'Nur gewischt';
    }

    return $value !== null && $value !== '' ? $value : 'Gesaugt und gewischt';
}

function offer_room_quantity_label(array $room): string
{
    $quantity = max(1, offer_int($room['quantity'] ?? 1));
    return $quantity > 1 ? $quantity . 'x ' : '';
}

function offer_cleaning_frequency(?string $frequency): string
{
    $allowedFrequencies = ['Täglich', 'Alle 2 Tage', 'Wöchentlich', '14-täglich', '30-täglich', 'Individuell'];
    return $frequency !== null && in_array($frequency, $allowedFrequencies, true) ? $frequency : 'Täglich';
}

function offer_floor_cleaning_method(?string $method): string
{
    if ($method === 'Nur gesaugt') {
        return 'Gesaugt';
    }
    if ($method === 'Nur gewischt') {
        return 'Gewischt';
    }

    $allowedMethods = ['Gesaugt', 'Gewischt', 'Gesaugt und gewischt'];
    return $method !== null && in_array($method, $allowedMethods, true) ? $method : 'Gesaugt und gewischt';
}

function offer_trash_bag_mode(?string $mode): string
{
    $allowedModes = ['Mit Mülltüte', 'Ohne Mülltüte'];
    return $mode !== null && in_array($mode, $allowedModes, true) ? $mode : 'Mit Mülltüte';
}

function offer_cleaning_task_label(string $key): string
{
    $labels = [
        'washbasin' => 'Waschbecken',
        'toilet' => 'WC',
        'mirror' => 'Spiegel',
        'floor' => 'Boden',
        'door' => 'Tür',
        'desk' => 'Schreibtische',
        'window' => 'Fensterbänke',
        'surface' => 'Oberflächen',
        'trash' => 'Mülleimer-Entleerung',
        'kitchen' => 'Küchenflächen',
        'handrail' => 'Handlauf / Geländer',
        'treatmentDesk' => 'Schreibtisch',
        'treatmentChair' => 'Behandlungsstühle',
        'treatmentTable' => 'Behandlungstisch',
        'disinfection' => 'Desinfektion',
    ];

    return $labels[$key] ?? $key;
}

function offer_cleaning_item(array $item): ?array
{
    $key = trim((string) ($item['key'] ?? ($item['type'] ?? '')));
    if ($key === '') {
        return null;
    }

    $frequency = offer_cleaning_frequency(trim((string) ($item['frequency'] ?? '')));
    $method = trim((string) ($item['method'] ?? ($item['cleaningMethod'] ?? '')));

    return [
        'key' => $key,
        'label' => offer_cleaning_task_label($key),
        'frequency' => $frequency,
        'customFrequency' => $frequency === 'Individuell' ? trim((string) ($item['customFrequency'] ?? '')) : '',
        'method' => $key === 'floor' && $method !== '' ? offer_floor_cleaning_method($method) : '',
        'bagMode' => $key === 'trash' ? offer_trash_bag_mode(trim((string) ($item['bagMode'] ?? ($item['trashBagMode'] ?? '')))) : '',
    ];
}

function offer_legacy_cleaning_items_from_room(array $room): array
{
    $items = [];
    if (offer_int($room['sinks'] ?? 0) > 0) {
        $items[] = ['key' => 'washbasin', 'label' => 'Waschbecken', 'frequency' => 'Täglich', 'customFrequency' => ''];
    }
    if (offer_int($room['toilets'] ?? 0) > 0) {
        $items[] = ['key' => 'toilet', 'label' => 'WC', 'frequency' => 'Täglich', 'customFrequency' => ''];
    }
    if (offer_int($room['mirrors'] ?? 0) > 0) {
        $items[] = ['key' => 'mirror', 'label' => 'Spiegel', 'frequency' => 'Täglich', 'customFrequency' => ''];
    }
    if (offer_int($room['desks'] ?? 0) > 0) {
        $items[] = ['key' => 'desk', 'label' => 'Schreibtische', 'frequency' => 'Wöchentlich', 'customFrequency' => ''];
    }
    if (offer_int($room['windows'] ?? 0) > 0) {
        $items[] = ['key' => 'window', 'label' => 'Fensterbänke', 'frequency' => '30-täglich', 'customFrequency' => ''];
    }
    if (trim((string) ($room['cleaningType'] ?? '')) !== '') {
        $items[] = ['key' => 'floor', 'label' => 'Boden', 'frequency' => 'Täglich', 'customFrequency' => '', 'method' => offer_floor_cleaning_method(trim((string) ($room['cleaningType'] ?? '')))];
    }

    return $items;
}

function offer_cleaning_items_from_room(array $room): array
{
    $items = [];
    if (isset($room['cleaningItems']) && is_array($room['cleaningItems'])) {
        foreach ($room['cleaningItems'] as $item) {
            if (is_array($item)) {
                $normalizedItem = offer_cleaning_item($item);
                if ($normalizedItem !== null) {
                    $items[] = $normalizedItem;
                }
            }
        }
    }

    return $items !== [] ? $items : offer_legacy_cleaning_items_from_room($room);
}

function offer_cleaning_item_text(array $item, array $room = []): string
{
    $frequency = $item['frequency'] === 'Individuell'
        ? (trim((string) ($item['customFrequency'] ?? '')) ?: 'Individuell')
        : $item['frequency'];

    $details = [$frequency];
    if (($item['key'] ?? '') === 'floor') {
        if (trim((string) ($item['method'] ?? '')) !== '') {
            $details[] = (string) $item['method'];
        }
    }
    if (($item['key'] ?? '') === 'trash' && trim((string) ($item['bagMode'] ?? '')) !== '') {
        $details[] = (string) $item['bagMode'];
    }

    return $item['label'] . ': ' . implode(', ', $details);
}

function offer_normalize_room(array $room, int $index = 0): array
{
    return [
        'name' => trim((string) ($room['name'] ?? '')) ?: 'Raum ' . ($index + 1),
        'roomType' => trim((string) ($room['roomType'] ?? '')) ?: 'Büro',
        'quantity' => max(1, offer_int($room['quantity'] ?? 1)),
        'squareMeters' => offer_int($room['squareMeters'] ?? 0),
        'cleaningItems' => offer_cleaning_items_from_room($room),
        'sinks' => offer_int($room['sinks'] ?? 0),
        'mirrors' => offer_int($room['mirrors'] ?? 0),
        'toilets' => offer_int($room['toilets'] ?? 0),
        'desks' => offer_int($room['desks'] ?? 0),
        'windows' => offer_int($room['windows'] ?? 0),
        'cleaningType' => offer_cleaning_type(trim((string) ($room['cleaningType'] ?? ''))),
        'floorCondition' => trim((string) ($room['floorCondition'] ?? '')) ?: 'Teppich',
        'extraAgreements' => trim((string) ($room['extraAgreements'] ?? '')),
        'notes' => trim((string) ($room['notes'] ?? ($room['areaNotes'] ?? ''))),
    ];
}

function offer_legacy_rooms_from_floor(array $floor): array
{
    $rooms = [];
    $areaName = trim((string) ($floor['areaName'] ?? ''));
    $areaNotes = trim((string) ($floor['areaNotes'] ?? ($floor['notes'] ?? '')));
    $extraAgreements = trim((string) ($floor['extraAgreements'] ?? ''));
    $cleaningType = offer_cleaning_type(trim((string) ($floor['cleaningType'] ?? '')));
    $floorCondition = trim((string) ($floor['floorCondition'] ?? '')) ?: 'Teppich';
    $sanitaryRooms = offer_int($floor['sanitaryRooms'] ?? 0);
    $officeRooms = offer_int($floor['officeRooms'] ?? 0);

    if ($sanitaryRooms > 0) {
        $rooms[] = offer_normalize_room([
            'name' => $areaName !== '' && $officeRooms === 0 ? $areaName : 'Sanitärbereich',
            'roomType' => 'Sanitär',
            'quantity' => $sanitaryRooms,
            'sinks' => $floor['sinks'] ?? 0,
            'mirrors' => $floor['mirrors'] ?? 0,
            'toilets' => $floor['toilets'] ?? 0,
            'cleaningType' => $cleaningType,
            'floorCondition' => $floorCondition,
            'extraAgreements' => $officeRooms === 0 ? $extraAgreements : '',
            'notes' => $officeRooms === 0 ? $areaNotes : '',
        ]);
    }

    if ($officeRooms > 0) {
        $rooms[] = offer_normalize_room([
            'name' => $areaName !== '' && $sanitaryRooms === 0 ? $areaName : 'Bürobereich',
            'roomType' => 'Büro',
            'quantity' => $officeRooms,
            'desks' => $floor['desks'] ?? 0,
            'windows' => $floor['windows'] ?? 0,
            'cleaningType' => $cleaningType,
            'floorCondition' => $floorCondition,
            'extraAgreements' => $extraAgreements,
            'notes' => $areaNotes,
        ]);
    }

    if ($rooms === [] && ($areaName !== '' || $areaNotes !== '' || $extraAgreements !== '')) {
        $rooms[] = offer_normalize_room([
            'name' => $areaName !== '' ? $areaName : 'Bereich',
            'roomType' => 'Sonstiger Raum',
            'cleaningType' => $cleaningType,
            'floorCondition' => $floorCondition,
            'extraAgreements' => $extraAgreements,
            'notes' => $areaNotes,
        ]);
    }

    return $rooms;
}

function offer_rooms_from_floor(array $floor): array
{
    $rooms = [];
    if (isset($floor['rooms']) && is_array($floor['rooms']) && count($floor['rooms']) > 0) {
        foreach ($floor['rooms'] as $index => $room) {
            if (is_array($room)) {
                $rooms[] = offer_normalize_room($room, (int) $index);
            }
        }
    }

    return $rooms !== [] ? $rooms : offer_legacy_rooms_from_floor($floor);
}

function offer_room_details(array $room): string
{
    return '';
}

function offer_cleaning_items_text(array $room): string
{
    $items = offer_cleaning_items_from_room($room);
    return implode(' · ', array_map(fn(array $item): string => offer_cleaning_item_text($item, $room), $items));
}

function render_offer_floor_plan_html(?array $siteVisit): string
{
    if ($siteVisit === null) {
        return '';
    }

    $floors = json_decode((string) ($siteVisit['floors_json'] ?? '[]'), true);
    if (!is_array($floors) || count($floors) === 0) {
        return '';
    }

    $html = '<h2>Etagen und Räume</h2><div class="floor-plan">';
    foreach ($floors as $index => $floor) {
        if (!is_array($floor)) {
            continue;
        }
        $floorName = trim((string) ($floor['name'] ?? '')) ?: 'Etage ' . ($index + 1);
        $rooms = offer_rooms_from_floor($floor);
        $html .= '<section class="floor-plan-floor"><h3>' . h($floorName) . '</h3>';
        if ($rooms === []) {
            $html .= '<p class="muted">Keine Räume hinterlegt.</p>';
        } else {
            $html .= '<ul>';
            foreach ($rooms as $room) {
                $details = offer_room_details($room);
                $cleaning = offer_cleaning_items_text($room);
                $extra = trim((string) ($room['extraAgreements'] ?? ''));
                $notes = trim((string) ($room['notes'] ?? ''));
                $html .= '<li><strong>' . h(offer_room_quantity_label($room) . $room['name']) . '</strong>'
                    . '<span>' . h($room['roomType']) . ($details !== '' ? ' · ' . h($details) : '') . '</span>'
                    . ($cleaning !== '' ? '<span>Reinigung: ' . h($cleaning) . '</span>' : '')
                    . ($extra !== '' ? '<span>Extra Vereinbarungen: ' . h($extra) . '</span>' : '')
                    . ($notes !== '' ? '<span>Notiz: ' . h($notes) . '</span>' : '')
                    . '</li>';
            }
            $html .= '</ul>';
        }
        $html .= '</section>';
    }
    $html .= '</div>';

    return $html;
}

function render_offer_document(array $offer, array $customer, ?array $siteVisit = null): string
{
    $logoHtml = contract_logo_html();
    $contractorLegalName = h(CONTRACTOR['legal_name']);
    $contractorTrade = h(CONTRACTOR['trade_description']);

    $customerName = h(contract_customer_display_name($customer));
    $signatoryName = h(contract_signatory_display($customer));
    $customerEmail = h((string) $customer['email']);
    $customerPhone = h((string) $customer['phone']);
    $customerAddress = h($customer['address'] . ' ' . $customer['house_number']);
    $customerZipCity = h($customer['zip'] . ' ' . $customer['city']);

    $service = h((string) $offer['service']);
    $interval = h((string) $offer['interval_label']);
    $squareMeters = (int) $offer['square_meters'];
    $startDate = $offer['start_date'] !== null ? contract_format_date($offer['start_date']) : 'Nach Absprache';
    $validUntil = contract_format_date($offer['expires_at']);
    $price = (float) $offer['price'];
    $basePrice = isset($offer['base_price']) && (float) $offer['base_price'] > 0
        ? (float) $offer['base_price']
        : $price;
    $priceAdjustment = (float) ($offer['price_adjustment'] ?? 0);
    $priceFormatted = contract_format_money($price);
    $basePriceFormatted = contract_format_money($basePrice);
    $priceAdjustmentFormatted = contract_format_money($priceAdjustment);
    $priceAdjustmentNote = trim((string) ($offer['price_adjustment_note'] ?? ''));
    $priceAdjustmentRows = abs($priceAdjustment) > 0.0001
        ? '<dt>Berechneter Grundpreis</dt><dd>' . h($basePriceFormatted) . ' netto</dd>'
            . '<dt>Preisanpassung</dt><dd>' . h($priceAdjustmentFormatted) . ' netto'
            . ($priceAdjustmentNote !== '' ? '<br><span class="muted">' . h($priceAdjustmentNote) . '</span>' : '')
            . '</dd>'
        : '';

    $offerNotes = trim((string) ($offer['notes'] ?? ''));
    $notesBlock = $offerNotes !== '' ? '<p><strong>Besondere Vereinbarungen:</strong> ' . h($offerNotes) . '</p>' : '';
    $floorPlanHtml = render_offer_floor_plan_html($siteVisit);

    $signUrl = h(base_url() . '/o.php?token=' . $offer['token']);

    return <<<HTML
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Ihr Kostenvoranschlag von {$contractorLegalName}</title>
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
  .muted { color: #666; font-size: 13px; }
  @media print { .cta { display: none; } body { margin: 0; } }
</style>
</head>
<body>

{$logoHtml}
<h1>Ihr individueller Reinigungs-Kostenvoranschlag</h1>
<p class="muted">{$contractorLegalName} – {$contractorTrade}</p>

<h2>Kunde</h2>
<dl>
  <dt>Firma</dt><dd>{$customerName}</dd>
  <dt>Ansprechpartner</dt><dd>{$signatoryName}</dd>
  <dt>E-Mail</dt><dd>{$customerEmail}</dd>
  <dt>Telefon</dt><dd>{$customerPhone}</dd>
  <dt>Objekt / Anschrift</dt><dd>{$customerAddress}, {$customerZipCity}</dd>
</dl>

<h2>Kostenvoranschlag</h2>
<dl>
  <dt>Leistung</dt><dd>{$service}</dd>
  <dt>Fläche</dt><dd>{$squareMeters} m²</dd>
  <dt>Intervall</dt><dd>{$interval}</dd>
  <dt>Startdatum</dt><dd>{$startDate}</dd>
  {$priceAdjustmentRows}
  <dt>Finaler Preis</dt><dd>{$priceFormatted} netto</dd>
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
