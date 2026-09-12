<?php

$offersApi = file_get_contents(__DIR__ . '/../api/offers.php');
$contractsApi = file_get_contents(__DIR__ . '/../api/contracts.php');
$helpers = file_get_contents(__DIR__ . '/../includes/helpers.php');
$quotePdf = file_get_contents(__DIR__ . '/../includes/quote_pdf.php');

if (str_contains($offersApi, 'Ein bereits versendeter Kostenvoranschlag kann nicht mehr geändert werden.')) {
    throw new RuntimeException('Ein versendeter Kostenvoranschlag sperrt die Entwurfsbearbeitung weiterhin.');
}

if (!str_contains($offersApi, 'invalidate_offer_generated_documents($pdo, $id)')) {
    throw new RuntimeException('Beim Speichern des Vertragsentwurfs werden alte Dokumentkopien nicht verworfen.');
}

if (!str_contains($contractsApi, "invalidate_offer_generated_documents(\$pdo, \$row['offer_id'])")) {
    throw new RuntimeException('Beim Speichern über die Vertragsbearbeitung werden alte Dokumentkopien nicht verworfen.');
}

foreach (['DELETE FROM quote_documents', 'DELETE cd FROM contract_documents'] as $statement) {
    if (!str_contains($helpers, $statement)) {
        throw new RuntimeException('Die Dokumentinvalidierung ist unvollständig.');
    }
}

if (!str_contains($quotePdf, 'render_quote_pdf($offer, $customer')) {
    throw new RuntimeException('Der Kostenvoranschlag wird nicht aus den aktuellen Angebotsdaten neu erzeugt.');
}

echo "PASS: sent quotes remain editable and regenerated documents use current data\n";
