const intervalFactors = {
  Einmalig: 1,
  Wöchentlich: 4.33,
  "14-tägig": 2.16,
  Monatlich: 1,
  Quartalsweise: 0.33,
};

const serviceRates = {
  Unterhaltsreinigung: 1.95,
  Büroreinigung: 2.1,
  Treppenhausreinigung: 2.45,
  Grundreinigung: 3.8,
  Glasreinigung: 3.2,
};

const CONTRACT_STATUS_LABELS = {
  entwurf: "Läuft beim Kunden",
  daten_abgelehnt: "Rückfrage: Daten prüfen",
  intervall_abgelehnt: "Rückfrage: Intervall prüfen",
  signiert: "Signiert",
};

const state = {
  data: { customers: [], siteVisits: [], offers: [], contracts: [] },
  currentView: "overview",
  selectedContractId: null,
};

const els = {
  loginScreen: document.querySelector("#login-screen"),
  appShell: document.querySelector("#app-shell"),
  loginForm: document.querySelector("#login-form"),
  loginEmail: document.querySelector("#login-email"),
  loginPassword: document.querySelector("#login-password"),
  loginError: document.querySelector("#login-error"),
  logoutButton: document.querySelector("#logout-button"),
  navLinks: document.querySelectorAll(".nav-link[data-view]"),
  customersGroupToggle: document.querySelector("#customers-group-toggle"),
  customersSubgroup: document.querySelector("#customers-subgroup"),
  siteVisitsGroupToggle: document.querySelector("#site-visits-group-toggle"),
  siteVisitsSubgroup: document.querySelector("#site-visits-subgroup"),
  settingsGroupToggle: document.querySelector("#settings-group-toggle"),
  settingsSubgroup: document.querySelector("#settings-subgroup"),
  views: document.querySelectorAll(".view"),
  viewTitle: document.querySelector("#view-title"),
  sidebar: document.querySelector(".sidebar"),
  menuButton: document.querySelector("#menu-button"),
  mobileBackdrop: document.querySelector("#mobile-backdrop"),
  quickCustomer: document.querySelector("#quick-customer"),
  quickOffer: document.querySelector("#quick-offer"),
  newCustomerButton: document.querySelector("#new-customer-button"),
  customerForm: document.querySelector("#customer-form"),
  customerSearch: document.querySelector("#customer-search"),
  customerList: document.querySelector("#customer-list"),
  customerId: document.querySelector("#customer-id"),
  cancelCustomerEdit: document.querySelector("#cancel-customer-edit"),
  siteVisitForm: document.querySelector("#site-visit-form"),
  addSiteVisitFloor: document.querySelector("#add-site-visit-floor"),
  siteVisitFloors: document.querySelector("#site-visit-floors"),
  siteVisitList: document.querySelector("#site-visit-list"),
  offerForm: document.querySelector("#offer-form"),
  offerCustomer: document.querySelector("#offer-customer"),
  offerSquareMeters: document.querySelector("#offer-square-meters"),
  offerInterval: document.querySelector("#offer-interval"),
  offerService: document.querySelector("#offer-service"),
  offerStartDate: document.querySelector("#offer-start-date"),
  offerNotes: document.querySelector("#offer-notes"),
  offerPricePreview: document.querySelector("#offer-price-preview"),
  offerList: document.querySelector("#offer-list"),
  contractList: document.querySelector("#contract-list"),
  contractPreview: document.querySelector("#contract-preview"),
  printContract: document.querySelector("#print-contract"),
  smtpForm: document.querySelector("#smtp-form"),
  smtpHost: document.querySelector("#smtp-host"),
  smtpPort: document.querySelector("#smtp-port"),
  smtpEncryption: document.querySelector("#smtp-encryption"),
  smtpUsername: document.querySelector("#smtp-username"),
  smtpPassword: document.querySelector("#smtp-password"),
  smtpFromName: document.querySelector("#smtp-from-name"),
  smtpFromEmail: document.querySelector("#smtp-from-email"),
  sendTestMail: document.querySelector("#send-test-mail"),
  mailboxNotConfigured: document.querySelector("#mailbox-not-configured"),
  mailboxConfigured: document.querySelector("#mailbox-configured"),
  mailboxGotoSettings: document.querySelector("#mailbox-goto-settings"),
  mailboxAccountLabel: document.querySelector("#mailbox-account-label"),
  mailboxRefresh: document.querySelector("#mailbox-refresh"),
  mailboxList: document.querySelector("#mailbox-list"),
  mailboxMessage: document.querySelector("#mailbox-message"),
  mailboxComposeToggle: document.querySelector("#mailbox-compose-toggle"),
  mailboxComposeForm: document.querySelector("#mailbox-compose-form"),
  mailboxComposeCancel: document.querySelector("#mailbox-compose-cancel"),
  mailboxComposeTo: document.querySelector("#mailbox-compose-to"),
  mailboxComposeSubject: document.querySelector("#mailbox-compose-subject"),
  mailboxComposeBody: document.querySelector("#mailbox-compose-body"),
  mailboxSettingsForm: document.querySelector("#mailbox-settings-form"),
  mailboxHost: document.querySelector("#mailbox-host"),
  mailboxImapPort: document.querySelector("#mailbox-imap-port"),
  mailboxImapEncryption: document.querySelector("#mailbox-imap-encryption"),
  mailboxSmtpPort: document.querySelector("#mailbox-smtp-port"),
  mailboxSmtpEncryption: document.querySelector("#mailbox-smtp-encryption"),
  mailboxUsername: document.querySelector("#mailbox-username"),
  mailboxPassword: document.querySelector("#mailbox-password"),
  mailboxFromName: document.querySelector("#mailbox-from-name"),
  mailboxSignature: document.querySelector("#mailbox-signature"),
  mailboxFolderTabs: document.querySelectorAll(".mailbox-folder-tabs [data-folder]"),
  contractNotifyForm: document.querySelector("#contract-notify-form"),
  contractNotifyEnabled: document.querySelector("#contract-notify-enabled"),
  contractNotifyEmails: document.querySelector("#contract-notify-emails"),
  contractNotifyAddEmail: document.querySelector("#contract-notify-add-email"),
  contractNotifyTest: document.querySelector("#contract-notify-test"),
  linkModal: document.querySelector("#link-modal"),
  linkModalInput: document.querySelector("#link-modal-input"),
  linkModalCopy: document.querySelector("#link-modal-copy"),
  linkModalClose: document.querySelector("#link-modal-close"),
  logoPreview: document.querySelector("#logo-preview"),
  logoFileInput: document.querySelector("#logo-file-input"),
  logoRemove: document.querySelector("#logo-remove"),
  brandMarks: document.querySelectorAll(".brand-mark"),
  toast: document.querySelector("#toast"),
  metricCustomers: document.querySelector("#metric-customers"),
  metricSiteVisits: document.querySelector("#metric-site-visits"),
  metricOffers: document.querySelector("#metric-offers"),
  metricContracts: document.querySelector("#metric-contracts"),
  metricSigned: document.querySelector("#metric-signed"),
  metricFollowups: document.querySelector("#metric-followups"),
  recentOffers: document.querySelector("#recent-offers"),
  contractStatus: document.querySelector("#contract-status"),
};

const titles = {
  overview: "Übersicht",
  "customer-new": "Kunden anlegen",
  "customer-list": "Kundenliste",
  "site-visit-new": "Neue Begehung erstellen",
  "site-visit-saved": "Gespeicherte Begehungen",
  offers: "Kostenvoranschlagserstellung",
  contracts: "Verträge",
  mailbox: "Postfach",
  "settings-smtp": "SMTP-Server-Einstellungen",
  "settings-notify": "Vertragsbenachrichtigungen-Einstellungen",
  "settings-logo": "Logo-Einstellungen",
};

let currentLogoUrl = null;

const mailboxState = {
  messages: [],
  selectedUid: null,
  folder: "inbox",
  signature: "",
};

async function apiFetch(path, options = {}) {
  const response = await fetch(path, {
    credentials: "same-origin",
    ...options,
    headers: options.body ? { "Content-Type": "application/json", ...options.headers } : options.headers,
  });

  let data = {};
  try {
    data = await response.json();
  } catch (error) {
    data = {};
  }

  if (!response.ok) {
    throw new Error(data.error || "Es ist ein Fehler aufgetreten.");
  }

  return data;
}

const apiGet = (path) => apiFetch(path);
const apiPost = (path, body) => apiFetch(path, { method: "POST", body: JSON.stringify(body || {}) });
const apiPut = (path, body) => apiFetch(path, { method: "PUT", body: JSON.stringify(body || {}) });
const apiDelete = (path) => apiFetch(path, { method: "DELETE" });

