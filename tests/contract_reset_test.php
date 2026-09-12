<?php

function assert_source_contains(string $source, string $needle, string $message): void
{
    if (!str_contains($source, $needle)) {
        throw new RuntimeException($message);
    }
}

$contractsApi = file_get_contents(__DIR__ . '/../api/contracts.php');
$contractPage = file_get_contents(__DIR__ . '/../contract.php');
$contractTemplate = file_get_contents(__DIR__ . '/../includes/contract_template.php');
$appJs = file_get_contents(__DIR__ . '/../app.js');
$sendOffer = file_get_contents(__DIR__ . '/../api/send-offer.php');

foreach ([
    'DELETE FROM contract_documents' => 'Gespeicherte Vertragsdokumente werden nicht entfernt.',
    'DELETE FROM contracts' => 'Der Vertragsdatensatz wird nicht entfernt.',
    'DELETE FROM quote_documents' => 'Der alte Kostenvoranschlag wird nicht entfernt.',
    'token = :token' => 'Der bisherige öffentliche Vertragslink wird nicht ungültig gemacht.',
    "quote_status = 'entwurf'" => 'Der Kostenvoranschlagsprozess wird nicht zurückgesetzt.',
    'quote_token = NULL' => 'Der Kostenvoranschlagslink wird nicht ungültig gemacht.',
    'quote_accepted_at = NULL' => 'Der Annahmezeitpunkt wird nicht zurückgesetzt.',
    'agb_snapshot_text = NULL' => 'Der alte AGB-Nachweis wird nicht entfernt.',
    'reminder1_sent_at = NULL' => 'Die Erinnerungszeitpunkte werden nicht zurückgesetzt.',
] as $needle => $message) {
    assert_source_contains($contractsApi, $needle, $message);
}

assert_source_contains($appJs, '&preview=1', 'Die Vertragsvorschau ist nicht ausdrücklich als Vorschau gekennzeichnet.');
assert_source_contains($contractPage, "'excludeAgb' => \$isPreview", 'Die AGB werden in der Vertragsvorschau nicht ausgeblendet.');
assert_source_contains($contractPage, "'excludeProtocol' => \$isPreview", 'Das Signaturprotokoll wird in der Vertragsvorschau nicht ausgeblendet.');
assert_source_contains($contractPage, "'excludeContractorSignature' => \$isPreview", 'Die CleanTeam-Unterschrift wird in der Vertragsvorschau nicht ausgeblendet.');
assert_source_contains($contractTemplate, '$isCleanTeamCopy && $isSigned && empty($options[\'excludeProtocol\'])', 'Das Signaturprotokoll wird auch für nicht unterschriebene Verträge gerendert.');
assert_source_contains($contractTemplate, "empty(\$options['excludeContractorSignature']) && \$contractorSignatureDataUrl !== null", 'Die CleanTeam-Unterschrift berücksichtigt den Vorschau-Ausschluss nicht.');

if (str_contains($sendOffer, 'Der Kostenvoranschlag wurde bereits angenommen.')) {
    throw new RuntimeException('Ein bereits versendeter Kostenvoranschlag wird weiterhin für den erneuten Versand gesperrt.');
}
assert_source_contains($sendOffer, "\$contractStatus !== 'signiert'", 'Ein erneuter Versand berücksichtigt den tatsächlichen Vertragsstatus nicht.');

echo "PASS: contract deletion resets completion data and quote resend remains available\n";
