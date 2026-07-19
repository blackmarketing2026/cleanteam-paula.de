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
  session: {
    email: "",
    role: "role_one",
    roleLabel: "Rolle 1",
    isAdmin: false,
  },
  users: [],
  userRoles: {},
  currentUserId: null,
  currentView: "overview",
  selectedContractId: null,
  pendingOfferSiteVisitId: null,
  pendingSendOfferId: null,
  offerSendRecipientMode: "customer",
  editingSiteVisitId: null,
  contractFilters: {
    search: "",
    period: "all",
    sortKey: "customerName",
    sortDirection: "asc",
  },
  pendingQuizCustomerReturn: false,
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
  offersGroupToggle: document.querySelector("#offers-group-toggle"),
  offersSubgroup: document.querySelector("#offers-subgroup"),
  settingsGroupToggle: document.querySelector("#settings-group-toggle"),
  settingsSubgroup: document.querySelector("#settings-subgroup"),
  views: document.querySelectorAll(".view"),
  viewTitle: document.querySelector("#view-title"),
  sidebar: document.querySelector(".sidebar"),
  bottomMenuButton: document.querySelector("#bottom-menu-button"),
  mobileBackdrop: document.querySelector("#mobile-backdrop"),
  newCustomerButton: document.querySelector("#new-customer-button"),
  customerForm: document.querySelector("#customer-form"),
  customerSearch: document.querySelector("#customer-search"),
  customerList: document.querySelector("#customer-list"),
  customerId: document.querySelector("#customer-id"),
  cancelCustomerEdit: document.querySelector("#cancel-customer-edit"),
  siteVisitForm: document.querySelector("#site-visit-form"),
  addSiteVisitFloor: document.querySelector("#add-site-visit-floor"),
  siteVisitFloors: document.querySelector("#site-visit-floors"),
  siteVisitEditorPanel: document.querySelector("#site-visit-editor-panel"),
  siteVisitEditorHeading: document.querySelector("#site-visit-editor-heading"),
  siteVisitEditorMeta: document.querySelector("#site-visit-editor-meta"),
  cancelSiteVisitEdit: document.querySelector("#cancel-site-visit-edit"),
  siteVisitSubmitLabel: document.querySelector("#site-visit-submit-label"),
  siteVisitList: document.querySelector("#site-visit-list"),
  offerForm: document.querySelector("#offer-form"),
  offerSiteVisit: document.querySelector("#offer-site-visit"),
  offerCustomer: document.querySelector("#offer-customer"),
  offerSquareMeters: document.querySelector("#offer-square-meters"),
  offerInterval: document.querySelector("#offer-interval"),
  offerService: document.querySelector("#offer-service"),
  offerStartDate: document.querySelector("#offer-start-date"),
  offerManualPrice: document.querySelector("#offer-manual-price"),
  offerNotes: document.querySelector("#offer-notes"),
  offerEstimatedPricePreview: document.querySelector("#offer-estimated-price-preview"),
  offerList: document.querySelector("#offer-list"),
  offerSendModal: document.querySelector("#offer-send-modal"),
  offerSendForm: document.querySelector("#offer-send-form"),
  offerSendCustomer: document.querySelector("#offer-send-customer"),
  offerSendSuggested: document.querySelector("#offer-send-suggested"),
  offerSendManual: document.querySelector("#offer-send-manual"),
  offerSendEmail: document.querySelector("#offer-send-email"),
  offerSendCancel: document.querySelector("#offer-send-cancel"),
  offerSendSubmit: document.querySelector("#offer-send-submit"),
  contractList: document.querySelector("#contract-list"),
  contractSearch: document.querySelector("#contract-search"),
  contractPeriodFilter: document.querySelector("#contract-period-filter"),
  contractSort: document.querySelector("#contract-sort"),
  contractSortDirection: document.querySelector("#contract-sort-direction"),
  contractCount: document.querySelector("#contract-count"),
  smtpForm: document.querySelector("#smtp-form"),
  smtpHost: document.querySelector("#smtp-host"),
  smtpPort: document.querySelector("#smtp-port"),
  smtpEncryption: document.querySelector("#smtp-encryption"),
  smtpUsername: document.querySelector("#smtp-username"),
  smtpPassword: document.querySelector("#smtp-password"),
  smtpFromName: document.querySelector("#smtp-from-name"),
  smtpFromEmail: document.querySelector("#smtp-from-email"),
  sendTestMail: document.querySelector("#send-test-mail"),
  emailSettingsForm: document.querySelector("#email-settings-form"),
  emailSettingsCustomerEnabled: document.querySelector("#email-settings-customer-enabled"),
  emailSettingsOfferEnabled: document.querySelector("#email-settings-offer-enabled"),
  emailSettingsContractEnabled: document.querySelector("#email-settings-contract-enabled"),
  emailSettingsMailboxEnabled: document.querySelector("#email-settings-mailbox-enabled"),
  emailSettingsInternalContractEnabled: document.querySelector("#email-settings-internal-contract-enabled"),
  emailSettingsTestEnabled: document.querySelector("#email-settings-test-enabled"),
  emailSignatureForm: document.querySelector("#email-signature-form"),
  emailSignatureName: document.querySelector("#email-signature-name"),
  emailSignatureRole: document.querySelector("#email-signature-role"),
  emailSignaturePhone: document.querySelector("#email-signature-phone"),
  emailSignatureMobile: document.querySelector("#email-signature-mobile"),
  emailSignatureEmail: document.querySelector("#email-signature-email"),
  emailSignatureWebsite: document.querySelector("#email-signature-website"),
  emailSignatureCompany: document.querySelector("#email-signature-company"),
  emailSignatureAddress1: document.querySelector("#email-signature-address-1"),
  emailSignatureAddress2: document.querySelector("#email-signature-address-2"),
  emailSignatureExtra: document.querySelector("#email-signature-extra"),
  emailSignatureUseAll: document.querySelector("#email-signature-use-all"),
  emailSignatureUseOffer: document.querySelector("#email-signature-use-offer"),
  emailSignatureUseContract: document.querySelector("#email-signature-use-contract"),
  emailSignatureUseMailbox: document.querySelector("#email-signature-use-mailbox"),
  emailSignatureUsageOptions: document.querySelectorAll("[data-email-signature-use]"),
  emailSignatureImagePreview: document.querySelector("#email-signature-image-preview"),
  emailSignatureImageInput: document.querySelector("#email-signature-image-input"),
  emailSignatureImageRemove: document.querySelector("#email-signature-image-remove"),
  emailSignatureImageStatus: document.querySelector("#email-signature-image-status"),
  emailSignaturePreview: document.querySelector("#email-signature-preview"),
  emailSignatureSaveStatus: document.querySelector("#email-signature-save-status"),
  mailboxNotConfigured: document.querySelector("#mailbox-not-configured"),
  mailboxNotConfiguredAdminText: document.querySelector("#mailbox-not-configured-admin-text"),
  mailboxNotConfiguredUserText: document.querySelector("#mailbox-not-configured-user-text"),
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
  mailboxSettingsPanel: document.querySelector("#mailbox-settings-panel"),
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
  templatePlaceholderGroups: document.querySelector("#template-placeholder-groups"),
  templateEditor: document.querySelector("#contract-template-editor"),
  templateReset: document.querySelector("#contract-template-reset"),
  templatePreviewButton: document.querySelector("#contract-template-preview-button"),
  templateSave: document.querySelector("#contract-template-save"),
  templatePreviewFrame: document.querySelector("#contract-template-preview-frame"),
  contractorSignaturePad: document.querySelector("#contractor-signature-pad"),
  contractorSignatureStatus: document.querySelector("#contractor-signature-status"),
  contractorSignatureClear: document.querySelector("#contractor-signature-clear"),
  contractorSignatureRemove: document.querySelector("#contractor-signature-remove"),
  contractorSignatureSave: document.querySelector("#contractor-signature-save"),
  userForm: document.querySelector("#user-form"),
  userName: document.querySelector("#user-name"),
  userEmail: document.querySelector("#user-email"),
  userPassword: document.querySelector("#user-password"),
  userRole: document.querySelector("#user-role"),
  userList: document.querySelector("#user-list"),
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
  "site-visit-saved": "Gespeicherte Begehungen",
  "site-visit-quiz": "Neue Begehung erstellen",
  "offers-new": "Neue Kostenvoranschläge",
  "offers-saved": "Gespeicherte Kostenvoranschläge",
  contracts: "Verträge",
  mailbox: "Postfach",
  "settings-smtp": "SMTP-Server-Einstellungen",
  "settings-email": "E-Mail-Einstellungen",
  "settings-email-signature": "E-Mail-Signatur",
  "settings-notify": "Vertragsbenachrichtigungen-Einstellungen",
  "settings-logo": "Logo-Einstellungen",
  "settings-signature": "Signatur",
  "settings-template": "Mustervertrag",
  "settings-users": "User & Rollen",
};

let currentLogoUrl = null;
let emailSignatureImageUrl = "";
let pendingEmailSignatureImageFile = null;
let pendingEmailSignatureImageDataUrl = "";
let pendingEmailSignatureImageRemoval = false;
let contractorSignaturePadReady = false;
let contractorSignatureHasInk = false;
let contractorSignatureDrawing = false;
let contractorSignatureLastPoint = null;

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

window.ctCustomerList = () => [...state.data.customers];

function getSiteVisit(id) {
  return state.data.siteVisits.find((visit) => visit.id === id);
}

function getOffer(id) {
  return state.data.offers.find((offer) => offer.id === id);
}

function signedContractOfferIds() {
  const ids = new Set(
    state.data.contracts
      .filter((contract) => contract.status === "signiert")
      .map((contract) => contract.offer?.id || contract.offerId)
      .filter(Boolean),
  );

  state.data.offers.forEach((offer) => {
    if (offer.contractStatus === "signiert") {
      ids.add(offer.id);
    }
  });

  return ids;
}

function contractLinkedSiteVisitIds() {
  const ids = new Set(
    state.data.contracts
      .map((contract) => contract.offer?.siteVisitId)
      .filter(Boolean),
  );

  state.data.offers.forEach((offer) => {
    if (offer.siteVisitId && (offer.contractId || offer.contractStatus)) {
      ids.add(offer.siteVisitId);
    }
  });

  return ids;
}

window.ctContractLinkedSiteVisitIds = () => [...contractLinkedSiteVisitIds()];

function visibleSavedOffers() {
  const hiddenOfferIds = signedContractOfferIds();
  return state.data.offers.filter((offer) => !hiddenOfferIds.has(offer.id));
}

function visibleSavedSiteVisits() {
  const hiddenVisitIds = contractLinkedSiteVisitIds();
  return state.data.siteVisits.filter((visit) => !hiddenVisitIds.has(visit.id));
}

function getOpenSiteVisits() {
  const processedVisitIds = new Set(
    state.data.offers
      .map((offer) => offer.siteVisitId)
      .filter(Boolean),
  );

  return [...state.data.siteVisits]
    .filter((visit) => !processedVisitIds.has(visit.id))
    .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
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

function isValidEmail(value) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || "").trim());
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

function isAdmin() {
  return Boolean(state.session?.isAdmin);
}

function applyRolePermissions() {
  const canManageSettings = isAdmin();

  els.settingsGroupToggle.hidden = !canManageSettings;
  if (!canManageSettings) {
    setSettingsGroupExpanded(false);
  }

  if (els.mailboxSettingsPanel) {
    els.mailboxSettingsPanel.hidden = !canManageSettings;
  }
  if (els.mailboxGotoSettings) {
    els.mailboxGotoSettings.hidden = !canManageSettings;
  }
  if (els.mailboxNotConfiguredAdminText) {
    els.mailboxNotConfiguredAdminText.hidden = !canManageSettings;
  }
  if (els.mailboxNotConfiguredUserText) {
    els.mailboxNotConfiguredUserText.hidden = canManageSettings;
  }
}

function applySession(session = {}) {
  const user = session.user || session;
  state.session = {
    email: user.email || "",
    role: user.role || "role_one",
    roleLabel: user.roleLabel || (user.role === "admin" ? "Admin" : "Rolle 1"),
    isAdmin: Boolean(user.isAdmin || user.role === "admin"),
  };
  window.currentUserEmail = state.session.email;
  applyRolePermissions();
}

function refreshIcons() {
  if (window.lucide) {
    window.lucide.createIcons();
  }
}

function showLogin() {
  state.session = {
    email: "",
    role: "role_one",
    roleLabel: "Rolle 1",
    isAdmin: false,
  };
  els.loginScreen.hidden = false;
  els.appShell.hidden = true;
}

