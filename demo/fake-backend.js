/**
 * Reine Browser-Simulation des PHP-Backends fuer den Klick-Durchlauf.
 * Kein Server, keine Datenbank, kein echter E-Mail-Versand - alles liegt
 * in localStorage. Bildet dieselben JSON-Formen wie die echten api/*.php
 * Endpunkte nach, damit demo/app.js und demo/public.js praktisch die
 * gleiche Logik wie die echte Dashboard-/Kunden-Oberflaeche verwenden.
 */

const DEMO_STORAGE_KEY = "cleanteam-demo-data-v1";
const DEMO_SESSION_KEY = "cleanteam-demo-session";

const DEMO_INTERVAL_FACTORS = {
  Einmalig: 1,
  Wöchentlich: 4.33,
  "14-tägig": 2.16,
  Monatlich: 1,
  Quartalsweise: 0.33,
};

const DEMO_SERVICE_RATES = {
  Unterhaltsreinigung: 1.95,
  Büroreinigung: 2.1,
  Treppenhausreinigung: 2.45,
  Grundreinigung: 3.8,
  Glasreinigung: 3.2,
};

const DEMO_STEP_ORDER = ["daten", "intervall", "vollmacht", "vertragspartner", "leistung", "bedingungen", "signatur", "fertig"];
const DEMO_TERMINAL_STATUSES = ["daten_abgelehnt", "intervall_abgelehnt"];

function demoId(prefix) {
  return `${prefix}-${Math.random().toString(16).slice(2)}${Date.now().toString(16)}`;
}

function demoToken() {
  const bytes = new Uint8Array(32);
  window.crypto.getRandomValues(bytes);
  return Array.from(bytes, (b) => b.toString(16).padStart(2, "0")).join("");
}

function demoPrice(squareMeters, interval, service) {
  const sqm = Number(squareMeters) || 0;
  const factor = DEMO_INTERVAL_FACTORS[interval] || 1;
  const rate = DEMO_SERVICE_RATES[service] || DEMO_SERVICE_RATES.Unterhaltsreinigung;
  const setup = interval === "Einmalig" ? 65 : 35;
  return Math.round(Math.max(0, sqm * rate * factor + setup) * 100) / 100;
}

function demoSeed() {
  const now = Date.now();
  return {
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
        createdAt: new Date(now - 6 * 86400000).toISOString(),
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
        createdAt: new Date(now - 5 * 86400000).toISOString(),
      },
    ],
    offers: [
      {
        id: "offer-1",
        customerId: "customer-1",
        squareMeters: 420,
        interval: "Wöchentlich",
        service: "Büroreinigung",
        startDate: new Date(now + 7 * 86400000).toISOString().slice(0, 10),
        notes: "Reinigung außerhalb der Bürozeiten, Schwerpunkt Sanitärbereiche und Eingangszone.",
        price: demoPrice(420, "Wöchentlich", "Büroreinigung"),
        token: demoToken(),
        createdAt: new Date(now - 1 * 86400000).toISOString(),
        expiresAt: new Date(now + 13 * 86400000).toISOString(),
        sentAt: null,
      },
    ],
    contracts: [],
    settings: {
      host: "",
      port: 587,
      encryption: "tls",
      username: "",
      hasPassword: false,
      fromName: "CleanTeam",
      fromEmail: "",
    },
  };
}

function demoLoad() {
  try {
    const raw = localStorage.getItem(DEMO_STORAGE_KEY);
    if (!raw) {
      const seeded = demoSeed();
      demoSave(seeded);
      return seeded;
    }
    return JSON.parse(raw);
  } catch (error) {
    const seeded = demoSeed();
    demoSave(seeded);
    return seeded;
  }
}

function demoSave(data) {
  localStorage.setItem(DEMO_STORAGE_KEY, JSON.stringify(data));
}

function demoOfferUrl(token) {
  return new URL(`offer.html?token=${token}`, document.baseURI).toString();
}

function demoCustomerJson(customer) {
  return { ...customer };
}

