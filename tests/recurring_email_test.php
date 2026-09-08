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