function escapeHtml(value) {
  return String(value == null ? "" : value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function formatCurrency(value) {
  return new Intl.NumberFormat("de-DE", {
    style: "currency",
    currency: "EUR",
  }).format(Number(value) || 0);
}

function formatDate(value) {
  if (!value) {
    return "Noch offen";
  }

  return new Intl.DateTimeFormat("de-DE", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(new Date(value));
}

function todayAsInputValue() {
  const now = new Date();
  const offsetDate = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
  return offsetDate.toISOString().slice(0, 10);
}

function getCustomer(id) {
  return state.data.customers.find((customer) => customer.id === id);
}

function getSiteVisit(id) {
  return state.data.siteVisits.find((visit) => visit.id === id);
}

function getOffer(id) {
  return state.data.offers.find((offer) => offer.id === id);
}

function getContract(id) {
  return state.data.contracts.find((contract) => contract.id === id);
}

function getLatestContractForCustomer(customerId) {
  const matches = state.data.contracts
    .filter((contract) => contract.customer.id === customerId)
    .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));

  return matches[0] || null;
}

function calculateOfferPrice(squareMeters, interval, service) {
  const sqm = Number(squareMeters) || 0;
  const factor = intervalFactors[interval] || 1;
  const rate = serviceRates[service] || serviceRates.Unterhaltsreinigung;
  const setup = interval === "Einmalig" ? 65 : 35;
  return Math.max(0, sqm * rate * factor + setup);
}

function customerAddress(customer) {
  return `${customer.address} ${customer.houseNumber}, ${customer.zip} ${customer.city}`;
}

function contactName(customer) {
  return `${customer.salutation} ${customer.contactLastName}`;
}

function offerValidity(offer) {
  const diffMs = new Date(offer.expiresAt).getTime() - Date.now();

  if (diffMs <= 0) {
    return { label: "Abgelaufen", className: "danger" };
  }

  const days = Math.floor(diffMs / 86400000);
  if (days >= 1) {
    return {
      label: `Noch ${days} ${days === 1 ? "Tag" : "Tage"} gültig`,
      className: days <= 2 ? "warning" : "success",
    };
  }

  const hours = Math.max(1, Math.floor(diffMs / 3600000));
  return { label: `Noch ${hours} Std. gültig`, className: "warning" };
}

function showToast(message) {
  els.toast.textContent = message;
  els.toast.hidden = false;

  window.clearTimeout(showToast.timer);
  showToast.timer = window.setTimeout(() => {
    els.toast.hidden = true;
  }, 3200);
}

function refreshIcons() {
  if (window.lucide) {
    window.lucide.createIcons();
  }
}

function showLogin() {
  els.loginScreen.hidden = false;
  els.appShell.hidden = true;
}

function showApp() {
  els.loginScreen.hidden = true;
  els.appShell.hidden = false;
  loadAll();
}

function setSettingsGroupExpanded(expanded) {
  els.settingsSubgroup.hidden = !expanded;
  els.settingsGroupToggle.setAttribute("aria-expanded", String(expanded));
}

function setCustomersGroupExpanded(expanded) {
  els.customersSubgroup.hidden = !expanded;
  els.customersGroupToggle.setAttribute("aria-expanded", String(expanded));
}

function setSiteVisitsGroupExpanded(expanded) {
  els.siteVisitsSubgroup.hidden = !expanded;
  els.siteVisitsGroupToggle.setAttribute("aria-expanded", String(expanded));
}

function switchView(view) {
  state.currentView = view;
  els.viewTitle.textContent = titles[view];

  els.navLinks.forEach((button) => {
    button.classList.toggle("active", button.dataset.view === view);
  });

  els.views.forEach((panel) => {
    panel.classList.toggle("active-view", panel.id === `${view}-view`);
  });

  const isSettingsView = view.startsWith("settings-");
  els.settingsGroupToggle.classList.toggle("active", isSettingsView);
  if (isSettingsView) {
    setSettingsGroupExpanded(true);
  }

  const isCustomerView = view.startsWith("customer-");
  els.customersGroupToggle.classList.toggle("active", isCustomerView);
  if (isCustomerView) {
    setCustomersGroupExpanded(true);
  }

  const isSiteVisitView = view.startsWith("site-visit-");
  els.siteVisitsGroupToggle.classList.toggle("active", isSiteVisitView);
  if (isSiteVisitView) {
    setSiteVisitsGroupExpanded(true);
  }

  closeMobileNav();

  if (view === "settings-smtp") {
    loadSmtpSettings();
  }

  if (view === "settings-notify") {
    loadContractNotifySettings();
  }

  if (view === "mailbox") {
    loadMailbox();
  }

  loadAll();
}

function openMobileNav() {
  els.sidebar.classList.add("open");
  els.mobileBackdrop.hidden = false;
}

function closeMobileNav() {
  els.sidebar.classList.remove("open");
  els.mobileBackdrop.hidden = true;
}

async function loadAll() {
  try {
    const [customers, siteVisits, offers, contracts] = await Promise.all([
      apiGet("api/customers.php"),
      apiGet("api/site-visits.php"),
      apiGet("api/offers.php"),
      apiGet("api/contracts.php"),
    ]);

    state.data.customers = customers;
    state.data.siteVisits = siteVisits;
    state.data.offers = offers;
    state.data.contracts = contracts;
    renderAll();
  } catch (error) {
    showToast(error.message);
  }
}

function renderAll() {
  renderMetrics();
  renderCustomerOptions();
  renderCustomers();
  renderSiteVisits();
  renderOffers();
  renderContracts();
  updateOfferPreview();
  refreshIcons();
}

function renderMetrics() {
  els.metricCustomers.textContent = state.data.customers.length;
  els.metricSiteVisits.textContent = state.data.siteVisits.length;
  els.metricOffers.textContent = state.data.offers.length;
  els.metricContracts.textContent = state.data.contracts.length;
  els.metricSigned.textContent = state.data.contracts.filter((contract) => contract.status === "signiert").length;
  els.metricFollowups.textContent = state.data.contracts.filter((contract) =>
    contract.status === "daten_abgelehnt" || contract.status === "intervall_abgelehnt",
  ).length;

  const latestOffers = [...state.data.offers]
    .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
    .slice(0, 4);

  els.recentOffers.innerHTML = latestOffers.length
    ? latestOffers
        .map((offer) => {
          return `
            <article class="compact-item">
              <div>
                <strong>${escapeHtml(offer.customer.name)}</strong>
                <span>${escapeHtml(offer.service)} · ${offer.squareMeters} m² · ${escapeHtml(offer.interval)}</span>
              </div>
              <span class="badge">${formatCurrency(offer.price)}</span>
            </article>
          `;
        })
        .join("")
    : `<div class="empty-state">Noch keine Kostenvoranschläge vorhanden.</div>`;

  const openContracts = state.data.contracts.filter((contract) => contract.status === "entwurf").length;
  const signedContracts = state.data.contracts.filter((contract) => contract.status === "signiert").length;
  els.contractStatus.innerHTML = `
    <article class="status-item">
      <div>
        <strong>Offene Signaturen</strong>
        <span>Verträge warten auf digitale Unterschrift.</span>
      </div>
      <span class="badge warning">${openContracts}</span>
    </article>
    <article class="status-item">
      <div>
        <strong>Abgeschlossen</strong>
        <span>Online signierte Verträge.</span>
      </div>
      <span class="badge success">${signedContracts}</span>
    </article>
  `;
}

function renderCustomerOptions() {
  const previousValue = els.offerCustomer.value;
  const options = state.data.customers
    .map((customer) => `<option value="${escapeHtml(customer.id)}">${escapeHtml(customer.name)}</option>`)
    .join("");

  els.offerCustomer.innerHTML = state.data.customers.length
    ? options
    : `<option value="">Bitte zuerst Kunden anlegen</option>`;
  els.offerCustomer.disabled = state.data.customers.length === 0;

  if (previousValue && getCustomer(previousValue)) {
    els.offerCustomer.value = previousValue;
  }
}

function renderCustomers() {
  const query = els.customerSearch.value.trim().toLowerCase();
  const customers = state.data.customers
    .filter((customer) => {
      const haystack = [
        customer.name,
        customer.email,
        customer.phone,
        customer.salutation,
        customer.contactLastName,
        customer.address,
        customer.houseNumber,
        customer.zip,
        customer.city,
      ]
        .join(" ")
        .toLowerCase();

      return haystack.includes(query);
    })
    .sort((a, b) => a.name.localeCompare(b.name, "de"));

  els.customerList.innerHTML = customers.length
    ? customers.map(renderCustomerCard).join("")
    : `<div class="empty-state">Keine Kunden gefunden.</div>`;
}

function renderCustomerCard(customer) {
  const contract = getLatestContractForCustomer(customer.id);
  const contractBadge = contract ? `<span class="badge success">Vertrag vorhanden</span>` : "";
  const contractButton = contract
    ? `
      <a class="secondary-button" href="contract.php?contractId=${encodeURIComponent(contract.id)}" target="_blank" rel="noopener">
        <i data-lucide="file-text" aria-hidden="true"></i>
        Vertrag anzeigen
      </a>
    `
    : "";

  return `
    <article class="record-item">
      <div class="record-main">
        <div>
          <div class="record-title">${escapeHtml(customer.name)}</div>
          <div class="record-meta">
            <span>${escapeHtml(contactName(customer))}</span>
            <span>${escapeHtml(customer.email)} · ${escapeHtml(customer.phone)}</span>
          </div>
        </div>
        <div class="record-side">
          <span class="badge">${escapeHtml(customer.city)}</span>
          ${contractBadge}
        </div>
      </div>
      <div class="record-lines">
        <span>${escapeHtml(customerAddress(customer))}</span>
      </div>
      <div class="record-actions">
        <button class="secondary-button" type="button" data-action="edit-customer" data-id="${escapeHtml(customer.id)}">
          <i data-lucide="pencil" aria-hidden="true"></i>
          Bearbeiten
        </button>
        <button class="primary-button" type="button" data-action="offer-for-customer" data-id="${escapeHtml(customer.id)}">
          <i data-lucide="file-plus-2" aria-hidden="true"></i>
          Kostenvoranschlag
        </button>
        ${contractButton}
        <button class="ghost-button" type="button" data-action="delete-customer" data-id="${escapeHtml(customer.id)}">
          <i data-lucide="trash-2" aria-hidden="true"></i>
          Löschen
        </button>
      </div>
    </article>
  `;
}

function optionSelected(value, currentValue) {
  return value === currentValue ? " selected" : "";
}

function numericValue(value) {
  return Math.max(0, Number(value) || 0);
}

function normalizeCleaningType(value) {
  if (value === "Gesaugt") {
    return "Nur gesaugt";
  }
  if (value === "Gewischt") {
    return "Nur gewischt";
  }
  return value || "Gesaugt und gewischt";
}

function counterMarkup(name, label, value, options = {}) {
  const min = Number(options.min ?? 0);
  const step = Number(options.step ?? 1);
  const suffix = `<span class="counter-suffix">${options.suffix ? escapeHtml(options.suffix) : ""}</span>`;
  const className = options.className ? ` ${options.className}` : "";
  const safeName = escapeHtml(name);
  const safeLabel = escapeHtml(label);
  const currentValue = numericValue(value);

  return `
    <div class="counter-control${className}">
      <span class="counter-label">${safeLabel}</span>
      <div class="counter-stepper">
        <button class="icon-button" type="button" data-action="counter-decrement" data-counter-target="${safeName}" aria-label="${safeLabel} verringern">
          <i data-lucide="minus" aria-hidden="true"></i>
        </button>
        <strong data-counter-value="${safeName}">${currentValue}</strong>
        ${suffix}
        <button class="icon-button" type="button" data-action="counter-increment" data-counter-target="${safeName}" aria-label="${safeLabel} erhöhen">
          <i data-lucide="plus" aria-hidden="true"></i>
        </button>
      </div>
      <input name="${safeName}" type="hidden" value="${currentValue}" data-counter-input="${safeName}" data-min="${min}" data-step="${step}" />
    </div>
  `;
}

function ensureSiteVisitFloorEmptyState() {
  if (els.siteVisitFloors.querySelector(".floor-section")) {
    return;
  }

  els.siteVisitFloors.innerHTML = `<div class="empty-state floor-empty-state">Noch keine Etage geöffnet.</div>`;
}

function renumberSiteVisitFloors() {
  els.siteVisitFloors.querySelectorAll(".floor-section").forEach((section, index) => {
    const name = section.querySelector('[name="floorName"]').value.trim();
    section.querySelector("legend").textContent = name || `Etage ${index + 1}`;
  });
}

function syncFloorConditionalSections(section) {
  const value = (name) => Number(section.querySelector(`[name="${name}"]`)?.value) || 0;
  const sanitaryDetails = section.querySelector('[data-conditional-section="sanitary"]');
  const officeDetails = section.querySelector('[data-conditional-section="office"]');

  if (sanitaryDetails) {
    sanitaryDetails.hidden = value("sanitaryRooms") <= 0;
  }

  if (officeDetails) {
    officeDetails.hidden = value("officeRooms") <= 0;
  }
}

function findCounterInput(button) {
  const target = button.dataset.counterTarget;
  const control = button.closest(".counter-control");
  return (
    control?.querySelector(`[data-counter-input="${target}"]`) ||
    document.querySelector(`[data-counter-input="${target}"]`)
  );
}

function updateCounterControl(input, value) {
  if (!input) {
    return;
  }

  const min = Number(input.dataset.min ?? 0);
  const nextValue = Math.max(min, numericValue(value));
  input.value = String(nextValue);

  const control = input.closest(".counter-control");
  const display = control?.querySelector(`[data-counter-value="${input.dataset.counterInput}"]`);
  if (display) {
    display.textContent = String(nextValue);
  }

  const floorSection = input.closest(".floor-section");
  if (floorSection) {
    syncFloorConditionalSections(floorSection);
  }
}

function changeCounter(button, direction) {
  const input = findCounterInput(button);
  if (!input) {
    return;
  }

  const step = Number(input.dataset.step ?? 1) || 1;
  updateCounterControl(input, (Number(input.value) || 0) + direction * step);
}

function addSiteVisitFloor(values = {}) {
  const emptyState = els.siteVisitFloors.querySelector(".floor-empty-state");
  if (emptyState) {
    emptyState.remove();
  }

  const floor = {
    name: values.name || "",
    sanitaryRooms: Number(values.sanitaryRooms) || 0,
    sinks: Number(values.sinks) || 0,
    mirrors: Number(values.mirrors) || 0,
    toilets: Number(values.toilets) || 0,
    officeRooms: Number(values.officeRooms) || 0,
    desks: Number(values.desks) || 0,
    windows: Number(values.windows) || 0,
    cleaningType: normalizeCleaningType(values.cleaningType),
    floorCondition: values.floorCondition || "Teppich",
    areaName: values.areaName || "",
    extraAgreements: values.extraAgreements || "",
    areaNotes: values.areaNotes || values.notes || "",
  };

  const section = document.createElement("fieldset");
  section.className = "floor-section";
  section.innerHTML = `
    <legend>Etage</legend>
    <div class="floor-section-toolbar">
      <button class="ghost-button" type="button" data-action="remove-site-visit-floor">
        <i data-lucide="x" aria-hidden="true"></i>
        Etage entfernen
      </button>
    </div>
    <div class="form-grid">
      <label class="span-2">
        Etagenname
        <input name="floorName" type="text" placeholder="z. B. Erdgeschoss, 1. OG" value="${escapeHtml(floor.name)}" />
      </label>
      ${counterMarkup("sanitaryRooms", "Sanitärräume", floor.sanitaryRooms)}
      <div class="conditional-fields span-2" data-conditional-section="sanitary" hidden>
        ${counterMarkup("sinks", "Anzahl der Waschbecken", floor.sinks)}
        ${counterMarkup("mirrors", "Anzahl der Spiegel", floor.mirrors)}
        ${counterMarkup("toilets", "Anzahl der Toiletten", floor.toilets)}
      </div>
      ${counterMarkup("officeRooms", "Büro-Räume", floor.officeRooms)}
      <div class="conditional-fields span-2" data-conditional-section="office" hidden>
        ${counterMarkup("desks", "Anzahl der Schreibtische", floor.desks)}
        ${counterMarkup("windows", "Anzahl der Fenster", floor.windows)}
        <label>
          Bodenart
          <select name="floorCondition">
            <option value="Teppich"${optionSelected("Teppich", floor.floorCondition)}>Teppich</option>
            <option value="Laminat"${optionSelected("Laminat", floor.floorCondition)}>Laminat</option>
            <option value="Parkett"${optionSelected("Parkett", floor.floorCondition)}>Parkett</option>
            <option value="Fliesen"${optionSelected("Fliesen", floor.floorCondition)}>Fliesen</option>
          </select>
        </label>
        <label>
          Wie soll der Boden behandelt werden?
          <select name="cleaningType">
            <option value="Gesaugt und gewischt"${optionSelected("Gesaugt und gewischt", floor.cleaningType)}>Gesaugt und gewischt</option>
            <option value="Nur gesaugt"${optionSelected("Nur gesaugt", floor.cleaningType)}>Nur gesaugt</option>
            <option value="Nur gewischt"${optionSelected("Nur gewischt", floor.cleaningType)}>Nur gewischt</option>
          </select>
        </label>
      </div>
      <label>
        Bereich
        <input name="areaName" type="text" placeholder="z. B. Empfang, Flur, Küche" value="${escapeHtml(floor.areaName)}" />
      </label>
      <label class="span-2">
        Extra Vereinbarungen
        <textarea name="extraAgreements" rows="3" placeholder="Besondere Absprachen für diesen Bereich">${escapeHtml(floor.extraAgreements)}</textarea>
      </label>
      <label class="span-2">
        Notiz zu dem Bereich
        <textarea name="areaNotes" rows="3" placeholder="Notizen zu diesem Bereich">${escapeHtml(floor.areaNotes)}</textarea>
      </label>
    </div>
  `;

  els.siteVisitFloors.appendChild(section);
  renumberSiteVisitFloors();
  syncFloorConditionalSections(section);
  refreshIcons();
  section.querySelector('[name="floorName"]').focus();
}

function collectSiteVisitFloors() {
  return [...els.siteVisitFloors.querySelectorAll(".floor-section")].map((section, index) => {
    const field = (name) => section.querySelector(`[name="${name}"]`);
    const counter = (name) => Number(field(name)?.value) || 0;
    const sanitaryRooms = counter("sanitaryRooms");
    const officeRooms = counter("officeRooms");
    return {
      name: field("floorName").value.trim() || `Etage ${index + 1}`,
      sanitaryRooms,
      sinks: sanitaryRooms > 0 ? counter("sinks") : 0,
      mirrors: sanitaryRooms > 0 ? counter("mirrors") : 0,
      toilets: sanitaryRooms > 0 ? counter("toilets") : 0,
      officeRooms,
      desks: officeRooms > 0 ? counter("desks") : 0,
      windows: officeRooms > 0 ? counter("windows") : 0,
      cleaningType: field("cleaningType")?.value || "Gesaugt und gewischt",
      floorCondition: field("floorCondition")?.value || "Teppich",
      areaName: field("areaName").value.trim(),
      extraAgreements: field("extraAgreements").value.trim(),
      areaNotes: field("areaNotes").value.trim(),
      notes: field("areaNotes").value.trim(),
    };
  });
}

function resetSiteVisitForm() {
  els.siteVisitForm.reset();
  updateCounterControl(document.querySelector("#site-visit-square-meters"), 0);
  els.siteVisitFloors.innerHTML = `<div class="empty-state floor-empty-state">Noch keine Etage geöffnet.</div>`;
  refreshIcons();
}

function renderSiteVisits() {
  const visits = [...state.data.siteVisits].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));

  els.siteVisitList.innerHTML = visits.length
    ? visits.map(renderSiteVisitCard).join("")
    : `<div class="empty-state">Noch keine Begehung gespeichert.</div>`;
}

