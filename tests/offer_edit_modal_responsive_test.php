<?php

$index = file_get_contents(__DIR__ . '/../index.html');
$styles = file_get_contents(__DIR__ . '/../styles.css');

foreach (['offer-edit-wide', 'offer-edit-subgrid', 'mobile-app-20260915'] as $needle) {
    if (!str_contains($index, $needle)) {
        throw new RuntimeException('Responsive Struktur oder Cache-Version des Bearbeitungsdialogs fehlt: ' . $needle);
    }
}

foreach (['max-width: 1040px', '@media (max-width: 700px)', 'max-height: calc(100dvh - 16px)', 'position: sticky'] as $needle) {
    if (!str_contains($styles, $needle)) {
        throw new RuntimeException('Desktop- oder Mobilregel des Bearbeitungsdialogs fehlt: ' . $needle);
    }
}

echo "PASS: offer edit modal is wide on desktop and optimized for mobile\n";
