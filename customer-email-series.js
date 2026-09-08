// One recurring message per customer, shared by all of that customer's contracts.
(() => {
  const dialog = document.querySelector('#email-series-dialog');
  const form = document.querySelector('#email-series-form');
  const fieldset = document.querySelector('#email-series-fields');
  const error = document.querySelector('#email-series-error');
  const status = document.querySelector('#email-series-status');
  const fileInput = form.elements.attachment;
  let customerId = null;
  let current = null;
  let removeAttachment = false;
  let busy = false;
  let previousFocus = null;
  let csrfToken = '';
  let contractEmail = '';
  let placeholderValues = {};
  const placeholderLabels = {firma: 'Firma', ansprechpartner: 'Ansprechpartner', email: 'Kunden-E-Mail', telefon: 'Telefon',
    strasse: 'Straße und Hausnummer', plz: 'Postleitzahl', ort: 'Ort', vertragsbeginn: 'Vertragsbeginn',
    unterschrieben_am: 'Unterschriftsdatum', intervall: 'Reinigungsintervall', monatspreis_netto: 'Monatspreis netto',
    leistung: 'Leistung', leistungsbeschreibung: 'Leistungsbeschreibung', datum: 'Heutiges Datum', monat: 'Aktueller Monat', jahr: 'Aktuelles Jahr'};

  function previewPlaceholders() {
    const expand = text => text.replace(/\{\{\s*([^{}]+?)\s*\}\}/g, (token, key) => {
      const value = placeholderValues[key.trim()];
      return value === undefined ? token : value || '(nicht hinterlegt)';
    });
    document.querySelector('#series-preview-subject').textContent = expand(form.elements.subject.value).replace(/[\r\n]+/g, ' ');
    document.querySelector('#series-preview-body').textContent = expand(form.elements.body.value);
  }
  const seriesEnabled = document.querySelector('#series-enabled');

  function renderSeriesSwitch() {
    document.querySelector('#series-enabled-label').textContent = seriesEnabled.checked ? 'Ein' : 'Aus';
    document.querySelector('#series-delivery-status').textContent = seriesEnabled.checked
      ? 'Nach dem Speichern wird diese E-Mail-Serie automatisch versendet. Test-E-Mails sind jederzeit m\u00f6glich.'
      : 'Nach dem Speichern ist der automatische Versand dieser Serie ausgeschaltet. Test-E-Mails sind weiterhin m\u00f6glich.';
  }

  const date = value => value ? new Intl.DateTimeFormat('de-DE', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Europe/Berlin' })
    .format(new Date(value.replace(' ', 'T') + 'Z')) : 'Noch nicht';
  const url = () => `api/customer-email-series.php?customerId=${encodeURIComponent(customerId)}`;

  function recipientFields() {
    const manual = form.elements.recipientMode.value === 'manual';
    document.querySelector('#series-manual-recipient').hidden = !manual;
    form.elements.recipientEmail.disabled = !manual;
    form.elements.recipientEmail.required = manual;
    const recipient = manual ? form.elements.recipientEmail.value.trim() : contractEmail;
    document.querySelector('#series-recipient-hint').textContent = recipient
      ? `Serie und Test-E-Mail gehen an: ${recipient}. Der Test sendet den aktuellen Inhalt samt Anhang, ohne die Serie zu speichern oder zu aktivieren.`
      : 'Bitte eine Empfänger-E-Mail-Adresse eingeben.';
  }

  function scheduleFields() {
    const weekly = form.elements.frequency.value === 'weekly';
    document.querySelector('#series-month-day').hidden = weekly;
    document.querySelector('#series-week-day').hidden = !weekly;
    form.elements.monthDay.disabled = weekly;
    form.elements.weekDay.disabled = !weekly;
    document.querySelector('#series-schedule-hint').textContent = weekly
      ? 'Der Versand erfolgt jede Woche am gewählten Wochentag. Der erste Versand erfolgt zum nächsten geplanten Termin.'
      : 'Fehlt der gewählte Tag in einem Monat, erfolgt der Versand am letzten Monatstag. Der erste Versand erfolgt zum nächsten geplanten Termin.';
  }

  function render(data) {
    csrfToken = data.csrfToken;
    current = data.series;
    form.reset();
    removeAttachment = false;
    contractEmail = data.customer.email;
    document.querySelector('#series-contract-recipient').textContent = `E-Mail aus dem Vertrag: ${contractEmail || 'Keine Adresse hinterlegt'}`;
    form.elements.recipientMode.value = current?.recipient_mode || 'contract';
    form.elements.recipientEmail.value = current?.recipient_email || '';
    recipientFields();
    document.querySelector('#email-series-customer').textContent = `${data.customer.name} · ${data.customer.email}`;
    form.elements.subject.value = current?.subject || '';
    form.elements.body.value = current?.body || '';
    form.elements.frequency.value = current?.frequency || 'monthly';
    form.elements.monthDay.value = current?.frequency === 'monthly' ? current.schedule_day : 1;
    form.elements.weekDay.value = current?.frequency === 'weekly' ? current.schedule_day : 1;
    form.elements.sendTime.value = current?.send_time || '09:00';
    placeholderValues = data.placeholders?.values || {};
    const choices = document.querySelector('#series-placeholder');
    choices.replaceChildren();
    for (const [key, label] of Object.entries(placeholderLabels)) {
      const option = document.createElement('option');
      option.value = key;
      option.textContent = `${label} – {{${key}}}`;
      choices.append(option);
    }
    document.querySelector('#series-placeholder-source').textContent = 'Verwendet wird der zuletzt unterschriebene Vertrag'
      + (data.placeholders?.contractSignedAt ? ` (${date(data.placeholders.contractSignedAt)})` : '')
      + '. Platzhalter werden bei jedem Versand neu ausgefüllt. Vorschau: Daten beim Öffnen des Editors.';
    previewPlaceholders();
    scheduleFields();
    const active = Number(current?.enabled) === 1;
    status.textContent = `${active ? 'Aktiv' : current ? 'Pausiert / Entwurf' : 'Noch keine Serie angelegt'} · Nächster Versand: ${date(current?.next_run_at)} · Zuletzt versendet: ${date(current?.last_sent_at)}`;
    seriesEnabled.checked = active;
    seriesEnabled.disabled = !data.eligible;
    renderSeriesSwitch();
    document.querySelector('#series-save').disabled = !data.eligible;
    document.querySelector('#series-test').disabled = !data.eligible;
    const attachment = document.querySelector('#series-current-attachment');
    attachment.hidden = !current?.attachment_name;
    const link = document.querySelector('#series-attachment-link');
    link.textContent = current?.attachment_name || '';
    link.href = url() + '&attachment=1';
    error.textContent = current?.last_error || (!data.eligible ? 'Die Serie kann nur für einen aktiven Kunden mit unterschriebenem Vertrag aktiviert werden.' : '');
    const scheduler = document.querySelector('#series-scheduler-status');
    const heartbeat = data.schedulerLastRunAt ? new Date(data.schedulerLastRunAt.replace(' ', 'T') + 'Z') : null;
    scheduler.textContent = heartbeat && Date.now() - heartbeat.getTime() < 15 * 60 * 1000
      ? 'Automatischer Versand ist erreichbar. Alle Zeiten gelten für Deutschland.'
      : 'Automatischer Versand noch nicht bestätigt: Der regelmäßige Hosting-Aufruf muss eingerichtet bzw. geprüft werden. Alle Zeiten gelten für Deutschland.';
    const history = document.querySelector('#series-history');
    history.replaceChildren();
    for (const run of data.runs) {
      const item = document.createElement('li');
      const labels = { sent: 'Versendet', sending: 'In Bearbeitung', uncertain: 'Zustellung prüfen' };
      item.textContent = `${date(run.started_at)} – ${labels[run.status] || run.status} – ${run.recipient}${run.error_message ? ': ' + run.error_message : ''}`;
      history.append(item);
    }
    if (!data.runs.length) {
      const item = document.createElement('li');
      item.textContent = 'Noch keine Versandläufe.';
      history.append(item);
    }
  }

  async function request(options) {
    const response = await fetch(url(), { credentials: 'same-origin', ...options });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(data.error || 'Die E-Mail-Serie konnte nicht gespeichert oder geladen werden.');
    return data;
  }

  async function open(id, trigger) {
    if (busy || dialog.open) return;
    busy = true;
    previousFocus = trigger;
    customerId = id;
    current = null;
    form.reset();
    error.textContent = '';
    document.querySelector('#series-test-status').textContent = '';
    status.textContent = 'Serie wird geladen …';
    document.querySelector('#email-series-customer').textContent = '';
    document.querySelector('#series-history').replaceChildren();
    fieldset.disabled = true;
    dialog.showModal();
    try {
      render(await request());
      fieldset.disabled = false;
      form.elements.subject.focus();
    } catch (failure) {
      error.textContent = failure.message;
    } finally {
      busy = false;
    }
  }

  document.addEventListener('click', event => {
    const trigger = event.target.closest('[data-action="email-series"]');
    if (trigger) open(trigger.dataset.customerId, trigger);
  });
  document.querySelector('#series-close').addEventListener('click', () => { if (!busy) dialog.close(); });
  dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
  dialog.addEventListener('close', () => previousFocus?.focus());
  form.elements.frequency.addEventListener('change', scheduleFields);
  form.elements.recipientMode.addEventListener('change', recipientFields);
  form.elements.recipientEmail.addEventListener('input', recipientFields);
  form.elements.subject.addEventListener('input', previewPlaceholders);
  form.elements.body.addEventListener('input', previewPlaceholders);
  document.querySelector('#series-insert-placeholder').addEventListener('click', () => {
    const key = document.querySelector('#series-placeholder').value;
    if (!Object.hasOwn(placeholderLabels, key)) return;
    const target = document.querySelector('#series-placeholder-target').value === 'subject' ? form.elements.subject : form.elements.body;
    target.setRangeText(`{{${key}}}`, target.selectionStart ?? target.value.length, target.selectionEnd ?? target.value.length, 'end');
    target.focus();
    previewPlaceholders();
  });
  document.querySelector('#series-insert-template').addEventListener('click', () => {
    if (!form.elements.subject.value.trim()) form.elements.subject.value = 'Checkliste für {{firma}} – {{monat}} {{jahr}}';
    const template = 'Guten Tag {{ansprechpartner}},\n\nbitte prüfen Sie die beigefügte Checkliste für {{firma}} in {{strasse}}, {{plz}} {{ort}}.\n\nVereinbartes Reinigungsintervall: {{intervall}}.\n\nBitte senden Sie uns die ausgefüllte Checkliste zurück. Vielen Dank!';
    form.elements.body.value += (form.elements.body.value.trim() ? '\n\n' : '') + template;
    form.elements.body.focus();
    previewPlaceholders();
  });
  seriesEnabled.addEventListener('change', renderSeriesSwitch);
  document.querySelector('#series-remove-attachment').addEventListener('click', () => {
    removeAttachment = true;
    fileInput.value = '';
    document.querySelector('#series-current-attachment').hidden = true;
  });
  fileInput.addEventListener('change', () => {
    error.textContent = '';
    if (fileInput.files[0]?.size > 5 * 1024 * 1024) {
      error.textContent = 'Der Anhang darf maximal 5 MB groß sein.';
      fileInput.value = '';
    }
  });
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (busy) return;
    const action = event.submitter?.value === 'test' ? 'test' : (seriesEnabled.checked ? 'activate' : 'draft');
    if (['activate', 'test'].includes(action) && (!form.elements.subject.value.trim() || !form.elements.body.value.trim())) {
      error.textContent = 'Bitte Betreff und Inhalt eingeben.';
      return;
    }
    if (action !== 'pause' && form.elements.recipientMode.value === 'manual' && !form.elements.recipientEmail.reportValidity()) return;
    const payload = new FormData(form);
    payload.set('csrfToken', csrfToken);
    payload.set('action', action);
    payload.set('revision', String(current?.revision || 0));
    payload.set('scheduleDay', form.elements.frequency.value === 'weekly' ? form.elements.weekDay.value : form.elements.monthDay.value);
    payload.set('removeAttachment', removeAttachment ? '1' : '0');
    busy = true;
    fieldset.disabled = true;
    error.textContent = '';
    document.querySelector('#series-test-status').textContent = action === 'test' ? 'Test-E-Mail wird gesendet …' : '';
    try {
      const response = await request({ method: 'POST', body: payload });
      if (action === 'test') {
        document.querySelector('#series-test-status').textContent = `Test-E-Mail an ${response.recipient} versendet. Die Serie und ihr Zeitplan wurden nicht verändert.`;
      } else {
        render(response);
        status.textContent = 'Gespeichert. ' + status.textContent;
      }
    } catch (failure) {
      document.querySelector('#series-test-status').textContent = '';
      error.textContent = failure.message;
    } finally {
      fieldset.disabled = false;
      busy = false;
    }
  });
})();
