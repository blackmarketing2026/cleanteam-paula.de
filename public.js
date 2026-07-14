const token = document.body.dataset.token || "";

const state = {
  offer: null,
  contract: null,
};

const els = {
  card: document.querySelector("#public-card"),
  screens: document.querySelectorAll(".public-screen"),
  errorMessage: document.querySelector("#error-message"),
  offerSummary: document.querySelector("#offer-summary"),
  offerValidity: document.querySelector("#offer-validity"),
  startContract: document.querySelector("#start-contract"),
  dataCheckList: document.querySelector("#data-check-list"),
  intervalCheckText: document.querySelector("#interval-check-text"),
  authYes: document.querySelector("#auth-yes"),
  authNo: document.querySelector("#auth-no"),
  representationField: document.querySelector("#representation-field"),
  representationNote: document.querySelector("#representation-note"),
  representationContinue: document.querySelector("#representation-continue"),
  partnerDetails: document.querySelector("#partner-details"),
  serviceDetails: document.querySelector("#service-details"),
  signaturePad: document.querySelector("#signature-pad"),
  clearSignature: document.querySelector("#clear-signature"),
  saveSignature: document.querySelector("#save-signature"),
  finalContractFrame: document.querySelector("#final-contract-frame"),
  printFinalContract: document.querySelector("#print-final-contract"),
  toast: document.querySelector("#toast"),
};

let signatureHasInk = false;