function renderSiteVisitCard(visit) {
  const floors = Array.isArray(visit.floors) ? visit.floors : [];
  const sanitaryTotal = floors.reduce((sum, floor) => sum + (Number(floor.sanitaryRooms) || 0), 0);
  const officeTotal = floors.reduce((sum, floor) => sum + (Number(floor.officeRooms) || 0), 0);
  const floorLabel = floors.length === 1 ? "1 Etage" : `${floors.length} Etagen`;
  const notes = visit.notes
    ? `<div class="record-lines"><span>${escapeHtml(visit.notes)}</span></div>`
    : "";

  return `
    <article class="record-item">
      <div class="record-main">
        <div>
          <div class="record-title">${escapeHtml(visit.customerName)}</div>
          <div class="record-meta">
            <span>${escapeHtml(visit.onsiteContact)} vor Ort</span>
            <span>${escapeHtml(visit.email)} · ${escapeHtml(visit.phone)}</span>
            <span>Erfasst am ${formatDate(visit.createdAt)}</span>
          </div>
        </div>
        <div class="record-side">
          <span class="badge">${visit.squareMeters} m²</span>
          <span class="badge">${escapeHtml(floorLabel)}</span>
        </div>
      </div>
      <div class="record-lines">
        <span>${escapeHtml(visit.address)}</span>
        <span>${sanitaryTotal} Sanitärräume · ${officeTotal} Büro-Räume</span>
      </div>
      ${notes}
      <details class="visit-details">
        <summary>Etagen anzeigen</summary>
        <div class="floor-summary-list">
          ${floors.map(renderSiteVisitFloorSummary).join("")}
        </div>
      </details>
      <div class="record-actions">
        <button class="primary-button" type="button" data-action="offer-from-site-visit" data-id="${escapeHtml(visit.id)}">
          <i data-lucide="file-plus-2" aria-hidden="true"></i>
          Kostenvoranschlag
        </button>
        <button class="ghost-button" type="button" data-action="delete-site-visit" data-id="${escapeHtml(visit.id)}">
          <i data-lucide="trash-2" aria-hidden="true"></i>
          Löschen
        </button>
      </div>
    </article>
  `;
}

