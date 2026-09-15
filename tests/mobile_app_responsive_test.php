<?php

$index = file_get_contents(__DIR__ . '/../index.html');
$styles = file_get_contents(__DIR__ . '/../styles.css');
$app = file_get_contents(__DIR__ . '/../app.js');

foreach (['data-view="overview"', 'data-view="offers-new"', 'data-view="offers-saved"', 'data-view="contracts"', 'id="bottom-menu-button"'] as $needle) {
    if (!str_contains($index, $needle)) {
        throw new RuntimeException('Mobiles Navigationsziel fehlt: ' . $needle);
    }
}

foreach (['aria-controls="mobile-navigation-drawer"', 'id="mobile-navigation-close"'] as $needle) {
    if (!str_contains($index, $needle)) {
        throw new RuntimeException('Zugängliche Drawer-Steuerung fehlt: ' . $needle);
    }
}

foreach (['contract-data-row', 'data-label="Status"', 'data-label="Dokumente"', 'data-label="Zustellung"'] as $needle) {
    if (!str_contains($app, $needle)) {
        throw new RuntimeException('Mobile Vertragskarte ist unvollständig: ' . $needle);
    }
}

foreach (['body.mobile-nav-open', 'height: 100dvh', '.contract-table tr.contract-data-row td::before', 'font-size: 16px', '@media (max-width: 350px)'] as $needle) {
    if (!str_contains($styles, $needle)) {
        throw new RuntimeException('Mobile Schutz- oder Layoutregel fehlt: ' . $needle);
    }
}

echo "PASS: dashboard navigation, forms, dialogs and contract cards are optimized for mobile\n";
