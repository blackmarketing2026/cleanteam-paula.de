<?php
header('Content-Type: application/json');
echo json_encode([
    'php_version' => phpversion(),
    'imap_extension' => extension_loaded('imap'),
    'imap_open_exists' => function_exists('imap_open'),
    'openssl' => extension_loaded('openssl'),
]);