function renderSiteVisitFloorSummary(floor, index) {
  const title = floor.name || `Etage ${index + 1}`;
  const areaName = floor.areaName ? `<span>Bereich: ${escapeHtml(floor.areaName)}</span>` : "";
  const extraAgreements = floor.extraAgreements
    ? `<span>Extra Vereinbarungen: ${escapeHtml(floor.extraAgreements)}</span>`
    : "";
  const areaNotes = floor.areaNotes || floor.notes;
  const notes = areaNotes ? `<span>Notiz: ${escapeHtml(areaNotes)}</span>` : "";
  const sanitaryLine = Number(floor.sanitaryRooms) > 0
    ? `<span>Sanitär: ${Number(floor.sanitaryRooms) || 0} Räume, ${Number(floor.sinks) || 0} Waschbecken, ${Number(floor.mirrors) || 0} Spiegel, ${Number(floor.toilets) || 0} Toiletten</span>`
    : "";
  const officeLine = Number(floor.officeRooms) > 0
    ? `<span>Büro: ${Number(floor.officeRooms) || 0} Räume, ${Number(floor.desks) || 0} Schreibtische, ${Number(floor.windows) || 0} Fenster</span>`
    : "";
  const floorLine = Number(floor.officeRooms) > 0
    ? `<span>Boden: ${escapeHtml(normalizeCleaningType(floor.cleaningType))} · ${escapeHtml(floor.floorCondition || "Teppich")}</span>`
    : "";

  return `
    <article class="floor-summary-item">
      <strong>${escapeHtml(title)}</strong>
      <div class="record-lines">
        ${areaName}
        ${sanitaryLine}
        ${officeLine}
        ${floorLine}
        ${extraAgreements}
        ${notes}
      </div>
    </article>
  `;
}

function siteVisitOfferNotes(visit) {
  const floors = Array.isArray(visit.floors) ? visit.floors : [];
  const floorLines = floors.map((floor, index) => {
    const title = floor.name || `Etage ${index + 1}`;
    const areaNotes = floor.areaNotes || floor.notes;
    return [
      `- ${title}`,
      floor.areaName ? `  Bereich: ${floor.areaName}` : "",
      Number(floor.sanitaryRooms) > 0
        ? `  Sanitär: ${Number(floor.sanitaryRooms) || 0} Räume, ${Number(floor.sinks) || 0} Waschbecken, ${Number(floor.mirrors) || 0} Spiegel, ${Number(floor.toilets) || 0} Toiletten`
        : "",
      Number(floor.officeRooms) > 0
        ? `  Büro: ${Number(floor.officeRooms) || 0} Räume, ${Number(floor.desks) || 0} Schreibtische, ${Number(floor.windows) || 0} Fenster`
        : "",
      Number(floor.officeRooms) > 0
        ? `  Boden: ${normalizeCleaningType(floor.cleaningType)} · ${floor.floorCondition || "Teppich"}`
        : "",
      floor.extraAgreements ? `  Extra Vereinbarungen: ${floor.extraAgreements}` : "",
      areaNotes ? `  Notiz zum Bereich: ${areaNotes}` : "",
    ]
      .filter(Boolean)
      .join("\n");
  });

  return [
    "Aus Begehung übernommen:",
    `Ansprechpartner vor Ort: ${visit.onsiteContact}`,
    `Adresse: ${visit.address}`,
    `Objektgröße: ${visit.squareMeters} m²`,
    "",
    "Etagenaufnahme:",
    floorLines.join("\n"),
    visit.notes ? `\nAllgemeine Notizen:\n${visit.notes}` : "",
  ]
    .filter((line) => line !== "")
    .join("\n");
}

function findCustomerForSiteVisit(visit) {
  const email = String(visit.email || "").trim().toLowerCase();
  const phone = String(visit.phone || "").trim();
  const name = String(visit.customerName || "").trim().toLowerCase();

  return (
    state.data.customers.find((customer) => String(customer.email || "").trim().toLowerCase() === email) ||
    state.data.customers.find((customer) => phone && String(customer.phone || "").trim() === phone) ||
    state.data.customers.find((customer) => String(customer.name || "").trim().toLowerCase() === name) ||
    null
  );
}

function parseSiteVisitContact(contactValue) {
  const contact = String(contactValue || "").trim();
  const salutation = contact.toLowerCase().startsWith("frau") ? "Frau" : "Herr";
  const withoutSalutation = contact.replace(/^(herr|frau)\s+/i, "").trim();
  const parts = withoutSalutation.split(/\s+/).filter(Boolean);

  return {
    salutation,
    contactLastName: parts.length ? parts[parts.length - 1] : withoutSalutation || contact || "Ansprechpartner",
  };
}

function parseSiteVisitAddress(addressValue) {
  const address = String(addressValue || "").trim();
  const [streetLine = "", cityLine = ""] = address.split(",").map((part) => part.trim());
  const streetMatch = streetLine.match(/^(.+?)\s+(\d+\s*[a-zA-Z]?(?:[-/]\w+)?)$/);
  const cityMatch = cityLine.match(/^(\d{4,5})\s+(.+)$/);

  return {
    address: streetMatch ? streetMatch[1].trim() : streetLine,
    houseNumber: streetMatch ? streetMatch[2].trim() : "",
    zip: cityMatch ? cityMatch[1].trim() : "",
    city: cityMatch ? cityMatch[2].trim() : "",
  };
}

