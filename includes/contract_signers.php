<?php

function ensure_contracts_second_signer_columns(PDO $pdo): void
{
    foreach (['second_signer_name' => 'VARCHAR(190)', 'second_signature_data' => 'LONGTEXT', 'second_signed_at' => 'DATETIME'] as $column => $type) {
        $stmt = $pdo->query("SHOW COLUMNS FROM contracts LIKE '{$column}'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE contracts ADD COLUMN {$column} {$type} NULL");
        }
    }
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
