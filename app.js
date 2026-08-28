const CONTRACT_STATUS_LABELS = {
  entwurf: "Läuft beim Kunden",
  daten_abgelehnt: "Rückfrage: Daten prüfen",
  intervall_abgelehnt: "Rückfrage: Intervall prüfen",
  datenschutz_abgelehnt: "Rückfrage: Datenschutz",
  signiert: "Signiert",
};

const REJECTED_CONTRACT_STATUSES = ["daten_abgelehnt", "intervall_abgelehnt", "datenschutz_abgelehnt"];

const state = {
  data: { customers: [], offers: [], contracts: [] },
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
  pendingSendOfferId: null,
  offerSendRecipientMode: "customer",
  contractFilters: {
    search: "",
    period: "all",
    sortKey: "customerName",
    sortDirection: "asc",
  },
  ftpBrowserPath: "",
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
  offersGroupToggle: document.querySelector("#contracts-group-toggle"),
  offersSubgroup: document.querySelector("#contracts-subgroup"),
  settingsGroupToggle: document.querySelector("#settings-group-toggle"),
  settingsSubgroup: document.querySelector("#settings-subgroup"),
  views: document.querySelectorAll(".view"),
  viewTitle: document.querySelector("#view-title"),
  sidebar: document.querySelector(".sidebar"),
  bottomMenuButton: document.querySelector("#bottom-menu-button"),
  mobileBackdrop: document.querySelector("#mobile-backdrop"),
  offerIntakePanel: document.querySelector("#offer-intake-panel"),
  offerReviewPanel: document.querySelector("#offer-review-panel"),
  offerForm: document.querySelector("#offer-form"),
  offerCustomerName: document.querySelector("#offer-customer-name"),
  offerContactPerson: document.querySelector("#offer-contact-person"),
  offerEmail: document.querySelector("#offer-email"),
  offerAddress: document.querySelector("#offer-address"),
  offerZip: document.querySelector("#offer-zip"),
  offerCity: document.querySelector("#offer-city"),
  offerSquareMeters: document.querySelector("#offer-square-meters"),
  offerInterval: document.querySelector("#offer-interval"),
  offerPrice: document.querySelector("#offer-price"),
  offerStartDate: document.querySelector("#offer-start-date"),
  offerValidityDays: document.querySelector("#offer-validity-days"),
  offerVat: document.querySelector("#offer-vat"),
  offerServiceText: document.querySelector("#offer-service-text"),
  offerObligationsText: document.querySelector("#offer-obligations-text"),
  offerReviewForm: document.querySelector("#offer-review-form"),
  offerReviewSummary: document.querySelector("#offer-review-summary"),
  offerServiceTextCorrected: document.querySelector("#offer-service-text-corrected"),
  offerObligationsTextCorrected: document.querySelector("#offer-obligations-text-corrected"),
  offerReviewBack: document.querySelector("#offer-review-back"),
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
  emailSettingsOfferEnabled: document.querySelector("#email-settings-offer-enabled"),
  emailSettingsContractEnabled: document.querySelector("#email-settings-contract-enabled"),
  emailSettingsInternalContractEnabled: document.querySelector("#email-settings-internal-contract-enabled"),
  emailPreviewCheckboxes: document.querySelectorAll(
    "#email-settings-offer-enabled-2, #email-settings-reminder1-enabled, #email-settings-reminder2-enabled, #email-settings-reminder3-enabled",
  ),
  emailPreviewContractCheckbox: document.querySelector("#email-settings-contract-enabled-2"),
  ftpSettingsForm: document.querySelector("#ftp-settings-form"),
  ftpEnabled: document.querySelector("#ftp-enabled"),
  ftpHost: document.querySelector("#ftp-host"),
  ftpPort: document.querySelector("#ftp-port"),
  ftpUseSsl: document.querySelector("#ftp-use-ssl"),
  ftpUsername: document.querySelector("#ftp-username"),
  ftpPassword: document.querySelector("#ftp-password"),
  ftpBasePath: document.querySelector("#ftp-base-path"),
  ftpPassiveMode: document.querySelector("#ftp-passive-mode"),
  ftpTestButton: document.querySelector("#ftp-test-button"),
  ftpBrowserBreadcrumb: document.querySelector("#ftp-browser-breadcrumb"),
  ftpBrowserList: document.querySelector("#ftp-browser-list"),
  ftpBrowserRefresh: document.querySelector("#ftp-browser-refresh"),
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
  emailSignatureUsageOptions: document.querySelectorAll("[data-email-signature-use]"),
  emailSignatureImagePreview: document.querySelector("#email-signature-image-preview"),
  emailSignatureImageInput: document.querySelector("#email-signature-image-input"),
  emailSignatureImageRemove: document.querySelector("#email-signature-image-remove"),
  emailSignatureImageStatus: document.querySelector("#email-signature-image-status"),
  emailSignaturePreview: document.querySelector("#email-signature-preview"),
  emailSignatureSaveStatus: document.querySelector("#email-signature-save-status"),
  mailboxSettingsForm: document.querySelector("#mailbox-settings-form"),
  mailboxSettingsPanel: document.querySelector("#mailbox-settings-panel"),
  mailboxHost: document.querySelector("#mailbox-host"),
  mailboxSmtpPort: document.querySelector("#mailbox-smtp-port"),
  mailboxSmtpEncryption: document.querySelector("#mailbox-smtp-encryption"),
  mailboxUsername: document.querySelector("#mailbox-username"),
  mailboxPassword: document.querySelector("#mailbox-password"),
  mailboxFromName: document.querySelector("#mailbox-from-name"),
  mailboxSignature: document.querySelector("#mailbox-signature"),
  contractNotifyForm: document.querySelector("#contract-notify-form"),
  contractNotifyEnabled: document.querySelector("#contract-notify-enabled"),
  contractNotifyEmails: document.querySelector("#contract-notify-emails"),
  contractNotifyAddEmail: document.querySelector("#contract-notify-add-email"),
  contractNotifyTest: document.querySelector("#contract-notify-test"),
  linkModal: document.querySelector("#link-modal"),
  linkModalInput: document.querySelector("#link-modal-input"),
  linkModalCopy: document.querySelector("#link-modal-copy"),
  linkModalClose: document.querySelector("#link-modal-close"),
  contractCorrectionModal: document.querySelector("#contract-correction-modal"),
  contractCorrectionForm: document.querySelector("#contract-correction-form"),
  contractCorrectionId: document.querySelector("#contract-correction-id"),
  contractCorrectionCustomerName: document.querySelector("#contract-correction-customer-name"),
  contractCorrectionContactPerson: document.querySelector("#contract-correction-contact-person"),
  contractCorrectionEmail: document.querySelector("#contract-correction-email"),
  contractCorrectionAddress: document.querySelector("#contract-correction-address"),
  contractCorrectionZip: document.querySelector("#contract-correction-zip"),
  contractCorrectionCity: document.querySelector("#contract-correction-city"),
  contractCorrectionSquareMeters: document.querySelector("#contract-correction-square-meters"),
  contractCorrectionInterval: document.querySelector("#contract-correction-interval"),
  contractCorrectionPrice: document.querySelector("#contract-correction-price"),
  contractCorrectionStartDate: document.querySelector("#contract-correction-start-date"),
  contractCorrectionVat: document.querySelector("#contract-correction-vat"),
  contractCorrectionServiceText: document.querySelector("#contract-correction-service-text"),
  contractCorrectionObligationsText: document.querySelector("#contract-correction-obligations-text"),
  contractCorrectionCancel: document.querySelector("#contract-correction-cancel"),
  offerEditModal: document.querySelector("#offer-edit-modal"),
  offerEditForm: document.querySelector("#offer-edit-form"),
  offerEditId: document.querySelector("#offer-edit-id"),
  offerEditCustomerName: document.querySelector("#offer-edit-customer-name"),
  offerEditContactPerson: document.querySelector("#offer-edit-contact-person"),
  offerEditEmail: document.querySelector("#offer-edit-email"),
  offerEditAddress: document.querySelector("#offer-edit-address"),
  offerEditZip: document.querySelector("#offer-edit-zip"),
  offerEditCity: document.querySelector("#offer-edit-city"),
  offerEditSquareMeters: document.querySelector("#offer-edit-square-meters"),
  offerEditInterval: document.querySelector("#offer-edit-interval"),
  offerEditPrice: document.querySelector("#offer-edit-price"),
  offerEditStartDate: document.querySelector("#offer-edit-start-date"),
  offerEditValidityDays: document.querySelector("#offer-edit-validity-days"),
  offerEditVat: document.querySelector("#offer-edit-vat"),
  offerEditServiceText: document.querySelector("#offer-edit-service-text"),
  offerEditObligationsText: document.querySelector("#offer-edit-obligations-text"),
  offerEditCancel: document.querySelector("#offer-edit-cancel"),
  logoPreview: document.querySelector("#logo-preview"),
  logoFileInput: document.querySelector("#logo-file-input"),
  logoRemove: document.querySelector("#logo-remove"),
  contractorSignaturePad: document.querySelector("#contractor-signature-pad"),
  contractorSignatureStatus: document.querySelector("#contractor-signature-status"),
  contractorSignatureClear: document.querySelector("#contractor-signature-clear"),
  contractorSignatureUploadInput: document.querySelector("#contractor-signature-upload-input"),
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
  metricOffers: document.querySelector("#metric-offers"),
  metricContracts: document.querySelector("#metric-contracts"),
  metricSigned: document.querySelector("#metric-signed"),
  metricFollowups: document.querySelector("#metric-followups"),
  recentOffers: document.querySelector("#recent-offers"),
  contractStatus: document.querySelector("#contract-status"),
};

