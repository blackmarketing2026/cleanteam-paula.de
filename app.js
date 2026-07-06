const STORAGE_KEY = "cleanteam-dashboard-v1";
const SESSION_KEY = "cleanteam-session";

const TEST_USER = {
  email: "demo@cleanteam.de",
  password: "CleanTeam2026!",
};

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

const initialData = {
  customers: [
    {
      id: "customer-1",
      name: "Musterbau GmbH",
      email: "kontakt@musterbau.de",
      phone: "+49 711 245678",
      salutation: "Frau",
      contactLastName: "Schneider",
      address: "Königstraße",
      houseNumber: "18",
      zip: "70173",
      city: "Stuttgart",
      createdAt: "2026-07-01T10:10:00.000Z",
    },
    {
      id: "customer-2",
      name: "Praxis am Park",
      email: "verwaltung@praxis-park.de",
      phone: "+49 7153 808080",
      salutation: "Herr",
      contactLastName: "Weber",
      address: "Parkallee",
      houseNumber: "7",
      zip: "73728",
      city: "Esslingen",
      createdAt: "2026-07-02T08:30:00.000Z",
    },
  ],
  offers: [],
  contracts: [],
};

const state = {
  data: loadData(),
  currentView: "overview",
  selectedContractId: null,
  signatureHasInk: false,
};

const els = {
  loginScreen: document.querySelector("#login-screen"),
  appShell: document.querySelector("#app-shell"),
  loginForm: document.querySelector("#login-form"),
  loginEmail: document.querySelector("#login-email"),
  loginPassword: document.querySelector("#login-password"),
  loginError: document.querySelector("#login-error"),
  logoutButton: document.querySelector("#logout-button"),
  navLinks: document.querySelectorAll(".nav-link"),
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
  signatureArea: document.querySelector("#signature-area"),
  signaturePad: document.querySelector("#signature-pad"),
  clearSignature: document.querySelector("#clear-signature"),
  saveSignature: document.querySelector("#save-signature"),
  printContract: document.querySelector("#print-contract"),
  toast: document.querySelector("#toast"),
  metricCustomers: document.querySelector("#metric-customers"),
  metricOffers: document.querySelector("#metric-offers"),
  metricContracts: document.querySelector("#metric-contracts"),
  metricSigned: document.querySelector("#metric-signed"),
  recentOffers: document.querySelector("#recent-offers"),
  contractStatus: document.querySelector("#contract-status"),
};

const titles = {
  overview: "Übersicht",
  customers: "Kundenverwaltung",
  offers: "Angebotserstellung",
  contracts: "Verträge & Signatur",
};

function deepClone(value) {
  return JSON.parse(JSON.stringify(value));
}

function loadData() {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (!saved) {
      return deepClone(initialData);
    }

    const parsed = JSON.parse(saved);
    return {
      customers: Array.isArray(parsed.customers) ? parsed.customers : [],
      offers: Array.isArray(parsed.offers) ? parsed.offers : [],
      contracts: Array.isArray(parsed.contracts) ? parsed.contracts : [],
    };
  } catch (error) {
    return deepClone(initialData);
  }
}

function saveData() {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state.data));
  } catch (error) {
    showToast("Hinweis: Die Demo-Daten koennen in dieser Browseransicht nicht dauerhaft gespeichert werden.");
  }
}

function rememberSession() {
  try {
    sessionStorage.setItem(SESSION_KEY, "true");
  } catch (error) {
    // The dashboard can still run for the current page view.
  }
}

function clearSession() {
  try {
    sessionStorage.removeItem(SESSION_KEY);
  } catch (error) {
    // Some embedded previews block session storage.
  }
}

function hasSession() {
  try {
    return sessionStorage.getItem(SESSION_KEY) === "true";
  } catch (error) {
    return false;
  }
}

