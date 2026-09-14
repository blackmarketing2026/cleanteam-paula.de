<?php

$index = file_get_contents(__DIR__ . '/../index.html');
$app = file_get_contents(__DIR__ . '/../app.js');
$styles = file_get_contents(__DIR__ . '/../styles.css');

foreach (['offer-gross-price-preview', 'offer-edit-gross-price-preview'] as $id) {
    if (!str_contains($index, 'id="' . $id . '"')) {
        throw new RuntimeException('Brutto-Preisfeld fehlt: ' . $id);
    }
}

foreach (['const VAT_RATE = 0.19', 'vatSelect.value === "yes"', 'grossOutput.parentElement.hidden', 'vatSelect?.addEventListener("change", update)'] as $needle) {
    if (!str_contains($app, $needle)) {
        throw new RuntimeException('Dynamische Umsatzsteuer-Vorschau ist unvollständig: ' . $needle);
    }
}

if (!str_contains($styles, '.price-preview.has-vat-price')) {
    throw new RuntimeException('Vierspaltiges Layout der Brutto-Preisvorschau fehlt.');
}

echo "PASS: gross price after discount is shown only when VAT is enabled\n";
