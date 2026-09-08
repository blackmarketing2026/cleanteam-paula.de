<?php

// Pure scheduling tests: no production database or email connection.
require_once __DIR__ . '/../includes/recurring_email.php';

$cases = [
    ['monthly', 31, '09:00', '2026-01-31 09:01:00 Europe/Berlin', '2026-02-28 08:00:00'],
    ['monthly', 31, '09:00', '2028-01-31 09:01:00 Europe/Berlin', '2028-02-29 08:00:00'],
    ['monthly', 31, '09:00', '2026-02-28 09:01:00 Europe/Berlin', '2026-03-31 07:00:00'],
    ['monthly', 1, '09:00', '2026-12-31 09:00:00 Europe/Berlin', '2027-01-01 08:00:00'],
    ['weekly', 1, '09:00', '2026-09-07 08:59:00 Europe/Berlin', '2026-09-07 07:00:00'],
    ['weekly', 1, '09:00', '2026-09-07 09:00:00 Europe/Berlin', '2026-09-14 07:00:00'],
    ['weekly', 1, '09:00', '2026-03-23 10:00:00 Europe/Berlin', '2026-03-30 07:00:00'],
    ['weekly', 1, '09:00', '2026-10-19 10:00:00 Europe/Berlin', '2026-10-26 08:00:00'],
    ['weekly', 7, '02:30', '2026-03-29 04:00:00 Europe/Berlin', '2026-04-05 00:30:00'],
];
foreach ($cases as [$frequency, $day, $time, $after, $expected]) {
    $actual = recurring_email_next_run($frequency, $day, $time, new DateTimeImmutable($after));
    if ($actual !== $expected) {
        throw new RuntimeException("{$after}: expected {$expected}, got {$actual}");
    }
}
foreach ([['weekly', 8, '09:00'], ['monthly', 0, '09:00'], ['daily', 1, '09:00'], ['monthly', 1, '24:00']] as $bad) {
    try {
        recurring_email_next_run(...[...$bad, new DateTimeImmutable('now')]);
    } catch (InvalidArgumentException $exception) {
        continue;
    }
    throw new RuntimeException('Invalid schedule was accepted.');
}
echo "PASS: monthly/year boundaries, leap year, weekly schedules, DST and invalid input\n";

$customer = ['email' => 'contract@example.com'];
if (recurring_email_recipient([], $customer) !== 'contract@example.com'
    || recurring_email_recipient(['recipient_mode' => 'contract', 'recipient_email' => 'ignored@example.com'], $customer) !== 'contract@example.com'
    || recurring_email_recipient(['recipient_mode' => 'manual', 'recipient_email' => ' office@example.com '], $customer) !== 'office@example.com') {
    throw new RuntimeException('Recipient selection failed.');
}
foreach ([['recipient_mode' => 'invalid'], ['recipient_mode' => 'manual', 'recipient_email' => ''],
    ['recipient_mode' => 'manual', 'recipient_email' => "a@example.com\r\nBcc: b@example.com"]] as $bad) {
    try {
        recurring_email_recipient($bad, $customer);
    } catch (InvalidArgumentException $exception) {
        continue;
    }
    throw new RuntimeException('Invalid recipient accepted.');
}
echo "PASS: contract/manual recipients and invalid addresses\n";

$values = recurring_email_placeholder_values(
    ['name' => 'Muster & Co', 'salutation' => 'Frau', 'contact_last_name' => 'Muster'],
    ['price' => '1234.5', 'start_date' => '2026-09-01', 'interval_label' => 'Woechentlich'],
    new DateTimeImmutable('2026-08-31 23:30:00 UTC')
);
if ($values['datum'] !== '01.09.2026' || $values['monat'] !== 'September'
    || $values['monatspreis_netto'] !== '1.234,50 €'
    || recurring_email_expand('Hallo {{ ansprechpartner }}, {{firma}}', $values) !== 'Hallo Frau Muster, Muster & Co') {
    throw new RuntimeException('Placeholder formatting failed.');
}
if (recurring_email_expand('{{firma}}', ['firma' => '{{datum}}', 'datum' => 'secret']) !== '{{datum}}') {
    throw new RuntimeException('Replacement must not expand nested customer data.');
}
if (recurring_email_expand('{{firma}}', ['firma' => "Firma\r\nBcc: test"], true) !== 'Firma Bcc: test') {
    throw new RuntimeException('Subject newlines not normalized.');
}
foreach (['{{unknown}}', '{{telefon}}'] as $invalid) {
    try {
        recurring_email_expand($invalid, $values);
    } catch (InvalidArgumentException $e) {
        continue;
    }
    throw new RuntimeException('Unknown or missing placeholder accepted.');
}
echo "PASS: placeholder values, German formatting, timezone, unknown/missing data and nonrecursive expansion\n";
