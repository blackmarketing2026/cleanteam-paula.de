<?php
$token = htmlspecialchars($_GET['token'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="de">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <title>CleanTeam - Ihr Vertrag</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="styles.css?v=signer-identity-step-20260825-1" />
  </head>
  <body data-token="<?php echo $token; ?>">
    <main class="public-shell">
      <header class="public-header">
        <div class="brand-mark" aria-hidden="true"><span>CT</span></div>
        <div>
          <strong>CleanTeam</strong>
          <span>Ihr pers&ouml;nlicher Vertrag</span>
        </div>
      </header>

      <div class="public-card" id="public-card">
        <section id="screen-loading" class="public-screen active-screen">
          <p class="muted">Vertrag wird geladen ...</p>
        </section>

        <section id="screen-error" class="public-screen">
          <h2>Link nicht verf&uuml;gbar</h2>
          <p id="error-message" class="muted"></p>
        </section>

        <section id="screen-abgelehnt" class="public-screen">
          <h2>Vielen Dank f&uuml;r Ihre R&uuml;ckmeldung</h2>
          <p class="muted">
            Bitte kontaktieren Sie CleanTeam, damit wir den Vertrag f&uuml;r Sie anpassen k&ouml;nnen.
            Wir melden uns schnellstm&ouml;glich bei Ihnen.
          </p>
        </section>

        <section id="screen-datenschutz" class="public-screen wizard-screen">
          <h2>D&uuml;rfen wir Ihre Daten speichern?</h2>
          <p class="muted">
            D&uuml;rfen wir Ihre personenbezogenen Daten f&uuml;r die Erstellung dieses Vertrags speichern und verarbeiten?
          </p>
          <div class="form-actions">
            <button class="ghost-button" data-yesno="no" type="button">Nein</button>
            <button class="primary-button" data-yesno="yes" type="button">Ja, einverstanden</button>
          </div>
        </section>

        <section id="screen-daten" class="public-screen wizard-screen">
          <p class="step-indicator">Schritt 2 von 5</p>
          <h2>Sind diese Angaben korrekt?</h2>
          <dl id="data-check-list" class="data-check"></dl>
          <div class="form-actions">
            <button class="ghost-button" data-yesno="no" type="button">Nein</button>
            <button class="primary-button" data-yesno="yes" type="button">Ja, korrekt</button>
          </div>
        </section>

        <section id="screen-leistung" class="public-screen wizard-screen">
          <p class="step-indicator">Schritt 3 von 5</p>
          <h2>Leistungsumfang</h2>
          <div id="service-details" class="public-service-summary"></div>
          <div class="form-actions">
            <button class="primary-button" data-next="bedingungen" type="button">Weiter</button>
          </div>
        </section>

        <section id="screen-bedingungen" class="public-screen wizard-screen">
          <p class="step-indicator">Schritt 4 von 5</p>
          <h2>Vertragsbedingungen</h2>
          <div class="terms-text">
            <p>Bitte best&auml;tigen Sie den Auftrag, bevor Sie zur Unterschrift weitergehen.</p>
            <label class="public-confirm-check">
              <input id="terms-confirmation" type="checkbox" />
              <span>
                Alles passt &ndash; ich best&auml;tige den Auftrag und stimme den
                Vertragsbedingungen zu.
              </span>
            </label>
          </div>
          <div class="form-actions">
            <button id="terms-continue" class="primary-button" data-next="identitaet" type="button" disabled>Weiter</button>
          </div>
        </section>

        <section id="screen-identitaet" class="public-screen wizard-screen">
          <p class="step-indicator">Schritt 5 von 5</p>
          <h2 id="identity-check-question">Sind Sie die im Vertrag genannte Person?</h2>
          <div id="identity-check-step-1" class="form-actions">
            <button id="identity-check-no" class="ghost-button" type="button">Nein</button>
            <button id="identity-check-yes" class="primary-button" type="button">Ja, das bin ich</button>
          </div>

          <div id="identity-check-step-2" hidden>
            <p class="muted">Wer sind Sie? Sind Sie berechtigt, diesen Vertrag zu unterschreiben?</p>
            <label class="modal-field">
              Bitte tragen Sie Ihren Namen ein
              <input id="identity-check-name" type="text" autocomplete="name" />
            </label>
            <div class="form-actions">
              <button id="identity-check-authorized-no" class="ghost-button" type="button">Nein</button>
              <button id="identity-check-authorized-yes" class="primary-button" type="button" disabled>Ja, ich bin berechtigt</button>
            </div>
          </div>
        </section>

        <section id="screen-signatur" class="public-screen wizard-screen">
          <h2>Vertrag unterschreiben</h2>
          <p class="muted">Unterschreiben Sie digital mit dem Finger, Stift oder der Maus.</p>
          <div class="signature-area">
            <canvas id="signature-pad" width="900" height="260" aria-label="Signaturfeld"></canvas>
            <div class="form-actions">
              <button id="clear-signature" class="ghost-button" type="button">Leeren</button>
            </div>
          </div>
          <div class="form-actions">
            <button id="save-signature" class="primary-button" type="button">Vertrag jetzt unterschreiben</button>
          </div>
        </section>

        <section id="screen-fertig" class="public-screen">
          <h2>Willkommen bei CleanTeam!</h2>
          <p class="muted">
            Vielen Dank f&uuml;r Ihr Vertrauen &ndash; Ihr Vertrag ist erfolgreich unterschrieben. Wir freuen uns,
            Sie als Kunden begr&uuml;&szlig;en zu d&uuml;rfen, und stehen Ihnen jederzeit gerne zur Verf&uuml;gung.
            Den vollst&auml;ndigen Vertrag k&ouml;nnen Sie unten einsehen, ausdrucken oder als PDF speichern.
          </p>
          <div class="form-actions">
            <a id="print-final-contract" class="secondary-button" href="#" target="_blank" rel="noopener">
              Vertrag &ouml;ffnen / als PDF speichern
            </a>
          </div>
          <iframe id="final-contract-frame" class="contract-frame"></iframe>
        </section>
      </div>
    </main>

    <div id="toast" class="toast" role="status" aria-live="polite" hidden></div>

    <script src="public.js?v=signer-identity-step-20260825-1"></script>
  </body>
</html>