function prefillCustomerFromSiteVisit(visit) {
  const contact = parseSiteVisitContact(visit.onsiteContact);
  const address = parseSiteVisitAddress(visit.address);

  resetCustomerForm();
  document.querySelector("#customer-name").value = visit.customerName || "";
  document.querySelector("#customer-email").value = visit.email || "";
  document.querySelector("#customer-phone").value = visit.phone || "";
  document.querySelector("#customer-salutation").value = contact.salutation;
  document.querySelector("#customer-contact-lastname").value = contact.contactLastName;
  document.querySelector("#customer-address").value = address.address || visit.address || "";
  document.querySelector("#customer-house-number").value = address.houseNumber;
  document.querySelector("#customer-zip").value = address.zip;
  document.querySelector("#customer-city").value = address.city;
  document.querySelector("#customer-form-heading").textContent = "Kunde aus Begehung anlegen";
}

function customerPayloadFromSiteVisit(visit) {
  const contact = parseSiteVisitContact(visit.onsiteContact);
  const address = parseSiteVisitAddress(visit.address);

  return {
    payload: {
      name: String(visit.customerName || "").trim(),
      email: String(visit.email || "").trim(),
      phone: String(visit.phone || "").trim(),
      salutation: contact.salutation,
      contactLastName: contact.contactLastName,
      address: address.address,
      houseNumber: address.houseNumber,
      zip: address.zip,
      city: address.city,
    },
    complete: Boolean(address.address && address.houseNumber && address.zip && address.city),
  };
}

async function ensureCustomerForSiteVisit(visit) {
  const existingCustomer = findCustomerForSiteVisit(visit);
  if (existingCustomer) {
    return { customer: existingCustomer, created: false };
  }

  const { payload, complete } = customerPayloadFromSiteVisit(visit);
  if (!complete) {
    prefillCustomerFromSiteVisit(visit);
    switchView("customer-new");
    document.querySelector("#customer-house-number").focus();
    showToast("Bitte Kundendaten kurz ergänzen. Danach kann der Kostenvoranschlag übernommen werden.");
    return { customer: null, created: false };
  }

  const customer = await apiPost("api/customers.php", payload);
  await loadAll();
  return { customer, created: true };
}

async function startOfferFromSiteVisit(id) {
  const visit = getSiteVisit(id);
  if (!visit) {
    return;
  }

  try {
    const { customer, created } = await ensureCustomerForSiteVisit(visit);
    if (!customer) {
      return;
    }

    switchView("offers");
    els.offerCustomer.value = customer.id;
    els.offerSquareMeters.value = visit.squareMeters || "";
    els.offerNotes.value = siteVisitOfferNotes(visit);
    updateOfferPreview();
    els.offerInterval.focus();
    showToast(
      created
        ? "Kunde wurde aus der Begehung angelegt und in den Kostenvoranschlag übernommen."
        : "Begehung wurde in den Kostenvoranschlag übernommen.",
    );
  } catch (error) {
    showToast(error.message);
  }
}

function renderOffers() {
  const offers = [...state.data.offers].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));

  els.offerList.innerHTML = offers.length
    ? offers.map(renderOfferCard).join("")
    : `<div class="empty-state">Noch keine Kostenvoranschläge erstellt.</div>`;
}

function renderOfferCard(offer) {
  const validity = offerValidity(offer);
  const sentLabel = offer.sentAt
    ? `Gesendet am ${formatDate(offer.sentAt)}`
    : "Noch nicht per E-Mail versendet";

  const contractButton = offer.contractId
    ? `
      <button class="secondary-button" type="button" data-action="open-contract" data-id="${escapeHtml(offer.contractId)}">
        <i data-lucide="signature" aria-hidden="true"></i>
        Vertrag ansehen
      </button>
      <a class="secondary-button" href="contract.php?contractId=${encodeURIComponent(offer.contractId)}" target="_blank" rel="noopener">
        <i data-lucide="file-text" aria-hidden="true"></i>
        Vertragsdokument
      </a>
    `
    : `
      <button class="secondary-button" type="button" data-action="create-contract-document" data-id="${escapeHtml(offer.id)}">
        <i data-lucide="file-text" aria-hidden="true"></i>
        Vertrag erstellen
      </button>
    `;

  return `
    <article class="record-item">
      <div class="record-main">
        <div>
          <div class="record-title">${escapeHtml(offer.customer.name)}</div>
          <div class="record-meta">
            <span>${escapeHtml(offer.service)} · ${offer.squareMeters} m² · ${escapeHtml(offer.interval)}</span>
            <span>Erstellt am ${formatDate(offer.createdAt)} · Start ${formatDate(offer.startDate)}</span>
            <span>${escapeHtml(sentLabel)}</span>
          </div>
        </div>
        <div class="record-side">
          <span class="badge">${formatCurrency(offer.price)}</span>
          <span class="badge ${validity.className}">${escapeHtml(validity.label)}</span>
        </div>
      </div>
      <div class="record-lines">
        <span>${escapeHtml(contactName(offer.customer))}</span>
        <span>${escapeHtml(customerAddress(offer.customer))}</span>
      </div>
      <div class="record-actions">
        <a class="secondary-button" href="offer.php?offerId=${encodeURIComponent(offer.id)}" target="_blank" rel="noopener">
          <i data-lucide="eye" aria-hidden="true"></i>
          Kostenvoranschlag Vorschau
        </a>
        <button class="primary-button" type="button" data-action="send-offer" data-id="${escapeHtml(offer.id)}">
          <i data-lucide="send" aria-hidden="true"></i>
          Kostenvoranschlag senden
        </button>
        <button class="secondary-button" type="button" data-action="copy-offer-link" data-id="${escapeHtml(offer.id)}">
          <i data-lucide="link" aria-hidden="true"></i>
          Link kopieren
        </button>
        ${contractButton}
        <button class="ghost-button" type="button" data-action="delete-offer" data-id="${escapeHtml(offer.id)}">
          <i data-lucide="trash-2" aria-hidden="true"></i>
          Löschen
        </button>
      </div>
    </article>
  `;
}

function renderContracts() {
  const contracts = [...state.data.contracts].sort((a, b) =>
    a.customer.name.localeCompare(b.customer.name, "de", { sensitivity: "base" })
  );

  els.contractList.innerHTML = contracts.length
    ? contracts.map(renderContractCard).join("")
    : `<div class="empty-state">Noch keine Verträge vorhanden.</div>`;

  if (state.selectedContractId && !getContract(state.selectedContractId)) {
    state.selectedContractId = null;
  }

  if (!state.selectedContractId && contracts.length) {
    state.selectedContractId = contracts[0].id;
  }

  renderContractPreview();
}

function contractBadgeClass(status) {
  if (status === "signiert") {
    return "success";
  }
  if (status === "daten_abgelehnt" || status === "intervall_abgelehnt") {
    return "danger";
  }
  return "warning";
}

function renderContractCard(contract) {
  const selected = contract.id === state.selectedContractId ? " selected" : "";
  const badgeClass = contractBadgeClass(contract.status);
  const signedLine = contract.signedAt
    ? `Signiert am ${formatDate(contract.signedAt)}`
    : "Noch nicht signiert";
  const termsLine = contract.termsAcceptedAt || contract.signedAt
    ? "AGB-Zustimmung dokumentiert"
    : "AGB-Zustimmung noch offen";

  return `
    <article class="record-item${selected}">
      <div class="record-main">
        <div>
          <div class="record-title">${escapeHtml(contract.number)}</div>
          <div class="record-meta">
            <span>${escapeHtml(contract.customer.name)}</span>
            <span>${escapeHtml(contract.offer.service)} · ${contract.offer.squareMeters} m²</span>
            <span>${escapeHtml(signedLine)} · ${escapeHtml(termsLine)}</span>
          </div>
        </div>
        <span class="badge ${badgeClass}">${escapeHtml(CONTRACT_STATUS_LABELS[contract.status] || contract.status)}</span>
      </div>
      <div class="record-actions">
        <a class="primary-button" href="contract.php?contractId=${encodeURIComponent(contract.id)}&document=cleanteam" target="_blank" rel="noopener">
          <i data-lucide="file-check-2" aria-hidden="true"></i>
          Vertrag CleanTeam
        </a>
        <a class="secondary-button" href="contract.php?contractId=${encodeURIComponent(contract.id)}&document=customer" target="_blank" rel="noopener">
          <i data-lucide="file-text" aria-hidden="true"></i>
          Vertrag für Kunden
        </a>
        <button class="secondary-button" type="button" data-action="select-contract" data-id="${escapeHtml(contract.id)}">
          <i data-lucide="eye" aria-hidden="true"></i>
          Vorschau
        </button>
        <button class="ghost-button" type="button" data-action="delete-contract" data-id="${escapeHtml(contract.id)}">
          <i data-lucide="trash-2" aria-hidden="true"></i>
          Löschen
        </button>
      </div>
    </article>
  `;
}

function renderContractPreview() {
  const contract = getContract(state.selectedContractId);

  if (!contract) {
    els.contractPreview.className = "contract-preview empty-state";
    els.contractPreview.textContent = "Noch kein Vertrag ausgewählt.";
    els.printContract.disabled = true;
    return;
  }

  // Zeigt exakt dasselbe serverseitig generierte Vertragsdokument wie der
  // "Vertragsdokument"-Link bei den Kostenvoranschlägen, statt einer eigenen (und leicht
  // abweichenden) clientseitigen Zusammenfassung.
  els.contractPreview.className = "contract-preview";
  els.printContract.disabled = false;
  els.contractPreview.innerHTML = `<iframe class="contract-frame" src="contract.php?contractId=${encodeURIComponent(contract.id)}&document=cleanteam"></iframe>`;
}

