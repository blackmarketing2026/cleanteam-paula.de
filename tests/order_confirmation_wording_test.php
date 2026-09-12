<?php

$template = file_get_contents(__DIR__ . '/../includes/contract_template.php');
$pdf = file_get_contents(__DIR__ . '/../includes/contract_pdf.php');
$publicPage = file_get_contents(__DIR__ . '/../o.php');
$publicJs = file_get_contents(__DIR__ . '/../public.js');
$notification = file_get_contents(__DIR__ . '/../includes/contract_notify.php');
$contractPage = file_get_contents(__DIR__ . '/../contract.php');

foreach ([$template, $pdf] as $documentSource) {
    if (str_contains($documentSource, 'Gebäudereinigungsvertrag')) {
        throw new RuntimeException('Das Dokument enthält weiterhin die alte Bezeichnung Gebäudereinigungsvertrag.');
    }
    if (!str_contains($documentSource, 'Auftragsbestätigung')) {
        throw new RuntimeException('Die Bezeichnung Auftragsbestätigung fehlt im Dokument.');
    }
}

if (!str_contains($publicPage, 'Auftragsbest&auml;tigung unterschreiben')
    || !str_contains($publicJs, 'Auftragsbestätigung unterschreiben')) {
    throw new RuntimeException('Der letzte Signaturschritt ist nicht korrekt benannt.');
}

if (str_contains($contractPage, 'excludeContractorSignature')
    || !str_contains($template, 'Unterschrift Thomas Mündlein')
    || !str_contains($pdf, 'get_contract_template_contractor_signature_data')) {
    throw new RuntimeException('Die Unterschrift von Thomas ist nicht in allen vorgesehenen Dokumenten eingebunden.');
}

if (!str_contains($notification, 'Ihre unterschriebene Auftragsbestätigung')) {
    throw new RuntimeException('Die Abschluss-E-Mail benennt das Dokument nicht als Auftragsbestätigung.');
}

echo "PASS: contractor signature and order-confirmation wording are consistent\n";