function showApp(session) {
  if (session) {
    applySession(session);
  } else {
    applyRolePermissions();
  }
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

function setOffersGroupExpanded(expanded) {
  els.offersSubgroup.hidden = !expanded;
  els.offersGroupToggle.setAttribute("aria-expanded", String(expanded));
}

function switchView(view) {
  if (view === "site-visit-new") {
    view = "site-visit-quiz";
  }

  if (view.startsWith("settings-") && !isAdmin()) {
    showToast("Nur Admins können die Einstellungen öffnen.");
    view = "overview";
  }

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

  const isOfferView = view.startsWith("offers-");
  els.offersGroupToggle.classList.toggle("active", isOfferView);
  if (isOfferView) {
    setOffersGroupExpanded(true);
  }

  closeMobileNav();

  if (view === "settings-smtp") {
    loadSmtpSettings();
  }

  if (view === "settings-email") {
    loadEmailSettings();
  }

  if (view === "settings-email-signature") {
    loadEmailSignature();
  }

  if (view === "settings-notify") {
    loadContractNotifySettings();
  }

  if (view === "settings-users") {
    loadUsers();
  }

  if (view === "settings-signature") {
    loadContractorSignature();
  }

  if (view === "settings-template") {
    loadContractTemplate();
  }

  if (view === "mailbox") {
    loadMailbox();
  }

  if (view === "site-visit-quiz" && typeof svqShow === "function") {
    svqShow();
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
  const prunedQuizVisits = typeof window.svqPruneContractLinkedCompletedVisits === "function"
    ? window.svqPruneContractLinkedCompletedVisits()
    : false;

  renderMetrics();
  renderCustomerOptions();
  renderOfferSiteVisitOptions();
  renderCustomers();
  renderSiteVisits();
  renderOffers();
  renderContracts();
  updateOfferPreview();
  if (prunedQuizVisits && state.currentView === "site-visit-quiz" && typeof svqShow === "function") {
    svqShow();
  }
  refreshIcons();
}

function renderMetrics() {
  const savedSiteVisits = visibleSavedSiteVisits();
  const savedOffers = visibleSavedOffers();

  els.metricCustomers.textContent = state.data.customers.length;
  els.metricSiteVisits.textContent = savedSiteVisits.length;
  els.metricOffers.textContent = savedOffers.length;
  els.metricContracts.textContent = state.data.contracts.length;
  els.metricSigned.textContent = state.data.contracts.filter((contract) => contract.status === "signiert").length;
  els.metricFollowups.textContent = state.data.contracts.filter((contract) =>
    contract.status === "daten_abgelehnt" || contract.status === "intervall_abgelehnt",
  ).length;

  const latestOffers = [...savedOffers]
    .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
    .slice(0, 4);

  els.recentOffers.innerHTML = latestOffers.length
    ? latestOffers
        .map((offer) => {
          return `
            <article class="compact-item">
              <div>
                <strong>${escapeHtml(offer.customer.name)}</strong>
                <span>${offer.squareMeters} m² · Erstellt am ${formatDate(offer.createdAt)}</span>
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
  els.offerCustomer.disabled = true;

  if (previousValue && getCustomer(previousValue)) {
    els.offerCustomer.value = previousValue;
  }
}

function clearOfferSiteVisitFields() {
  els.offerCustomer.value = "";
  els.offerSquareMeters.value = "";
  els.offerManualPrice.value = "";
  els.offerNotes.value = "";
  updateOfferPreview();
}

function siteVisitCompanyName(visit) {
  return String(visit.companyName || visit.customerName || "").trim();
}

function renderOfferSiteVisitOptions() {
  const openVisits = getOpenSiteVisits();
  const selectedVisitId = state.pendingOfferSiteVisitId;
  const selectedVisitIsOpen = selectedVisitId && openVisits.some((visit) => visit.id === selectedVisitId);
  const submitButton = els.offerForm.querySelector('button[type="submit"]');

  els.offerSiteVisit.innerHTML = openVisits.length
    ? `<option value="">Offene Begehung auswählen</option>` + openVisits
        .map((visit) => {
          const label = `${siteVisitCompanyName(visit)} · ${formatDate(visit.createdAt)} · ${visit.squareMeters} m²`;
          return `<option value="${escapeHtml(visit.id)}">${escapeHtml(label)}</option>`;
        })
        .join("")
    : `<option value="">Keine offenen Begehungen verfügbar</option>`;
  els.offerSiteVisit.disabled = openVisits.length === 0;

  if (selectedVisitIsOpen) {
    els.offerSiteVisit.value = selectedVisitId;
  } else {
    state.pendingOfferSiteVisitId = null;
    els.offerSiteVisit.value = "";
    clearOfferSiteVisitFields();
  }

  submitButton.disabled = !state.pendingOfferSiteVisitId;
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

  const customersWithContract = customers.filter((customer) => getLatestContractForCustomer(customer.id));
  const newCustomers = customers.filter((customer) => !getLatestContractForCustomer(customer.id));

  els.customerList.innerHTML = customers.length
    ? `
        ${renderCustomerSection("Kunden, die neu erstellt worden sind", newCustomers, "Keine neuen Kunden gefunden.")}
        ${renderCustomerSection("Kunden, die einen Vertrag haben", customersWithContract, "Keine Kunden mit Vertrag gefunden.")}
      `
    : `<div class="empty-state">Keine Kunden gefunden.</div>`;
}

function renderCustomerSection(title, customers, emptyText) {
  return `
    <section class="customer-list-section" aria-label="${escapeHtml(title)}">
      <div class="customer-list-section-header">
        <h5>${escapeHtml(title)}</h5>
        <span class="badge">${customers.length}</span>
      </div>
      <div class="record-list">
        ${customers.length ? customers.map(renderCustomerCard).join("") : `<div class="empty-state">${escapeHtml(emptyText)}</div>`}
      </div>
    </section>
  `;
}

function renderCustomerCard(customer) {
  const contract = getLatestContractForCustomer(customer.id);
  const contractBadge = contract ? `<span class="badge success">Vertrag vorhanden</span>` : "";
  const contractButton = contract
    ? `
      <a class="secondary-button" href="contract.php?contractId=${encodeURIComponent(contract.id)}&document=cleanteam&format=pdf" target="_blank" rel="noopener">
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

const CLEANING_FREQUENCIES = [
  "Täglich",
  "Alle 2 Tage",
  "Wöchentlich",
  "14-täglich",
  "30-täglich",
  "Individuell",
];

const CLEANING_TASKS = [
  { key: "washbasin", label: "Waschbecken" },
  { key: "toilet", label: "WC" },
  { key: "mirror", label: "Spiegel" },
  { key: "floor", label: "Boden" },
  { key: "door", label: "Tür" },
  { key: "desk", label: "Schreibtische" },
  { key: "chairs", label: "Stühle" },
  { key: "tables", label: "Tische" },
  { key: "window", label: "Fensterbänke" },
  { key: "surface", label: "Oberflächen" },
  { key: "trash", label: "Mülleimer-Entleerung" },
  { key: "kitchen", label: "Küchenflächen" },
  { key: "handrail", label: "Handlauf / Geländer" },
  { key: "counter", label: "Tresen" },
  { key: "cabinets", label: "Schränke" },
  { key: "stairFloor", label: "Etage" },
  { key: "stairDoor", label: "Türen" },
  { key: "treatmentDesk", label: "Schreibtisch" },
  { key: "treatmentChair", label: "Behandlungsstühle" },
  { key: "treatmentTable", label: "Behandlungstisch" },
  { key: "disinfection", label: "Desinfektion" },
];

const SANITARY_CLEANING_TASK_KEYS = ["floor", "toilet", "washbasin", "mirror", "door"];
const OFFICE_CLEANING_TASK_KEYS = ["floor", "desk", "chairs", "window", "trash"];
const STAIRCASE_CLEANING_TASK_KEYS = ["floor", "handrail", "stairFloor", "stairDoor"];
const GENERAL_CLEANING_TASK_KEYS = [
  "washbasin",
  "toilet",
  "mirror",
  "floor",
  "door",
  "desk",
  "chairs",
  "tables",
  "window",
  "surface",
  "trash",
  "kitchen",
  "handrail",
  "counter",
  "cabinets",
];
const TREATMENT_ROOM_CLEANING_TASK_KEYS = [
  "floor",
  "window",
  "treatmentDesk",
  "trash",
  "treatmentChair",
  "treatmentTable",
  "disinfection",
];
const FLOOR_CLEANING_METHODS = ["Gesaugt", "Gewischt", "Gesaugt und gewischt"];
const TRASH_BAG_MODES = ["Mit Mülltüte", "Ohne Mülltüte"];

function cleaningTaskLabel(key) {
  return CLEANING_TASKS.find((task) => task.key === key)?.label || key;
}

function cleaningTasksFromKeys(keys) {
  return keys
    .map((key) => CLEANING_TASKS.find((task) => task.key === key))
    .filter(Boolean);
}

function cleaningTasksForRoomType(roomType) {
  if (roomType === "Sanitär") {
    return cleaningTasksFromKeys(SANITARY_CLEANING_TASK_KEYS);
  }
  if (roomType === "Büro") {
    return cleaningTasksFromKeys(OFFICE_CLEANING_TASK_KEYS);
  }
  if (roomType === "Behandlungsräume") {
    return cleaningTasksFromKeys(TREATMENT_ROOM_CLEANING_TASK_KEYS);
  }
  if (roomType === "Treppenhaus") {
    return cleaningTasksFromKeys(STAIRCASE_CLEANING_TASK_KEYS);
  }

  return cleaningTasksFromKeys(GENERAL_CLEANING_TASK_KEYS);
}

function normalizeCleaningFrequency(value) {
  return CLEANING_FREQUENCIES.includes(value) ? value : "Täglich";
}

function normalizeFloorCleaningMethod(value) {
  if (value === "Nur gesaugt") {
    return "Gesaugt";
  }
  if (value === "Nur gewischt") {
    return "Gewischt";
  }
  return FLOOR_CLEANING_METHODS.includes(value) ? value : "Gesaugt und gewischt";
}

function normalizeTrashBagMode(value) {
  return TRASH_BAG_MODES.includes(value) ? value : "Mit Mülltüte";
}

function normalizeCleaningItem(item = {}) {
  const key = item.key || item.type || "";
  const rawMethod = item.method || item.cleaningMethod || "";
  return {
    key,
    label: cleaningTaskLabel(key),
    frequency: normalizeCleaningFrequency(item.frequency),
    customFrequency: item.customFrequency || "",
    method: key === "floor" && rawMethod ? normalizeFloorCleaningMethod(rawMethod) : "",
    bagMode: key === "trash" ? normalizeTrashBagMode(item.bagMode || item.trashBagMode) : "",
    quantity: Number(item.quantity) || 0,
  };
}

function legacyCleaningItemsFromRoom(room = {}) {
  const items = [];
  if (Number(room.sinks) > 0) {
    items.push({ key: "washbasin", label: "Waschbecken", frequency: "Täglich" });
  }
  if (Number(room.toilets) > 0) {
    items.push({ key: "toilet", label: "WC", frequency: "Täglich" });
  }
  if (Number(room.mirrors) > 0) {
    items.push({ key: "mirror", label: "Spiegel", frequency: "Täglich" });
  }
  if (Number(room.desks) > 0) {
    items.push({ key: "desk", label: "Schreibtische", frequency: "Wöchentlich" });
  }
  if (Number(room.windows) > 0) {
    items.push({ key: "window", label: "Fensterbänke", frequency: "30-täglich" });
  }
  if (room.cleaningType) {
    items.push({ key: "floor", label: "Boden", frequency: "Täglich" });
  }

  return items.map(normalizeCleaningItem);
}

function cleaningItemsForDisplay(room = {}) {
  if (Array.isArray(room.cleaningItems) && room.cleaningItems.length > 0) {
    return room.cleaningItems.map(normalizeCleaningItem).filter((item) => item.key);
  }

  return legacyCleaningItemsFromRoom(room);
}

function cleaningItemFrequencyText(item) {
  if (item.frequency === "Individuell") {
    return item.customFrequency || "Individuell";
  }

  return item.frequency;
}

function cleaningItemText(item, room = {}) {
  const details = [cleaningItemFrequencyText(item)];

  if (item.key === "floor") {
    if (item.method) {
      details.push(item.method);
    }
  }

  if (item.key === "trash" && item.bagMode) {
    details.push(item.bagMode);
  }

  if (item.quantity > 0) {
    details.push(`Anzahl: ${item.quantity}`);
  }

  return `${item.label}: ${details.filter(Boolean).join(", ")}`;
}

function normalizeRoom(room = {}, index = 0) {
  return {
    name: room.name || `Raum ${index + 1}`,
    roomType: room.roomType || "Büro",
    quantity: Math.max(1, Number(room.quantity) || 1),
    squareMeters: Number(room.squareMeters) || 0,
    cleaningItems: cleaningItemsForDisplay(room),
    sinks: Number(room.sinks) || 0,
    mirrors: Number(room.mirrors) || 0,
    toilets: Number(room.toilets) || 0,
    desks: Number(room.desks) || 0,
    windows: Number(room.windows) || 0,
    cleaningType: normalizeCleaningType(room.cleaningType),
    floorCondition: room.floorCondition || "",
    extraAgreements: room.extraAgreements || "",
    notes: room.notes || room.areaNotes || "",
  };
}

function legacyRoomsFromFloor(floor = {}) {
  const rooms = [];
  const areaName = floor.areaName || "";
  const areaNotes = floor.areaNotes || floor.notes || "";
  const extraAgreements = floor.extraAgreements || "";
  const cleaningType = normalizeCleaningType(floor.cleaningType);
  const floorCondition = floor.floorCondition || "Teppich";
  const sanitaryRooms = Number(floor.sanitaryRooms) || 0;
  const officeRooms = Number(floor.officeRooms) || 0;

  if (sanitaryRooms > 0) {
    rooms.push(normalizeRoom({
      name: areaName && officeRooms === 0 ? areaName : "Sanitärbereich",
      roomType: "Sanitär",
      quantity: sanitaryRooms,
      sinks: floor.sinks,
      mirrors: floor.mirrors,
      toilets: floor.toilets,
      cleaningType,
      floorCondition,
      extraAgreements: officeRooms === 0 ? extraAgreements : "",
      notes: officeRooms === 0 ? areaNotes : "",
    }));
  }

  if (officeRooms > 0) {
    rooms.push(normalizeRoom({
      name: areaName && sanitaryRooms === 0 ? areaName : "Bürobereich",
      roomType: "Büro",
      quantity: officeRooms,
      desks: floor.desks,
      windows: floor.windows,
      cleaningType,
      floorCondition,
      extraAgreements,
      notes: areaNotes,
    }));
  }

  if (rooms.length === 0 && (areaName || areaNotes || extraAgreements)) {
    rooms.push(normalizeRoom({
      name: areaName || "Bereich",
      roomType: "Sonstiger Raum",
      cleaningType,
      floorCondition,
      extraAgreements,
      notes: areaNotes,
    }));
  }

  return rooms;
}

function floorRoomsForDisplay(floor = {}) {
  if (Array.isArray(floor.rooms) && floor.rooms.length > 0) {
    return floor.rooms.map(normalizeRoom);
  }

  return legacyRoomsFromFloor(floor);
}

function formatRoomQuantity(room) {
  return Number(room.quantity) > 1 ? `${Number(room.quantity)}x ` : "";
}

function roomDetailParts(room) {
  return [
    Number(room.squareMeters) > 0 ? `${Number(room.squareMeters)} m²` : "",
    room.floorCondition ? `Bodenart: ${room.floorCondition}` : "",
  ].filter(Boolean);
}

function cleaningItemsText(room) {
  return cleaningItemsForDisplay(room)
    .map((item) => cleaningItemText(item, room))
    .join(" · ");
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

  els.siteVisitFloors.innerHTML = `<div class="empty-state floor-empty-state">Noch keine Etage hinzugefügt.</div>`;
}

function renumberSiteVisitFloors() {
  els.siteVisitFloors.querySelectorAll(".floor-section").forEach((section, index) => {
    const name = section.querySelector('[name="floorName"]').value.trim();
    section.querySelector("legend").textContent = name || `Etage ${index + 1}`;
    renumberSiteVisitRooms(section);
  });
}

function renumberSiteVisitRooms(floorSection) {
  floorSection.querySelectorAll(".room-section").forEach((section, index) => {
    const name = section.querySelector('[name="roomName"]')?.value.trim();
    const title = name || "Neuer Raum";
    section.querySelector("legend").textContent = title;
    const roomTitle = section.querySelector(".room-section-title");
    if (roomTitle) {
      roomTitle.textContent = title;
    }
  });
}

function setRoomSectionCollapsed(roomSection, collapsed) {
  const body = roomSection.querySelector(".room-section-body");
  const button = roomSection.querySelector('[data-action="toggle-site-visit-room"]');
  roomSection.classList.toggle("is-collapsed", collapsed);
  if (body) {
    body.hidden = collapsed;
  }
  if (button) {
    button.setAttribute("aria-expanded", String(!collapsed));
    button.setAttribute("aria-label", collapsed ? "Raum aufklappen" : "Raum zuklappen");
    const label = button.querySelector("span");
    if (label) {
      label.textContent = collapsed ? "Aufklappen" : "Zuklappen";
    }
  }
}

function saveSiteVisitRoom(roomSection) {
  const roomName = roomSection.querySelector('[name="roomName"]');
  if (!roomName.value.trim()) {
    showToast("Bitte zuerst einen Raumnamen eintragen.");
    roomName.focus();
    return;
  }

  const floorSection = roomSection.closest(".floor-section");
  if (floorSection) {
    renumberSiteVisitRooms(floorSection);
  }
  syncCleaningTaskSections(roomSection);
  setRoomSectionCollapsed(roomSection, true);
  showToast("Raum wurde gespeichert.");
}

function ensureFloorRoomEmptyState(floorSection) {
  const roomList = floorSection.querySelector(".room-list");
  if (!roomList || roomList.querySelector(".room-section")) {
    return;
  }

  roomList.innerHTML = `<div class="empty-state room-empty-state">Noch kein Raum hinzugefügt.</div>`;
}

function addSiteVisitRoomFromToolbar(floorSection) {
  const roomNameInput = floorSection.querySelector('[name="newRoomName"]');
  const roomName = roomNameInput?.value.trim() || "";

  if (!roomName) {
    showToast("Bitte zuerst den Raumnamen eintragen.");
    roomNameInput?.focus();
    return;
  }

  floorSection.querySelectorAll(".room-section").forEach((roomSection) => {
    setRoomSectionCollapsed(roomSection, true);
  });

  const roomSection = addSiteVisitRoom(floorSection, { name: roomName }, { focus: false });
  roomNameInput.value = "";
  roomSection.querySelector('[name="roomType"]')?.focus();
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
    renumberSiteVisitRooms(floorSection);
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

function addSiteVisitFloor(values = {}, options = {}) {
  const emptyState = els.siteVisitFloors.querySelector(".floor-empty-state");
  if (emptyState) {
    emptyState.remove();
  }

  const floor = {
    name: values.name || "",
    rooms: floorRoomsForDisplay(values),
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
      <div class="room-builder span-2">
        <div class="floor-section-toolbar room-add-toolbar">
          <strong>Räume</strong>
          <div class="room-add-controls">
            <label class="room-add-name">
              Neuer Raum
              <input name="newRoomName" type="text" placeholder="z. B. Empfang, WC Damen, Konferenzraum" />
            </label>
            <button class="secondary-button" type="button" data-action="add-site-visit-room">
              <i data-lucide="plus" aria-hidden="true"></i>
              Raum hinzufügen
            </button>
          </div>
        </div>
        <div class="room-list"></div>
      </div>
    </div>
  `;

  els.siteVisitFloors.appendChild(section);
  if (floor.rooms.length) {
    floor.rooms.forEach((room) => addSiteVisitRoom(section, room, { focus: false }));
  } else {
    ensureFloorRoomEmptyState(section);
  }
  renumberSiteVisitFloors();
  refreshIcons();
  if (options.focus !== false) {
    section.querySelector('[name="floorName"]').focus();
  }
}

function cleaningFrequencyOptions(selectedFrequency) {
  return CLEANING_FREQUENCIES
    .map((frequency) => `<option value="${escapeHtml(frequency)}"${optionSelected(frequency, selectedFrequency)}>${escapeHtml(frequency)}</option>`)
    .join("");
}

function floorCleaningMethodOptions(selectedMethod) {
  return FLOOR_CLEANING_METHODS
    .map((method) => `<option value="${escapeHtml(method)}"${optionSelected(method, selectedMethod)}>${escapeHtml(method)}</option>`)
    .join("");
}

function trashBagModeOptions(selectedMode) {
  return TRASH_BAG_MODES
    .map((mode) => `<option value="${escapeHtml(mode)}"${optionSelected(mode, selectedMode)}>${escapeHtml(mode)}</option>`)
    .join("");
}

function cleaningTaskMarkup(task, items) {
  const item = items.find((entry) => entry.key === task.key);
  const checked = item ? " checked" : "";
  const frequency = item?.frequency || "Täglich";
  const customFrequency = item?.customFrequency || "";
  const method = item?.method || "Gesaugt und gewischt";
  const quantity = Number(item?.quantity) || 0;
  const bagMode = item?.bagMode || "Mit Mülltüte";
  const floorMethod = task.key === "floor"
    ? `
        <label data-cleaning-method="${escapeHtml(task.key)}" hidden>
          Reinigungsart
          <select name="cleaningMethod" data-cleaning-key="${escapeHtml(task.key)}">
            ${floorCleaningMethodOptions(method)}
          </select>
        </label>
      `
    : "";
  const trashBag = task.key === "trash"
    ? `
        <label data-cleaning-bag="${escapeHtml(task.key)}" hidden>
          Mülltüte
          <select name="cleaningTrashBagMode" data-cleaning-key="${escapeHtml(task.key)}">
            ${trashBagModeOptions(bagMode)}
          </select>
        </label>
      `
    : "";

  const quantityField = task.key === "floor"
    ? ""
    : `
        <label data-cleaning-quantity="${escapeHtml(task.key)}" hidden>
          Anzahl
          <input name="cleaningQuantity" type="number" min="0" inputmode="numeric" data-cleaning-key="${escapeHtml(task.key)}" value="${escapeHtml(quantity)}" />
        </label>
      `;

  return `
    <div class="cleaning-task-row" data-cleaning-task="${escapeHtml(task.key)}">
      <label class="checkbox-field">
        <input name="cleaningItem" type="checkbox" value="${escapeHtml(task.key)}" data-cleaning-key="${escapeHtml(task.key)}"${checked} />
        <span class="cleaning-task-title">${escapeHtml(task.label)}</span>
      </label>
      <div class="cleaning-frequency" data-cleaning-frequency="${escapeHtml(task.key)}" hidden>
        <label>
          Intervall
          <select name="cleaningFrequency" data-cleaning-key="${escapeHtml(task.key)}">
            ${cleaningFrequencyOptions(frequency)}
          </select>
        </label>
        <label data-cleaning-custom="${escapeHtml(task.key)}" hidden>
          Individueller Rhythmus
          <input name="cleaningCustomFrequency" type="text" data-cleaning-key="${escapeHtml(task.key)}" placeholder="z. B. 2x pro Woche" value="${escapeHtml(customFrequency)}" />
        </label>
        ${floorMethod}
        ${trashBag}
        ${quantityField}
      </div>
    </div>
  `;
}

function syncCleaningTaskSections(roomSection) {
  const roomType = roomSection.querySelector('[name="roomType"]')?.value || "";
  const visibleTasks = cleaningTasksForRoomType(roomType);
  const visibleKeys = new Set(visibleTasks.map((task) => task.key));
  const visibleOrder = new Map(visibleTasks.map((task, index) => [task.key, index + 1]));

  roomSection.querySelectorAll('[name="cleaningItem"]').forEach((checkbox) => {
    const key = checkbox.dataset.cleaningKey;
    const row = checkbox.closest(".cleaning-task-row");
    const frequencySection = roomSection.querySelector(`[data-cleaning-frequency="${key}"]`);
    const frequencySelect = roomSection.querySelector(`[name="cleaningFrequency"][data-cleaning-key="${key}"]`);
    const customField = roomSection.querySelector(`[data-cleaning-custom="${key}"]`);
    const methodField = roomSection.querySelector(`[data-cleaning-method="${key}"]`);
    const trashBagField = roomSection.querySelector(`[data-cleaning-bag="${key}"]`);
    const quantityField = roomSection.querySelector(`[data-cleaning-quantity="${key}"]`);
    const visible = visibleKeys.has(key);

    if (row) {
      row.hidden = !visible;
      row.style.order = visible ? String(visibleOrder.get(key) || 1) : "";
      row.classList.toggle("is-selected", visible && checkbox.checked);
    }
    if (!visible) {
      checkbox.checked = false;
    }

    if (frequencySection) {
      frequencySection.hidden = !visible || !checkbox.checked;
    }
    if (customField && frequencySelect) {
      customField.hidden = !visible || !checkbox.checked || frequencySelect.value !== "Individuell";
    }
    if (methodField) {
      methodField.hidden = !visible || !checkbox.checked || key !== "floor";
    }
    if (trashBagField) {
      trashBagField.hidden = !visible || !checkbox.checked;
    }
    if (quantityField) {
      quantityField.hidden = !visible || !checkbox.checked;
    }
  });
}

function addSiteVisitRoom(floorSection, values = {}, options = {}) {
  const roomList = floorSection.querySelector(".room-list");
  const emptyState = roomList.querySelector(".room-empty-state");
  if (emptyState) {
    emptyState.remove();
  }

  const room = {
    name: values.name || "",
    roomType: values.roomType || "Büro",
    squareMeters: Number(values.squareMeters) || 0,
    cleaningItems: cleaningItemsForDisplay(values),
    sinks: Number(values.sinks) || 0,
    mirrors: Number(values.mirrors) || 0,
    toilets: Number(values.toilets) || 0,
    desks: Number(values.desks) || 0,
    windows: Number(values.windows) || 0,
    cleaningType: normalizeCleaningType(values.cleaningType),
    floorCondition: values.floorCondition || "Teppich",
    extraAgreements: values.extraAgreements || "",
    notes: values.notes || values.areaNotes || "",
  };

  const section = document.createElement("fieldset");
  section.className = "room-section";
  section.innerHTML = `
    <legend>Raum</legend>
    <div class="room-section-header">
      <button class="ghost-button room-toggle-button" type="button" data-action="toggle-site-visit-room" aria-expanded="true">
        <i data-lucide="chevron-up" aria-hidden="true"></i>
        <span>Zuklappen</span>
      </button>
      <strong class="room-section-title">Raum</strong>
      <button class="ghost-button" type="button" data-action="remove-site-visit-room">
        <i data-lucide="x" aria-hidden="true"></i>
        Raum entfernen
      </button>
    </div>
    <div class="form-grid room-section-body">
      <label>
        Raumname
        <input name="roomName" type="text" placeholder="z. B. Empfang, WC Damen, Konferenzraum" value="${escapeHtml(room.name)}" />
      </label>
      <label>
        Raumart
        <select name="roomType">
          <option value="Büro"${optionSelected("Büro", room.roomType)}>Büro</option>
          <option value="Behandlungsräume"${optionSelected("Behandlungsräume", room.roomType)}>Behandlungsräume</option>
          <option value="Sanitär"${optionSelected("Sanitär", room.roomType)}>Sanitär</option>
          <option value="Küche"${optionSelected("Küche", room.roomType)}>Küche</option>
          <option value="Flur"${optionSelected("Flur", room.roomType)}>Flur</option>
          <option value="Treppenhaus"${optionSelected("Treppenhaus", room.roomType)}>Treppenhaus</option>
          <option value="Empfang"${optionSelected("Empfang", room.roomType)}>Empfang</option>
          <option value="Lager"${optionSelected("Lager", room.roomType)}>Lager</option>
          <option value="Sonstiger Raum"${optionSelected("Sonstiger Raum", room.roomType)}>Sonstiger Raum</option>
        </select>
      </label>
      <div class="cleaning-task-list span-2">
        ${CLEANING_TASKS.map((task) => cleaningTaskMarkup(task, room.cleaningItems)).join("")}
      </div>
      <label class="span-2">
        Extra Vereinbarungen
        <textarea name="roomExtraAgreements" rows="2" placeholder="Besondere Absprachen für diesen Raum">${escapeHtml(room.extraAgreements)}</textarea>
      </label>
      <label class="span-2">
        Notiz zum Raum
        <textarea name="roomNotes" rows="2" placeholder="Notizen zu diesem Raum">${escapeHtml(room.notes)}</textarea>
      </label>
      <div class="room-section-actions span-2">
        <button class="primary-button" type="button" data-action="save-site-visit-room">
          <i data-lucide="save" aria-hidden="true"></i>
          Speichern
        </button>
      </div>
    </div>
  `;

  roomList.appendChild(section);
  syncCleaningTaskSections(section);
  renumberSiteVisitRooms(floorSection);
  setRoomSectionCollapsed(section, Boolean(options.collapsed));
  refreshIcons();
  if (options.focus !== false) {
    section.querySelector('[name="roomName"]').focus();
  }

  return section;
}

function collectSiteVisitFloors() {
  return [...els.siteVisitFloors.querySelectorAll(".floor-section")].map((section, index) => {
    const field = (name) => section.querySelector(`[name="${name}"]`);
    const rooms = [...section.querySelectorAll(".room-section")].map((roomSection, roomIndex) => {
      const roomField = (name) => roomSection.querySelector(`[name="${name}"]`);
      const roomType = roomField("roomType").value;
      const cleaningItems = [...roomSection.querySelectorAll('[name="cleaningItem"]:checked')]
        .filter((checkbox) => !checkbox.closest(".cleaning-task-row")?.hidden)
        .map((checkbox) => {
          const key = checkbox.value;
          const frequency = roomSection.querySelector(`[name="cleaningFrequency"][data-cleaning-key="${key}"]`)?.value || "Täglich";
          const customFrequency = roomSection.querySelector(`[name="cleaningCustomFrequency"][data-cleaning-key="${key}"]`)?.value.trim() || "";
          return {
            key,
            label: cleaningTaskLabel(key),
            frequency,
            customFrequency: frequency === "Individuell" ? customFrequency : "",
            method: key === "floor"
              ? roomSection.querySelector(`[name="cleaningMethod"][data-cleaning-key="${key}"]`)?.value || "Gesaugt und gewischt"
              : "",
            bagMode: key === "trash"
              ? roomSection.querySelector(`[name="cleaningTrashBagMode"][data-cleaning-key="${key}"]`)?.value || "Mit Mülltüte"
              : "",
            quantity: Number(roomSection.querySelector(`[name="cleaningQuantity"][data-cleaning-key="${key}"]`)?.value) || 0,
          };
        });
      return {
        name: roomField("roomName").value.trim() || `Raum ${roomIndex + 1}`,
        roomType,
        quantity: 1,
        squareMeters: 0,
        cleaningItems,
        sinks: 0,
        mirrors: 0,
        toilets: 0,
        desks: 0,
        windows: 0,
        cleaningType: cleaningItems.some((item) => item.key === "floor") ? "Nach Rhythmus" : "",
        floorCondition: "",
        extraAgreements: roomField("roomExtraAgreements").value.trim(),
        notes: roomField("roomNotes").value.trim(),
      };
    });
    const sanitaryRooms = rooms.filter((room) => room.roomType === "Sanitär").length;
    const officeRooms = rooms.filter((room) => room.roomType === "Büro").length;
    return {
      name: field("floorName").value.trim() || `Etage ${index + 1}`,
      rooms,
      sanitaryRooms,
      sinks: 0,
      mirrors: 0,
      toilets: 0,
      officeRooms,
      desks: 0,
      windows: 0,
      cleaningType: rooms[0]?.cleaningType || "Gesaugt und gewischt",
      floorCondition: rooms[0]?.floorCondition || "Teppich",
      areaName: rooms[0]?.name || "",
      extraAgreements: rooms.map((room) => room.extraAgreements).filter(Boolean).join("\n"),
      areaNotes: rooms.map((room) => room.notes).filter(Boolean).join("\n"),
      notes: rooms.map((room) => room.notes).filter(Boolean).join("\n"),
    };
  });
}

function resetSiteVisitForm() {
  if (!els.siteVisitForm || !els.siteVisitFloors) {
    return;
  }

  els.siteVisitForm.reset();
  updateCounterControl(document.querySelector("#site-visit-square-meters"), 0);
  els.siteVisitFloors.innerHTML = `<div class="empty-state floor-empty-state">Noch keine Etage hinzugefügt.</div>`;
  refreshIcons();
}

function closeSiteVisitEditor() {
  state.editingSiteVisitId = null;
  if (els.siteVisitEditorPanel) {
    els.siteVisitEditorPanel.hidden = true;
  }
  resetSiteVisitForm();
}

function openSiteVisitEditor(id) {
  const visit = getSiteVisit(id);
  if (!visit || !els.siteVisitForm || !els.siteVisitFloors || !els.siteVisitEditorPanel) {
    showToast("Begehung konnte nicht zum Bearbeiten geoeffnet werden.");
    return;
  }

  state.editingSiteVisitId = id;
  els.siteVisitEditorPanel.hidden = false;
  if (els.siteVisitEditorHeading) {
    els.siteVisitEditorHeading.textContent = `Begehung bearbeiten: ${siteVisitCompanyName(visit)}`;
  }
  if (els.siteVisitEditorMeta) {
    const floors = Array.isArray(visit.floors) ? visit.floors : [];
    const roomCount = floors.reduce((sum, floor) => sum + floorRoomsForDisplay(floor).length, 0);
    els.siteVisitEditorMeta.textContent = [
      visit.createdAt ? `Erfasst am ${formatDate(visit.createdAt)}` : "",
      `${Number(visit.squareMeters) || 0} m²`,
      `${floors.length} Etage${floors.length === 1 ? "" : "n"}`,
      `${roomCount} Raum${roomCount === 1 ? "" : "e"}`,
    ].filter(Boolean).join(" · ");
  }

  document.querySelector("#site-visit-customer-name").value = siteVisitCompanyName(visit);
  document.querySelector("#site-visit-email").value = visit.email || "";
  document.querySelector("#site-visit-phone").value = visit.phone || "";
  document.querySelector("#site-visit-address").value = visit.address || "";
  document.querySelector("#site-visit-onsite-contact").value = visit.onsiteContact || "";
  document.querySelector("#site-visit-square-meters").value = Number(visit.squareMeters) || 0;
  document.querySelector("#site-visit-notes").value = visit.notes || "";

  els.siteVisitFloors.innerHTML = "";
  const floors = Array.isArray(visit.floors) ? visit.floors : [];
  if (floors.length) {
    floors.forEach((floor) => addSiteVisitFloor(floor, { focus: false }));
  } else {
    ensureSiteVisitFloorEmptyState();
  }
  renumberSiteVisitFloors();
  refreshIcons();
  els.siteVisitEditorPanel.scrollIntoView({ behavior: "smooth", block: "start" });
}

function renderSiteVisits() {
  const visits = [...visibleSavedSiteVisits()].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));

  els.siteVisitList.innerHTML = visits.length
    ? visits.map(renderSiteVisitCard).join("")
    : `<div class="empty-state">Noch keine Begehung gespeichert.</div>`;
}

function renderSiteVisitCard(visit) {
  const floors = Array.isArray(visit.floors) ? visit.floors : [];
  const companyName = siteVisitCompanyName(visit);
  const roomTotal = floors.reduce((sum, floor) => {
    return sum + floorRoomsForDisplay(floor).reduce((roomSum, room) => roomSum + (Number(room.quantity) || 1), 0);
  }, 0);
  const floorLabel = floors.length === 1 ? "1 Etage" : `${floors.length} Etagen`;
  const roomLabel = roomTotal === 1 ? "1 Raum" : `${roomTotal} Räume`;
  const notes = visit.notes
    ? `<div class="record-lines"><span>${escapeHtml(visit.notes)}</span></div>`
    : "";

  return `
    <article class="record-item">
      <div class="record-main">
        <div>
          <div class="record-title">${escapeHtml(companyName)}</div>
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
        <span>${escapeHtml(roomLabel)}</span>
      </div>
      ${notes}
      <details class="visit-details">
        <summary>Etagen anzeigen</summary>
        <div class="floor-summary-list">
          ${floors.map(renderSiteVisitFloorSummary).join("")}
        </div>
      </details>
      <div class="record-actions">
        <button class="secondary-button" type="button" data-action="edit-site-visit" data-id="${escapeHtml(visit.id)}">
          <i data-lucide="pencil" aria-hidden="true"></i>
          Bearbeiten
        </button>
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
  const rooms = floorRoomsForDisplay(floor);
  const roomRows = rooms.length
    ? rooms.map(renderSiteVisitRoomSummary).join("")
    : `<div class="record-lines"><span>Keine Räume hinterlegt.</span></div>`;

  return `
    <article class="floor-summary-item">
      <strong>${escapeHtml(title)}</strong>
      <div class="room-summary-list">${roomRows}</div>
    </article>
  `;
}

function renderSiteVisitRoomSummary(room) {
  const details = roomDetailParts(room).join(" · ");
  const detailLine = details ? `<span>${escapeHtml(details)}</span>` : "";
  const cleaning = cleaningItemsText(room);
  const cleaningLine = cleaning ? `<span>Reinigung: ${escapeHtml(cleaning)}</span>` : "";
  const extraAgreements = room.extraAgreements
    ? `<span>Extra Vereinbarungen: ${escapeHtml(room.extraAgreements)}</span>`
    : "";
  const notes = room.notes ? `<span>Notiz: ${escapeHtml(room.notes)}</span>` : "";

  return `
    <article class="room-summary-item">
      <strong>${escapeHtml(formatRoomQuantity(room) + room.name)}</strong>
      <div class="record-lines">
        <span>${escapeHtml(room.roomType)}</span>
        ${detailLine}
        ${cleaningLine}
        ${extraAgreements}
        ${notes}
      </div>
    </article>
  `;
}

function siteVisitNotesForOffer(visit) {
  const notes = String(visit?.notes || "").trim();
  return notes === "Über das Begehungs-Quiz erfasst." ? "" : notes;
}

function siteVisitOfferNotes(visit) {
  const lines = [
    "Leistungsbeschreibung / Dienstleistung",
    "",
    `Firma: ${siteVisitCompanyName(visit)}`,
    `Ansprechpartner vor Ort: ${visit.onsiteContact || "nicht angegeben"}`,
    `Adresse: ${visit.address || "nicht angegeben"}`,
    `Objektgröße: ${visit.squareMeters || 0} m²`,
  ];

  if (visit.createdAt) {
    lines.push(`Begehung erfasst am: ${formatDate(visit.createdAt)}`);
  }

  const floors = Array.isArray(visit.floors) ? visit.floors : [];
  if (floors.length > 0) {
    lines.push("", "Etagen und Räume:");
    floors.forEach((floor, floorIndex) => {
      const floorName = floor.name || `Etage ${floorIndex + 1}`;
      const rooms = floorRoomsForDisplay(floor);
      lines.push("", `${floorIndex + 1}. ${floorName}`);

      if (rooms.length === 0) {
        lines.push("- Keine Räume hinterlegt.");
        return;
      }

      rooms.forEach((room) => {
        const roomTitle = `${formatRoomQuantity(room)}${room.name}`;
        const roomDetails = [
          room.roomType,
          ...roomDetailParts(room),
        ].filter(Boolean);
        lines.push(`- ${roomTitle}${roomDetails.length ? ` (${roomDetails.join(", ")})` : ""}`);

        const cleaningItems = cleaningItemsForDisplay(room);
        if (cleaningItems.length > 0) {
          cleaningItems.forEach((item) => {
            lines.push(`  • ${cleaningItemText(item, room)}`);
          });
        } else {
          lines.push("  • Keine Reinigungsposition hinterlegt.");
        }

        if (room.extraAgreements) {
          lines.push(`  Extra Vereinbarungen: ${room.extraAgreements}`);
        }
        if (room.notes) {
          lines.push(`  Notiz: ${room.notes}`);
        }
      });
    });
  }

  const visitNotes = siteVisitNotesForOffer(visit);
  if (visitNotes) {
    lines.push("", "Allgemeine Notizen:", visitNotes);
  }

  return lines.join("\n");
}

function completeSiteVisitSummaryStats(visit) {
  const floors = Array.isArray(visit.floors) ? visit.floors : [];
  const rooms = floors.flatMap((floor) => floorRoomsForDisplay(floor));
  const cleaningItemCount = rooms.reduce((sum, room) => sum + cleaningItemsForDisplay(room).length, 0);
  return { floors, rooms, cleaningItemCount };
}

function completeOfferNotesValue(value, fallback = "nicht angegeben") {
  const text = String(value == null ? "" : value).trim();
  return text || fallback;
}

function completeOfferNotesMultiline(label, value) {
  const text = String(value || "").trim();
  if (!text) return [];
  return [`  ${label}:`, ...text.split(/\r?\n/).filter(Boolean).map((line) => `    ${line}`)];
}

function completeSiteVisitOfferNotes(visit) {
  const { floors, rooms, cleaningItemCount } = completeSiteVisitSummaryStats(visit);
  const lines = [
    "Leistungsbeschreibung / Dienstleistung",
    "",
    "Begehungsergebnis",
    "",
    "Objektdaten",
    `- Firma / Kunde: ${siteVisitCompanyName(visit)}`,
    `- Ansprechpartner vor Ort: ${completeOfferNotesValue(visit.onsiteContact)}`,
    `- E-Mail: ${completeOfferNotesValue(visit.email)}`,
    `- Telefon: ${completeOfferNotesValue(visit.phone)}`,
    `- Objektadresse: ${completeOfferNotesValue(visit.address)}`,
    `- Objektgröße: ${Number(visit.squareMeters) || 0} m²`,
  ];

  if (visit.createdAt) {
    lines.push(`- Begehung erfasst am: ${formatDate(visit.createdAt)}`);
  }

  lines.push(
    "",
    "Zusammenfassung",
    `- Etagen/Bereiche: ${floors.length}`,
    `- Räume: ${rooms.length}`,
    `- Reinigungspositionen: ${cleaningItemCount}`,
  );

  if (floors.length > 0) {
    lines.push("", "Etagen und Räume");
    floors.forEach((floor, floorIndex) => {
      const floorName = floor.name || `Etage ${floorIndex + 1}`;
      const floorRooms = floorRoomsForDisplay(floor);
      lines.push("", `${floorIndex + 1}. ${floorName}`);

      if (floorRooms.length === 0) {
        lines.push("  - Keine Räume hinterlegt.");
        return;
      }

      floorRooms.forEach((room, roomIndex) => {
        const roomTitle = `${formatRoomQuantity(room)}${room.name}`;
        lines.push(`  ${floorIndex + 1}.${roomIndex + 1} ${roomTitle}`);
        lines.push(`  Raumart: ${completeOfferNotesValue(room.roomType)}`);
        roomDetailParts(room).forEach((detail) => lines.push(`  ${detail}`));

        const cleaningItems = cleaningItemsForDisplay(room);
        if (cleaningItems.length > 0) {
          lines.push("  Leistungen:");
          cleaningItems.forEach((item) => {
            lines.push(`  - ${cleaningItemText(item, room)}`);
          });
        } else {
          lines.push("  Leistungen: Keine Reinigungsposition hinterlegt.");
        }

        lines.push(...completeOfferNotesMultiline("Extra Vereinbarungen", room.extraAgreements));
        lines.push(...completeOfferNotesMultiline("Raumnotizen", room.notes));
      });
    });
  }

  const visitNotes = siteVisitNotesForOffer(visit);
  if (visitNotes) {
    lines.push("", "Allgemeine Notizen zur Begehung", ...visitNotes.split(/\r?\n/).filter(Boolean).map((line) => `- ${line}`));
  }

  return lines.join("\n");
}

function findCustomerForSiteVisit(visit) {
  const email = String(visit.email || "").trim().toLowerCase();
  const phone = String(visit.phone || "").trim();
  const name = siteVisitCompanyName(visit).toLowerCase();

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
  document.querySelector("#customer-name").value = siteVisitCompanyName(visit);
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
      name: siteVisitCompanyName(visit),
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
    const companyName = siteVisitCompanyName(visit);
    if (companyName && existingCustomer.name !== companyName) {
      const updatedCustomer = {
        ...existingCustomer,
        name: companyName,
      };
      await apiPut(`api/customers.php?id=${encodeURIComponent(existingCustomer.id)}`, updatedCustomer);
      await loadAll();
      return { customer: getCustomer(existingCustomer.id) || updatedCustomer, created: false, updated: true };
    }

    return { customer: existingCustomer, created: false, updated: false };
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
  return { customer, created: true, updated: false };
}

async function startOfferFromSiteVisit(id) {
  const prepared = await prepareOfferFromSiteVisit(id);
  if (prepared) {
    switchView("offers-new");
    els.offerManualPrice.focus();
  }
}

async function prepareOfferFromSiteVisit(id) {
  const visit = getSiteVisit(id);
  if (!visit) {
    return false;
  }

  if (!getOpenSiteVisits().some((openVisit) => openVisit.id === id)) {
    state.pendingOfferSiteVisitId = null;
    renderOfferSiteVisitOptions();
    showToast("Diese Begehung wurde bereits abgearbeitet.");
    return false;
  }

  try {
    const { customer, created, updated } = await ensureCustomerForSiteVisit(visit);
    if (!customer) {
      return false;
    }

    state.pendingOfferSiteVisitId = visit.id;
    renderOfferSiteVisitOptions();
    els.offerCustomer.value = customer.id;
    els.offerSquareMeters.value = visit.squareMeters || "";
    els.offerManualPrice.value = "";
    els.offerNotes.value = completeSiteVisitOfferNotes(visit);
    updateOfferPreview();
    showToast(
      created
        ? "Kunde wurde aus der Begehung angelegt und in den Kostenvoranschlag übernommen."
        : updated
          ? "Firmenname wurde aus der Begehung aktualisiert und in den Kostenvoranschlag übernommen."
        : "Begehung wurde in den Kostenvoranschlag übernommen.",
    );
    return true;
  } catch (error) {
    showToast(error.message);
    return false;
  }
}

async function handleOfferSiteVisitChange() {
  const visitId = els.offerSiteVisit.value;
  if (!visitId) {
    state.pendingOfferSiteVisitId = null;
    clearOfferSiteVisitFields();
    renderOfferSiteVisitOptions();
    return;
  }

  await prepareOfferFromSiteVisit(visitId);
}

function renderOffers() {
  const offers = [...visibleSavedOffers()].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));

  els.offerList.innerHTML = offers.length
    ? offers.map(renderOfferCard).join("")
    : `<div class="empty-state">Noch keine Kostenvoranschläge erstellt.</div>`;
}

function renderOfferCard(offer) {
  const validity = offerValidity(offer);
  const sentLabel = offer.sentAt
    ? `Gesendet am ${formatDate(offer.sentAt)}`
    : "Noch nicht per E-Mail versendet";

  const contractActions = offer.contractId
    ? `
      <button class="secondary-button" type="button" data-action="open-contract" data-id="${escapeHtml(offer.contractId)}">
        <i data-lucide="signature" aria-hidden="true"></i>
        Vertrag ansehen
      </button>
      <a class="secondary-button" href="contract.php?contractId=${encodeURIComponent(offer.contractId)}&document=cleanteam&format=pdf" target="_blank" rel="noopener">
        <i data-lucide="file-text" aria-hidden="true"></i>
        Vertragsdokument
      </a>
    `
    : "";
  const contractProcessAction = offer.contractId
    ? ""
    : `
      <button class="secondary-button" type="button" data-action="open-offer-contract-link" data-id="${escapeHtml(offer.id)}">
        <i data-lucide="signature" aria-hidden="true"></i>
        Neuen Vertrag erstellen
      </button>
    `;

  return `
    <article class="record-item">
      <div class="record-main">
        <div>
          <div class="record-title">Firma: ${escapeHtml(offer.customer.name)}</div>
          <div class="record-meta">
            <span>${offer.squareMeters} m²</span>
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
        ${contractProcessAction}
        <button class="secondary-button" type="button" data-action="copy-offer-link" data-id="${escapeHtml(offer.id)}">
          <i data-lucide="link" aria-hidden="true"></i>
          Link kopieren
        </button>
        ${contractActions}
        <button class="ghost-button" type="button" data-action="delete-offer" data-id="${escapeHtml(offer.id)}">
          <i data-lucide="trash-2" aria-hidden="true"></i>
          Löschen
        </button>
      </div>
    </article>
  `;
}

function renderContracts() {
  if (state.selectedContractId && !getContract(state.selectedContractId)) {
    state.selectedContractId = null;
  }

  const contracts = filteredContracts();
  els.contractCount.textContent = `${contracts.length} von ${state.data.contracts.length} Verträgen angezeigt.`;
  els.contractSearch.value = state.contractFilters.search;
  els.contractPeriodFilter.value = state.contractFilters.period;
  els.contractSort.value = state.contractFilters.sortKey;
  els.contractSortDirection.value = state.contractFilters.sortDirection;

  els.contractList.innerHTML = contracts.length
    ? contracts.map(renderContractRow).join("")
    : `<tr><td colspan="7" class="table-empty">Keine Verträge für diese Auswahl gefunden.</td></tr>`;
}

function filteredContracts() {
  const query = state.contractFilters.search.trim().toLowerCase();

  return [...state.data.contracts]
    .filter((contract) => contractMatchesPeriod(contract))
    .filter((contract) => {
      if (!query) {
        return true;
      }

      return contractSearchText(contract).includes(query);
    })
    .sort(compareContracts);
}

function contractSearchText(contract) {
  return [
    contract.number,
    contract.customer.name,
    contactName(contract.customer),
    contract.customer.email,
    contract.customer.phone,
    contract.authorizationGrantorName,
    contract.authorizationCompanyAddress,
    contract.offer.service,
    contract.offer.interval,
    CONTRACT_STATUS_LABELS[contract.status] || contract.status,
  ]
    .join(" ")
    .toLowerCase();
}

function contractMatchesPeriod(contract) {
  const period = state.contractFilters.period;
  if (period === "all") {
    return true;
  }

  const date = new Date(contract.createdAt);
  if (Number.isNaN(date.getTime())) {
    return false;
  }

  const now = new Date();
  let start;
  let end = now;

  if (period === "quarter") {
    start = new Date(now.getFullYear(), Math.floor(now.getMonth() / 3) * 3, 1);
  } else if (period === "half-year") {
    start = new Date(now);
    start.setMonth(start.getMonth() - 6);
  } else if (period === "year") {
    start = new Date(now);
    start.setFullYear(start.getFullYear() - 1);
  } else if (period === "last-year") {
    start = new Date(now.getFullYear() - 1, 0, 1);
    end = new Date(now.getFullYear(), 0, 1);
  } else {
    return true;
  }

  return date >= start && date < end;
}

function compareContracts(a, b) {
  const direction = state.contractFilters.sortDirection === "desc" ? -1 : 1;
  const key = state.contractFilters.sortKey;
  const first = contractSortValue(a, key);
  const second = contractSortValue(b, key);

  if (typeof first === "number" || typeof second === "number") {
    return ((first || 0) - (second || 0)) * direction;
  }

  return String(first).localeCompare(String(second), "de", { sensitivity: "base" }) * direction;
}

function contractSortValue(contract, key) {
  if (key === "contactName") {
    return contactName(contract.customer);
  }
  if (key === "createdAt" || key === "signedAt") {
    const date = new Date(contract[key] || 0);
    return Number.isNaN(date.getTime()) ? 0 : date.getTime();
  }
  if (key === "number") {
    return contract.number;
  }

  return contract.customer.name;
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

function renderContractRow(contract) {
  const selected = contract.id === state.selectedContractId ? " selected" : "";
  const badgeClass = contractBadgeClass(contract.status);
  const signedAt = contract.signedAt ? formatDate(contract.signedAt) : "Noch offen";
  const siteVisitButton = contract.offer.siteVisitId
    ? `
        <a class="secondary-button" href="contract.php?contractId=${encodeURIComponent(contract.id)}&document=site_visit&format=pdf" target="_blank" rel="noopener">
          <i data-lucide="clipboard-list" aria-hidden="true"></i>
          Begehung
        </a>
      `
    : "";
  const cleaningChecklistButton = contract.offer.siteVisitId
    ? `
        <a class="secondary-button" href="contract.php?contractId=${encodeURIComponent(contract.id)}&document=checklist&format=pdf&download=1" target="_blank" rel="noopener">
          <i data-lucide="clipboard-check" aria-hidden="true"></i>
          Mitarbeiter-Checkliste
        </a>
      `
    : "";
  const authorizationButton = contract.hasAuthorizationDocument
    ? `
        <a class="secondary-button" href="contract.php?contractId=${encodeURIComponent(contract.id)}&document=authorization&format=pdf" target="_blank" rel="noopener">
          <i data-lucide="signature" aria-hidden="true"></i>
          Vollmacht
        </a>
      `
    : "";

  return `
    <tr class="${selected}">
      <td>
        <strong>${escapeHtml(contract.number)}</strong>
        <span>${contract.offer.squareMeters} m²</span>
      </td>
      <td>${escapeHtml(contract.customer.name)}</td>
      <td>${escapeHtml(contactName(contract.customer))}</td>
      <td>${escapeHtml(formatDate(contract.createdAt))}</td>
      <td>${escapeHtml(signedAt)}</td>
      <td><span class="badge ${badgeClass}">${escapeHtml(CONTRACT_STATUS_LABELS[contract.status] || contract.status)}</span></td>
      <td>
        <div class="table-actions">
          <a class="primary-button" href="contract.php?contractId=${encodeURIComponent(contract.id)}&document=cleanteam&format=pdf" target="_blank" rel="noopener">
            <i data-lucide="file-check-2" aria-hidden="true"></i>
            CleanTeam
          </a>
          <a class="secondary-button" href="contract.php?contractId=${encodeURIComponent(contract.id)}&document=customer&format=pdf" target="_blank" rel="noopener">
            <i data-lucide="file-text" aria-hidden="true"></i>
            Kunde
          </a>
          ${cleaningChecklistButton}
          ${authorizationButton}
          ${siteVisitButton}
          <button class="ghost-button" type="button" data-action="delete-contract" data-id="${escapeHtml(contract.id)}">
            <i data-lucide="trash-2" aria-hidden="true"></i>
            Löschen
          </button>
        </div>
      </td>
    </tr>
  `;
}

function updateOfferPreview() {
  const estimatedPrice = calculateOfferPrice(
    els.offerSquareMeters.value,
    els.offerInterval.value,
    els.offerService.value,
  );

  els.offerEstimatedPricePreview.textContent = formatCurrency(estimatedPrice);
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

function openCustomerCreateForQuiz() {
  state.pendingQuizCustomerReturn = true;
  resetCustomerForm();
  switchView("customer-new");
  document.querySelector("#customer-name").focus();
  showToast("Kunde anlegen. Danach geht es automatisch im Quiz weiter.");
}

window.ctOpenCustomerCreateForQuiz = openCustomerCreateForQuiz;

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
    let savedCustomer;
    if (id) {
      savedCustomer = await apiPut(`api/customers.php?id=${encodeURIComponent(id)}`, payload);
      showToast("Kunde wurde aktualisiert.");
    } else {
      savedCustomer = await apiPost("api/customers.php", payload);
      showToast("Kunde wurde angelegt.");
    }

    resetCustomerForm();
    await loadAll();
    if (state.pendingQuizCustomerReturn && savedCustomer) {
      state.pendingQuizCustomerReturn = false;
      switchView("site-visit-quiz");
      if (typeof window.svqStartFromCustomer === "function") {
        window.svqStartFromCustomer(getCustomer(savedCustomer.id) || savedCustomer);
      }
      return;
    }
    switchView("customer-list");
  } catch (error) {
    showToast(error.message);
  }
}

async function handleSiteVisitSubmit(event) {
  event.preventDefault();

  if (!els.siteVisitForm || !els.siteVisitFloors) {
    showToast("Begehungen werden nur noch über das Quiz erstellt.");
    switchView("site-visit-quiz");
    return;
  }

  if (!state.editingSiteVisitId) {
    showToast("Bitte zuerst eine gespeicherte Begehung zum Bearbeiten auswaehlen.");
    return;
  }

  const unnamedRoom = [...els.siteVisitFloors.querySelectorAll(".room-section")]
    .find((roomSection) => !roomSection.querySelector('[name="roomName"]')?.value.trim());
  if (unnamedRoom) {
    setRoomSectionCollapsed(unnamedRoom, false);
    showToast("Bitte pro Raum einen Raumnamen eintragen.");
    unnamedRoom.querySelector('[name="roomName"]').focus();
    return;
  }

  const floors = collectSiteVisitFloors();
  if (floors.length === 0) {
    showToast("Bitte zuerst eine Etage hinzufügen.");
    return;
  }
  if (floors.some((floor) => !Array.isArray(floor.rooms) || floor.rooms.length === 0)) {
    showToast("Bitte pro Etage mindestens einen Raum hinzufügen.");
    return;
  }
  if (floors.some((floor) => floor.rooms.some((room) => !Array.isArray(room.cleaningItems) || room.cleaningItems.length === 0))) {
    showToast("Bitte pro Raum mindestens einen Reinigungspunkt auswählen.");
    return;
  }

  if (floors.some((floor) => floor.rooms.some((room) => room.cleaningItems.some((item) => item.frequency === "Individuell" && !item.customFrequency)))) {
    showToast("Bitte bei individuellem Rhythmus eine Angabe eintragen.");
    return;
  }

  const squareMeters = Number(document.querySelector("#site-visit-square-meters").value);
  if (squareMeters <= 0) {
    showToast("Bitte die Objektgröße in Quadratmetern angeben.");
    return;
  }

  const companyName = document.querySelector("#site-visit-customer-name").value.trim();
  const payload = {
    companyName,
    customerName: companyName,
    email: document.querySelector("#site-visit-email").value.trim(),
    phone: document.querySelector("#site-visit-phone").value.trim(),
    address: document.querySelector("#site-visit-address").value.trim(),
    onsiteContact: document.querySelector("#site-visit-onsite-contact").value.trim(),
    squareMeters,
    floors,
    notes: document.querySelector("#site-visit-notes").value.trim(),
  };

  try {
    await apiPut(`api/site-visits.php?id=${encodeURIComponent(state.editingSiteVisitId)}`, payload);
    closeSiteVisitEditor();
    await loadAll();
    switchView("site-visit-saved");
    showToast("Begehung wurde aktualisiert.");
  } catch (error) {
    showToast(error.message);
  }
}

async function handleOfferSubmit(event) {
  event.preventDefault();

  if (!state.pendingOfferSiteVisitId) {
    showToast("Bitte zuerst eine offene Begehung auswählen.");
    els.offerSiteVisit.focus();
    return;
  }

  if (!getOpenSiteVisits().some((visit) => visit.id === state.pendingOfferSiteVisitId)) {
    state.pendingOfferSiteVisitId = null;
    renderOfferSiteVisitOptions();
    showToast("Diese Begehung wurde bereits abgearbeitet.");
    return;
  }

  const customerId = els.offerCustomer.value;
  if (!customerId) {
    showToast("Bitte zuerst eine Begehung auswählen, damit der Kunde übernommen wird.");
    els.offerSiteVisit.focus();
    return;
  }

  const estimatedPrice = calculateOfferPrice(
    els.offerSquareMeters.value,
    els.offerInterval.value,
    els.offerService.value,
  );
  const manualPrice = Number(els.offerManualPrice.value);
  if (!Number.isFinite(manualPrice) || manualPrice <= 0) {
    showToast("Bitte den manuellen Preis eintragen.");
    els.offerManualPrice.focus();
    return;
  }

  const payload = {
    customerId,
    siteVisitId: state.pendingOfferSiteVisitId,
    squareMeters: Number(els.offerSquareMeters.value),
    interval: els.offerInterval.value,
    service: els.offerService.value,
    startDate: els.offerStartDate.value,
    manualPrice,
    priceAdjustment: manualPrice - estimatedPrice,
    priceAdjustmentNote: "",
    notes: els.offerNotes.value.trim(),
  };

  try {
    await apiPost("api/offers.php", payload);
    els.offerForm.reset();
    state.pendingOfferSiteVisitId = null;
    els.offerStartDate.value = todayAsInputValue();
    updateOfferPreview();
    switchView("offers-saved");
    showToast("Kostenvoranschlag wurde erstellt.");
  } catch (error) {
    showToast(error.message);
  }
}

function setOfferSendRecipientMode(mode) {
  const suggestedEmail = els.offerSendModal.dataset.suggestedEmail || "";
  const canUseSuggested = isValidEmail(suggestedEmail);
  const nextMode = mode === "customer" && canUseSuggested ? "customer" : "manual";

  state.offerSendRecipientMode = nextMode;
  els.offerSendSuggested.classList.toggle("active", nextMode === "customer");
  els.offerSendManual.classList.toggle("active", nextMode === "manual");
  els.offerSendSuggested.disabled = !canUseSuggested;
  els.offerSendEmail.readOnly = nextMode === "customer";

  if (nextMode === "customer") {
    els.offerSendEmail.value = suggestedEmail;
  } else if (!els.offerSendEmail.value && canUseSuggested) {
    els.offerSendEmail.value = suggestedEmail;
  }
}

function openOfferSendModal(id) {
  const offer = getOffer(id);
  if (!offer) {
    showToast("Kostenvoranschlag wurde nicht gefunden.");
    return;
  }

  const suggestedEmail = String(offer.customer?.email || "").trim();
  const hasSuggestedEmail = isValidEmail(suggestedEmail);
  const customerLabel = offer.customer?.name || "Kunde";

  state.pendingSendOfferId = id;
  els.offerSendModal.dataset.offerId = id;
  els.offerSendModal.dataset.suggestedEmail = suggestedEmail;
  els.offerSendCustomer.textContent = hasSuggestedEmail
    ? `Vorschlag aus dem Kunden „${customerLabel}“: ${suggestedEmail}`
    : `Für „${customerLabel}“ ist keine gültige Kunden-E-Mail hinterlegt.`;
  els.offerSendEmail.value = "";
  setOfferSendRecipientMode(hasSuggestedEmail ? "customer" : "manual");

  els.offerSendModal.hidden = false;
  if (window.lucide) {
    window.lucide.createIcons();
  }
  els.offerSendEmail.focus();
  if (state.offerSendRecipientMode === "manual") {
    els.offerSendEmail.select();
  }
}

function closeOfferSendModal() {
  els.offerSendModal.hidden = true;
  state.pendingSendOfferId = null;
  els.offerSendModal.dataset.offerId = "";
  els.offerSendModal.dataset.suggestedEmail = "";
  els.offerSendForm.reset();
  els.offerSendEmail.readOnly = false;
  els.offerSendSubmit.disabled = false;
}

async function sendOffer(id, toEmail) {
  try {
    const result = await apiPost(`api/send-offer.php?id=${encodeURIComponent(id)}`, { toEmail });
    try {
      await loadAll();
    } catch (error) {
      // Der Versand war erfolgreich; ein spätes Listen-Refresh darf den Nutzer nicht irritieren.
    }
    showToast(`Kostenvoranschlag wurde an ${result.sentTo || toEmail} versendet.`);
    return true;
  } catch (error) {
    showToast(error.message);
    return false;
  }
}

async function submitOfferSendForm(event) {
  event.preventDefault();
  const id = state.pendingSendOfferId;
  const toEmail = els.offerSendEmail.value.trim();

  if (!id) {
    showToast("Kostenvoranschlag wurde nicht gefunden.");
    closeOfferSendModal();
    return;
  }

  if (!isValidEmail(toEmail)) {
    showToast("Bitte eine gültige E-Mail-Adresse eintragen.");
    els.offerSendEmail.focus();
    return;
  }

  els.offerSendSubmit.disabled = true;
  const sent = await sendOffer(id, toEmail);
  els.offerSendSubmit.disabled = false;
  if (sent) {
    closeOfferSendModal();
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

function openOfferContractLink(id) {
  const offer = getOffer(id);
  if (!offer || !offer.publicUrl) {
    showToast("Link wurde nicht gefunden.");
    return;
  }

  window.open(offer.publicUrl, "_blank", "noopener");
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

  const confirmed = window.confirm(`Begehung für "${siteVisitCompanyName(visit)}" löschen?`);
  if (!confirmed) {
    return;
  }

  try {
    await apiDelete(`api/site-visits.php?id=${encodeURIComponent(id)}`);
    if (state.editingSiteVisitId === id) {
      closeSiteVisitEditor();
    }
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
  const contract = getContract(id);
  const contractLabel = contract
    ? `${contract.number} f\u00fcr ${contract.customer.name}`
    : "diesen Vertrag";
  const firstConfirmed = window.confirm(
    `Vertrag ${contractLabel} wirklich l\u00f6schen? Die gespeicherten Vertragsdokumente werden ebenfalls entfernt.`,
  );
  if (!firstConfirmed) {
    return;
  }

  const finalConfirmed = window.confirm(
    "Letzte Nachfrage: Vertrag endg\u00fcltig l\u00f6schen? Danach kann aus dem Kostenvoranschlag ein neuer Vertrag erstellt werden.",
  );
  if (!finalConfirmed) {
    return;
  }

  try {
    await apiDelete(`api/contracts.php?id=${encodeURIComponent(id)}`);
    if (state.selectedContractId === id) {
      state.selectedContractId = null;
    }
    await loadAll();
    showToast("Vertrag wurde gel\u00f6scht. Der Kostenvoranschlag ist wieder f\u00fcr einen neuen Vertrag frei.");
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

async function loadEmailSettings() {
  try {
    const settings = await apiGet("api/email-settings.php");
    els.emailSettingsCustomerEnabled.checked = settings.customerEmailsEnabled;
    els.emailSettingsOfferEnabled.checked = settings.offerEmailsEnabled;
    els.emailSettingsContractEnabled.checked = settings.contractEmailsEnabled;
    els.emailSettingsMailboxEnabled.checked = settings.mailboxEmailsEnabled;
    els.emailSettingsInternalContractEnabled.checked = settings.internalContractNotificationsEnabled;
    els.emailSettingsTestEnabled.checked = settings.testEmailsEnabled;
  } catch (error) {
    showToast(error.message);
  }
}

async function handleEmailSettingsSubmit(event) {
  event.preventDefault();

  const payload = {
    customerEmailsEnabled: els.emailSettingsCustomerEnabled.checked,
    offerEmailsEnabled: els.emailSettingsOfferEnabled.checked,
    contractEmailsEnabled: els.emailSettingsContractEnabled.checked,
    mailboxEmailsEnabled: els.emailSettingsMailboxEnabled.checked,
    internalContractNotificationsEnabled: els.emailSettingsInternalContractEnabled.checked,
    testEmailsEnabled: els.emailSettingsTestEnabled.checked,
  };

  try {
    await apiPost("api/email-settings.php", payload);
    showToast("E-Mail-Einstellungen wurden gespeichert.");
    await loadEmailSettings();
  } catch (error) {
    showToast(error.message);
  }
}

function emailSignaturePayload() {
  return {
    senderName: els.emailSignatureName.value.trim(),
    senderRole: els.emailSignatureRole.value.trim(),
    phone: els.emailSignaturePhone.value.trim(),
    mobile: els.emailSignatureMobile.value.trim(),
    email: els.emailSignatureEmail.value.trim(),
    website: els.emailSignatureWebsite.value.trim(),
    companyName: els.emailSignatureCompany.value.trim(),
    addressLine1: els.emailSignatureAddress1.value.trim(),
    addressLine2: els.emailSignatureAddress2.value.trim(),
    extraText: els.emailSignatureExtra.value.trim(),
    useAllEmails: Boolean(els.emailSignatureUseAll?.checked),
    usage: {
      offer: Boolean(els.emailSignatureUseOffer?.checked),
      contractCustomer: Boolean(els.emailSignatureUseContract?.checked),
      mailbox: Boolean(els.emailSignatureUseMailbox?.checked),
    },
  };
}

function syncEmailSignatureUsageControls() {
  const useAll = Boolean(els.emailSignatureUseAll?.checked);
  els.emailSignatureUsageOptions.forEach((checkbox) => {
    checkbox.disabled = useAll;
  });
}

function applyEmailSignature(settings = {}) {
  const usage = settings.usage || {};
  emailSignatureImageUrl = settings.imageUrl || "";
  pendingEmailSignatureImageFile = null;
  pendingEmailSignatureImageDataUrl = "";
  pendingEmailSignatureImageRemoval = false;
  els.emailSignatureName.value = settings.senderName || "";
  els.emailSignatureRole.value = settings.senderRole || "";
  els.emailSignaturePhone.value = settings.phone || "";
  els.emailSignatureMobile.value = settings.mobile || "";
  els.emailSignatureEmail.value = settings.email || "";
  els.emailSignatureWebsite.value = settings.website || "";
  els.emailSignatureCompany.value = settings.companyName || "";
  els.emailSignatureAddress1.value = settings.addressLine1 || "";
  els.emailSignatureAddress2.value = settings.addressLine2 || "";
  els.emailSignatureExtra.value = settings.extraText || "";
  if (els.emailSignatureUseAll) {
    els.emailSignatureUseAll.checked = settings.useAllEmails !== false;
  }
  if (els.emailSignatureUseOffer) {
    els.emailSignatureUseOffer.checked = usage.offer !== false;
  }
  if (els.emailSignatureUseContract) {
    els.emailSignatureUseContract.checked = usage.contractCustomer !== false;
  }
  if (els.emailSignatureUseMailbox) {
    els.emailSignatureUseMailbox.checked = usage.mailbox !== false;
  }
  syncEmailSignatureUsageControls();
  renderEmailSignatureImage();
  renderEmailSignaturePreview();
  updateEmailSignatureImageStatus(
    emailSignatureImageUrl
      ? "Bild ist gespeichert. Ein neues Bild auswählen oder entfernen und anschließend speichern."
      : "PNG, JPG oder WEBP, maximal 2 MB."
  );
  updateEmailSignatureSaveStatus(
    settings.updatedAt
      ? `Gespeichert am ${formatDate(settings.updatedAt)}.`
      : "Bestehende Signatur geladen. Änderungen bitte speichern."
  );
}

function currentEmailSignaturePreviewImage() {
  if (pendingEmailSignatureImageRemoval) return "";
  return pendingEmailSignatureImageDataUrl || emailSignatureImageUrl || "";
}

function updateEmailSignatureImageStatus(text = "PNG, JPG oder WEBP, maximal 2 MB.") {
  if (els.emailSignatureImageStatus) {
    els.emailSignatureImageStatus.textContent = text;
  }
}

function updateEmailSignatureSaveStatus(text) {
  if (els.emailSignatureSaveStatus) {
    els.emailSignatureSaveStatus.textContent = text;
  }
}

function markEmailSignatureUnsaved() {
  updateEmailSignatureSaveStatus("Änderungen noch nicht gespeichert.");
}

function renderEmailSignatureImage() {
  if (!els.emailSignatureImagePreview) return;

  const imageUrl = currentEmailSignaturePreviewImage();
  if (imageUrl) {
    els.emailSignatureImagePreview.className = "email-signature-image-preview";
    els.emailSignatureImagePreview.innerHTML = `<img src="${escapeHtml(imageUrl)}" alt="E-Mail-Signatur" />`;
    els.emailSignatureImageRemove.hidden = false;
  } else {
    els.emailSignatureImagePreview.className = "email-signature-image-preview empty-state";
    els.emailSignatureImagePreview.textContent = "Kein Bild hinterlegt.";
    els.emailSignatureImageRemove.hidden = true;
  }
}

function emailSignatureContactLine(payload) {
  return [
    payload.phone ? `Tel.: ${payload.phone}` : "",
    payload.mobile ? `Mobil: ${payload.mobile}` : "",
    payload.email ? `E-Mail: ${payload.email}` : "",
  ].filter(Boolean).join(" | ");
}

function renderEmailSignaturePreview() {
  if (!els.emailSignaturePreview) return;

  const payload = emailSignaturePayload();
  const contactLine = emailSignatureContactLine(payload);
  const image = currentEmailSignaturePreviewImage();
  const imageHtml = image
    ? `<img src="${escapeHtml(image)}" alt="${escapeHtml(payload.companyName || "CleanTeam")}" />`
    : `<div class="email-signature-logo-fallback">CleanTeam</div>`;
  const websiteHtml = payload.website
    ? `<a href="${escapeHtml(payload.website)}" target="_blank" rel="noreferrer">${escapeHtml(payload.website)}</a>`
    : "";
  const extraHtml = payload.extraText
    ? `<p class="email-signature-preview-extra">${escapeHtml(payload.extraText).replace(/\n/g, "<br>")}</p>`
    : "";

  els.emailSignaturePreview.innerHTML = `
    <div class="email-signature-preview-card">
      <div class="email-signature-preview-image">${imageHtml}</div>
      <div class="email-signature-preview-content">
        <p class="email-signature-preview-greeting">Mit freundlichen Grüßen</p>
        <strong>${escapeHtml(payload.senderName || "Ihr CleanTeam-Team")}</strong>
        ${payload.senderRole ? `<span>${escapeHtml(payload.senderRole)}</span>` : ""}
        <p><b>${escapeHtml(payload.companyName || "Clean Team Group SRLS")}</b></p>
        ${contactLine ? `<p>${escapeHtml(contactLine)}</p>` : ""}
        ${payload.addressLine1 ? `<p>${escapeHtml(payload.addressLine1)}</p>` : ""}
        ${payload.addressLine2 ? `<p>${escapeHtml(payload.addressLine2)}</p>` : ""}
        ${websiteHtml ? `<p>${websiteHtml}</p>` : ""}
        ${extraHtml}
      </div>
    </div>
  `;
}

async function loadEmailSignature() {
  if (!els.emailSignatureForm) {
    return;
  }

  try {
    const settings = await apiGet("api/email-signature.php");
    applyEmailSignature(settings);
  } catch (error) {
    showToast(error.message);
  }
}

async function handleEmailSignatureSubmit(event) {
  event.preventDefault();

  try {
    let settings = await apiPost("api/email-signature.php", emailSignaturePayload());

    if (pendingEmailSignatureImageRemoval) {
      settings = await deleteEmailSignatureImage();
    }

    if (pendingEmailSignatureImageFile) {
      settings = await uploadEmailSignatureImage(pendingEmailSignatureImageFile);
    }

    applyEmailSignature(settings);
    showToast("E-Mail-Signatur wurde gespeichert.");
  } catch (error) {
    showToast(error.message);
  }
}

async function uploadEmailSignatureImage(file) {
  const formData = new FormData();
  formData.append("image", file);

  const response = await fetch("api/email-signature.php?action=image", { method: "POST", body: formData, credentials: "same-origin" });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(data.error || "Bild konnte nicht hochgeladen werden.");
  }
  return data;
}

async function deleteEmailSignatureImage() {
  const response = await fetch("api/email-signature.php?action=image", { method: "DELETE", credentials: "same-origin" });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(data.error || "Bild konnte nicht entfernt werden.");
  }
  return data;
}

function handleEmailSignatureImageUpload(event) {
  const file = event.target.files[0];
  if (!file) {
    return;
  }

  if (!["image/png", "image/jpeg", "image/webp"].includes(file.type)) {
    showToast("Nur PNG, JPG oder WEBP sind erlaubt.");
    els.emailSignatureImageInput.value = "";
    return;
  }

  if (file.size > 2 * 1024 * 1024) {
    showToast("Datei ist zu groß (max. 2 MB).");
    els.emailSignatureImageInput.value = "";
    return;
  }

  const reader = new FileReader();
  reader.onload = () => {
    pendingEmailSignatureImageFile = file;
    pendingEmailSignatureImageDataUrl = String(reader.result || "");
    pendingEmailSignatureImageRemoval = false;
    renderEmailSignatureImage();
    renderEmailSignaturePreview();
    markEmailSignatureUnsaved();
    updateEmailSignatureImageStatus(`${file.name} ausgewählt. Zum Übernehmen bitte Signatur speichern.`);
  };
  reader.onerror = () => {
    showToast("Bild konnte nicht gelesen werden.");
  };
  reader.readAsDataURL(file);
}

function handleEmailSignatureImageRemove() {
  pendingEmailSignatureImageFile = null;
  pendingEmailSignatureImageDataUrl = "";
  pendingEmailSignatureImageRemoval = Boolean(emailSignatureImageUrl);
  if (els.emailSignatureImageInput) {
    els.emailSignatureImageInput.value = "";
  }
  renderEmailSignatureImage();
  renderEmailSignaturePreview();
  markEmailSignatureUnsaved();
  updateEmailSignatureImageStatus(
    pendingEmailSignatureImageRemoval
      ? "Bild wird beim nächsten Speichern entfernt."
      : "PNG, JPG oder WEBP, maximal 2 MB."
  );
}

async function loadMailbox() {
  try {
    const settings = await apiGet("api/mailbox.php?action=settings");
    const canManageSettings = Boolean(settings.canManageSettings ?? isAdmin());

    if (els.mailboxSettingsPanel) {
      els.mailboxSettingsPanel.hidden = !canManageSettings;
    }
    if (els.mailboxGotoSettings) {
      els.mailboxGotoSettings.hidden = !canManageSettings;
    }
    if (els.mailboxNotConfiguredAdminText) {
      els.mailboxNotConfiguredAdminText.hidden = !canManageSettings;
    }
    if (els.mailboxNotConfiguredUserText) {
      els.mailboxNotConfiguredUserText.hidden = canManageSettings;
    }

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

function contractorSignatureContext() {
  if (!els.contractorSignaturePad) {
    return null;
  }

  const context = els.contractorSignaturePad.getContext("2d");
  context.lineCap = "round";
  context.lineJoin = "round";
  context.lineWidth = 5;
  context.strokeStyle = "#102033";
  return context;
}

function updateContractorSignatureStatus(message) {
  if (els.contractorSignatureStatus) {
    els.contractorSignatureStatus.textContent = message;
  }
}

function clearContractorSignaturePad(message = "Zeichenfläche ist leer.") {
  const canvas = els.contractorSignaturePad;
  const context = contractorSignatureContext();
  if (!canvas || !context) {
    return;
  }

  context.clearRect(0, 0, canvas.width, canvas.height);
  contractorSignatureHasInk = false;
  contractorSignatureLastPoint = null;
  updateContractorSignatureStatus(message);
}

function contractorSignaturePoint(event) {
  const canvas = els.contractorSignaturePad;
  const rect = canvas.getBoundingClientRect();
  return {
    x: ((event.clientX - rect.left) / rect.width) * canvas.width,
    y: ((event.clientY - rect.top) / rect.height) * canvas.height,
  };
}

function initContractorSignaturePad() {
  const canvas = els.contractorSignaturePad;
  const context = contractorSignatureContext();
  if (!canvas || !context || contractorSignaturePadReady) {
    return;
  }

  canvas.addEventListener("pointerdown", (event) => {
    event.preventDefault();
    canvas.setPointerCapture(event.pointerId);
    contractorSignatureDrawing = true;
    contractorSignatureLastPoint = contractorSignaturePoint(event);
    context.beginPath();
    context.moveTo(contractorSignatureLastPoint.x, contractorSignatureLastPoint.y);
    context.lineTo(contractorSignatureLastPoint.x + 0.01, contractorSignatureLastPoint.y + 0.01);
    context.stroke();
    contractorSignatureHasInk = true;
    updateContractorSignatureStatus("Neue Unterschrift gezeichnet. Zum Übernehmen speichern.");
  });

  canvas.addEventListener("pointermove", (event) => {
    if (!contractorSignatureDrawing || contractorSignatureLastPoint === null) {
      return;
    }

    event.preventDefault();
    const point = contractorSignaturePoint(event);
    context.lineTo(point.x, point.y);
    context.stroke();
    contractorSignatureLastPoint = point;
    contractorSignatureHasInk = true;
    updateContractorSignatureStatus("Neue Unterschrift gezeichnet. Zum Übernehmen speichern.");
  });

  const stopDrawing = (event) => {
    if (!contractorSignatureDrawing) {
      return;
    }
    contractorSignatureDrawing = false;
    contractorSignatureLastPoint = null;
    if (canvas.hasPointerCapture(event.pointerId)) {
      canvas.releasePointerCapture(event.pointerId);
    }
  };

  canvas.addEventListener("pointerup", stopDrawing);
  canvas.addEventListener("pointercancel", stopDrawing);
  canvas.addEventListener("pointerleave", stopDrawing);
  contractorSignaturePadReady = true;
}

function drawContractorSignatureDataUrl(dataUrl, updatedAt) {
  const canvas = els.contractorSignaturePad;
  const context = contractorSignatureContext();
  if (!canvas || !context || !dataUrl) {
    clearContractorSignaturePad("Noch keine Unterschrift gespeichert.");
    return;
  }

  const image = new Image();
  image.onload = () => {
    context.clearRect(0, 0, canvas.width, canvas.height);
    const scale = Math.min(canvas.width / image.width, canvas.height / image.height, 1);
    const width = image.width * scale;
    const height = image.height * scale;
    context.drawImage(image, 0, (canvas.height - height) / 2, width, height);
    contractorSignatureHasInk = true;
    const suffix = updatedAt ? ` Gespeichert am ${formatDate(updatedAt)}.` : "";
    updateContractorSignatureStatus(`Unterschrift Thomas Mündlein ist gespeichert.${suffix}`);
  };
  image.onerror = () => {
    clearContractorSignaturePad("Gespeicherte Unterschrift konnte nicht geladen werden.");
  };
  image.src = dataUrl;
}

async function handleContractorSignatureSave() {
  initContractorSignaturePad();
  if (!contractorSignatureHasInk) {
    showToast("Bitte zuerst auf der Zeichenfläche unterschreiben.");
    return;
  }

  try {
    const signatureDataUrl = els.contractorSignaturePad.toDataURL("image/png");
    await apiPost("api/contract-template.php?action=signature", { signatureDataUrl });
    updateContractorSignatureStatus("Unterschrift Thomas Mündlein ist gespeichert.");
    showToast("CleanTeam-Unterschrift wurde gespeichert.");
  } catch (error) {
    showToast(error.message);
  }
}

async function handleContractorSignatureRemove() {
  if (!window.confirm("Unterschrift Thomas Mündlein wirklich aus allen Verträgen entfernen?")) {
    return;
  }

  try {
    await apiPost("api/contract-template.php?action=remove-signature", {});
    clearContractorSignaturePad("Noch keine Unterschrift gespeichert.");
    showToast("CleanTeam-Unterschrift wurde entfernt.");
  } catch (error) {
    showToast(error.message);
  }
}

function applyContractorSignature(result = {}) {
  initContractorSignaturePad();
  if (result.contractorSignatureDataUrl) {
    drawContractorSignatureDataUrl(result.contractorSignatureDataUrl, result.contractorSignatureUpdatedAt);
  } else {
    clearContractorSignaturePad("Noch keine Unterschrift gespeichert.");
  }
}

async function loadContractorSignature() {
  if (!els.contractorSignaturePad) {
    return;
  }

  try {
    const result = await apiGet("api/contract-template.php");
    applyContractorSignature(result);
  } catch (error) {
    showToast(error.message);
  }
}

function renderTemplatePlaceholderChips(groups) {
  if (!els.templatePlaceholderGroups) {
    return;
  }

  els.templatePlaceholderGroups.innerHTML = Object.entries(groups || {})
    .map(([groupLabel, tokens]) => {
      const chips = Object.entries(tokens)
        .map(
          ([token, description]) =>
            `<button type="button" class="template-placeholder-chip" data-token="${escapeHtml(token)}" title="${escapeHtml(description)}">{{${escapeHtml(token)}}}</button>`
        )
        .join("");
      return `
        <div class="template-placeholder-group">
          <span class="template-placeholder-group-label">${escapeHtml(groupLabel)}</span>
          ${chips}
        </div>
      `;
    })
    .join("");
}

function insertTemplatePlaceholder(token) {
  const textarea = els.templateEditor;
  if (!textarea) {
    return;
  }

  const start = textarea.selectionStart ?? textarea.value.length;
  const end = textarea.selectionEnd ?? textarea.value.length;
  const insertion = `{{${token}}}`;
  textarea.value = textarea.value.slice(0, start) + insertion + textarea.value.slice(end);
  const cursor = start + insertion.length;
  textarea.focus();
  textarea.setSelectionRange(cursor, cursor);
}

async function loadContractTemplate() {
  if (!els.templateEditor) {
    return;
  }

  try {
    const result = await apiGet("api/contract-template.php");
    els.templateEditor.value = result.templateHtml || "";
    renderTemplatePlaceholderChips(result.placeholders || {});
  } catch (error) {
    showToast(error.message);
  }
}

async function handleContractTemplateSave() {
  try {
    await apiPost("api/contract-template.php", { templateHtml: els.templateEditor.value });
    showToast("Mustervertrag wurde gespeichert.");
  } catch (error) {
    showToast(error.message);
  }
}

async function handleContractTemplateReset() {
  if (!window.confirm("Vertragstext wirklich auf den Standardtext zurücksetzen? Ungespeicherte Änderungen gehen dabei verloren.")) {
    return;
  }

  try {
    const result = await apiGet("api/contract-template.php?action=default");
    els.templateEditor.value = result.templateHtml || "";
    showToast("Standardtext geladen. Zum Übernehmen bitte speichern.");
  } catch (error) {
    showToast(error.message);
  }
}

async function handleContractTemplatePreview() {
  try {
    const result = await apiPost("api/contract-template.php?action=preview", { templateHtml: els.templateEditor.value });
    if (els.templatePreviewFrame) {
      els.templatePreviewFrame.srcdoc = result.html || "";
    }
  } catch (error) {
    showToast(error.message);
  }
}

function userRoleOptions(selectedRole) {
  const roles = Object.keys(state.userRoles).length
    ? state.userRoles
    : { admin: "Admin", role_one: "Rolle 1" };

  return Object.entries(roles)
    .map(([role, label]) => `<option value="${escapeHtml(role)}"${optionSelected(role, selectedRole)}>${escapeHtml(label)}</option>`)
    .join("");
}

function renderUsers() {
  if (!els.userList) {
    return;
  }

  els.userList.innerHTML = state.users.length
    ? state.users.map((user) => {
        const currentBadge = Number(user.id) === Number(state.currentUserId) ? `<span class="badge">Aktuell angemeldet</span>` : "";
        return `
          <article class="user-management-item" data-user-id="${escapeHtml(user.id)}">
            <div class="user-management-main">
              <div>
                <strong>${escapeHtml(user.name) || "<em>Ohne Namen</em>"}</strong>
                <div class="record-meta">
                  <span>${escapeHtml(user.email)}</span>
                  <span>${escapeHtml(user.roleLabel)}</span>
                  <span>Angelegt am ${formatDate(user.createdAt)}</span>
                </div>
              </div>
              ${currentBadge}
            </div>
            <div class="user-management-controls">
              <label>
                Name
                <input name="managedUserName" type="text" value="${escapeHtml(user.name || "")}" placeholder="Vor- und Nachname" />
              </label>
              <label>
                Rolle
                <select name="managedUserRole">
                  ${userRoleOptions(user.role)}
                </select>
              </label>
              <label>
                Aktuelles Passwort
                <div class="password-reveal">
                  <input name="managedUserCurrentPassword" type="password" value="${escapeHtml(user.password || "")}" readonly />
                  <button class="ghost-button" type="button" data-action="toggle-user-password">
                    <i data-lucide="eye" aria-hidden="true"></i>
                  </button>
                </div>
              </label>
              <label>
                Neues Passwort
                <input name="managedUserPassword" type="password" minlength="6" placeholder="Leer lassen = unverändert" autocomplete="new-password" />
              </label>
              <button class="primary-button" type="button" data-action="save-user" data-id="${escapeHtml(user.id)}">
                <i data-lucide="save" aria-hidden="true"></i>
                Speichern
              </button>
            </div>
          </article>
        `;
      }).join("")
    : `<div class="empty-state">Noch keine User vorhanden.</div>`;

  refreshIcons();
}

async function loadUsers() {
  if (!isAdmin()) {
    return;
  }

  try {
    const result = await apiGet("api/users.php");
    state.users = result.users || [];
    state.userRoles = result.roles || {};
    state.currentUserId = result.currentUserId || null;
    renderUsers();
  } catch (error) {
    showToast(error.message);
  }
}

async function handleUserSubmit(event) {
  event.preventDefault();

  const payload = {
    name: els.userName.value.trim(),
    email: els.userEmail.value.trim(),
    password: els.userPassword.value,
    role: els.userRole.value,
  };

  try {
    await apiPost("api/users.php", payload);
    els.userForm.reset();
    els.userRole.value = "role_one";
    await loadUsers();
    showToast("User wurde angelegt.");
  } catch (error) {
    showToast(error.message);
  }
}

async function saveManagedUser(button) {
  const item = button.closest(".user-management-item");
  if (!item) {
    return;
  }

  const id = button.dataset.id;
  const name = item.querySelector('[name="managedUserName"]').value.trim();
  const role = item.querySelector('[name="managedUserRole"]').value;
  const passwordInput = item.querySelector('[name="managedUserPassword"]');
  const password = passwordInput.value;

  button.disabled = true;
  try {
    await apiPut(`api/users.php?id=${encodeURIComponent(id)}`, { name, role, password });
    passwordInput.value = "";
    await loadUsers();
    showToast("User wurde aktualisiert.");
  } catch (error) {
    showToast(error.message);
  } finally {
    button.disabled = false;
  }
}

function toggleUserPasswordVisibility(button) {
  const wrapper = button.closest(".password-reveal");
  const input = wrapper?.querySelector('[name="managedUserCurrentPassword"]');
  if (!input) {
    return;
  }

  const icon = button.querySelector("i");
  const revealed = input.type === "text";
  input.type = revealed ? "password" : "text";
  if (icon) {
    icon.setAttribute("data-lucide", revealed ? "eye" : "eye-off");
  }
  refreshIcons();
}

function handleUserListAction(event) {
  const saveButton = event.target.closest('[data-action="save-user"]');
  if (saveButton) {
    saveManagedUser(saveButton);
    return;
  }

  const toggleButton = event.target.closest('[data-action="toggle-user-password"]');
  if (toggleButton) {
    toggleUserPasswordVisibility(toggleButton);
  }
}

function handleSiteVisitFloorAction(event) {
  const button = event.target.closest('[data-action="remove-site-visit-floor"]');
  const addRoomButton = event.target.closest('[data-action="add-site-visit-room"]');
  const removeRoomButton = event.target.closest('[data-action="remove-site-visit-room"]');
  const toggleRoomButton = event.target.closest('[data-action="toggle-site-visit-room"]');
  const saveRoomButton = event.target.closest('[data-action="save-site-visit-room"]');

  if (addRoomButton) {
    event.preventDefault();
    const floorSection = addRoomButton.closest(".floor-section");
    addSiteVisitRoomFromToolbar(floorSection);
    return;
  }

  if (toggleRoomButton) {
    event.preventDefault();
    const roomSection = toggleRoomButton.closest(".room-section");
    setRoomSectionCollapsed(roomSection, !roomSection.classList.contains("is-collapsed"));
    return;
  }

  if (saveRoomButton) {
    event.preventDefault();
    saveSiteVisitRoom(saveRoomButton.closest(".room-section"));
    return;
  }

  if (removeRoomButton) {
    event.preventDefault();
    const floorSection = removeRoomButton.closest(".floor-section");
    removeRoomButton.closest(".room-section").remove();
    renumberSiteVisitRooms(floorSection);
    ensureFloorRoomEmptyState(floorSection);
    refreshIcons();
    return;
  }

  if (button) {
    event.preventDefault();
    button.closest(".floor-section").remove();
    renumberSiteVisitFloors();
    ensureSiteVisitFloorEmptyState();
    refreshIcons();
  }
}

function handleSiteVisitFloorInput(event) {
  if (event.target.matches('[name="floorName"]')) {
    renumberSiteVisitFloors();
  }
  if (event.target.matches('[name="roomName"], [name="roomType"]')) {
    const floorSection = event.target.closest(".floor-section");
    if (floorSection) {
      renumberSiteVisitRooms(floorSection);
    }
    const roomSection = event.target.closest(".room-section");
    if (roomSection) {
      syncCleaningTaskSections(roomSection);
    }
  }
  if (event.target.matches('[name="cleaningItem"], [name="cleaningFrequency"]')) {
    const roomSection = event.target.closest(".room-section");
    if (roomSection) {
      syncCleaningTaskSections(roomSection);
    }
  }
}

function handleSiteVisitFloorKeydown(event) {
  if (event.key !== "Enter" || !event.target.matches('[name="newRoomName"]')) {
    return;
  }

  event.preventDefault();
  const floorSection = event.target.closest(".floor-section");
  if (floorSection) {
    addSiteVisitRoomFromToolbar(floorSection);
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

  if (button.dataset.action === "cancel-site-visit-edit") {
    event.preventDefault();
    closeSiteVisitEditor();
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
      state.pendingQuizCustomerReturn = false;
      fillCustomerForm(customer);
      switchView("customer-new");
      document.querySelector("#customer-new-view").scrollIntoView({ behavior: "smooth", block: "start" });
    }
  }

  if (action === "delete-customer") {
    deleteCustomer(id);
  }

  if (action === "delete-site-visit") {
    deleteSiteVisit(id);
  }

  if (action === "edit-site-visit") {
    openSiteVisitEditor(id);
  }

  if (action === "offer-from-site-visit") {
    startOfferFromSiteVisit(id);
  }

  if (action === "send-offer") {
    openOfferSendModal(id);
  }

  if (action === "copy-offer-link") {
    copyOfferLink(id);
  }

  if (action === "open-offer-contract-link") {
    openOfferContractLink(id);
  }

  if (action === "open-contract") {
    state.selectedContractId = id;
    state.contractFilters.search = "";
    state.contractFilters.period = "all";
    switchView("contracts");
  }

  if (action === "delete-offer") {
    deleteOffer(id);
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
      const result = await apiPost("api/login.php", { email, password });
      els.loginError.hidden = true;
      showApp(result.user || result);
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
    button.addEventListener("click", () => {
      if (button.dataset.view !== "customer-new") {
        state.pendingQuizCustomerReturn = false;
      }
      if (button.dataset.view === "offers-new") {
        state.pendingOfferSiteVisitId = null;
        clearOfferSiteVisitFields();
      }
      switchView(button.dataset.view);
    });
  });

  els.customersGroupToggle.addEventListener("click", () => {
    setCustomersGroupExpanded(els.customersSubgroup.hidden);
  });

  els.siteVisitsGroupToggle.addEventListener("click", () => {
    setSiteVisitsGroupExpanded(els.siteVisitsSubgroup.hidden);
  });

  els.offersGroupToggle.addEventListener("click", () => {
    setOffersGroupExpanded(els.offersSubgroup.hidden);
  });

  els.settingsGroupToggle.addEventListener("click", () => {
    if (!isAdmin()) {
      showToast("Nur Admins können die Einstellungen öffnen.");
      return;
    }
    setSettingsGroupExpanded(els.settingsSubgroup.hidden);
  });

  els.bottomMenuButton.addEventListener("click", openMobileNav);
  els.mobileBackdrop.addEventListener("click", closeMobileNav);
  document.querySelectorAll("[data-overview-target]").forEach((button) => {
    button.addEventListener("click", () => {
      const target = button.dataset.overviewTarget;
      if (target === "offers-new") {
        state.pendingOfferSiteVisitId = null;
        clearOfferSiteVisitFields();
      }
      switchView(target);
    });
  });
  els.newCustomerButton.addEventListener("click", () => {
    state.pendingQuizCustomerReturn = false;
    resetCustomerForm();
    switchView("customer-new");
    document.querySelector("#customer-name").focus();
  });
  els.cancelCustomerEdit.addEventListener("click", () => {
    state.pendingQuizCustomerReturn = false;
    resetCustomerForm();
  });

  els.customerForm.addEventListener("submit", handleCustomerSubmit);
  els.customerSearch.addEventListener("input", renderCustomers);

  document.addEventListener("click", handleDashboardAction);
  if (els.siteVisitForm) {
    els.siteVisitForm.addEventListener("submit", handleSiteVisitSubmit);
  }
  if (els.siteVisitFloors) {
    els.siteVisitFloors.addEventListener("click", handleSiteVisitFloorAction);
    els.siteVisitFloors.addEventListener("input", handleSiteVisitFloorInput);
    els.siteVisitFloors.addEventListener("keydown", handleSiteVisitFloorKeydown);
  }
  if (els.cancelSiteVisitEdit) {
    els.cancelSiteVisitEdit.addEventListener("click", closeSiteVisitEditor);
  }

  els.offerForm.addEventListener("submit", handleOfferSubmit);
  els.offerSiteVisit.addEventListener("change", handleOfferSiteVisitChange);
  els.offerSquareMeters.addEventListener("input", updateOfferPreview);
  els.offerInterval.addEventListener("change", updateOfferPreview);
  els.offerService.addEventListener("change", updateOfferPreview);

  els.customerList.addEventListener("click", handleRecordAction);
  els.siteVisitList.addEventListener("click", handleRecordAction);
  els.offerList.addEventListener("click", handleRecordAction);
  els.contractList.addEventListener("click", handleRecordAction);
  els.contractSearch.addEventListener("input", () => {
    state.contractFilters.search = els.contractSearch.value;
    renderContracts();
    refreshIcons();
  });
  els.contractPeriodFilter.addEventListener("change", () => {
    state.contractFilters.period = els.contractPeriodFilter.value;
    renderContracts();
    refreshIcons();
  });
  els.contractSort.addEventListener("change", () => {
    state.contractFilters.sortKey = els.contractSort.value;
    renderContracts();
    refreshIcons();
  });
  els.contractSortDirection.addEventListener("change", () => {
    state.contractFilters.sortDirection = els.contractSortDirection.value;
    renderContracts();
    refreshIcons();
  });

  els.smtpForm.addEventListener("submit", handleSmtpSubmit);
  els.sendTestMail.addEventListener("click", sendTestMail);
  els.emailSettingsForm.addEventListener("submit", handleEmailSettingsSubmit);
  if (els.emailSignatureForm) {
    els.emailSignatureForm.addEventListener("submit", handleEmailSignatureSubmit);
    els.emailSignatureForm.addEventListener("input", () => {
      renderEmailSignaturePreview();
      markEmailSignatureUnsaved();
    });
  }
  if (els.emailSignatureUseAll) {
    els.emailSignatureUseAll.addEventListener("change", syncEmailSignatureUsageControls);
  }
  if (els.emailSignatureImageInput) {
    els.emailSignatureImageInput.addEventListener("change", handleEmailSignatureImageUpload);
  }
  if (els.emailSignatureImageRemove) {
    els.emailSignatureImageRemove.addEventListener("click", handleEmailSignatureImageRemove);
  }

  els.mailboxList.addEventListener("click", handleRecordAction);
  els.mailboxRefresh.addEventListener("click", loadMailboxInbox);
  els.mailboxGotoSettings.addEventListener("click", () => {
    document.querySelector("#mailbox-settings-heading").scrollIntoView({ behavior: "smooth", block: "start" });
  });
  els.mailboxComposeToggle.addEventListener("click", () => {
    const wasHidden = els.mailboxComposeForm.hidden;
    els.mailboxComposeForm.hidden = !wasHidden;
    if (wasHidden) {
      els.mailboxComposeBody.focus();
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
  els.templatePlaceholderGroups.addEventListener("click", (event) => {
    const chip = event.target.closest(".template-placeholder-chip");
    if (chip) {
      insertTemplatePlaceholder(chip.dataset.token);
    }
  });
  els.templateSave.addEventListener("click", handleContractTemplateSave);
  els.templateReset.addEventListener("click", handleContractTemplateReset);
  els.templatePreviewButton.addEventListener("click", handleContractTemplatePreview);
  els.contractorSignatureClear.addEventListener("click", () => {
    clearContractorSignaturePad("Zeichenfläche ist leer. Neue Unterschrift zeichnen und speichern.");
  });
  els.contractorSignatureSave.addEventListener("click", handleContractorSignatureSave);
  els.contractorSignatureRemove.addEventListener("click", handleContractorSignatureRemove);
  els.userForm.addEventListener("submit", handleUserSubmit);
  els.userList.addEventListener("click", handleUserListAction);

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

  els.offerSendForm.addEventListener("submit", submitOfferSendForm);
  els.offerSendCancel.addEventListener("click", closeOfferSendModal);
  els.offerSendSuggested.addEventListener("click", () => setOfferSendRecipientMode("customer"));
  els.offerSendManual.addEventListener("click", () => {
    setOfferSendRecipientMode("manual");
    els.offerSendEmail.focus();
    els.offerSendEmail.select();
  });
  els.offerSendModal.addEventListener("click", (event) => {
    if (event.target === els.offerSendModal) {
      closeOfferSendModal();
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
    if (event.key === "Escape" && !els.offerSendModal.hidden) {
      closeOfferSendModal();
      return;
    }
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
  try {
    bindEvents();
  } catch (error) {
    console.error("Dashboard event binding failed.", error);
  }
  if (els.offerStartDate) {
    els.offerStartDate.value = todayAsInputValue();
  }
  loadBranding();

  try {
    const session = await apiGet("api/me.php");
    if (session.loggedIn) {
      showApp(session.user || session);
      return;
    }
  } catch (error) {
    // Fall through to the login screen if the session check fails.
  }

  showLogin();
  refreshIcons();
}

init().catch((error) => {
  console.error("Dashboard init failed.", error);
  showLogin();
  refreshIcons();
});