function escapeHtml(value) {
  return String(value == null ? "" : value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function formatCurrency(value) {
  return new Intl.NumberFormat("de-DE", { style: "currency", currency: "EUR" }).format(Number(value) || 0);
}

function formatDate(value) {
  if (!value) {
    return "";
  }
  return new Intl.DateTimeFormat("de-DE", { day: "2-digit", month: "2-digit", year: "numeric" }).format(new Date(value));
}

function showToast(message) {
  els.toast.textContent = message;
  els.toast.hidden = false;
  window.clearTimeout(showToast.timer);
  showToast.timer = window.setTimeout(() => {
    els.toast.hidden = true;
  }, 3200);
}

function showScreen(name) {
  els.screens.forEach((screen) => {
    screen.classList.toggle("active-screen", screen.id === `screen-${name}`);
  });
}

async function api(action, body) {
  const response = await fetch(`api/public.php?action=${encodeURIComponent(action)}&token=${encodeURIComponent(token)}`, {
    method: body === undefined ? "GET" : "POST",
    headers: body === undefined ? undefined : { "Content-Type": "application/json" },
    body: body === undefined ? undefined : JSON.stringify(body),
  });

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    throw new Error(data.error || "Es ist ein Fehler aufgetreten.");
  }

  return data;
}

function customerAddress(customer) {
  return `${customer.address} ${customer.houseNumber}, ${customer.zip} ${customer.city}`;
}

function contactName(customer) {
  return `${customer.salutation} ${customer.contactLastName}`;
}

function renderDefinitionList(target, entries) {
  target.innerHTML = entries
    .map(([term, value]) => `<dt>${escapeHtml(term)}</dt><dd>${escapeHtml(value)}</dd>`)
    .join("");
}

function renderOfferSummary() {
  const offer = state.offer;
  renderDefinitionList(els.offerSummary, [
    ["Kunde", offer.customer.name],
    ["Ansprechpartner", contactName(offer.customer)],
    ["Leistung", offer.service],
    ["Fläche", `${offer.squareMeters} m²`],
    ["Intervall", offer.interval],
    ["Geschätzter Nettowert", formatCurrency(offer.price)],
  ]);
  els.offerValidity.textContent = `Dieser Kostenvoranschlag ist gültig bis ${formatDate(offer.expiresAt)}.`;
}

function renderDataCheck() {
  const offer = state.offer;
  renderDefinitionList(els.dataCheckList, [
    ["Firma", offer.customer.name],
    ["Ansprechpartner", contactName(offer.customer)],
    ["E-Mail", offer.customer.email],
    ["Telefon", offer.customer.phone],
    ["Adresse", customerAddress(offer.customer)],
  ]);
}

function renderIntervalCheck() {
  els.intervalCheckText.textContent = `Wie besprochen reinigen wir im Intervall „${state.offer.interval}“. Ist das korrekt?`;
}

function renderPartnerDetails() {
  const offer = state.offer;
  renderDefinitionList(els.partnerDetails, [
    ["Kunde", offer.customer.name],
    ["Ansprechpartner", contactName(offer.customer)],
    ["E-Mail", offer.customer.email],
    ["Telefon", offer.customer.phone],
    ["Adresse", customerAddress(offer.customer)],
  ]);
}

function renderServiceDetails() {
  const offer = state.offer;
  const entries = [
    ["Leistung", offer.service],
    ["Fläche", `${offer.squareMeters} m²`],
    ["Intervall", offer.interval],
    ["Startdatum", offer.startDate ? formatDate(offer.startDate) : "Nach Absprache"],
    ["Netto-Betrag", formatCurrency(offer.price)],
  ];
  if (offer.notes) {
    entries.push(["Besondere Vereinbarungen", offer.notes]);
  }
  renderDefinitionList(els.serviceDetails, entries);
}

function renderFinalContract() {
  // Zeigt dasselbe serverseitig erzeugte Kunden-PDF, das auch per E-Mail verschickt wird.
  els.finalContractFrame.src = `contract.php?token=${encodeURIComponent(token)}&format=pdf`;
}

function routeToState(data) {
  state.offer = data.offer;
  state.contract = data.contract;

  if (data.offer.expired) {
    els.errorMessage.textContent = "Dieser Kostenvoranschlag ist leider abgelaufen. Bitte kontaktieren Sie CleanTeam für einen neuen Kostenvoranschlag.";
    showScreen("error");
    return;
  }

  const contract = data.contract;

  if (!contract) {
    renderOfferSummary();
    showScreen("offer");
    return;
  }

  if (contract.status === "signiert") {
    renderFinalContract();
    showScreen("fertig");
    return;
  }

  if (contract.status === "daten_abgelehnt" || contract.status === "intervall_abgelehnt") {
    showScreen("abgelehnt");
    return;
  }

  switch (contract.currentStep) {
    case "daten":
      renderDataCheck();
      break;
    case "intervall":
      renderIntervalCheck();
      break;
    case "vertragspartner":
      renderPartnerDetails();
      break;
    case "leistung":
      renderServiceDetails();
      break;
    default:
      break;
  }

  showScreen(contract.currentStep);
}

async function loadOffer() {
  try {
    const data = await api("offer");
    routeToState(data);
  } catch (error) {
    els.errorMessage.textContent = error.message;
    showScreen("error");
  }
}

async function handleAction(action, body) {
  try {
    const data = await api(action, body || {});
    routeToState(data);
  } catch (error) {
    showToast(error.message);
  }
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
    drawing = true;
    canvas.setPointerCapture(event.pointerId);
    const point = positionFromEvent(event);
    context.beginPath();
    context.moveTo(point.x, point.y);
    context.lineTo(point.x + 0.01, point.y + 0.01);
    context.stroke();
    signatureHasInk = true;
  });

  canvas.addEventListener("pointermove", (event) => {
    if (!drawing) {
      return;
    }
    const point = positionFromEvent(event);
    context.lineTo(point.x, point.y);
    context.stroke();
    signatureHasInk = true;
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
  signatureHasInk = false;
}

function bindEvents() {
  els.startContract.addEventListener("click", () => handleAction("start"));

  els.card.addEventListener("click", (event) => {
    const yesNoButton = event.target.closest("[data-yesno]");
    if (yesNoButton) {
      const screen = yesNoButton.closest(".public-screen");
      const confirmed = yesNoButton.dataset.yesno === "yes";
      if (screen.id === "screen-daten") {
        handleAction("confirm-data", { confirmed });
      } else if (screen.id === "screen-intervall") {
        handleAction("confirm-interval", { confirmed });
      }
      return;
    }

    const nextButton = event.target.closest("[data-next]");
    if (nextButton) {
      handleAction("advance", { step: nextButton.dataset.next });
    }
  });

  els.authYes.addEventListener("click", () => {
    els.representationField.hidden = true;
    handleAction("authorization", { authorized: true });
  });

  els.authNo.addEventListener("click", () => {
    els.representationField.hidden = false;
    els.representationNote.focus();
  });

  els.representationContinue.addEventListener("click", () => {
    const note = els.representationNote.value.trim();
    if (!note) {
      showToast("Bitte geben Sie an, in welcher Vertretung Sie handeln.");
      return;
    }
    handleAction("authorization", { authorized: false, representationNote: note });
  });

  els.clearSignature.addEventListener("click", clearSignaturePad);

  els.saveSignature.addEventListener("click", () => {
    if (!signatureHasInk) {
      showToast("Bitte zuerst im Signaturfeld unterschreiben.");
      return;
    }
    handleAction("sign", { signatureDataUrl: els.signaturePad.toDataURL("image/png") });
  });

  els.printFinalContract.addEventListener("click", () => {
    els.finalContractFrame.contentWindow.print();
  });
}

async function loadBranding() {
  try {
    const response = await fetch("api/branding.php");
    const data = await response.json();
    if (data.logoUrl) {
      const mark = document.querySelector(".brand-mark");
      mark.classList.add("has-logo");
      mark.innerHTML = `<img src="${escapeHtml(data.logoUrl)}" alt="Logo" />`;
    }
  } catch (error) {
    // Kein Logo hinterlegt oder Ladefehler: Fallback-Initialen bleiben stehen.
  }
}

function init() {
  if (!token) {
    els.errorMessage.textContent = "Dieser Link enthält keinen gültigen Kostenvoranschlag.";
    showScreen("error");
    return;
  }

  bindEvents();
  setupSignaturePad();
  loadBranding();
  loadOffer();
}

init();