function updateOfferPreview() {
  const price = calculateOfferPrice(
    els.offerSquareMeters.value,
    els.offerInterval.value,
    els.offerService.value,
  );
  els.offerPricePreview.textContent = formatCurrency(price);
}

function resetCustomerForm() {
  els.customerForm.reset();
  els.customerId.value = "";
  document.querySelector("#customer-form-heading").textContent = "Kunde anlegen";
  els.cancelCustomerEdit.hidden = true;
}

function fillCustomerForm(customer) {
  els.customerId.value = customer.id;
  document.querySelector("#customer-name").value = customer.name;
  document.querySelector("#customer-email").value = customer.email;
  document.querySelector("#customer-phone").value = customer.phone;
  document.querySelector("#customer-salutation").value = customer.salutation;
  document.querySelector("#customer-contact-lastname").value = customer.contactLastName;
  document.querySelector("#customer-address").value = customer.address;
  document.querySelector("#customer-house-number").value = customer.houseNumber;
  document.querySelector("#customer-zip").value = customer.zip;
  document.querySelector("#customer-city").value = customer.city;
  document.querySelector("#customer-form-heading").textContent = "Kunde bearbeiten";
  els.cancelCustomerEdit.hidden = false;
  document.querySelector("#customer-name").focus();
}

async function handleCustomerSubmit(event) {
  event.preventDefault();

  const id = els.customerId.value;
  const payload = {
    name: document.querySelector("#customer-name").value.trim(),
    email: document.querySelector("#customer-email").value.trim(),
    phone: document.querySelector("#customer-phone").value.trim(),
    salutation: document.querySelector("#customer-salutation").value,
    contactLastName: document.querySelector("#customer-contact-lastname").value.trim(),
    address: document.querySelector("#customer-address").value.trim(),
    houseNumber: document.querySelector("#customer-house-number").value.trim(),
    zip: document.querySelector("#customer-zip").value.trim(),
    city: document.querySelector("#customer-city").value.trim(),
  };

  try {
    if (id) {
      await apiPut(`api/customers.php?id=${encodeURIComponent(id)}`, payload);
      showToast("Kunde wurde aktualisiert.");
    } else {
      await apiPost("api/customers.php", payload);
      showToast("Kunde wurde angelegt.");
    }

    resetCustomerForm();
    await loadAll();
    switchView("customer-list");
  } catch (error) {
    showToast(error.message);
  }
}

async function handleSiteVisitSubmit(event) {
  event.preventDefault();

  const floors = collectSiteVisitFloors();
  if (floors.length === 0) {
    showToast("Bitte zuerst eine Etage öffnen.");
    return;
  }

  const squareMeters = Number(document.querySelector("#site-visit-square-meters").value);
  if (squareMeters <= 0) {
    showToast("Bitte die Objektgröße in Quadratmetern angeben.");
    return;
  }

  const payload = {
    customerName: document.querySelector("#site-visit-customer-name").value.trim(),
    email: document.querySelector("#site-visit-email").value.trim(),
    phone: document.querySelector("#site-visit-phone").value.trim(),
    address: document.querySelector("#site-visit-address").value.trim(),
    onsiteContact: document.querySelector("#site-visit-onsite-contact").value.trim(),
    squareMeters,
    floors,
    notes: document.querySelector("#site-visit-notes").value.trim(),
  };

  try {
    await apiPost("api/site-visits.php", payload);
    resetSiteVisitForm();
    await loadAll();
    switchView("site-visit-saved");
    showToast("Begehung wurde gespeichert.");
  } catch (error) {
    showToast(error.message);
  }
}

async function handleOfferSubmit(event) {
  event.preventDefault();

  const customerId = els.offerCustomer.value;
  if (!customerId) {
    showToast("Bitte zuerst einen Kunden anlegen.");
    switchView("customer-new");
    return;
  }

  const payload = {
    customerId,
    squareMeters: Number(els.offerSquareMeters.value),
    interval: els.offerInterval.value,
    service: els.offerService.value,
    startDate: els.offerStartDate.value,
    notes: els.offerNotes.value.trim(),
  };

  try {
    await apiPost("api/offers.php", payload);
    els.offerForm.reset();
    els.offerStartDate.value = todayAsInputValue();
    updateOfferPreview();
    await loadAll();
    showToast("Kostenvoranschlag wurde erstellt.");
  } catch (error) {
    showToast(error.message);
  }
}

async function sendOffer(id) {
  try {
    await apiPost(`api/send-offer.php?id=${encodeURIComponent(id)}`);
    await loadAll();
    showToast("Kostenvoranschlag wurde per E-Mail versendet.");
  } catch (error) {
    showToast(error.message);
  }
}

async function createContractDocument(offerId) {
  try {
    const contract = await apiPost("api/contracts.php", { offerId });
    await loadAll();
    window.open(`contract.php?contractId=${encodeURIComponent(contract.id)}`, "_blank");
  } catch (error) {
    showToast(error.message);
  }
}

function openLinkModal(url) {
  els.linkModalInput.value = url;
  els.linkModal.hidden = false;
  els.linkModalInput.focus();
  els.linkModalInput.select();
}

function closeLinkModal() {
  els.linkModal.hidden = true;
}

async function copyLinkModalValue() {
  const url = els.linkModalInput.value;
  try {
    await navigator.clipboard.writeText(url);
    showToast("Link wurde kopiert.");
  } catch (error) {
    els.linkModalInput.select();
    document.execCommand("copy");
    showToast("Link wurde kopiert.");
  }
}

function copyOfferLink(id) {
  const offer = getOffer(id);
  if (!offer) {
    return;
  }

  openLinkModal(offer.publicUrl);
}

async function deleteCustomer(id) {
  const customer = getCustomer(id);
  if (!customer) {
    return;
  }

  const confirmed = window.confirm(`Kunden "${customer.name}" löschen?`);
  if (!confirmed) {
    return;
  }

  try {
    await apiDelete(`api/customers.php?id=${encodeURIComponent(id)}`);
    await loadAll();
    showToast("Kunde wurde gelöscht.");
  } catch (error) {
    showToast(error.message);
  }
}

async function deleteSiteVisit(id) {
  const visit = getSiteVisit(id);
  if (!visit) {
    return;
  }

  const confirmed = window.confirm(`Begehung für "${visit.customerName}" löschen?`);
  if (!confirmed) {
    return;
  }

  try {
    await apiDelete(`api/site-visits.php?id=${encodeURIComponent(id)}`);
    await loadAll();
    showToast("Begehung wurde gelöscht.");
  } catch (error) {
    showToast(error.message);
  }
}

async function deleteOffer(id) {
  const confirmed = window.confirm("Kostenvoranschlag löschen? Ein bereits gestarteter Vertrag wird ebenfalls entfernt.");
  if (!confirmed) {
    return;
  }

  try {
    await apiDelete(`api/offers.php?id=${encodeURIComponent(id)}`);
    if (state.selectedContractId && !state.data.contracts.some((contract) => contract.id === state.selectedContractId)) {
      state.selectedContractId = null;
    }
    await loadAll();
    showToast("Kostenvoranschlag wurde gelöscht.");
  } catch (error) {
    showToast(error.message);
  }
}

async function deleteContract(id) {
  const confirmed = window.confirm("Vertrag löschen?");
  if (!confirmed) {
    return;
  }

  try {
    await apiDelete(`api/contracts.php?id=${encodeURIComponent(id)}`);
    if (state.selectedContractId === id) {
      state.selectedContractId = null;
    }
    await loadAll();
    showToast("Vertrag wurde gelöscht.");
  } catch (error) {
    showToast(error.message);
  }
}

async function loadSmtpSettings() {
  try {
    const settings = await apiGet("api/settings.php");
    els.smtpHost.value = settings.host || "";
    els.smtpPort.value = settings.port || 587;
    els.smtpEncryption.value = settings.encryption || "tls";
    els.smtpUsername.value = settings.username || "";
    els.smtpPassword.value = "";
    els.smtpPassword.placeholder = settings.hasPassword
      ? "Unverändert lassen = altes Passwort behalten"
      : "Noch kein Passwort hinterlegt";
    els.smtpFromName.value = settings.fromName || "CleanTeam";
    els.smtpFromEmail.value = settings.fromEmail || "";
  } catch (error) {
    showToast(error.message);
  }
}

async function handleSmtpSubmit(event) {
  event.preventDefault();

  const payload = {
    host: els.smtpHost.value.trim(),
    port: Number(els.smtpPort.value),
    encryption: els.smtpEncryption.value,
    username: els.smtpUsername.value.trim(),
    password: els.smtpPassword.value,
    fromName: els.smtpFromName.value.trim(),
    fromEmail: els.smtpFromEmail.value.trim(),
  };

  try {
    await apiPost("api/settings.php", payload);
    showToast("Einstellungen wurden gespeichert.");
    await loadSmtpSettings();
  } catch (error) {
    showToast(error.message);
  }
}

