<?php

// Oeffentlicher Endpunkt (kein Login): E-Mail-Clients laden dieses 1x1-Pixel automatisch,
// wenn Bilder im geoeffneten Angebot/Vertrag-E-Mail angezeigt werden. Der Aufruf selbst ist
// das Tracking-Signal fuer "E-Mail geoeffnet" - siehe api/send-offer.php fuer das eingebettete <img>.
//
// Achtung: Dieses Signal ist nicht 100% zuverlaessig. Sicherheits-Gateways (Microsoft Defender,
// Proofpoint, Mimecast, ...) und Apple Mail Privacy Protection laden Bilder oft automatisch vor,
// bevor ein Mensch die Mail je gesehen hat. Bekannte Scanner-User-Agents werden herausgefiltert
// (is_automated_email_scanner) und Aufrufe kurz nach dem Versand - typisch fuer automatisches
// Vorladen - werden ignoriert. Das reduziert Fehlalarme deutlich, garantiert aber keine echte
// Zustellbestaetigung.

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

const MIN_SECONDS_SINCE_SEND_FOR_REAL_OPEN = 5;

$token = trim((string) ($_GET['token'] ?? ''));

if ($token !== '' && !is_automated_email_scanner((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''))) {
    try {
        $pdo = db();
        ensure_offers_email_opened_at_column($pdo);

        $stmt = $pdo->prepare('SELECT sent_at FROM offers WHERE token = :token AND email_opened_at IS NULL');
        $stmt->execute(['token' => $token]);
        $sentAt = $stmt->fetchColumn();

        if ($sentAt !== false && $sentAt !== null) {
            $secondsSinceSend = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->getTimestamp()
                - (new DateTimeImmutable($sentAt, new DateTimeZone('UTC')))->getTimestamp();

            if ($secondsSinceSend >= MIN_SECONDS_SINCE_SEND_FOR_REAL_OPEN) {
                $pdo->prepare('UPDATE offers SET email_opened_at = UTC_TIMESTAMP() WHERE token = :token AND email_opened_at IS NULL')
                    ->execute(['token' => $token]);
            }
        }
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
