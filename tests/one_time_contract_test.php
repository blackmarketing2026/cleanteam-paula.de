<?php
require_once __DIR__ . '/../includes/contract_template.php';
$offer = ['price'=>100, 'vat_applicable'=>1, 'start_date'=>'2026-12-12', 'created_at'=>'2026-11-11', 'interval_label'=>'Einmalig', 'square_meters'=>0, 'service'=>'Eventreinigung', 'notes'=>"Einsatzzeit: 20:00 Uhr\n\nReinigung", 'customer_obligations_note'=>'Zugang bereitstellen'];
$customer = ['name'=>'Test GmbH','address'=>'Musterstrasse','house_number'=>'1','zip'=>'12345','city'=>'Berlin','salutation'=>'','contact_last_name'=>'Test'];
$body = render_contract_template_body(one_time_contract_template_html(), contract_template_placeholder_map($offer, $customer, null, true));
foreach (['12.12.2026', '20:00 Uhr', '100,00', '119,00', 'Zugang bereitstellen'] as $expected) {
    if (!str_contains($body, $expected)) throw new RuntimeException('Missing: ' . $expected);
}
foreach (['monatliche Pauschal', 'regulären Arbeitstag', 'Tariferhöhung', '{{'] as $forbidden) {
    if (str_contains($body, $forbidden)) throw new RuntimeException('Unexpected: ' . $forbidden);
}
$offer['vat_applicable'] = 0;
$body = render_contract_template_body(one_time_contract_template_html(), contract_template_placeholder_map($offer, $customer, null, true));
if (!str_contains($body, 'keine Umsatzsteuer') || str_contains($body, '119,00')) throw new RuntimeException('Incorrect VAT');
echo "PASS: one-time contract date, service, price, VAT and obligations\n";
