<?php

function source_between(string $source, string $start, string $end): string
{
    $startOffset = strpos($source, $start);
    $endOffset = $startOffset === false ? false : strpos($source, $end, $startOffset + strlen($start));
    if ($startOffset === false || $endOffset === false) {
        throw new RuntimeException('Workflow-Test konnte den erwarteten Quelltextabschnitt nicht finden.');
    }

    return substr($source, $startOffset, $endOffset - $startOffset);
}

$publicApi = file_get_contents(__DIR__ . '/../api/public.php');
$sendOffer = file_get_contents(__DIR__ . '/../api/send-offer.php');
$publicJs = file_get_contents(__DIR__ . '/../public.js');
$quotePdf = file_get_contents(__DIR__ . '/../includes/quote_pdf.php');

if (!str_contains($sendOffer, 'QUOTE_VALIDITY_DAYS')
    || !str_contains($sendOffer, 'validity_hours = 0')
    || !str_contains($quotePdf, "const QUOTE_VALIDITY_DAYS = 14;")) {
    throw new RuntimeException('Beim KVA-Versand wird die feste Gültigkeit von 14 Tagen nicht gesetzt.');
}

$acceptBlock = source_between(
    $publicApi,
    "if (\$method === 'POST' && \$action === 'accept-quote')",
    "if (\$isQuoteAccess && \$method === 'POST'"
);
if (!str_contains($acceptBlock, "quote_status = 'signing'")) {
    throw new RuntimeException('Die KVA-Annahme startet den Vertragsprozess nicht.');
}
if (!str_contains($acceptBlock, "['entwurf', 'sent']")) {
    throw new RuntimeException('Ein unmittelbar nach dem E-Mail-Empfang geöffneter KVA-Link wird nicht verarbeitet.');
}
foreach (["status = 'bestaetigt'", 'save_contract_pdfs', 'notify_contract_created', 'notify_customer_contract_signed', 'export_contract_to_ftp'] as $forbidden) {
    if (str_contains($acceptBlock, $forbidden)) {
        throw new RuntimeException('Die KVA-Annahme darf keine Auftragsbestätigung oder Abschlussaktionen auslösen.');
    }
}

$signBlock = source_between(
    $publicApi,
    "if (\$method === 'POST' && \$action === 'sign')",
    "json_error('Unbekannte Aktion.'"
);
if (!str_contains($signBlock, "status = 'signiert'") || str_contains($signBlock, "quote_status = 'accepted'")) {
    throw new RuntimeException('Nur die Vertragsunterschrift darf den Abschlussstatus bestimmen.');
}

if (!str_contains($sendOffer, "&start=online-contract")
    || !str_contains($sendOffer, "email_button_html(\$publicUrl, 'Kostenvoranschlag annehmen')")
    || str_contains($sendOffer, 'Danach erhalten Sie automatisch Ihre Auftragsbestätigung.')) {
    throw new RuntimeException('Der KVA-E-Mail-Button startet nicht korrekt den Online-Vertragsabschluss.');
}

if (!str_contains($publicJs, 'startsQuoteContract')
    || !str_contains($publicJs, '["entwurf", "sent"].includes(data.offer.quoteStatus)')
    || str_contains($publicJs, 'Kostenvoranschlag angenommen')) {
    throw new RuntimeException('Die öffentliche Oberfläche bildet den Signaturstatus nicht korrekt ab.');
}
if (str_contains($publicJs, 'offer.price * 1.19') || str_contains($publicJs, 'Monatlicher Preis brutto')) {
    throw new RuntimeException('Die KVA-Webansicht darf den Nettopreis nicht zu einem Bruttopreis hochrechnen.');
}

if (str_contains($sendOffer, 'Der Kostenvoranschlag wurde bereits angenommen.')
    || !str_contains($sendOffer, "\$contractStatus !== 'signiert'")
    || !str_contains($sendOffer, "? 'signing'")
    || !str_contains($sendOffer, ": 'sent'")) {
    throw new RuntimeException('Ein Kostenvoranschlag muss auch nach der Vertragsunterschrift erneut versendet werden können.');
}

if (!str_contains($publicApi, "(\$offer['quote_status'] ?? '') === 'accepted'")
    || !str_contains($publicApi, "? 'signing'")
    || !str_contains($publicApi, ": 'sent'")) {
    throw new RuntimeException('Inkonsistente alte KVA-Statuswerte werden beim Öffnen nicht repariert.');
}

$signedPosition = strpos($publicJs, 'contract && contract.status === "signiert"');
$quotePosition = strpos($publicJs, 'if (data.accessMode === "quote")');
if ($signedPosition === false || $quotePosition === false || $signedPosition > $quotePosition) {
    throw new RuntimeException('Ein unterschriebener Vertrag wird nicht vor dem KVA-Zwischenstatus angezeigt.');
}

echo "PASS: quote starts online signing, contract signature is authoritative, and legacy states self-heal\n";