const titles = {
  overview: "Übersicht",
  "offers-new": "Neuer Vertrag erstellen",
  "offers-saved": "Vertragsentwürfe",
  contracts: "Verträge",
  "settings-smtp": "SMTP-Server-Einstellungen",
  "settings-email": "E-Mail-Einstellungen",
  "settings-email-signature": "E-Mail-Signatur",
  "settings-notify": "Vertragsbenachrichtigungen-Einstellungen",
  "settings-logo": "Logo-Einstellungen",
  "settings-signature": "Signatur",
  "settings-users": "User & Rollen",
  "settings-ftp": "FTP",
  "settings-ftp-browser": "Ordner",
};

const hiddenViews = new Set(["settings-smtp"]);

const CURRENT_VIEW_STORAGE_KEY = "cleanteam-current-view";

function loadPersistedView() {
  try {
    const view = localStorage.getItem(CURRENT_VIEW_STORAGE_KEY);
    return Object.prototype.hasOwnProperty.call(titles, view) ? view : "overview";
  } catch (error) {
    return "overview";
  }
}

function persistCurrentView(view) {
  try {
    localStorage.setItem(CURRENT_VIEW_STORAGE_KEY, view);
  } catch (error) {
    // Kein Speicherzugriff (z. B. privater Modus) - Ansicht wird dann nicht gemerkt.
  }
}

let currentLogoUrl = null;
let emailSignatureImageUrl = "";
let pendingEmailSignatureImageFile = null;
let pendingEmailSignatureImageDataUrl = "";
let pendingEmailSignatureImageRemoval = false;
let contractorSignaturePadReady = false;
let contractorSignatureHasInk = false;
let contractorSignatureDrawing = false;
let contractorSignatureLastPoint = null;

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
const apiPatch = (path, body) => apiFetch(path, { method: "PATCH", body: JSON.stringify(body || {}) });
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

function formatDateTime(value) {
  if (!value) {
    return "";
  }

  return (
    new Intl.DateTimeFormat("de-DE", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    }).format(new Date(value)) + " Uhr"
  );
}