function demoOfferJson(data, offer) {
  const customer = data.customers.find((item) => item.id === offer.customerId);
  const contract = data.contracts.find((item) => item.offerId === offer.id);
  return {
    id: offer.id,
    customerId: offer.customerId,
    customer: customer ? demoCustomerJson(customer) : null,
    squareMeters: offer.squareMeters,
    interval: offer.interval,
    service: offer.service,
    startDate: offer.startDate,
    notes: offer.notes,
    price: offer.price,
    token: offer.token,
    publicUrl: demoOfferUrl(offer.token),
    createdAt: offer.createdAt,
    expiresAt: offer.expiresAt,
    sentAt: offer.sentAt,
    contractId: contract ? contract.id : null,
    contractStatus: contract ? contract.status : null,
  };
}

function demoContractJson(data, contract) {
  const offer = data.offers.find((item) => item.id === contract.offerId);
  const customer = data.customers.find((item) => item.id === contract.customerId);
  return {
    id: contract.id,
    offerId: contract.offerId,
    number: contract.number,
    status: contract.status,
    currentStep: contract.currentStep,
    dataConfirmed: contract.dataConfirmed,
    intervalConfirmed: contract.intervalConfirmed,
    authorized: contract.authorized,
    representationNote: contract.representationNote,
    signedAt: contract.signedAt,
    signatureDataUrl: contract.signatureDataUrl,
    createdAt: contract.createdAt,
    customer: customer ? demoCustomerJson(customer) : null,
    offer: offer
      ? {
          id: offer.id,
          squareMeters: offer.squareMeters,
          interval: offer.interval,
          service: offer.service,
          startDate: offer.startDate,
          notes: offer.notes,
          price: offer.price,
          createdAt: offer.createdAt,
        }
      : null,
  };
}

function demoPublicState(data, offer, contract) {
  const customer = data.customers.find((item) => item.id === offer.customerId);
  return {
    offer: {
      squareMeters: offer.squareMeters,
      interval: offer.interval,
      service: offer.service,
      startDate: offer.startDate,
      notes: offer.notes,
      price: offer.price,
      expiresAt: offer.expiresAt,
      expired: new Date(offer.expiresAt).getTime() < Date.now(),
      customer: customer ? demoCustomerJson(customer) : null,
    },
    contract: contract
      ? {
          status: contract.status,
          currentStep: contract.currentStep,
          dataConfirmed: contract.dataConfirmed,
          intervalConfirmed: contract.intervalConfirmed,
          authorized: contract.authorized,
          representationNote: contract.representationNote,
          number: contract.number,
          signedAt: contract.signedAt,
          signatureDataUrl: contract.signatureDataUrl,
        }
      : null,
  };
}

function demoRequireOffer(data, token) {
  const offer = data.offers.find((item) => item.token === token);
  if (!offer) {
    throw new Error("Dieser Angebots-Link ist ungültig.");
  }
  return offer;
}

function demoRequireActiveContract(contract) {
  if (!contract) {
    throw new Error("Der Vertrag wurde noch nicht gestartet.");
  }
  if (DEMO_TERMINAL_STATUSES.includes(contract.status)) {
    throw new Error("Für diesen Vertrag wurde eine Rückfrage vermerkt. Bitte kontaktieren Sie CleanTeam.");
  }
  if (contract.status === "signiert") {
    throw new Error("Dieser Vertrag wurde bereits unterschrieben.");
  }
  return contract;
}

function demoDelay() {
  return new Promise((resolve) => window.setTimeout(resolve, 120));
}

