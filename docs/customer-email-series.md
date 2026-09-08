# Wiederkehrende Kunden-E-Mails

In der Vertragsliste öffnet **E-Mail-Serie** den Editor. Pro Kunde wird eine Serie
gespeichert, auch wenn der Kunde mehrere unterschriebene Verträge hat.
Ein aktiver Kunde ist hier ein nicht gelöschter Kunde mit mindestens einem
unterschriebenen Vertrag. Eine Serie kann als Entwurf gespeichert, aktiviert oder
pausiert werden. Ohne Aktivierung wird nichts versendet. Eine Pausierung stoppt
künftige Versandläufe; ein bereits vom Mailserver angenommener Versand ist nicht rückrufbar.

Betreff und Inhalt sind Klartext. Absätze bleiben erhalten; die vorhandene
CleanTeam-E-Mail-Vorlage und Signatur werden verwendet. Ein optionaler Anhang wird
geschützt in der Datenbank gespeichert und bei jeder Wiederholung erneut angehängt.
Anhänge sind nur über die angemeldete Anwendung herunterladbar, maximal 5 MB
(zusätzlich gelten `upload_max_filesize`, `post_max_size` und das Datenbank-Paketlimit).

## Automatischer Aufruf bei ALL-INKL

Die Versandlogik läuft vollständig in PHP. PHP benötigt einen regelmäßigen
Hosting-Aufruf, damit der Versand auch ohne geöffnetes Dashboard funktioniert.
Im ALL-INKL KAS einen HTTP-Cronjob **alle fünf Minuten** einrichten:

`https://<Installationsadresse>/api/cron-customer-emails.php?key=<cron_secret>`

Der Schlüssel stammt aus der bestehenden `config.php`. Nicht in Git einchecken
oder weitergeben. Alternativ akzeptiert der Endpunkt den Header `X-Cron-Key`.
Ein vorhandener Cronjob nur für `cron-reminders.php` ersetzt diesen Aufruf nicht.
Der Editor zeigt an, wann der neue Versandlauf zuletzt erreichbar war.

Die Tabellen werden beim ersten API-/Cron-Aufruf automatisch angelegt. Es werden
keine Serien automatisch aktiviert und beim Deployment keine Kunden angeschrieben.
SMTP-Einstellungen und die bestehenden Schalter für Kunden- und Vertrags-E-Mails
werden berücksichtigt.

## Zeitplan und Fehlerbehandlung

- Standardzeit 09:00, je Serie änderbar, Zeitzone Europe/Berlin mit Sommerzeit.
- Monatstage 29–31 werden in kürzeren Monaten auf den letzten Tag gekürzt.
- Aktivierung plant den nächsten zukünftigen Termin; kein Sofortversand.
- Verpasste Termine werden zu einem Versand zusammengefasst, keine Nachholflut.
- Versand erfolgt beim ersten Hosting-Aufruf ab Fälligkeit; bei vielen Kunden
  werden je Aufruf bis zu 25 Serien unter einem Laufzeitbudget verarbeitet.
- Sperren je Kunde und ein eindeutiges Versandprotokoll verhindern gleichzeitige
  doppelte Versandversuche. Editor und Versand verwenden dieselbe Sperre.
- Bei unklarem SMTP-Ergebnis oder Prozessabbruch wird die Serie pausiert; keine
  automatische Wiederholung, da die Nachricht bereits angenommen worden sein kann.
  Zustellung im Mailkonto prüfen und anschließend bei Bedarf erneut aktivieren.

Prüfung ohne Kundendaten: `php tests/recurring_email_test.php`.
