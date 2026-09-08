<?php

function ensure_contracts_second_signer_columns(PDO $pdo): void
{
    foreach (['second_signer_name' => 'VARCHAR(190)', 'second_signature_data' => 'LONGTEXT', 'second_signed_at' => 'DATETIME', 'additional_signers' => 'LONGTEXT'] as $column => $type) {
        $stmt = $pdo->query("SHOW COLUMNS FROM contracts LIKE '{$column}'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE contracts ADD COLUMN {$column} {$type} NULL");
        }
    }
}

function contract_additional_signers(?array $contract): array
{
    if (!empty($contract['additional_signers'])) {
        return json_decode($contract['additional_signers'], true) ?: [];
    }
    return empty($contract['second_signature_data']) ? [] : [[
        'name' => $contract['second_signer_name'],
        'signatureDataUrl' => $contract['second_signature_data'],
        'signedAt' => $contract['second_signed_at'],
        'confirmed' => true,
    ]];
}

function validate_additional_signers($signers): array
{
    if (!is_array($signers) || !array_is_list($signers) || count($signers) > 4) {
        throw new InvalidArgumentException('Es koennen maximal fuenf Personen unterschreiben.');
    }
    foreach ($signers as &$signer) {
        if (!is_array($signer) || !is_string($signer['name'] ?? null)
            || trim($signer['name']) === '' || mb_strlen($signer['name']) > 190
            || ($signer['confirmed'] ?? false) !== true
            || !is_string($signer['signatureDataUrl'] ?? null)
            || !validate_contract_signature($signer['signatureDataUrl'])) {
            throw new InvalidArgumentException('Bitte Namen, Bestaetigung und Unterschrift jeder weiteren Person angeben.');
        }
        $signer = ['name' => trim($signer['name']), 'signatureDataUrl' => $signer['signatureDataUrl'],
            'confirmed' => true, 'signedAt' => gmdate('Y-m-d H:i:s')];
    }
    return $signers;
}

function validate_contract_signature(string $value): bool
{
    if (strlen($value) > 2500000 || !str_starts_with($value, 'data:image/png;base64,')) {
        return false;
    }
    $binary = base64_decode(substr($value, 22), true);
    $info = $binary === false ? false : @getimagesizefromstring($binary);
    return $info !== false && $info[2] === IMAGETYPE_PNG && $info[0] <= 4096 && $info[1] <= 4096;
}
