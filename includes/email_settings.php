<?php

require_once __DIR__ . '/helpers.php';

function ensure_email_delivery_settings_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS email_delivery_settings (
          id TINYINT UNSIGNED NOT NULL,
          customer_emails_enabled TINYINT(1) NOT NULL DEFAULT 1,
          offer_emails_enabled TINYINT(1) NOT NULL DEFAULT 1,
          contract_emails_enabled TINYINT(1) NOT NULL DEFAULT 1,
          mailbox_emails_enabled TINYINT(1) NOT NULL DEFAULT 1,
          internal_contract_notifications_enabled TINYINT(1) NOT NULL DEFAULT 1,
          test_emails_enabled TINYINT(1) NOT NULL DEFAULT 1,
          updated_at DATETIME NULL,
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $columns = [
        'customer_emails_enabled' => 'ALTER TABLE email_delivery_settings ADD COLUMN customer_emails_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER id',
        'offer_emails_enabled' => 'ALTER TABLE email_delivery_settings ADD COLUMN offer_emails_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER customer_emails_enabled',
        'contract_emails_enabled' => 'ALTER TABLE email_delivery_settings ADD COLUMN contract_emails_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER offer_emails_enabled',
        'mailbox_emails_enabled' => 'ALTER TABLE email_delivery_settings ADD COLUMN mailbox_emails_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER contract_emails_enabled',
        'internal_contract_notifications_enabled' => 'ALTER TABLE email_delivery_settings ADD COLUMN internal_contract_notifications_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER mailbox_emails_enabled',
        'test_emails_enabled' => 'ALTER TABLE email_delivery_settings ADD COLUMN test_emails_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER internal_contract_notifications_enabled',
        'updated_at' => 'ALTER TABLE email_delivery_settings ADD COLUMN updated_at DATETIME NULL AFTER test_emails_enabled',
    ];

    foreach ($columns as $column => $statement) {
        $stmt = $pdo->query("SHOW COLUMNS FROM email_delivery_settings LIKE '{$column}'");
        if (!$stmt->fetch()) {
            $pdo->exec($statement);
        }
    }

    $pdo->exec(
        'INSERT IGNORE INTO email_delivery_settings (
          id,
          customer_emails_enabled,
          offer_emails_enabled,
          contract_emails_enabled,
          mailbox_emails_enabled,
          internal_contract_notifications_enabled,
          test_emails_enabled
        ) VALUES (1, 1, 1, 1, 1, 1, 1)'
    );
}

function email_delivery_row_to_response(array $row): array
{
    return [
        'customerEmailsEnabled' => (bool) $row['customer_emails_enabled'],
        'offerEmailsEnabled' => (bool) $row['offer_emails_enabled'],
        'contractEmailsEnabled' => (bool) $row['contract_emails_enabled'],
        'mailboxEmailsEnabled' => (bool) $row['mailbox_emails_enabled'],
        'internalContractNotificationsEnabled' => (bool) $row['internal_contract_notifications_enabled'],
        'testEmailsEnabled' => (bool) $row['test_emails_enabled'],
        'updatedAt' => to_iso($row['updated_at'] ?? null),
    ];
}

function load_email_delivery_settings(PDO $pdo): array
{
    ensure_email_delivery_settings_table($pdo);

    $stmt = $pdo->query('SELECT * FROM email_delivery_settings WHERE id = 1');
    $row = $stmt->fetch();

    if (!$row) {
        $row = [
            'customer_emails_enabled' => 1,
            'offer_emails_enabled' => 1,
            'contract_emails_enabled' => 1,
            'mailbox_emails_enabled' => 1,
            'internal_contract_notifications_enabled' => 1,
            'test_emails_enabled' => 1,
            'updated_at' => null,
        ];
    }

    return email_delivery_row_to_response($row);
}

