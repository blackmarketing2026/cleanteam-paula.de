<?php

function recurring_email_placeholder_values(array $customer, array $contract, ?DateTimeImmutable $now = null): array
{
    $now = ($now ?? new DateTimeImmutable('now'))->setTimezone(new DateTimeZone('Europe/Berlin'));
    $date = static fn($value) => empty($value) ? '' : (new DateTimeImmutable($value, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('Europe/Berlin'))->format('d.m.Y');
    $months = [1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
    return [
        'firma' => (string) ($customer['name'] ?? ''),
        'ansprechpartner' => trim(($customer['salutation'] ?? '') . ' ' . ($customer['contact_last_name'] ?? '')),
        'email' => (string) ($customer['email'] ?? ''),
        'telefon' => (string) ($customer['phone'] ?? ''),
        'strasse' => trim(($customer['address'] ?? '') . ' ' . ($customer['house_number'] ?? '')),
        'plz' => (string) ($customer['zip'] ?? ''), 'ort' => (string) ($customer['city'] ?? ''),
        'vertragsbeginn' => $date($contract['start_date'] ?? null),
        'unterschrieben_am' => $date($contract['signed_at'] ?? null),
        'intervall' => (string) ($contract['interval_label'] ?? ''),
        'monatspreis_netto' => isset($contract['price']) ? number_format((float) $contract['price'], 2, ',', '.') . ' €' : '',
        'leistung' => (string) ($contract['service'] ?? ''),
        'leistungsbeschreibung' => (string) ($contract['notes'] ?? ''),
        'datum' => $now->format('d.m.Y'), 'monat' => $months[(int) $now->format('n')], 'jahr' => $now->format('Y'),
    ];
}

function recurring_email_placeholder_context(PDO $pdo, string $customerId): array
{
    $stmt = $pdo->prepare("SELECT ct.id, ct.signed_at, o.start_date, o.interval_label, o.price, o.service, o.notes
        FROM contracts ct INNER JOIN offers o ON o.id = ct.offer_id
        WHERE ct.customer_id = ? AND ct.status = 'signiert'
        ORDER BY ct.signed_at DESC, ct.created_at DESC, ct.id DESC LIMIT 1");
    $stmt->execute([$customerId]);
    $contract = $stmt->fetch() ?: [];
    $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([$customerId]);
    return ['values' => recurring_email_placeholder_values($stmt->fetch() ?: [], $contract),
        'contractSignedAt' => $contract['signed_at'] ?? null];
}

function recurring_email_expand(string $text, array $values, bool $subject = false): string
{
    $expanded = preg_replace_callback('/\{\{\s*([^{}]+?)\s*\}\}/u', static function ($match) use ($values) {
        $key = trim($match[1]);
        if (!array_key_exists($key, $values)) {
            throw new InvalidArgumentException('Unbekannter Platzhalter: {{' . $key . '}}');
        }
        if ($values[$key] === '') {
            throw new InvalidArgumentException('Fuer den Platzhalter {{' . $key . '}} fehlen Daten. Bitte Daten ergaenzen oder Platzhalter entfernen.');
        }
        return $values[$key];
    }, $text);
    if ($subject) {
        $expanded = trim(preg_replace('/[\r\n]+/', ' ', $expanded));
        if (mb_strlen($expanded) > 190) {
            throw new InvalidArgumentException('Der ausgefuellte Betreff ist laenger als 190 Zeichen. Bitte kuerzen.');
        }
    }
    return $expanded;
}

function recurring_email_expand_series(array $series, array $values): array
{
    $series['subject'] = recurring_email_expand($series['subject'], $values, true);
    $series['body'] = recurring_email_expand($series['body'], $values);
    return $series;
}
