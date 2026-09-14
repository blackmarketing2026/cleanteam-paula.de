<?php

function assert_contract_list_source_contains(string $source, string $needle, string $message): void
{
    if (!str_contains($source, $needle)) {
        throw new RuntimeException($message);
    }
}

$appJs = file_get_contents(__DIR__ . '/../app.js');
$indexHtml = file_get_contents(__DIR__ . '/../index.html');
$contractsApi = file_get_contents(__DIR__ . '/../api/contracts.php');
$offersApi = file_get_contents(__DIR__ . '/../api/offers.php');

foreach ([
    'entwurf: "Wartet auf Unterschrift"' => 'Der offene Vertragsstatus wurde nicht umbenannt.',
    'pendingContracts = contracts.filter' => 'Offene Verträge werden nicht separat gruppiert.',
    'signedContracts = contracts.filter' => 'Signierte Verträge werden nicht separat gruppiert.',
    'contract.status === "entwurf"' => 'Die Löschaktion wird für offene Verträge nicht ausgeblendet.',
] as $needle => $message) {
    assert_contract_list_source_contains($appJs, $needle, $message);
}

foreach (['pending-contract-list', 'signed-contract-list', 'Wartet auf Unterschrift', 'Signierte Verträge'] as $needle) {
    assert_contract_list_source_contains($indexHtml, $needle, 'Die gruppierte Vertragsansicht ist unvollständig.');
}

assert_contract_list_source_contains(
    $contractsApi,
    "(string) \$row['status'] === 'entwurf'",
    'Offene Verträge sind in der Vertrags-API nicht gegen Löschen geschützt.'
);
assert_contract_list_source_contains(
    $offersApi,
    "status = 'entwurf'",
    'Offene Verträge können weiterhin indirekt über den Vertragsentwurf gelöscht werden.'
);

echo "PASS: contracts are grouped and pending signatures are protected from deletion\n";
