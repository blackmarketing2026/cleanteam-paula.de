<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/contract_template.php';

const EMAIL_SIGNATURE_UPLOADS_DIR = __DIR__ . '/../uploads';

function ensure_email_signature_settings_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS email_signature_settings (
          id TINYINT UNSIGNED NOT NULL,
          sender_name VARCHAR(190) NOT NULL DEFAULT \'\',
          sender_role VARCHAR(190) NOT NULL DEFAULT \'\',
          phone VARCHAR(80) NOT NULL DEFAULT \'\',
          mobile VARCHAR(80) NOT NULL DEFAULT \'\',
          email VARCHAR(190) NOT NULL DEFAULT \'\',
          website VARCHAR(190) NOT NULL DEFAULT \'\',
          company_name VARCHAR(190) NOT NULL DEFAULT \'\',
          address_line1 VARCHAR(190) NOT NULL DEFAULT \'\',
          address_line2 VARCHAR(190) NOT NULL DEFAULT \'\',
          extra_text TEXT NULL,
          image_filename VARCHAR(190) NULL,
          updated_at DATETIME NULL,
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $columns = [
        'sender_name' => 'ALTER TABLE email_signature_settings ADD COLUMN sender_name VARCHAR(190) NOT NULL DEFAULT \'\' AFTER id',
        'sender_role' => 'ALTER TABLE email_signature_settings ADD COLUMN sender_role VARCHAR(190) NOT NULL DEFAULT \'\' AFTER sender_name',
        'phone' => 'ALTER TABLE email_signature_settings ADD COLUMN phone VARCHAR(80) NOT NULL DEFAULT \'\' AFTER sender_role',
        'mobile' => 'ALTER TABLE email_signature_settings ADD COLUMN mobile VARCHAR(80) NOT NULL DEFAULT \'\' AFTER phone',
        'email' => 'ALTER TABLE email_signature_settings ADD COLUMN email VARCHAR(190) NOT NULL DEFAULT \'\' AFTER mobile',
        'website' => 'ALTER TABLE email_signature_settings ADD COLUMN website VARCHAR(190) NOT NULL DEFAULT \'\' AFTER email',
        'company_name' => 'ALTER TABLE email_signature_settings ADD COLUMN company_name VARCHAR(190) NOT NULL DEFAULT \'\' AFTER website',
        'address_line1' => 'ALTER TABLE email_signature_settings ADD COLUMN address_line1 VARCHAR(190) NOT NULL DEFAULT \'\' AFTER company_name',
        'address_line2' => 'ALTER TABLE email_signature_settings ADD COLUMN address_line2 VARCHAR(190) NOT NULL DEFAULT \'\' AFTER address_line1',
        'extra_text' => 'ALTER TABLE email_signature_settings ADD COLUMN extra_text TEXT NULL AFTER address_line2',
        'image_filename' => 'ALTER TABLE email_signature_settings ADD COLUMN image_filename VARCHAR(190) NULL AFTER extra_text',
        'updated_at' => 'ALTER TABLE email_signature_settings ADD COLUMN updated_at DATETIME NULL AFTER image_filename',
    ];

    foreach ($columns as $column => $statement) {
        $stmt = $pdo->query("SHOW COLUMNS FROM email_signature_settings LIKE '{$column}'");
        if (!$stmt->fetch()) {
            $pdo->exec($statement);
        }
    }

    $pdo->exec(
        "INSERT IGNORE INTO email_signature_settings (
          id, sender_name, sender_role, website, company_name, address_line1, address_line2
        ) VALUES (
          1,
          'Ihr CleanTeam-Team',
          'Meisterbetrieb Gebaeudereinigung',
          'https://cleanteam-group.com',
          'Clean Team Group SRLS',
          'Service Point: Ober der Muehle 30, 42699 Solingen',
          'Sitz: Via Dorsale 11, 54100 Massa, Italien'
        )"
    );
}

function email_signature_image_url(?string $filename): ?string
{
    if ($filename === null || trim($filename) === '') {
        return null;
    }

    return base_url() . '/uploads/' . rawurlencode($filename);
}