function todayAsInputValue() {
  const now = new Date();
  const offsetDate = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
  return offsetDate.toISOString().slice(0, 10);
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

function visibleSavedOffers() {
  const hiddenOfferIds = signedContractOfferIds();
  return state.data.offers.filter((offer) => !hiddenOfferIds.has(offer.id));
}



function getContract(id) {
  return state.data.contracts.find((contract) => contract.id === id);
}

function customerAddress(customer) {
  const street = [customer.address, customer.houseNumber].filter(Boolean).join(" ");
  const place = [customer.zip, customer.city].filter(Boolean).join(" ");
  return [street, place].filter(Boolean).join(", ");
}

function contactName(customer) {
  return [customer.salutation, customer.contactLastName].filter(Boolean).join(" ");
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
  switchView(loadPersistedView());
}

function setSettingsGroupExpanded(expanded) {
  els.settingsSubgroup.hidden = !expanded;
  els.settingsGroupToggle.setAttribute("aria-expanded", String(expanded));
}

function setOffersGroupExpanded(expanded) {
  els.offersSubgroup.hidden = !expanded;
  els.offersGroupToggle.setAttribute("aria-expanded", String(expanded));
}

function collapseNavGroups(except = null) {
  if (except !== "offers") {
    setOffersGroupExpanded(false);
  }
  if (except !== "settings") {
    setSettingsGroupExpanded(false);
  }
}

function toggleNavGroup(group) {
  const groups = {
    offers: [els.offersSubgroup, setOffersGroupExpanded],
    settings: [els.settingsSubgroup, setSettingsGroupExpanded],
  };
  const target = groups[group];
  if (!target) {
    return;
  }

  const [subgroup, setExpanded] = target;
  const shouldOpen = subgroup.hidden;
  collapseNavGroups(group);
  setExpanded(shouldOpen);
}

function switchView(view) {
  if (hiddenViews.has(view)) {
    view = "overview";
  }

  if (view.startsWith("settings-") && !isAdmin()) {
    showToast("Nur Admins können die Einstellungen öffnen.");
    view = "overview";
  }

  state.currentView = view;
  persistCurrentView(view);
  els.viewTitle.textContent = titles[view];

  els.navLinks.forEach((button) => {
    button.classList.toggle("active", button.dataset.view === view);
  });

  els.views.forEach((panel) => {
    panel.classList.toggle("active-view", panel.id === `${view}-view`);
  });

  const isSettingsView = view.startsWith("settings-");
  els.settingsGroupToggle.classList.toggle("active", isSettingsView);

  const isOfferView = view.startsWith("offers-") || view === "contracts";
  els.offersGroupToggle.classList.toggle("active", isOfferView);

  collapseNavGroups();

  closeMobileNav();

  if (view === "settings-smtp") {
    loadSmtpSettings();
  }

  if (view === "settings-email") {
    loadEmailSettings();
    loadMailboxSettings();
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

  if (view === "settings-ftp") {
    loadFtpSettings();
  }

  if (view === "settings-ftp-browser") {
    loadFtpBrowserPath(state.ftpBrowserPath || "");
  }

  if (view === "offers-new") {
    resetOfferIntake();
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
    const [customers, offers, contracts] = await Promise.all([
      apiGet("api/customers.php"),
      apiGet("api/offers.php"),
      apiGet("api/contracts.php"),
    ]);

    state.data.customers = customers;
    state.data.offers = offers;
    state.data.contracts = contracts;
    renderAll();
  } catch (error) {
    showToast(error.message);
  }
}

function renderAll() {
  renderMetrics();
  renderOffers();
  renderContracts();
  refreshIcons();
}

function renderMetrics() {
  const savedOffers = visibleSavedOffers();

  els.metricOffers.textContent = savedOffers.length;
  els.metricContracts.textContent = state.data.contracts.length;
  els.metricSigned.textContent = state.data.contracts.filter((contract) => contract.status === "signiert").length;
  els.metricFollowups.textContent = state.data.contracts.filter((contract) =>
    REJECTED_CONTRACT_STATUSES.includes(contract.status),
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
                <span>Erstellt am ${formatDate(offer.createdAt)}</span>
              </div>
              ${offer.price > 0 ? `<span class="badge">${formatCurrency(offer.price)}</span>` : ""}
            </article>
          `;
        })
        .join("")
    : `<div class="empty-state">Noch keine Vertragsentwürfe vorhanden.</div>`;

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

}

function changeCounter(button, direction) {
  const input = findCounterInput(button);
  if (!input) {
    return;
  }

  const step = Number(input.dataset.step ?? 1) || 1;
  updateCounterControl(input, (Number(input.value) || 0) + direction * step);
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













function completeOfferNotesValue(value, fallback = "nicht angegeben") {
  const text = String(value == null ? "" : value).trim();
  return text || fallback;
}

function completeOfferNotesMultiline(label, value) {
  const text = String(value || "").trim();
  if (!text) return [];
  return [`  ${label}:`, ...text.split(/\r?\n/).filter(Boolean).map((line) => `    ${line}`)];
}











function renderOffers() {
  const offers = [...visibleSavedOffers()].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));

  els.offerList.innerHTML = offers.length
    ? offers.map(renderOfferCard).join("")
    : `<div class="empty-state">Noch keine Vertragsentwürfe erstellt.</div>`;
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
  const resetLinkButton = `
    <button class="ghost-button" type="button" data-action="reset-contract-link" data-id="${escapeHtml(offer.id)}">
      <i data-lucide="rotate-ccw" aria-hidden="true"></i>
      Link zurücksetzen
    </button>
  `;

  return `
    <article class="record-item">
      <div class="record-main">
        <div>
          <div class="record-title">Firma: ${escapeHtml(offer.customer.name)}</div>
          <div class="record-meta">
            ${offer.squareMeters > 0 ? `<span>${offer.squareMeters} m²</span>` : ""}
            <span>Erstellt am ${formatDate(offer.createdAt)}${offer.startDate ? ` · Start ${formatDate(offer.startDate)}` : ""}</span>
            <span>${escapeHtml(sentLabel)}</span>
          </div>
        </div>
        <div class="record-side">
          ${offer.price > 0 ? `<span class="badge">${formatCurrency(offer.price)}</span>` : ""}
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
          Vertrag Vorschau
        </a>
        <button class="primary-button" type="button" data-action="send-offer" data-id="${escapeHtml(offer.id)}">
          <i data-lucide="send" aria-hidden="true"></i>
          Vertrag senden
        </button>
        ${contractProcessAction}
        <button class="secondary-button" type="button" data-action="copy-offer-link" data-id="${escapeHtml(offer.id)}">
          <i data-lucide="link" aria-hidden="true"></i>
          Link kopieren
        </button>
        ${contractActions}
        ${resetLinkButton}
        <button class="secondary-button" type="button" data-action="edit-offer" data-id="${escapeHtml(offer.id)}">
          <i data-lucide="pencil" aria-hidden="true"></i>
          Bearbeiten
        </button>
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
    : `<tr><td colspan="8" class="table-empty">Keine Verträge für diese Auswahl gefunden.</td></tr>`;
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
  return contract.customer.name;
}

function contractBadgeClass(status) {
  if (status === "signiert") {
    return "success";
  }
  if (REJECTED_CONTRACT_STATUSES.includes(status)) {
    return "danger";
  }
  return "warning";
}

function renderDeliveryStatus(offer) {
  const steps = [
    { done: Boolean(offer.sentAt), label: "Gesendet", at: offer.sentAt },
    {
      done: Boolean(offer.emailOpenedAt),
      label: "Geöffnet",
      at: offer.emailOpenedAt,
      hint: "Technisches Signal (Ladepixel). Kann auch durch Sicherheits-Scanner oder E-Mail-Anbieter ausgelöst werden, bevor ein Mensch die Mail gesehen hat – keine Garantie, dass sie wirklich gelesen wurde.",
    },
    { done: Boolean(offer.linkOpenedAt), label: "Vertrag geöffnet", at: offer.linkOpenedAt },
  ];

  return `
    <div class="delivery-status">
      <div class="delivery-icons">
        ${steps
          .map(
            (step) => `
              <span class="delivery-check${step.done ? " is-done" : ""}"${step.hint ? ` title="${escapeHtml(step.hint)}"` : ""} aria-hidden="true">
                <i data-lucide="check" aria-hidden="true"></i>
              </span>
            `
          )
          .join("")}
      </div>
      <div class="delivery-details">
        ${steps
          .map(
            (step) => `
              <div class="delivery-detail${step.done ? " is-done" : ""}${step.hint ? " has-hint" : ""}"${step.hint ? ` title="${escapeHtml(step.hint)}"` : ""}>
                ${escapeHtml(step.label)}: ${step.done ? escapeHtml(formatDateTime(step.at)) : "noch nicht"}
              </div>
            `
          )
          .join("")}
      </div>
    </div>
  `;
}

function renderContractRow(contract) {
  const selected = contract.id === state.selectedContractId ? " selected" : "";
  const badgeClass = contractBadgeClass(contract.status);
  const signedAt = contract.signedAt ? formatDate(contract.signedAt) : "Noch offen";
  const documentActions = contract.status === "signiert"
    ? `
        <a class="primary-button" href="contract.php?contractId=${encodeURIComponent(contract.id)}&document=cleanteam&format=pdf" target="_blank" rel="noopener">
          <i data-lucide="file-check-2" aria-hidden="true"></i>
          CleanTeam
        </a>
        <a class="secondary-button" href="contract.php?contractId=${encodeURIComponent(contract.id)}&document=customer&format=pdf" target="_blank" rel="noopener">
          <i data-lucide="file-text" aria-hidden="true"></i>
          Kunde
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
  const rejectionActions = REJECTED_CONTRACT_STATUSES.includes(contract.status)
    ? `
        <button class="secondary-button" type="button" data-action="correct-contract" data-id="${escapeHtml(contract.id)}">
          <i data-lucide="pencil" aria-hidden="true"></i>
          Daten korrigieren
        </button>
        <button class="secondary-button" type="button" data-action="release-contract" data-id="${escapeHtml(contract.id)}">
          <i data-lucide="unlock" aria-hidden="true"></i>
          Vertrag freigeben
        </button>
      `
    : "";

  return `
    <tr class="${selected}">
      <td>${escapeHtml(contract.customer.name)}</td>
      <td>${escapeHtml(contactName(contract.customer))}</td>
      <td>${escapeHtml(formatDate(contract.createdAt))}</td>
      <td>${escapeHtml(signedAt)}</td>
      <td><span class="badge ${badgeClass}">${escapeHtml(CONTRACT_STATUS_LABELS[contract.status] || contract.status)}</span></td>
      <td>
        <div class="table-actions">
          ${documentActions}
          ${authorizationButton}
          ${rejectionActions}
          <button class="ghost-button" type="button" data-action="delete-contract" data-id="${escapeHtml(contract.id)}">
            <i data-lucide="trash-2" aria-hidden="true"></i>
            Löschen
          </button>
        </div>
      </td>
      <td>${renderDeliveryStatus(contract.offer)}</td>
    </tr>
  `;
}

function resetOfferIntake() {
  els.offerForm.reset();
  els.offerReviewForm.reset();
  els.offerIntakePanel.hidden = false;
  els.offerReviewPanel.hidden = true;
}

async function handleOfferSubmit(event) {
  event.preventDefault();

  const customerName = els.offerCustomerName.value.trim();
  const contactPerson = els.offerContactPerson.value.trim();
  const email = els.offerEmail.value.trim();
  const address = els.offerAddress.value.trim();
  const zip = els.offerZip.value.trim();
  const city = els.offerCity.value.trim();
  const squareMeters = Number(els.offerSquareMeters.value) || 0;
  const interval = els.offerInterval.value;
  const price = Number(els.offerPrice.value);
  const startDate = els.offerStartDate.value;
  const validityDays = Number(els.offerValidityDays.value);
  const serviceText = els.offerServiceText.value.trim();
  const obligationsText = els.offerObligationsText.value.trim();

  if (!customerName) {
    showToast("Bitte den Namen des Kunden eintragen.");
    els.offerCustomerName.focus();
    return;
  }

  if (!contactPerson) {
    showToast("Bitte den Geschäftsführer / Inhaber eintragen.");
    els.offerContactPerson.focus();
    return;
  }

  if (!isValidEmail(email)) {
    showToast("Bitte eine gültige E-Mail-Adresse eintragen.");
    els.offerEmail.focus();
    return;
  }

  if (!address) {
    showToast("Bitte die Objektadresse (Straße und Hausnummer) eintragen.");
    els.offerAddress.focus();
    return;
  }

  if (!zip) {
    showToast("Bitte die Postleitzahl eintragen.");
    els.offerZip.focus();
    return;
  }

  if (!city) {
    showToast("Bitte den Ort eintragen.");
    els.offerCity.focus();
    return;
  }

  if (!interval) {
    showToast("Bitte ein Reinigungsintervall auswählen.");
    els.offerInterval.focus();
    return;
  }

  if (!Number.isFinite(price) || price <= 0) {
    showToast("Bitte den monatlichen Preis eintragen.");
    els.offerPrice.focus();
    return;
  }

  if (!startDate) {
    showToast("Bitte den Beginn der Dienstleistung eintragen.");
    els.offerStartDate.focus();
    return;
  }

  if (!serviceText) {
    showToast("Bitte die Leistungsbeschreibung eintragen.");
    els.offerServiceText.focus();
    return;
  }

  if (!Number.isFinite(validityDays) || validityDays <= 0) {
    showToast("Bitte eine gültige Anzahl an Tagen für die Gültigkeitsdauer eintragen.");
    els.offerValidityDays.focus();
    return;
  }

  try {
    const { text } = await apiPost("api/format-text.php", { text: serviceText });
    els.offerServiceTextCorrected.value = text;
    els.offerObligationsTextCorrected.value = obligationsText;
    els.offerReviewSummary.innerHTML = `
      <div class="record-lines">
        <span><strong>${escapeHtml(customerName)}</strong> · ${escapeHtml(contactPerson)}</span>
        <span>${escapeHtml(email)}</span>
        <span>${escapeHtml(address)}, ${escapeHtml(zip)} ${escapeHtml(city)}${squareMeters > 0 ? ` · ${squareMeters} m²` : ""} · ${escapeHtml(interval)}</span>
        <span>${formatCurrency(price)} netto monatlich · Beginn ${formatDate(startDate)} · ${els.offerVat.value === "yes" ? "zzgl. USt." : "ohne USt."} · Link gültig ${validityDays} Tage</span>
      </div>
    `;
    els.offerIntakePanel.hidden = true;
    els.offerReviewPanel.hidden = false;
    els.offerServiceTextCorrected.focus();
  } catch (error) {
    showToast(error.message);
  }
}

async function handleOfferReviewSubmit(event) {
  event.preventDefault();

  const payload = {
    customerName: els.offerCustomerName.value.trim(),
    contactPerson: els.offerContactPerson.value.trim(),
    email: els.offerEmail.value.trim(),
    address: els.offerAddress.value.trim(),
    zip: els.offerZip.value.trim(),
    city: els.offerCity.value.trim(),
    squareMeters: Number(els.offerSquareMeters.value) || 0,
    interval: els.offerInterval.value,
    price: Number(els.offerPrice.value),
    vatApplicable: els.offerVat.value === "yes",
    startDate: els.offerStartDate.value,
    validityDays: Number(els.offerValidityDays.value) || 14,
    serviceText: els.offerServiceTextCorrected.value.trim(),
    customerObligationsNote: els.offerObligationsTextCorrected.value.trim(),
  };

  try {
    await apiPost("api/offers.php", payload);
    resetOfferIntake();
    await loadAll();
    switchView("offers-saved");
    showToast("Vertrag wurde erstellt.");
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
    showToast("Vertrag wurde nicht gefunden.");
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
    showToast(`Vertrag wurde an ${result.sentTo || toEmail} versendet.`);
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
    showToast("Vertrag wurde nicht gefunden.");
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

function ensureSelectHasValue(selectEl, value) {
  const legacyOption = selectEl.querySelector('option[data-legacy-value="true"]');
  if (legacyOption) {
    legacyOption.remove();
  }

  if (!value) {
    return;
  }

  const hasMatch = Array.from(selectEl.options).some((option) => option.value === value);
  if (!hasMatch) {
    const option = document.createElement("option");
    option.value = value;
    option.textContent = value;
    option.dataset.legacyValue = "true";
    selectEl.insertBefore(option, selectEl.firstChild);
  }
}

function openContractCorrectionModal(id) {
  const contract = getContract(id);
  if (!contract) {
    showToast("Vertrag wurde nicht gefunden.");
    return;
  }

  els.contractCorrectionId.value = contract.id;
  els.contractCorrectionCustomerName.value = contract.customer.name;
  els.contractCorrectionContactPerson.value = contract.customer.contactLastName;
  els.contractCorrectionEmail.value = contract.customer.email;
  els.contractCorrectionAddress.value = contract.customer.address;
  els.contractCorrectionZip.value = contract.customer.zip;
  els.contractCorrectionCity.value = contract.customer.city;
  els.contractCorrectionSquareMeters.value = contract.offer.squareMeters || "";
  ensureSelectHasValue(els.contractCorrectionInterval, contract.offer.interval);
  els.contractCorrectionInterval.value = contract.offer.interval;
  els.contractCorrectionPrice.value = contract.offer.price;
  els.contractCorrectionVat.value = contract.offer.vatApplicable === false ? "no" : "yes";
  els.contractCorrectionStartDate.value = contract.offer.startDate || "";
  els.contractCorrectionServiceText.value = contract.offer.notes || "";
  els.contractCorrectionObligationsText.value = contract.offer.customerObligationsNote || "";
  els.contractCorrectionModal.hidden = false;
  els.contractCorrectionCustomerName.focus();
}

function closeContractCorrectionModal() {
  els.contractCorrectionModal.hidden = true;
  els.contractCorrectionForm.reset();
}

async function handleContractCorrectionSubmit(event) {
  event.preventDefault();

  const interval = els.contractCorrectionInterval.value;

  const id = els.contractCorrectionId.value;
  const payload = {
    action: "update-contact",
    customerName: els.contractCorrectionCustomerName.value.trim(),
    contactPerson: els.contractCorrectionContactPerson.value.trim(),
    email: els.contractCorrectionEmail.value.trim(),
    address: els.contractCorrectionAddress.value.trim(),
    zip: els.contractCorrectionZip.value.trim(),
    city: els.contractCorrectionCity.value.trim(),
    squareMeters: Number(els.contractCorrectionSquareMeters.value) || 0,
    interval,
    price: Number(els.contractCorrectionPrice.value),
    vatApplicable: els.contractCorrectionVat.value === "yes",
    startDate: els.contractCorrectionStartDate.value,
    serviceText: els.contractCorrectionServiceText.value.trim(),
    customerObligationsNote: els.contractCorrectionObligationsText.value.trim(),
  };

  if (!payload.startDate) {
    showToast("Bitte den Beginn der Dienstleistung eintragen.");
    els.contractCorrectionStartDate.focus();
    return;
  }

  try {
    await apiPatch(`api/contracts.php?id=${encodeURIComponent(id)}`, payload);
    closeContractCorrectionModal();
    await loadAll();
    showToast("Daten wurden aktualisiert.");
  } catch (error) {
    showToast(error.message);
  }
}

function openOfferEditModal(id) {
  const offer = getOffer(id);
  if (!offer) {
    showToast("Vertragsentwurf wurde nicht gefunden.");
    return;
  }

  els.offerEditId.value = offer.id;
  els.offerEditCustomerName.value = offer.customer.name;
  els.offerEditContactPerson.value = offer.customer.contactLastName;
  els.offerEditEmail.value = offer.customer.email;
  els.offerEditAddress.value = offer.customer.address;
  els.offerEditZip.value = offer.customer.zip;
  els.offerEditCity.value = offer.customer.city;
  els.offerEditSquareMeters.value = offer.squareMeters || "";
  ensureSelectHasValue(els.offerEditInterval, offer.interval);
  els.offerEditInterval.value = offer.interval;
  els.offerEditPrice.value = offer.price;
  els.offerEditVat.value = offer.vatApplicable === false ? "no" : "yes";
  els.offerEditStartDate.value = offer.startDate || "";
  els.offerEditValidityDays.value = offer.validityDays || 14;
  els.offerEditServiceText.value = offer.notes || "";
  els.offerEditObligationsText.value = offer.customerObligationsNote || "";
  els.offerEditModal.hidden = false;
  els.offerEditCustomerName.focus();
}

function closeOfferEditModal() {
  els.offerEditModal.hidden = true;
  els.offerEditForm.reset();
}

async function handleOfferEditSubmit(event) {
  event.preventDefault();

  const interval = els.offerEditInterval.value;
  const validityDays = Number(els.offerEditValidityDays.value);
  if (!Number.isFinite(validityDays) || validityDays <= 0) {
    showToast("Bitte eine gültige Anzahl an Tagen für die Gültigkeitsdauer eintragen.");
    els.offerEditValidityDays.focus();
    return;
  }

  const id = els.offerEditId.value;
  const payload = {
    customerName: els.offerEditCustomerName.value.trim(),
    contactPerson: els.offerEditContactPerson.value.trim(),
    email: els.offerEditEmail.value.trim(),
    address: els.offerEditAddress.value.trim(),
    zip: els.offerEditZip.value.trim(),
    city: els.offerEditCity.value.trim(),
    squareMeters: Number(els.offerEditSquareMeters.value) || 0,
    interval,
    price: Number(els.offerEditPrice.value),
    vatApplicable: els.offerEditVat.value === "yes",
    startDate: els.offerEditStartDate.value,
    validityDays,
    serviceText: els.offerEditServiceText.value.trim(),
    customerObligationsNote: els.offerEditObligationsText.value.trim(),
  };

  if (!payload.startDate) {
    showToast("Bitte den Beginn der Dienstleistung eintragen.");
    els.offerEditStartDate.focus();
    return;
  }

  try {
    await apiPut(`api/offers.php?id=${encodeURIComponent(id)}`, payload);
    closeOfferEditModal();
    await loadAll();
    showToast("Vertragsentwurf wurde aktualisiert.");
  } catch (error) {
    showToast(error.message);
  }
}

async function releaseContract(id) {
  const contract = getContract(id);
  if (!contract) {
    return;
  }

  const confirmed = window.confirm(`Vertrag von "${contract.customer.name}" wieder freigeben, damit der Kunde ihn erneut abschließen kann?`);
  if (!confirmed) {
    return;
  }

  try {
    await apiPatch(`api/contracts.php?id=${encodeURIComponent(id)}`, { action: "release" });
    await loadAll();
    showToast("Vertrag wurde freigegeben.");
  } catch (error) {
    showToast(error.message);
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


async function deleteOffer(id) {
  const confirmed = window.confirm("Vertragsentwurf löschen? Ein bereits gestarteter Vertrag wird ebenfalls entfernt.");
  if (!confirmed) {
    return;
  }

  try {
    await apiDelete(`api/offers.php?id=${encodeURIComponent(id)}`);
    if (state.selectedContractId && !state.data.contracts.some((contract) => contract.id === state.selectedContractId)) {
      state.selectedContractId = null;
    }
    await loadAll();
    showToast("Vertragsentwurf wurde gelöscht.");
  } catch (error) {
    showToast(error.message);
  }
}

async function resetContractLink(offerId) {
  const offer = getOffer(offerId);
  const confirmed = window.confirm(
    "Neuen Link für diesen Vertrag erstellen? Der bisherige Link wird dabei ungültig, ein eventueller Fortschritt geht verloren.",
  );
  if (!confirmed) {
    return;
  }

  try {
    if (offer?.contractId) {
      await apiDelete(`api/contracts.php?id=${encodeURIComponent(offer.contractId)}`);
      if (state.selectedContractId === offer.contractId) {
        state.selectedContractId = null;
      }
    } else {
      await apiPatch(`api/offers.php?id=${encodeURIComponent(offerId)}`, { action: "reset-link" });
    }
    await loadAll();
    showToast("Neuer Token wurde erstellt.");
  } catch (error) {
    showToast(error.message);
  }
}

async function deleteContract(id) {
  const contract = getContract(id);
  const contractLabel = contract
    ? `von ${contract.customer.name}`
    : "diesen Vertrag";
  const firstConfirmed = window.confirm(
    `Vertrag ${contractLabel} wirklich l\u00f6schen? Die gespeicherten Vertragsdokumente werden ebenfalls entfernt.`,
  );
  if (!firstConfirmed) {
    return;
  }

  const finalConfirmed = window.confirm(
    "Letzte Nachfrage: Vertrag endg\u00fcltig l\u00f6schen? Danach kann aus dem Vertragsentwurf ein neuer Vertrag erstellt werden.",
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
    showToast("Vertrag wurde gel\u00f6scht. Der Vertragsentwurf ist wieder f\u00fcr einen neuen Vertrag frei.");
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
    els.emailSettingsOfferEnabled.checked = settings.offerEmailsEnabled;
    els.emailSettingsContractEnabled.checked = settings.contractEmailsEnabled;
    els.emailSettingsInternalContractEnabled.checked = settings.internalContractNotificationsEnabled;
    els.emailPreviewCheckboxes.forEach((checkbox) => {
      checkbox.checked = settings.offerEmailsEnabled;
    });
    els.emailPreviewContractCheckbox.checked = settings.contractEmailsEnabled;
  } catch (error) {
    showToast(error.message);
  }
}

async function handleEmailSettingsSubmit(event) {
  event.preventDefault();

  const payload = {
    offerEmailsEnabled: els.emailSettingsOfferEnabled.checked,
    contractEmailsEnabled: els.emailSettingsContractEnabled.checked,
    internalContractNotificationsEnabled: els.emailSettingsInternalContractEnabled.checked,
  };

  try {
    await apiPost("api/email-settings.php", payload);
    showToast("E-Mail-Einstellungen wurden gespeichert.");
    await loadEmailSettings();
  } catch (error) {
    showToast(error.message);
  }
}

async function loadFtpSettings() {
  try {
    const settings = await apiGet("api/ftp-settings.php");
    els.ftpEnabled.checked = settings.enabled;
    els.ftpHost.value = settings.host || "";
    els.ftpPort.value = settings.port || 21;
    els.ftpUseSsl.value = settings.useSsl ? "1" : "0";
    els.ftpUsername.value = settings.username || "";
    els.ftpPassword.value = "";
    els.ftpPassword.placeholder = settings.hasPassword
      ? "Unverändert lassen = altes Passwort behalten"
      : "Passwort eingeben";
    els.ftpBasePath.value = settings.basePath || "";
    els.ftpPassiveMode.checked = settings.passiveMode;
  } catch (error) {
    showToast(error.message);
  }
}

async function handleFtpSettingsSubmit(event) {
  event.preventDefault();

  const payload = {
    enabled: els.ftpEnabled.checked,
    host: els.ftpHost.value.trim(),
    port: Number(els.ftpPort.value) || 21,
    useSsl: els.ftpUseSsl.value === "1",
    username: els.ftpUsername.value.trim(),
    password: els.ftpPassword.value,
    basePath: els.ftpBasePath.value.trim(),
    passiveMode: els.ftpPassiveMode.checked,
  };

  try {
    await apiPost("api/ftp-settings.php", payload);
    showToast("FTP-Einstellungen wurden gespeichert.");
    await loadFtpSettings();
  } catch (error) {
    showToast(error.message);
  }
}

async function testFtpConnection() {
  try {
    await apiPost("api/ftp-settings.php?action=test", {});
    showToast("FTP-Verbindung erfolgreich.");
  } catch (error) {
    showToast(error.message);
  }
}

function ftpBrowserFileIcon(name) {
  return name.toLowerCase().endsWith(".pdf") ? "file-text" : "file";
}

function renderFtpBrowserBreadcrumb(path) {
  const segments = path ? path.split("/").filter(Boolean) : [];
  let accumulated = "";
  const crumbs = [`<button type="button" data-path="">Cleanteam Verträge</button>`];

  segments.forEach((segment) => {
    accumulated = accumulated ? `${accumulated}/${segment}` : segment;
    crumbs.push(`<span>/</span><button type="button" data-path="${escapeHtml(accumulated)}">${escapeHtml(segment)}</button>`);
  });

  els.ftpBrowserBreadcrumb.innerHTML = crumbs.join(" ");
  els.ftpBrowserBreadcrumb.querySelectorAll("button").forEach((button) => {
    button.addEventListener("click", () => loadFtpBrowserPath(button.dataset.path || ""));
  });
}

function renderFtpBrowserList(items, path) {
  if (items.length === 0) {
    els.ftpBrowserList.innerHTML = `<div class="empty-state">Dieser Ordner ist leer.</div>`;
    return;
  }

  els.ftpBrowserList.innerHTML = items
    .map((item) => {
      const itemPath = path ? `${path}/${item.name}` : item.name;
      if (item.type === "dir") {
        return `
          <div class="ftp-browser-row is-dir">
            <button type="button" class="ftp-browser-name" data-path="${escapeHtml(itemPath)}" data-type="dir">
              <i data-lucide="folder" aria-hidden="true"></i>
              ${escapeHtml(item.name)}
            </button>
          </div>
        `;
      }

      const downloadUrl = `api/ftp-browse.php?action=download&path=${encodeURIComponent(itemPath)}`;
      return `
        <div class="ftp-browser-row">
          <button type="button" class="ftp-browser-name" data-path="${escapeHtml(itemPath)}" data-type="file">
            <i data-lucide="${ftpBrowserFileIcon(item.name)}" aria-hidden="true"></i>
            ${escapeHtml(item.name)}
          </button>
          <span class="ftp-browser-meta">${item.modified ? escapeHtml(item.modified) : ""}</span>
          <a class="ghost-button" href="${downloadUrl}&mode=attachment" target="_blank" rel="noopener" title="Herunterladen">
            <i data-lucide="download" aria-hidden="true"></i>
          </a>
        </div>
      `;
    })
    .join("");

  els.ftpBrowserList.querySelectorAll("button.ftp-browser-name").forEach((button) => {
    button.addEventListener("click", () => {
      if (button.dataset.type === "dir") {
        loadFtpBrowserPath(button.dataset.path);
      } else {
        window.open(`api/ftp-browse.php?action=download&path=${encodeURIComponent(button.dataset.path)}`, "_blank");
      }
    });
  });

  refreshIcons();
}

async function loadFtpBrowserPath(path) {
  state.ftpBrowserPath = path || "";
  els.ftpBrowserList.innerHTML = `<div class="empty-state">Lade…</div>`;
  renderFtpBrowserBreadcrumb(state.ftpBrowserPath);

  try {
    const result = await apiGet(`api/ftp-browse.php?action=list&path=${encodeURIComponent(state.ftpBrowserPath)}`);
    renderFtpBrowserList(result.items || [], state.ftpBrowserPath);
  } catch (error) {
    els.ftpBrowserList.innerHTML = `<div class="empty-state">${escapeHtml(error.message)}</div>`;
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

async function loadMailboxSettings() {
  try {
    const settings = await apiGet("api/mailbox.php?action=settings");
    const canManageSettings = Boolean(settings.canManageSettings ?? isAdmin());

    if (els.mailboxSettingsPanel) {
      els.mailboxSettingsPanel.hidden = !canManageSettings;
    }

    els.mailboxHost.value = settings.host || "";
    els.mailboxSmtpPort.value = settings.smtpPort || 587;
    els.mailboxSmtpEncryption.value = settings.smtpEncryption || "tls";
    els.mailboxUsername.value = settings.username || "";
    els.mailboxPassword.value = "";
    els.mailboxPassword.placeholder = settings.hasPassword
      ? "Unverändert lassen = altes Passwort behalten"
      : "Noch kein Passwort hinterlegt";
    els.mailboxFromName.value = settings.fromName || "CleanTeam";
    els.mailboxSignature.value = settings.signature || "";
  } catch (error) {
    showToast(error.message);
  }
}

async function handleMailboxSettingsSubmit(event) {
  event.preventDefault();

  const payload = {
    host: els.mailboxHost.value.trim(),
    smtpPort: Number(els.mailboxSmtpPort.value),
    smtpEncryption: els.mailboxSmtpEncryption.value,
    username: els.mailboxUsername.value.trim(),
    password: els.mailboxPassword.value,
    fromName: els.mailboxFromName.value.trim(),
    signature: els.mailboxSignature.value,
  };

  try {
    await apiPost("api/mailbox.php?action=settings", payload);
    showToast("Versand-Konto wurde gespeichert.");
    await loadMailboxSettings();
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

function handleContractorSignatureUpload(file) {
  if (!file) {
    return;
  }

  if (!file.type.startsWith("image/")) {
    showToast("Bitte eine Bilddatei auswählen (JPG oder PNG).");
    return;
  }

  const canvas = els.contractorSignaturePad;
  const context = contractorSignatureContext();
  if (!canvas || !context) {
    return;
  }

  const reader = new FileReader();
  reader.onload = () => {
    const image = new Image();
    image.onload = () => {
      context.clearRect(0, 0, canvas.width, canvas.height);
      const scale = Math.min(canvas.width / image.width, canvas.height / image.height, 1);
      const width = image.width * scale;
      const height = image.height * scale;
      context.drawImage(image, (canvas.width - width) / 2, (canvas.height - height) / 2, width, height);
      contractorSignatureHasInk = true;
      updateContractorSignatureStatus("Neues Bild geladen. Zum Übernehmen speichern.");
    };
    image.onerror = () => {
      showToast("Bild konnte nicht gelesen werden. Bitte eine andere Datei wählen.");
    };
    image.src = reader.result;
  };
  reader.onerror = () => {
    showToast("Bild konnte nicht gelesen werden. Bitte eine andere Datei wählen.");
  };
  reader.readAsDataURL(file);
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
              <button class="ghost-button" type="button" data-action="delete-user" data-id="${escapeHtml(user.id)}" ${Number(user.id) === Number(state.currentUserId) ? "disabled" : ""}>
                <i data-lucide="trash-2" aria-hidden="true"></i>
                Löschen
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
    els.userPassword.type = "password";
    const toggleIcon = els.userForm.querySelector('[data-action="toggle-user-password"] i');
    if (toggleIcon) {
      toggleIcon.setAttribute("data-lucide", "eye");
      refreshIcons();
    }
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

async function deleteUser(id) {
  const user = state.users.find((candidate) => Number(candidate.id) === Number(id));
  const confirmed = window.confirm(`User "${user?.email || id}" wirklich löschen?`);
  if (!confirmed) {
    return;
  }

  try {
    await apiDelete(`api/users.php?id=${encodeURIComponent(id)}`);
    await loadUsers();
    showToast("User wurde gelöscht.");
  } catch (error) {
    showToast(error.message);
  }
}

function toggleUserPasswordVisibility(button) {
  const wrapper = button.closest(".password-reveal");
  const input = wrapper?.querySelector("input");
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

  const deleteButton = event.target.closest('[data-action="delete-user"]');
  if (deleteButton && !deleteButton.disabled) {
    deleteUser(deleteButton.dataset.id);
    return;
  }

  const toggleButton = event.target.closest('[data-action="toggle-user-password"]');
  if (toggleButton) {
    toggleUserPasswordVisibility(toggleButton);
  }
}




function handleDashboardAction(event) {
  const button = event.target.closest("[data-action]");
  if (!button) {
    return;
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

  if (action === "reset-contract-link") {
    resetContractLink(id);
  }

  if (action === "delete-offer") {
    deleteOffer(id);
  }

  if (action === "edit-offer") {
    openOfferEditModal(id);
  }

  if (action === "delete-contract") {
    deleteContract(id);
  }

  if (action === "correct-contract") {
    openContractCorrectionModal(id);
  }

  if (action === "release-contract") {
    releaseContract(id);
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
      switchView(button.dataset.view);
    });
  });

  els.offersGroupToggle.addEventListener("click", () => {
    toggleNavGroup("offers");
  });

  els.settingsGroupToggle.addEventListener("click", () => {
    if (!isAdmin()) {
      showToast("Nur Admins können die Einstellungen öffnen.");
      return;
    }
    toggleNavGroup("settings");
  });

  els.bottomMenuButton.addEventListener("click", openMobileNav);
  els.mobileBackdrop.addEventListener("click", closeMobileNav);
  document.querySelectorAll("[data-overview-target]").forEach((button) => {
    button.addEventListener("click", () => {
      switchView(button.dataset.overviewTarget);
    });
  });
  document.addEventListener("click", handleDashboardAction);

  els.offerForm.addEventListener("submit", handleOfferSubmit);
  els.offerReviewForm.addEventListener("submit", handleOfferReviewSubmit);
  els.offerReviewBack.addEventListener("click", () => {
    els.offerIntakePanel.hidden = false;
    els.offerReviewPanel.hidden = true;
    els.offerCustomerName.focus();
  });
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
  if (els.ftpSettingsForm) {
    els.ftpSettingsForm.addEventListener("submit", handleFtpSettingsSubmit);
  }
  if (els.ftpTestButton) {
    els.ftpTestButton.addEventListener("click", testFtpConnection);
  }
  if (els.ftpBrowserRefresh) {
    els.ftpBrowserRefresh.addEventListener("click", () => loadFtpBrowserPath(state.ftpBrowserPath));
  }
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

  els.mailboxSettingsForm.addEventListener("submit", handleMailboxSettingsSubmit);

  els.logoFileInput.addEventListener("change", handleLogoUpload);
  els.logoRemove.addEventListener("click", handleLogoRemove);
  els.contractorSignatureClear.addEventListener("click", () => {
    clearContractorSignaturePad("Zeichenfläche ist leer. Neue Unterschrift zeichnen und speichern.");
  });
  els.contractorSignatureUploadInput.addEventListener("change", (event) => {
    handleContractorSignatureUpload(event.target.files[0]);
    event.target.value = "";
  });
  els.contractorSignatureSave.addEventListener("click", handleContractorSignatureSave);
  els.contractorSignatureRemove.addEventListener("click", handleContractorSignatureRemove);
  els.userForm.addEventListener("submit", handleUserSubmit);
  els.userForm.addEventListener("click", (event) => {
    const toggleButton = event.target.closest('[data-action="toggle-user-password"]');
    if (toggleButton) {
      toggleUserPasswordVisibility(toggleButton);
    }
  });
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

  els.contractCorrectionForm.addEventListener("submit", handleContractCorrectionSubmit);
  els.contractCorrectionCancel.addEventListener("click", closeContractCorrectionModal);
  els.contractCorrectionModal.addEventListener("click", (event) => {
    if (event.target === els.contractCorrectionModal) {
      closeContractCorrectionModal();
    }
  });

  els.offerEditForm.addEventListener("submit", handleOfferEditSubmit);
  els.offerEditCancel.addEventListener("click", closeOfferEditModal);

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !els.offerSendModal.hidden) {
      closeOfferSendModal();
      return;
    }
    if (event.key === "Escape" && !els.linkModal.hidden) {
      closeLinkModal();
      return;
    }
    if (event.key === "Escape" && !els.contractCorrectionModal.hidden) {
      closeContractCorrectionModal();
      return;
    }
    if (event.key === "Escape" && !els.offerEditModal.hidden) {
      closeOfferEditModal();
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
