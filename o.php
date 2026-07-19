<?php
$token = htmlspecialchars($_GET['token'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="de">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <title>CleanTeam - Ihr Kostenvoranschlag</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="styles.css" />
  </head>
  <body data-token="<?php echo $token; ?>">
    <main class="public-shell">
      <header class="public-header">
        <div class="brand-mark" aria-hidden="true"><span>CT</span></div>
        <div>
          <strong>CleanTeam</strong>
          <span>Ihr pers&ouml;nlicher Kostenvoranschlag</span>
        </div>
      </header>

      <div class="public-card" id="public-card">
        <section id="screen-loading" class="public-screen active-screen">
          <p class="muted">Kostenvoranschlag wird geladen ...</p>
        </section>

        <section id="screen-error" class="public-screen">
          <h2>Link nicht verf&uuml;gbar</h2>
          <p id="error-message" class="muted"></p>
        </section>

        <section id="screen-offer" class="public-screen">
          <p class="eyebrow">Kostenvoranschlag</p>
          <h2>Ihr individueller Reinigungs-Kostenvoranschlag</h2>
          <dl id="offer-summary" class="data-check"></dl>
          <p id="offer-validity" class="muted"></p>
          <p class="question">Sind diese Daten korrekt?</p>
          <div class="form-actions">
            <button id="start-contract" class="primary-button" type="button">
              Schritt 1 f&uuml;r die Vertragserstellung
            </button>
          </div>
        </section>

        <section id="screen-abgelehnt" class="public-screen">
          <h2>Vielen Dank f&uuml;r Ihre R&uuml;ckmeldung</h2>
          <p class="muted">
            Bitte kontaktieren Sie CleanTeam, damit wir den Kostenvoranschlag f&uuml;r Sie anpassen k&ouml;nnen.
            Wir melden uns schnellstm&ouml;glich bei Ihnen.
          </p>
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

        <section id="screen-vollmacht" class="public-screen wizard-screen">
          <p class="step-indicator">Schritt 3 von 5</p>
          <h2>Sind Sie berechtigt, diesen Vertrag zu unterschreiben?</h2>
          <div id="authorization-question-actions" class="form-actions">
            <button class="ghost-button" id="auth-no" type="button">Nein, Vollmacht erfassen</button>
            <button class="primary-button" id="auth-yes" type="button">Ja, bin ich</button>
          </div>
          <div id="representation-field" class="representation-field" hidden>
            <label>
              Name des Ansprechpartners, der die Vollmacht erteilt *
              <input id="authorization-grantor-name" type="text" autocomplete="name" />
            </label>
            <label>
              Firmenadresse *
              <select id="authorization-address"></select>
            </label>
            <div class="form-actions">
              <button class="ghost-button" id="authorization-back" type="button">Zur&uuml;ck</button>
              <button class="primary-button" id="representation-continue" type="button">Vollmacht speichern</button>
            </div>
          </div>
        </section>

        <section id="screen-leistung" class="public-screen wizard-screen">
          <p class="step-indicator">Schritt 4 von 5</p>
          <h2>Leistungsumfang</h2>
          <div id="service-details" class="public-service-summary"></div>
          <div class="form-actions">
            <button class="primary-button" data-next="bedingungen" type="button">Weiter</button>
          </div>
        </section>

        <section id="screen-bedingungen" class="public-screen wizard-screen">
          <p class="step-indicator">Schritt 5 von 5</p>
          <h2>Vertragsbedingungen</h2>
          <div class="terms-text">
            <p>Bitte best&auml;tigen Sie den Auftrag, bevor Sie zur Unterschrift weitergehen.</p>
            <label class="public-confirm-check">
              <input id="terms-confirmation" type="checkbox" />
              <span>Ja, ich best&auml;tige den Auftrag f&uuml;r die vereinbarten Reinigungsleistungen.</span>
            </label>
          </div>
          <div class="form-actions">
            <button id="terms-continue" class="primary-button" data-next="signatur" type="button" disabled>Weiter zur Unterschrift</button>
          </div>
        </section>

        <section id="screen-signatur" class="public-screen wizard-screen">
          <h2>Vertrag unterschreiben</h2>
          <p class="muted">Bitte unterschreiben Sie mit dem Finger, Stift oder der Maus.</p>
          <div class="signature-area">
            <canvas id="signature-pad" width="900" height="260" aria-label="Signaturfeld"></canvas>
            <div class="form-actions">
              <button id="clear-signature" class="ghost-button" type="button">Leeren</button>
              <button id="save-signature" class="primary-button" type="button">Vertrag jetzt unterschreiben</button>
            </div>
          </div>
        </section>

        <section id="screen-fertig" class="public-screen">
          <h2>Vielen Dank, Ihr Vertrag ist unterschrieben!</h2>
          <p class="muted">
            Sie k&ouml;nnen den vollst&auml;ndigen Vertrag unten einsehen, ausdrucken oder als PDF speichern.
          </p>
          <div class="form-actions">
            <button id="print-final-contract" class="secondary-button" type="button">
              Drucken / Als PDF speichern
            </button>
          </div>
          <iframe id="final-contract-frame" class="contract-frame"></iframe>
        </section>
      </div>
    </main>

    <div id="toast" class="toast" role="status" aria-live="polite" hidden></div>

    <script src="public.js?v=contract-flow-simplified-20260719-1"></script>
  </body>
</html>
