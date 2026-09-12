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

$acceptBlock = source_between(
    $publicApi,
    "if (\$method === 'POST' && \$action === 'accept-quote')",
    "if (\$isQuoteAccess && \$method === 'POST'"
);
if (!str_contains($acceptBlock, "quote_status = 'signing'")) {
    throw new RuntimeException('Die KVA-Annahme startet den Vertragsprozess nicht.');
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
if (!str_contains($signBlock, "quote_status = 'accepted'") || !str_contains($signBlock, "status = 'signiert'")) {
    throw new RuntimeException('Der KVA-Status wird nicht gemeinsam mit der Vertragsunterschrift abgeschlossen.');
}

if (!str_contains($sendOffer, "&start=online-contract")
    || !str_contains($sendOffer, "email_button_html(\$publicUrl, 'Kostenvoranschlag annehmen')")
    || str_contains($sendOffer, 'Danach erhalten Sie automatisch Ihre Auftragsbestätigung.')) {
    throw new RuntimeException('Der KVA-E-Mail-Button startet nicht korrekt den Online-Vertragsabschluss.');
}

if (!str_contains($publicJs, 'startsQuoteContract')
    || !str_contains($publicJs, 'data.offer.quoteStatus === "accepted" && contract && contract.status === "signiert"')) {
    throw new RuntimeException('Die öffentliche Oberfläche bildet den Signaturstatus nicht korrekt ab.');
}

echo "PASS: quote starts online contract flow and is accepted only after signature\n";