function createId(prefix) {
  if (window.crypto && typeof window.crypto.randomUUID === "function") {
    return `${prefix}-${window.crypto.randomUUID()}`;
  }

  return `${prefix}-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

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

function getOffer(id) {
  return state.data.offers.find((offer) => offer.id === id);
}

function getContract(id) {
  return state.data.contracts.find((contract) => contract.id === id);
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

function customerSnapshot(customer) {
  return {
    id: customer.id,
    name: customer.name,
    email: customer.email,
    phone: customer.phone,
    salutation: customer.salutation,
    contactLastName: customer.contactLastName,
    address: customer.address,
    houseNumber: customer.houseNumber,
    zip: customer.zip,
    city: customer.city,
  };
}

function offerSnapshot(offer) {
  return {
    id: offer.id,
    squareMeters: offer.squareMeters,
    interval: offer.interval,
    service: offer.service,
    startDate: offer.startDate,
    notes: offer.notes,
    price: offer.price,
    createdAt: offer.createdAt,
  };
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
  renderAll();
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

  closeMobileNav();
  renderAll();
}

function openMobileNav() {
  els.sidebar.classList.add("open");
  els.mobileBackdrop.hidden = false;
}

function closeMobileNav() {
  els.sidebar.classList.remove("open");
  els.mobileBackdrop.hidden = true;
}

function renderAll() {
  renderMetrics();
  renderCustomerOptions();
  renderCustomers();
  renderOffers();
  renderContracts();
  updateOfferPreview();
  refreshIcons();
}

function renderMetrics() {
  els.metricCustomers.textContent = state.data.customers.length;
  els.metricOffers.textContent = state.data.offers.length;
  els.metricContracts.textContent = state.data.contracts.length;
  els.metricSigned.textContent = state.data.contracts.filter((contract) => contract.status === "Signiert").length;

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
    : `<div class="empty-state">Noch keine Angebote vorhanden.</div>`;

  const openContracts = state.data.contracts.filter((contract) => contract.status !== "Signiert").length;
  const signedContracts = state.data.contracts.filter((contract) => contract.status === "Signiert").length;
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
        <span class="badge">${escapeHtml(customer.city)}</span>
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
          Angebot
        </button>
        <button class="ghost-button" type="button" data-action="delete-customer" data-id="${escapeHtml(customer.id)}">
          <i data-lucide="trash-2" aria-hidden="true"></i>
          Löschen
        </button>
      </div>
    </article>
  `;
}

function renderOffers() {
  const offers = [...state.data.offers].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));

  els.offerList.innerHTML = offers.length
    ? offers.map(renderOfferCard).join("")
    : `<div class="empty-state">Noch keine Angebote erstellt.</div>`;
}

function renderOfferCard(offer) {
  const contract = state.data.contracts.find((item) => item.offerId === offer.id);
  const contractButton = contract
    ? `
      <button class="secondary-button" type="button" data-action="open-contract" data-id="${escapeHtml(contract.id)}">
        <i data-lucide="signature" aria-hidden="true"></i>
        Vertrag öffnen
      </button>
    `
    : `
      <button class="primary-button" type="button" data-action="create-contract" data-id="${escapeHtml(offer.id)}">
        <i data-lucide="file-signature" aria-hidden="true"></i>
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
          </div>
        </div>
        <span class="badge">${formatCurrency(offer.price)}</span>
      </div>
      <div class="record-lines">
        <span>${escapeHtml(contactName(offer.customer))}</span>
        <span>${escapeHtml(customerAddress(offer.customer))}</span>
      </div>
      <div class="record-actions">
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
  const contracts = [...state.data.contracts].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));

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

function renderContractCard(contract) {
  const selected = contract.id === state.selectedContractId ? " selected" : "";
  const badgeClass = contract.status === "Signiert" ? "success" : "warning";

  return `
    <article class="record-item${selected}">
      <div class="record-main">
        <div>
          <div class="record-title">${escapeHtml(contract.number)}</div>
          <div class="record-meta">
            <span>${escapeHtml(contract.customer.name)}</span>
            <span>${escapeHtml(contract.offer.service)} · ${contract.offer.squareMeters} m²</span>
          </div>
        </div>
        <span class="badge ${badgeClass}">${escapeHtml(contract.status)}</span>
      </div>
      <div class="record-actions">
        <button class="secondary-button" type="button" data-action="select-contract" data-id="${escapeHtml(contract.id)}">
          <i data-lucide="eye" aria-hidden="true"></i>
          Anzeigen
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
  clearSignaturePad();

  if (!contract) {
    els.contractPreview.className = "contract-preview empty-state";
    els.contractPreview.textContent = "Noch kein Vertrag ausgewählt.";
    els.signatureArea.hidden = true;
    els.printContract.disabled = true;
    return;
  }

  els.contractPreview.className = "contract-preview";
  els.printContract.disabled = false;
  els.signatureArea.hidden = contract.status === "Signiert";
  els.contractPreview.innerHTML = renderContractDocument(contract);
  refreshIcons();
}