async function loadMailbox() {
  try {
    const settings = await apiGet("api/mailbox.php?action=settings");

    els.mailboxHost.value = settings.host || "";
    els.mailboxImapPort.value = settings.imapPort || 993;
    els.mailboxImapEncryption.value = settings.imapEncryption || "ssl";
    els.mailboxSmtpPort.value = settings.smtpPort || 587;
    els.mailboxSmtpEncryption.value = settings.smtpEncryption || "tls";
    els.mailboxUsername.value = settings.username || "";
    els.mailboxPassword.value = "";
    els.mailboxPassword.placeholder = settings.hasPassword
      ? "Unverändert lassen = altes Passwort behalten"
      : "Noch kein Passwort hinterlegt";
    els.mailboxFromName.value = settings.fromName || "CleanTeam";
    els.mailboxSignature.value = settings.signature || "";
    mailboxState.signature = settings.signature || "";

    els.mailboxNotConfigured.hidden = settings.configured;
    els.mailboxConfigured.hidden = !settings.configured;

    if (settings.configured) {
      els.mailboxAccountLabel.textContent = settings.username;
      await loadMailboxInbox();
    }
  } catch (error) {
    showToast(error.message);
  }
}

function switchMailboxFolder(folder) {
  mailboxState.folder = folder;
  mailboxState.selectedUid = null;
  els.mailboxFolderTabs.forEach((button) => {
    button.classList.toggle("active", button.dataset.folder === folder);
  });
  els.mailboxMessage.innerHTML = "Noch keine Nachricht ausgewählt.";
  loadMailboxInbox();
}

async function loadMailboxInbox() {
  const folderLabel = mailboxState.folder === "sent" ? "Postausgang" : "Posteingang";
  els.mailboxList.innerHTML = `<div class="empty-state">Lade ${folderLabel}…</div>`;

  try {
    const result = await apiGet(`api/mailbox.php?action=inbox&folder=${mailboxState.folder}`);
    mailboxState.messages = result.messages;
    renderMailboxList();
  } catch (error) {
    els.mailboxList.innerHTML = `<div class="empty-state">${escapeHtml(error.message)}</div>`;
  }
}

function renderMailboxList() {
  const messages = mailboxState.messages;

  els.mailboxList.innerHTML = messages.length
    ? messages.map(renderMailboxListItem).join("")
    : `<div class="empty-state">Keine E-Mails im Posteingang.</div>`;

  refreshIcons();
}

function renderMailboxListItem(message) {
  const selected = message.uid === mailboxState.selectedUid ? " selected" : "";
  const unreadBadge = message.seen ? "" : `<span class="badge">Neu</span>`;

  return `
    <button class="compact-item${selected}" type="button" data-action="open-mailbox-message" data-id="${message.uid}">
      <div>
        <div class="record-title">${escapeHtml(message.subject)}</div>
        <div class="record-meta">
          <span>${escapeHtml(message.from)}</span>
          <span>${message.date ? formatDate(message.date) : ""}</span>
        </div>
      </div>
      ${unreadBadge}
    </button>
  `;
}

async function openMailboxMessage(uid) {
  mailboxState.selectedUid = Number(uid);
  renderMailboxList();

  els.mailboxMessage.classList.remove("empty-state");
  els.mailboxMessage.innerHTML = `<div class="empty-state">Lade Nachricht…</div>`;

  try {
    const message = await apiGet(
      `api/mailbox.php?action=message&uid=${encodeURIComponent(uid)}&folder=${mailboxState.folder}`
    );

    els.mailboxMessage.innerHTML = `
      <div class="record-lines" style="margin-bottom:16px;">
        <strong>${escapeHtml(message.subject)}</strong>
        <span>Von: ${escapeHtml(message.from)}</span>
        <span>An: ${escapeHtml(message.to)}</span>
        <span>${message.date ? formatDate(message.date) : ""}</span>
      </div>
      <div class="mailbox-body"></div>
    `;

    const bodyContainer = els.mailboxMessage.querySelector(".mailbox-body");
    if (message.html) {
      // E-Mail-HTML stammt von Dritten und ist nicht vertrauenswürdig. Wird deshalb in einem
      // sandboxed iframe ohne Skriptausführung und ohne Zugriff auf die App-Session gerendert.
      const frame = document.createElement("iframe");
      frame.sandbox = "";
      frame.className = "mailbox-frame";
      bodyContainer.appendChild(frame);
      frame.srcdoc = message.html;
    } else if (message.plain) {
      const pre = document.createElement("pre");
      pre.textContent = message.plain;
      bodyContainer.appendChild(pre);
    } else {
      bodyContainer.textContent = "Kein Inhalt.";
    }

    const messageInList = mailboxState.messages.find((item) => item.uid === Number(uid));
    if (messageInList) {
      messageInList.seen = true;
      renderMailboxList();
    }
  } catch (error) {
    els.mailboxMessage.innerHTML = `<div class="empty-state">${escapeHtml(error.message)}</div>`;
  }
}

async function handleMailboxComposeSubmit(event) {
  event.preventDefault();

  const payload = {
    to: els.mailboxComposeTo.value.trim(),
    subject: els.mailboxComposeSubject.value.trim(),
    body: els.mailboxComposeBody.value,
  };

  try {
    await apiPost("api/mailbox.php?action=send", payload);
    showToast("E-Mail wurde gesendet.");
    els.mailboxComposeForm.reset();
    els.mailboxComposeForm.hidden = true;
    if (mailboxState.folder === "sent") {
      await loadMailboxInbox();
    }
  } catch (error) {
    showToast(error.message);
  }
}

async function handleMailboxSettingsSubmit(event) {
  event.preventDefault();

  const payload = {
    host: els.mailboxHost.value.trim(),
    imapPort: Number(els.mailboxImapPort.value),
    imapEncryption: els.mailboxImapEncryption.value,
    smtpPort: Number(els.mailboxSmtpPort.value),
    smtpEncryption: els.mailboxSmtpEncryption.value,
    username: els.mailboxUsername.value.trim(),
    password: els.mailboxPassword.value,
    fromName: els.mailboxFromName.value.trim(),
    signature: els.mailboxSignature.value,
  };

  try {
    await apiPost("api/mailbox.php?action=settings", payload);
    showToast("Postfach-Einstellungen wurden gespeichert.");
    await loadMailbox();
  } catch (error) {
    showToast(error.message);
  }
}

async function sendTestMail() {
  try {
    await apiPost("api/settings.php?action=test");
    showToast("Test-E-Mail wurde an die Absenderadresse gesendet.");
  } catch (error) {
    showToast(error.message);
  }
}

function renderEmailRow(value) {
  const row = document.createElement("div");
  row.className = "email-row";
  row.innerHTML = `
    <input type="email" placeholder="name@beispiel.de" value="${escapeHtml(value)}" />
    <button class="ghost-button" type="button" data-action="remove-email-row">
      <i data-lucide="x" aria-hidden="true"></i>
    </button>
  `;
  return row;
}

function addContractNotifyEmailRow(value = "") {
  els.contractNotifyEmails.appendChild(renderEmailRow(value));
  refreshIcons();
}

async function loadContractNotifySettings() {
  try {
    const settings = await apiGet("api/contract-notifications.php");
    els.contractNotifyEnabled.checked = settings.enabled;
    els.contractNotifyEmails.innerHTML = "";

    const emails = settings.recipients.length ? settings.recipients : [""];
    emails.forEach((email) => addContractNotifyEmailRow(email));
  } catch (error) {
    showToast(error.message);
  }
}

async function handleContractNotifySubmit(event) {
  event.preventDefault();

  const recipients = [...els.contractNotifyEmails.querySelectorAll("input")]
    .map((input) => input.value.trim())
    .filter((value) => value !== "");

  try {
    await apiPost("api/contract-notifications.php", {
      enabled: els.contractNotifyEnabled.checked,
      recipients,
    });
    showToast("Einstellungen wurden gespeichert.");
    await loadContractNotifySettings();
  } catch (error) {
    showToast(error.message);
  }
}

async function sendTestContractNotification() {
  els.contractNotifyTest.disabled = true;
  try {
    const result = await apiPost("api/contract-notifications.php?action=test");
    showToast(`Testvertrag wurde gesendet an: ${result.sentTo.join(", ")}`);
  } catch (error) {
    showToast(error.message);
  } finally {
    els.contractNotifyTest.disabled = false;
  }
}

function applyBrandLogo(logoUrl) {
  currentLogoUrl = logoUrl || null;

  els.brandMarks.forEach((mark) => {
    mark.classList.toggle("has-logo", Boolean(logoUrl));
    mark.innerHTML = logoUrl ? `<img src="${escapeHtml(logoUrl)}" alt="Logo" />` : "<span>CT</span>";
  });

  if (logoUrl) {
    els.logoPreview.className = "logo-preview";
    els.logoPreview.innerHTML = `<img src="${escapeHtml(logoUrl)}" alt="Logo" />`;
    els.logoRemove.hidden = false;
  } else {
    els.logoPreview.className = "logo-preview empty-state";
    els.logoPreview.textContent = "Kein Logo hinterlegt.";
    els.logoRemove.hidden = true;
  }
}

