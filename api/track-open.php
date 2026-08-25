<?php

// Oeffentlicher Endpunkt (kein Login): E-Mail-Clients laden dieses 1x1-Pixel automatisch,
// wenn Bilder im geoeffneten Angebot/Vertrag-E-Mail angezeigt werden. Der Aufruf selbst ist
// das Tracking-Signal fuer "E-Mail geoeffnet" - siehe api/send-offer.php fuer das eingebettete <img>.

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$token = trim((string) ($_GET['token'] ?? ''));

if ($token !== '') {
    try {
        $pdo = db();
        ensure_offers_email_opened_at_column($pdo);
        $pdo->prepare('UPDATE offers SET email_opened_at = UTC_TIMESTAMP() WHERE token = :token AND email_opened_at IS NULL')
            ->execute(['token' => $token]);
    } catch (Throwable $exception) {
        // Tracking darf das Laden des Bildes im E-Mail-Client nie verhindern.
    }
}

$pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBTAA7');

header('Content-Type: image/gif');
header('Content-Length: ' . strlen($pixel));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
echo $pixel;