function save_email_delivery_settings(PDO $pdo, array $input): array
{
    $current = load_email_delivery_settings($pdo);
    $settings = [
        'customerEmailsEnabled' => array_key_exists('customerEmailsEnabled', $input) ? (bool) $input['customerEmailsEnabled'] : $current['customerEmailsEnabled'],
        'offerEmailsEnabled' => array_key_exists('offerEmailsEnabled', $input) ? (bool) $input['offerEmailsEnabled'] : $current['offerEmailsEnabled'],
        'contractEmailsEnabled' => array_key_exists('contractEmailsEnabled', $input) ? (bool) $input['contractEmailsEnabled'] : $current['contractEmailsEnabled'],
        'mailboxEmailsEnabled' => array_key_exists('mailboxEmailsEnabled', $input) ? (bool) $input['mailboxEmailsEnabled'] : $current['mailboxEmailsEnabled'],
        'internalContractNotificationsEnabled' => array_key_exists('internalContractNotificationsEnabled', $input) ? (bool) $input['internalContractNotificationsEnabled'] : $current['internalContractNotificationsEnabled'],
        'testEmailsEnabled' => array_key_exists('testEmailsEnabled', $input) ? (bool) $input['testEmailsEnabled'] : $current['testEmailsEnabled'],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO email_delivery_settings (
          id,
          customer_emails_enabled,
          offer_emails_enabled,
          contract_emails_enabled,
          mailbox_emails_enabled,
          internal_contract_notifications_enabled,
          test_emails_enabled,
          updated_at
        ) VALUES (
          1,
          :customer_emails_enabled,
          :offer_emails_enabled,
          :contract_emails_enabled,
          :mailbox_emails_enabled,
          :internal_contract_notifications_enabled,
          :test_emails_enabled,
          UTC_TIMESTAMP()
        )
        ON DUPLICATE KEY UPDATE
          customer_emails_enabled = :customer_emails_enabled2,
          offer_emails_enabled = :offer_emails_enabled2,
          contract_emails_enabled = :contract_emails_enabled2,
          mailbox_emails_enabled = :mailbox_emails_enabled2,
          internal_contract_notifications_enabled = :internal_contract_notifications_enabled2,
          test_emails_enabled = :test_emails_enabled2,
          updated_at = UTC_TIMESTAMP()'
    );

    $values = [
        'customer_emails_enabled' => $settings['customerEmailsEnabled'] ? 1 : 0,
        'offer_emails_enabled' => $settings['offerEmailsEnabled'] ? 1 : 0,
        'contract_emails_enabled' => $settings['contractEmailsEnabled'] ? 1 : 0,
        'mailbox_emails_enabled' => $settings['mailboxEmailsEnabled'] ? 1 : 0,
        'internal_contract_notifications_enabled' => $settings['internalContractNotificationsEnabled'] ? 1 : 0,
        'test_emails_enabled' => $settings['testEmailsEnabled'] ? 1 : 0,
    ];
    $stmt->execute($values + [
        'customer_emails_enabled2' => $values['customer_emails_enabled'],
        'offer_emails_enabled2' => $values['offer_emails_enabled'],
        'contract_emails_enabled2' => $values['contract_emails_enabled'],
        'mailbox_emails_enabled2' => $values['mailbox_emails_enabled'],
        'internal_contract_notifications_enabled2' => $values['internal_contract_notifications_enabled'],
        'test_emails_enabled2' => $values['test_emails_enabled'],
    ]);

    return load_email_delivery_settings($pdo);
}

function email_delivery_is_allowed(PDO $pdo, string $type): bool
{
    $settings = load_email_delivery_settings($pdo);

    switch ($type) {
        case 'offer':
            return $settings['customerEmailsEnabled'] && $settings['offerEmailsEnabled'];
        case 'contract_customer':
            return $settings['customerEmailsEnabled'] && $settings['contractEmailsEnabled'];
        case 'mailbox':
            return $settings['customerEmailsEnabled'] && $settings['mailboxEmailsEnabled'];
        case 'internal_contract_notification':
            return $settings['internalContractNotificationsEnabled'];
        case 'test':
            return $settings['testEmailsEnabled'];
        case 'contract_notification_test':
            return $settings['internalContractNotificationsEnabled'] && $settings['testEmailsEnabled'];
        default:
            return false;
    }
}

function email_delivery_disabled_message(string $type): string
{
    switch ($type) {
        case 'offer':
            return 'E-Mail-Versand fuer Kostenvoranschlaege ist in den Einstellungen ausgeschaltet.';
        case 'contract_customer':
            return 'E-Mail-Versand fuer Kundenvertraege ist in den Einstellungen ausgeschaltet.';
        case 'mailbox':
            return 'Manueller E-Mail-Versand aus dem Postfach ist in den Einstellungen ausgeschaltet.';
        case 'internal_contract_notification':
            return 'Interne Vertragsbenachrichtigungen sind in den Einstellungen ausgeschaltet.';
        case 'test':
        case 'contract_notification_test':
            return 'Test-E-Mails sind in den Einstellungen ausgeschaltet.';
        default:
            return 'E-Mail-Versand ist in den Einstellungen ausgeschaltet.';
    }
}

function email_delivery_assert_allowed(PDO $pdo, string $type): void
{
    if (!email_delivery_is_allowed($pdo, $type)) {
        json_error(email_delivery_disabled_message($type), 422);
    }
}