async function loadBranding() {
  try {
    const branding = await apiGet("api/branding.php");
    applyBrandLogo(branding.logoUrl);
  } catch (error) {
    // Kein Logo hinterlegt oder Ladefehler: Fallback-Initialen bleiben stehen.
  }
}

async function handleLogoUpload(event) {
  const file = event.target.files[0];
  if (!file) {
    return;
  }

  const formData = new FormData();
  formData.append("logo", file);

  try {
    const response = await fetch("api/branding.php", { method: "POST", body: formData, credentials: "same-origin" });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(data.error || "Logo konnte nicht hochgeladen werden.");
    }
    applyBrandLogo(data.logoUrl);
    showToast("Logo wurde hochgeladen.");
  } catch (error) {
    showToast(error.message);
  } finally {
    els.logoFileInput.value = "";
  }
}

async function handleLogoRemove() {
  try {
    await apiDelete("api/branding.php");
    applyBrandLogo(null);
    showToast("Logo wurde entfernt.");
  } catch (error) {
    showToast(error.message);
  }
}

function handleSiteVisitFloorAction(event) {
  const button = event.target.closest('[data-action="remove-site-visit-floor"]');
  if (!button) {
    return;
  }

  button.closest(".floor-section").remove();
  renumberSiteVisitFloors();
  ensureSiteVisitFloorEmptyState();
  refreshIcons();
}

function handleSiteVisitFloorInput(event) {
  if (event.target.matches('[name="floorName"]')) {
    renumberSiteVisitFloors();
  }
}

function handleDashboardAction(event) {
  const button = event.target.closest("[data-action]");
  if (!button) {
    return;
  }

  if (button.dataset.action === "add-site-visit-floor") {
    event.preventDefault();
    addSiteVisitFloor();
  }

  if (button.dataset.action === "counter-decrement") {
    event.preventDefault();
    changeCounter(button, -1);
  }

  if (button.dataset.action === "counter-increment") {
    event.preventDefault();
    changeCounter(button, 1);
  }
}

function handleRecordAction(event) {
  const button = event.target.closest("[data-action]");
  if (!button) {
    return;
  }

  const { action, id } = button.dataset;

  if (action === "edit-customer") {
    const customer = getCustomer(id);
    if (customer) {
      fillCustomerForm(customer);
      switchView("customer-new");
      document.querySelector("#customer-new-view").scrollIntoView({ behavior: "smooth", block: "start" });
    }
  }

  if (action === "offer-for-customer") {
    els.offerCustomer.value = id;
    switchView("offers");
    els.offerSquareMeters.focus();
  }

  if (action === "delete-customer") {
    deleteCustomer(id);
  }

  if (action === "delete-site-visit") {
    deleteSiteVisit(id);
  }

  if (action === "offer-from-site-visit") {
    startOfferFromSiteVisit(id);
  }

  if (action === "send-offer") {
    sendOffer(id);
  }

  if (action === "copy-offer-link") {
    copyOfferLink(id);
  }

  if (action === "open-contract") {
    state.selectedContractId = id;
    switchView("contracts");
  }

  if (action === "create-contract-document") {
    createContractDocument(id);
  }

  if (action === "delete-offer") {
    deleteOffer(id);
  }

  if (action === "select-contract") {
    state.selectedContractId = id;
    renderAll();
  }

  if (action === "open-mailbox-message") {
    openMailboxMessage(id);
  }

  if (action === "delete-contract") {
    deleteContract(id);
  }
}

function bindEvents() {
  els.loginForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    const email = els.loginEmail.value.trim();
    const password = els.loginPassword.value;

    try {
      await apiPost("api/login.php", { email, password });
      els.loginError.hidden = true;
      showApp();
    } catch (error) {
      els.loginError.textContent = error.message;
      els.loginError.hidden = false;
    }
  });

  els.logoutButton.addEventListener("click", async () => {
    try {
      await apiPost("api/logout.php");
    } catch (error) {
      // Ignore network errors on logout, still return to the login screen.
    }
    showLogin();
  });

  els.navLinks.forEach((button) => {
    button.addEventListener("click", () => switchView(button.dataset.view));
  });

  els.customersGroupToggle.addEventListener("click", () => {
    setCustomersGroupExpanded(els.customersSubgroup.hidden);
  });

  els.siteVisitsGroupToggle.addEventListener("click", () => {
    setSiteVisitsGroupExpanded(els.siteVisitsSubgroup.hidden);
  });

  els.settingsGroupToggle.addEventListener("click", () => {
    setSettingsGroupExpanded(els.settingsSubgroup.hidden);
  });

  els.menuButton.addEventListener("click", openMobileNav);
  els.mobileBackdrop.addEventListener("click", closeMobileNav);
  els.quickCustomer.addEventListener("click", () => switchView("customer-new"));
  els.quickOffer.addEventListener("click", () => switchView("offers"));
  els.newCustomerButton.addEventListener("click", () => {
    resetCustomerForm();
    switchView("customer-new");
    document.querySelector("#customer-name").focus();
  });
  els.cancelCustomerEdit.addEventListener("click", resetCustomerForm);

  els.customerForm.addEventListener("submit", handleCustomerSubmit);
  els.customerSearch.addEventListener("input", renderCustomers);

  els.siteVisitForm.addEventListener("submit", handleSiteVisitSubmit);
  document.addEventListener("click", handleDashboardAction);
  els.siteVisitFloors.addEventListener("click", handleSiteVisitFloorAction);
  els.siteVisitFloors.addEventListener("input", handleSiteVisitFloorInput);

  els.offerForm.addEventListener("submit", handleOfferSubmit);
  els.offerSquareMeters.addEventListener("input", updateOfferPreview);
  els.offerInterval.addEventListener("change", updateOfferPreview);
  els.offerService.addEventListener("change", updateOfferPreview);

  els.customerList.addEventListener("click", handleRecordAction);
  els.siteVisitList.addEventListener("click", handleRecordAction);
  els.offerList.addEventListener("click", handleRecordAction);
  els.contractList.addEventListener("click", handleRecordAction);

  els.printContract.addEventListener("click", () => {
    const frame = els.contractPreview.querySelector(".contract-frame");
    if (frame) {
      frame.contentWindow.print();
    }
  });

  els.smtpForm.addEventListener("submit", handleSmtpSubmit);
  els.sendTestMail.addEventListener("click", sendTestMail);

  els.mailboxList.addEventListener("click", handleRecordAction);
  els.mailboxRefresh.addEventListener("click", loadMailboxInbox);
  els.mailboxGotoSettings.addEventListener("click", () => {
    document.querySelector("#mailbox-settings-heading").scrollIntoView({ behavior: "smooth", block: "start" });
  });
  els.mailboxComposeToggle.addEventListener("click", () => {
    const wasHidden = els.mailboxComposeForm.hidden;
    els.mailboxComposeForm.hidden = !wasHidden;
    if (wasHidden && !els.mailboxComposeBody.value && mailboxState.signature) {
      els.mailboxComposeBody.value = `\n\n-- \n${mailboxState.signature}`;
      els.mailboxComposeBody.setSelectionRange(0, 0);
    }
  });
  els.mailboxFolderTabs.forEach((button) => {
    button.addEventListener("click", () => switchMailboxFolder(button.dataset.folder));
  });
  els.mailboxComposeCancel.addEventListener("click", () => {
    els.mailboxComposeForm.reset();
    els.mailboxComposeForm.hidden = true;
  });
  els.mailboxComposeForm.addEventListener("submit", handleMailboxComposeSubmit);
  els.mailboxSettingsForm.addEventListener("submit", handleMailboxSettingsSubmit);

  els.logoFileInput.addEventListener("change", handleLogoUpload);
  els.logoRemove.addEventListener("click", handleLogoRemove);

  els.contractNotifyForm.addEventListener("submit", handleContractNotifySubmit);
  els.contractNotifyAddEmail.addEventListener("click", () => addContractNotifyEmailRow(""));
  els.contractNotifyTest.addEventListener("click", sendTestContractNotification);
  els.contractNotifyEmails.addEventListener("click", (event) => {
    const button = event.target.closest('[data-action="remove-email-row"]');
    if (!button) {
      return;
    }
    if (els.contractNotifyEmails.children.length > 1) {
      button.closest(".email-row").remove();
    } else {
      button.closest(".email-row").querySelector("input").value = "";
    }
  });

  els.linkModalCopy.addEventListener("click", copyLinkModalValue);
  els.linkModalClose.addEventListener("click", closeLinkModal);
  els.linkModal.addEventListener("click", (event) => {
    if (event.target === els.linkModal) {
      closeLinkModal();
    }
  });
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !els.linkModal.hidden) {
      closeLinkModal();
    }
  });

  window.setInterval(() => {
    if (!els.appShell.hidden) {
      loadAll();
    }
  }, 60000);
}

async function init() {
  bindEvents();
  els.offerStartDate.value = todayAsInputValue();
  loadBranding();

  try {
    const session = await apiGet("api/me.php");
    if (session.loggedIn) {
      showApp();
      return;
    }
  } catch (error) {
    // Fall through to the login screen if the session check fails.
  }

  showLogin();
  refreshIcons();
}

init();