function renderContractDocument(contract) {
  const signedBlock =
    contract.status === "Signiert"
      ? `
        <section class="signature-box">
          <strong>Digitale Signatur des Kunden</strong>
          <img src="${contract.signatureDataUrl}" alt="Digitale Signatur" />
          <span>Signiert am ${formatDate(contract.signedAt)}</span>
        </section>
      `
      : `
        <section class="signature-box">
          <strong>Digitale Signatur des Kunden</strong>
          <span>Der Vertrag ist vorbereitet und wartet auf die Online-Signatur.</span>
        </section>
      `;

  const notes = contract.offer.notes
    ? `<p><strong>Besondere Vereinbarungen:</strong> ${escapeHtml(contract.offer.notes)}</p>`
    : "";

  return `
    <div class="contract-document">
      <header>
        <div>
          <span class="doc-brand">CleanTeam</span>
          <h3>Reinigungsvertrag</h3>
          <p class="muted">Vertragsnummer ${escapeHtml(contract.number)}</p>
        </div>
        <span class="badge ${contract.status === "Signiert" ? "success" : "warning"}">${escapeHtml(contract.status)}</span>
      </header>

      <section>
        <h4>Vertragspartner</h4>
        <dl>
          <dt>Kunde</dt>
          <dd>${escapeHtml(contract.customer.name)}</dd>
          <dt>Ansprechpartner</dt>
          <dd>${escapeHtml(contactName(contract.customer))}</dd>
          <dt>E-Mail</dt>
          <dd>${escapeHtml(contract.customer.email)}</dd>
          <dt>Telefon</dt>
          <dd>${escapeHtml(contract.customer.phone)}</dd>
          <dt>Adresse</dt>
          <dd>${escapeHtml(customerAddress(contract.customer))}</dd>
        </dl>
      </section>

      <section>
        <h4>Leistungsumfang</h4>
        <dl>
          <dt>Leistung</dt>
          <dd>${escapeHtml(contract.offer.service)}</dd>
          <dt>Fläche</dt>
          <dd>${contract.offer.squareMeters} m²</dd>
          <dt>Intervall</dt>
          <dd>${escapeHtml(contract.offer.interval)}</dd>
          <dt>Startdatum</dt>
          <dd>${formatDate(contract.offer.startDate)}</dd>
          <dt>Netto-Betrag</dt>
          <dd>${formatCurrency(contract.offer.price)}</dd>
        </dl>
        ${notes}
      </section>

      <section>
        <h4>Vereinbarung</h4>
        <p>
          CleanTeam übernimmt die oben beschriebene Reinigungsleistung gemäß Angebot.
          Leistungszeiten, Zugang und Objektbesonderheiten werden vor Leistungsbeginn abgestimmt.
          Alle Preise verstehen sich netto zuzüglich gesetzlicher Umsatzsteuer.
        </p>
      </section>

      ${signedBlock}
    </div>
  `;
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

function handleCustomerSubmit(event) {
  event.preventDefault();

  const id = els.customerId.value;
  const payload = {
    id: id || createId("customer"),
    name: document.querySelector("#customer-name").value.trim(),
    email: document.querySelector("#customer-email").value.trim(),
    phone: document.querySelector("#customer-phone").value.trim(),
    salutation: document.querySelector("#customer-salutation").value,
    contactLastName: document.querySelector("#customer-contact-lastname").value.trim(),
    address: document.querySelector("#customer-address").value.trim(),
    houseNumber: document.querySelector("#customer-house-number").value.trim(),
    zip: document.querySelector("#customer-zip").value.trim(),
    city: document.querySelector("#customer-city").value.trim(),
    createdAt: id && getCustomer(id) ? getCustomer(id).createdAt : new Date().toISOString(),
  };

  if (id) {
    state.data.customers = state.data.customers.map((customer) => (customer.id === id ? payload : customer));
    showToast("Kunde wurde aktualisiert.");
  } else {
    state.data.customers.push(payload);
    showToast("Kunde wurde angelegt.");
  }

  saveData();
  resetCustomerForm();
  renderAll();
}

function handleOfferSubmit(event) {
  event.preventDefault();

  const customer = getCustomer(els.offerCustomer.value);
  if (!customer) {
    showToast("Bitte zuerst einen Kunden anlegen.");
    switchView("customers");
    return;
  }

  const price = calculateOfferPrice(
    els.offerSquareMeters.value,
    els.offerInterval.value,
    els.offerService.value,
  );

  const offer = {
    id: createId("offer"),
    customerId: customer.id,
    customer: customerSnapshot(customer),
    squareMeters: Number(els.offerSquareMeters.value),
    interval: els.offerInterval.value,
    service: els.offerService.value,
    startDate: els.offerStartDate.value,
    notes: els.offerNotes.value.trim(),
    price,
    createdAt: new Date().toISOString(),
  };

  state.data.offers.push(offer);
  saveData();
  els.offerForm.reset();
  els.offerStartDate.value = todayAsInputValue();
  updateOfferPreview();
  renderAll();
  showToast("Angebot wurde erstellt.");
}

function createContractFromOffer(offerId) {
  const offer = getOffer(offerId);
  if (!offer) {
    return;
  }

  const existing = state.data.contracts.find((contract) => contract.offerId === offerId);
  if (existing) {
    state.selectedContractId = existing.id;
    switchView("contracts");
    return;
  }

  const contract = {
    id: createId("contract"),
    offerId: offer.id,
    number: nextContractNumber(),
    customer: deepClone(offer.customer),
    offer: offerSnapshot(offer),
    status: "Entwurf",
    createdAt: new Date().toISOString(),
    signedAt: null,
    signatureDataUrl: "",
  };

  state.data.contracts.push(contract);
  state.selectedContractId = contract.id;
  saveData();
  switchView("contracts");
  showToast("Vertrag wurde aus dem Angebot erstellt.");
}

function nextContractNumber() {
  const year = new Date().getFullYear();
  const number = String(state.data.contracts.length + 1).padStart(3, "0");
  return `CT-${year}-${number}`;
}

function deleteCustomer(id) {
  const customer = getCustomer(id);
  if (!customer) {
    return;
  }

  const confirmed = window.confirm(`Kunden "${customer.name}" löschen? Bereits erstellte Angebote behalten ihre Kundendaten.`);
  if (!confirmed) {
    return;
  }

  state.data.customers = state.data.customers.filter((item) => item.id !== id);
  saveData();
  renderAll();
  showToast("Kunde wurde gelöscht.");
}

function deleteOffer(id) {
  const confirmed = window.confirm("Angebot löschen? Zugehörige Verträge werden ebenfalls entfernt.");
  if (!confirmed) {
    return;
  }

  const contractIds = state.data.contracts
    .filter((contract) => contract.offerId === id)
    .map((contract) => contract.id);

  state.data.offers = state.data.offers.filter((offer) => offer.id !== id);
  state.data.contracts = state.data.contracts.filter((contract) => contract.offerId !== id);

  if (contractIds.includes(state.selectedContractId)) {
    state.selectedContractId = null;
  }

  saveData();
  renderAll();
  showToast("Angebot wurde gelöscht.");
}

function deleteContract(id) {
  const confirmed = window.confirm("Vertrag löschen?");
  if (!confirmed) {
    return;
  }

  state.data.contracts = state.data.contracts.filter((contract) => contract.id !== id);

  if (state.selectedContractId === id) {
    state.selectedContractId = null;
  }

  saveData();
  renderAll();
  showToast("Vertrag wurde gelöscht.");
}

function setupSignaturePad() {
  const canvas = els.signaturePad;
  const context = canvas.getContext("2d");
  let drawing = false;

  context.lineCap = "round";
  context.lineJoin = "round";
  context.lineWidth = 3;
  context.strokeStyle = "#102033";

  function positionFromEvent(event) {
    const rect = canvas.getBoundingClientRect();
    return {
      x: ((event.clientX - rect.left) / rect.width) * canvas.width,
      y: ((event.clientY - rect.top) / rect.height) * canvas.height,
    };
  }

  canvas.addEventListener("pointerdown", (event) => {
    const contract = getContract(state.selectedContractId);
    if (!contract || contract.status === "Signiert") {
      return;
    }

    drawing = true;
    canvas.setPointerCapture(event.pointerId);
    const point = positionFromEvent(event);
    context.beginPath();
    context.moveTo(point.x, point.y);
    context.lineTo(point.x + 0.01, point.y + 0.01);
    context.stroke();
    state.signatureHasInk = true;
  });

  canvas.addEventListener("pointermove", (event) => {
    if (!drawing) {
      return;
    }

    const point = positionFromEvent(event);
    context.lineTo(point.x, point.y);
    context.stroke();
    state.signatureHasInk = true;
  });

  function stopDrawing(event) {
    if (!drawing) {
      return;
    }

    drawing = false;
    try {
      canvas.releasePointerCapture(event.pointerId);
    } catch (error) {
      // Pointer capture can already be released by the browser.
    }
  }

  canvas.addEventListener("pointerup", stopDrawing);
  canvas.addEventListener("pointercancel", stopDrawing);
  canvas.addEventListener("pointerleave", stopDrawing);
}

function clearSignaturePad() {
  const canvas = els.signaturePad;
  const context = canvas.getContext("2d");
  context.clearRect(0, 0, canvas.width, canvas.height);
  state.signatureHasInk = false;
}

function saveSignature() {
  const contract = getContract(state.selectedContractId);
  if (!contract) {
    return;
  }

  if (!state.signatureHasInk) {
    showToast("Bitte zuerst im Signaturfeld unterschreiben.");
    return;
  }

  contract.status = "Signiert";
  contract.signedAt = new Date().toISOString();
  contract.signatureDataUrl = els.signaturePad.toDataURL("image/png");

  saveData();
  renderAll();
  showToast("Vertrag wurde online signiert.");
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
      document.querySelector("#customers-view").scrollIntoView({ behavior: "smooth", block: "start" });
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

  if (action === "create-contract") {
    createContractFromOffer(id);
  }

  if (action === "open-contract") {
    state.selectedContractId = id;
    switchView("contracts");
  }

  if (action === "delete-offer") {
    deleteOffer(id);
  }

  if (action === "select-contract") {
    state.selectedContractId = id;
    renderAll();
  }

  if (action === "delete-contract") {
    deleteContract(id);
  }
}

function bindEvents() {
  els.loginForm.addEventListener("submit", (event) => {
    event.preventDefault();
    const email = els.loginEmail.value.trim();
    const password = els.loginPassword.value;

    if (email === TEST_USER.email && password === TEST_USER.password) {
      rememberSession();
      els.loginError.hidden = true;
      showApp();
      return;
    }

    els.loginError.hidden = false;
  });

  els.logoutButton.addEventListener("click", () => {
    clearSession();
    showLogin();
  });

  els.navLinks.forEach((button) => {
    button.addEventListener("click", () => switchView(button.dataset.view));
  });

  els.menuButton.addEventListener("click", openMobileNav);
  els.mobileBackdrop.addEventListener("click", closeMobileNav);
  els.quickCustomer.addEventListener("click", () => switchView("customers"));
  els.quickOffer.addEventListener("click", () => switchView("offers"));
  els.newCustomerButton.addEventListener("click", () => {
    resetCustomerForm();
    document.querySelector("#customer-name").focus();
  });
  els.cancelCustomerEdit.addEventListener("click", resetCustomerForm);

  els.customerForm.addEventListener("submit", handleCustomerSubmit);
  els.customerSearch.addEventListener("input", renderCustomers);

  els.offerForm.addEventListener("submit", handleOfferSubmit);
  els.offerSquareMeters.addEventListener("input", updateOfferPreview);
  els.offerInterval.addEventListener("change", updateOfferPreview);
  els.offerService.addEventListener("change", updateOfferPreview);

  els.customerList.addEventListener("click", handleRecordAction);
  els.offerList.addEventListener("click", handleRecordAction);
  els.contractList.addEventListener("click", handleRecordAction);

  els.clearSignature.addEventListener("click", clearSignaturePad);
  els.saveSignature.addEventListener("click", saveSignature);
  els.printContract.addEventListener("click", () => {
    if (state.selectedContractId) {
      window.print();
    }
  });
}

function bootstrapDemoOffer() {
  if (state.data.offers.length || !state.data.customers.length) {
    return;
  }

  const customer = state.data.customers[0];
  state.data.offers.push({
    id: "offer-1",
    customerId: customer.id,
    customer: customerSnapshot(customer),
    squareMeters: 420,
    interval: "Wöchentlich",
    service: "Büroreinigung",
    startDate: "2026-07-15",
    notes: "Reinigung außerhalb der Bürozeiten, Schwerpunkt Sanitärbereiche und Eingangszone.",
    price: calculateOfferPrice(420, "Wöchentlich", "Büroreinigung"),
    createdAt: "2026-07-03T12:00:00.000Z",
  });
  saveData();
}

function init() {
  bootstrapDemoOffer();
  bindEvents();
  setupSignaturePad();
  els.offerStartDate.value = todayAsInputValue();

  if (hasSession()) {
    showApp();
  } else {
    showLogin();
    refreshIcons();
  }
}

init();