const FakeAPI = {
  async getCustomers() {
    await demoDelay();
    const data = demoLoad();
    return [...data.customers].sort((a, b) => a.name.localeCompare(b.name, "de"));
  },

  async createCustomer(payload) {
    await demoDelay();
    const data = demoLoad();
    const customer = { id: demoId("customer"), createdAt: new Date().toISOString(), ...payload };
    data.customers.push(customer);
    demoSave(data);
    return demoCustomerJson(customer);
  },

  async updateCustomer(id, payload) {
    await demoDelay();
    const data = demoLoad();
    const existing = data.customers.find((item) => item.id === id);
    if (!existing) {
      throw new Error("Kunde wurde nicht gefunden.");
    }
    Object.assign(existing, payload);
    demoSave(data);
    return demoCustomerJson(existing);
  },

  async deleteCustomer(id) {
    await demoDelay();
    const data = demoLoad();
    const hasOffers = data.offers.some((offer) => offer.customerId === id);
    if (hasOffers) {
      throw new Error("Kunde kann nicht gelöscht werden, solange noch Angebote oder Verträge vorhanden sind.");
    }
    data.customers = data.customers.filter((item) => item.id !== id);
    demoSave(data);
    return { ok: true };
  },

  async getOffers() {
    await demoDelay();
    const data = demoLoad();
    return [...data.offers]
      .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
      .map((offer) => demoOfferJson(data, offer));
  },

  async createOffer(payload) {
    await demoDelay();
    const data = demoLoad();
    const customer = data.customers.find((item) => item.id === payload.customerId);
    if (!customer) {
      throw new Error("Kunde wurde nicht gefunden.");
    }
    const now = new Date();
    const offer = {
      id: demoId("offer"),
      customerId: payload.customerId,
      squareMeters: Number(payload.squareMeters),
      interval: payload.interval,
      service: payload.service,
      startDate: payload.startDate || null,
      notes: payload.notes || "",
      price: demoPrice(payload.squareMeters, payload.interval, payload.service),
      token: demoToken(),
      createdAt: now.toISOString(),
      expiresAt: new Date(now.getTime() + 14 * 86400000).toISOString(),
      sentAt: null,
    };
    data.offers.push(offer);
    demoSave(data);
    return demoOfferJson(data, offer);
  },

  async deleteOffer(id) {
    await demoDelay();
    const data = demoLoad();
    data.offers = data.offers.filter((item) => item.id !== id);
    data.contracts = data.contracts.filter((item) => item.offerId !== id);
    demoSave(data);
    return { ok: true };
  },

  async sendOffer(id) {
    await demoDelay();
    const data = demoLoad();
    const offer = data.offers.find((item) => item.id === id);
    if (!offer) {
      throw new Error("Angebot wurde nicht gefunden.");
    }
    offer.sentAt = new Date().toISOString();
    demoSave(data);
    return { ok: true, sentAt: offer.sentAt, publicUrl: demoOfferUrl(offer.token) };
  },

  async getContracts() {
    await demoDelay();
    const data = demoLoad();
    return [...data.contracts]
      .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
      .map((contract) => demoContractJson(data, contract));
  },

  async deleteContract(id) {
    await demoDelay();
    const data = demoLoad();
    data.contracts = data.contracts.filter((item) => item.id !== id);
    demoSave(data);
    return { ok: true };
  },

  async getSettings() {
    await demoDelay();
    const data = demoLoad();
    return { ...data.settings };
  },

  async saveSettings(payload) {
    await demoDelay();
    const data = demoLoad();
    data.settings = {
      host: payload.host,
      port: payload.port,
      encryption: payload.encryption,
      username: payload.username,
      hasPassword: payload.password ? true : data.settings.hasPassword,
      fromName: payload.fromName,
      fromEmail: payload.fromEmail,
    };
    demoSave(data);
    return { ok: true };
  },

  async sendTestMail() {
    await demoDelay();
    return { ok: true };
  },

  async login(email, password) {
    await demoDelay();
    if (!email || !password) {
      throw new Error("E-Mail-Adresse und Passwort werden benötigt.");
    }
    sessionStorage.setItem(DEMO_SESSION_KEY, email);
    return { ok: true, email };
  },

  async logout() {
    await demoDelay();
    sessionStorage.removeItem(DEMO_SESSION_KEY);
    return { ok: true };
  },

  async me() {
    await demoDelay();
    const email = sessionStorage.getItem(DEMO_SESSION_KEY);
    return email ? { loggedIn: true, email } : { loggedIn: false };
  },

  async publicGetOffer(token) {
    await demoDelay();
    const data = demoLoad();
    const offer = demoRequireOffer(data, token);
    const contract = data.contracts.find((item) => item.offerId === offer.id) || null;
    return demoPublicState(data, offer, contract);
  },

  async publicStart(token) {
    await demoDelay();
    const data = demoLoad();
    const offer = demoRequireOffer(data, token);
    let contract = data.contracts.find((item) => item.offerId === offer.id);
    if (!contract) {
      contract = {
        id: demoId("contract"),
        offerId: offer.id,
        customerId: offer.customerId,
        number: `CT-${new Date().getFullYear()}-${String(data.contracts.length + 1).padStart(3, "0")}`,
        status: "entwurf",
        currentStep: "daten",
        dataConfirmed: false,
        intervalConfirmed: false,
        authorized: null,
        representationNote: null,
        signedAt: null,
        signatureDataUrl: null,
        createdAt: new Date().toISOString(),
      };
      data.contracts.push(contract);
      demoSave(data);
    }
    return demoPublicState(data, offer, contract);
  },

  async publicConfirmData(token, confirmed) {
    await demoDelay();
    const data = demoLoad();
    const offer = demoRequireOffer(data, token);
    const contract = demoRequireActiveContract(data.contracts.find((item) => item.offerId === offer.id));
    if (confirmed) {
      contract.dataConfirmed = true;
      contract.currentStep = "intervall";
    } else {
      contract.status = "daten_abgelehnt";
    }
    demoSave(data);
    return demoPublicState(data, offer, contract);
  },

  async publicConfirmInterval(token, confirmed) {
    await demoDelay();
    const data = demoLoad();
    const offer = demoRequireOffer(data, token);
    const contract = demoRequireActiveContract(data.contracts.find((item) => item.offerId === offer.id));
    if (confirmed) {
      contract.intervalConfirmed = true;
      contract.currentStep = "vollmacht";
    } else {
      contract.status = "intervall_abgelehnt";
    }
    demoSave(data);
    return demoPublicState(data, offer, contract);
  },

  async publicAuthorization(token, authorized, note) {
    await demoDelay();
    const data = demoLoad();
    const offer = demoRequireOffer(data, token);
    const contract = demoRequireActiveContract(data.contracts.find((item) => item.offerId === offer.id));
    if (!authorized && !note) {
      throw new Error("Bitte geben Sie an, in welcher Vertretung Sie handeln.");
    }
    contract.authorized = authorized;
    contract.representationNote = authorized ? null : note;
    contract.currentStep = "vertragspartner";
    demoSave(data);
    return demoPublicState(data, offer, contract);
  },

  async publicAdvance(token, step) {
    await demoDelay();
    const data = demoLoad();
    const offer = demoRequireOffer(data, token);
    const contract = demoRequireActiveContract(data.contracts.find((item) => item.offerId === offer.id));
    const currentIndex = DEMO_STEP_ORDER.indexOf(contract.currentStep);
    const targetIndex = DEMO_STEP_ORDER.indexOf(step);
    if (targetIndex === -1 || targetIndex < currentIndex || targetIndex > currentIndex + 1) {
      throw new Error("Ungültiger Schrittwechsel.");
    }
    contract.currentStep = step;
    demoSave(data);
    return demoPublicState(data, offer, contract);
  },

  async publicSign(token, signatureDataUrl) {
    await demoDelay();
    const data = demoLoad();
    const offer = demoRequireOffer(data, token);
    const contract = demoRequireActiveContract(data.contracts.find((item) => item.offerId === offer.id));
    if (!signatureDataUrl || !signatureDataUrl.startsWith("data:image/png;base64,")) {
      throw new Error("Ungültige Signatur.");
    }
    contract.status = "signiert";
    contract.signedAt = new Date().toISOString();
    contract.signatureDataUrl = signatureDataUrl;
    contract.currentStep = "fertig";
    demoSave(data);
    return demoPublicState(data, offer, contract);
  },
};

window.FakeAPI = FakeAPI;