function email_signature_row_to_response(array $row): array
{
    return [
        'senderName' => (string) ($row['sender_name'] ?? ''),
        'senderRole' => (string) ($row['sender_role'] ?? ''),
        'phone' => (string) ($row['phone'] ?? ''),
        'mobile' => (string) ($row['mobile'] ?? ''),
        'email' => (string) ($row['email'] ?? ''),
        'website' => (string) ($row['website'] ?? ''),
        'companyName' => (string) ($row['company_name'] ?? ''),
        'addressLine1' => (string) ($row['address_line1'] ?? ''),
        'addressLine2' => (string) ($row['address_line2'] ?? ''),
        'extraText' => (string) ($row['extra_text'] ?? ''),
        'imageUrl' => email_signature_image_url($row['image_filename'] ?? null),
        'updatedAt' => to_iso($row['updated_at'] ?? null),
    ];
}

function load_email_signature_settings(PDO $pdo): array
{
    ensure_email_signature_settings_table($pdo);

    $stmt = $pdo->query('SELECT * FROM email_signature_settings WHERE id = 1');
    $row = $stmt->fetch();

    if (!$row) {
        $row = [
            'sender_name' => 'Ihr CleanTeam-Team',
            'sender_role' => 'Meisterbetrieb Gebaeudereinigung',
            'phone' => '',
            'mobile' => '',
            'email' => '',
            'website' => CONTRACTOR['website'],
            'company_name' => CONTRACTOR['legal_name'],
            'address_line1' => 'Service Point: ' . CONTRACTOR['service_point_street'] . ', ' . CONTRACTOR['service_point_postal_code'] . ' ' . CONTRACTOR['service_point_city'],
            'address_line2' => 'Sitz: ' . CONTRACTOR['street'] . ', ' . CONTRACTOR['postal_code'] . ' ' . CONTRACTOR['city'] . ', ' . CONTRACTOR['country'],
            'extra_text' => '',
            'image_filename' => null,
            'updated_at' => null,
        ];
    }

    return email_signature_row_to_response($row);
}

function save_email_signature_settings(PDO $pdo, array $input): array
{
    ensure_email_signature_settings_table($pdo);

    $currentStmt = $pdo->query('SELECT image_filename FROM email_signature_settings WHERE id = 1');
    $current = $currentStmt->fetch() ?: ['image_filename' => null];

    $settings = [
        'sender_name' => trim((string) ($input['senderName'] ?? '')),
        'sender_role' => trim((string) ($input['senderRole'] ?? '')),
        'phone' => trim((string) ($input['phone'] ?? '')),
        'mobile' => trim((string) ($input['mobile'] ?? '')),
        'email' => trim((string) ($input['email'] ?? '')),
        'website' => trim((string) ($input['website'] ?? '')),
        'company_name' => trim((string) ($input['companyName'] ?? '')),
        'address_line1' => trim((string) ($input['addressLine1'] ?? '')),
        'address_line2' => trim((string) ($input['addressLine2'] ?? '')),
        'extra_text' => trim((string) ($input['extraText'] ?? '')),
        'image_filename' => $current['image_filename'] ?? null,
    ];

    if ($settings['email'] !== '' && !filter_var($settings['email'], FILTER_VALIDATE_EMAIL)) {
        json_error('Bitte eine gueltige E-Mail-Adresse fuer die Signatur eintragen.', 422);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO email_signature_settings (
          id, sender_name, sender_role, phone, mobile, email, website, company_name,
          address_line1, address_line2, extra_text, image_filename, updated_at
        ) VALUES (
          1, :sender_name, :sender_role, :phone, :mobile, :email, :website, :company_name,
          :address_line1, :address_line2, :extra_text, :image_filename, UTC_TIMESTAMP()
        )
        ON DUPLICATE KEY UPDATE
          sender_name = :sender_name2,
          sender_role = :sender_role2,
          phone = :phone2,
          mobile = :mobile2,
          email = :email2,
          website = :website2,
          company_name = :company_name2,
          address_line1 = :address_line1_update,
          address_line2 = :address_line2_update,
          extra_text = :extra_text2,
          image_filename = :image_filename2,
          updated_at = UTC_TIMESTAMP()'
    );

    $stmt->execute($settings + [
        'sender_name2' => $settings['sender_name'],
        'sender_role2' => $settings['sender_role'],
        'phone2' => $settings['phone'],
        'mobile2' => $settings['mobile'],
        'email2' => $settings['email'],
        'website2' => $settings['website'],
        'company_name2' => $settings['company_name'],
        'address_line1_update' => $settings['address_line1'],
        'address_line2_update' => $settings['address_line2'],
        'extra_text2' => $settings['extra_text'],
        'image_filename2' => $settings['image_filename'],
    ]);

    return load_email_signature_settings($pdo);
}
