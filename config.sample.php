<?php
// Diese Datei nach config.php kopieren und mit den echten Zugangsdaten des
// Hostings befuellen. config.php selbst wird nicht eingecheckt (.gitignore).

return [
    'db' => [
        'host' => 'localhost',
        'name' => 'datenbank_name',
        'user' => 'datenbank_user',
        'pass' => 'datenbank_passwort',
        'charset' => 'utf8mb4',
    ],

    // Zufaelligen, langen Schluessel erzeugen, z. B. mit:
    // php -r "echo bin2hex(random_bytes(32));"
    // Wird zur Verschluesselung des gespeicherten SMTP-Passworts verwendet.
    'app_key' => 'BITTE_ZUFAELLIGEN_64-STELLIGEN_HEX_SCHLUESSEL_EINTRAGEN',

    // Basis-URL der Installation ohne abschliessenden Slash, wird fuer die
    // Kostenvoranschlags-Links in den E-Mails an Kunden verwendet.
    'base_url' => 'https://www.example.de/salemanager',
];
