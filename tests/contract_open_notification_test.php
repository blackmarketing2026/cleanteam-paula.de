<?php

require_once __DIR__ . '/../includes/contract_notify.php';

function assert_same($expected, $actual, string $message): void
{
    if ($actual !== $expected) throw new RuntimeException($message);
}

assert_same(
    ['cleanteam@function-concept.de', 'info@cleanteam-group.com'],
    contract_open_recipients(['open_recipients' => '']),
    'Die internen Systemadressen müssen standardmäßig Empfänger sein.'
);
assert_same(
    ['intern@example.com'],
    contract_open_recipients(['open_recipients' => 'intern@example.com']),
    'Eigene Empfänger müssen die Standardadressen ersetzen.'
);
assert_same('tel:+491701234567', contract_call_url('+49 (170) 123 45 67'), 'Der Anruf-Link ist ungültig.');
assert_same(null, contract_call_url('keine Nummer'), 'Ohne Telefonnummer darf kein Anruf-Link entstehen.');
assert_same(true, contract_open_is_notifiable(['status' => 'entwurf', 'quote_status' => 'signing'], 'quote'), 'Ein offener Vertrag nach KVA-Annahme muss melden.');
assert_same(false, contract_open_is_notifiable(['status' => 'entwurf', 'quote_status' => 'sent'], 'quote'), 'Ein bloß geöffneter KVA darf nicht melden.');
assert_same(false, contract_open_is_notifiable(['status' => 'signiert', 'quote_status' => 'signing'], 'contract'), 'Ein unterschriebener Vertrag darf nicht melden.');
assert_same(false, contract_open_is_notifiable(['status' => 'datenschutz_abgelehnt', 'quote_status' => 'signing'], 'contract'), 'Ein abgelehnter Vertrag darf nicht melden.');
$message = contract_open_message_content(['name' => 'Firma <Test>', 'salutation' => 'Herr', 'contact_last_name' => 'Muster', 'phone' => '+49 (170) 123 45 67']);
if (!str_contains($message, 'Firma &lt;Test&gt;') || !str_contains($message, 'href="tel:+491701234567"') || !str_contains($message, 'Kunden jetzt anrufen')) {
    throw new RuntimeException('Die interne E-Mail braucht Kundendaten und einen sicheren Anruf-Button.');
}

echo "PASS: internal recipients, phone link and unsigned-contract notification rules\n";
