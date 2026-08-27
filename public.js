const token = document.body.dataset.token || "";

const state = {
  offer: null,
  contract: null,
};

const els = {
  card: document.querySelector("#public-card"),
  screens: document.querySelectorAll(".public-screen"),
  errorMessage: document.querySelector("#error-message"),
  dataCheckList: document.querySelector("#data-check-list"),
  serviceDetails: document.querySelector("#service-details"),
  signaturePad: document.querySelector("#signature-pad"),
  clearSignature: document.querySelector("#clear-signature"),
  saveSignature: document.querySelector("#save-signature"),
  finalContractFrame: document.querySelector("#final-contract-frame"),
  printFinalContract: document.querySelector("#print-final-contract"),
  toast: document.querySelector("#toast"),
  identityCheckQuestion: document.querySelector("#identity-check-question"),
  identityCheckStep1: document.querySelector("#identity-check-step-1"),
  identityCheckStep2: document.querySelector("#identity-check-step-2"),
  identityCheckYes: document.querySelector("#identity-check-yes"),
  identityCheckNo: document.querySelector("#identity-check-no"),
  identityCheckName: document.querySelector("#identity-check-name"),
  identityCheckAuthorizedYes: document.querySelector("#identity-check-authorized-yes"),
  identityCheckAuthorizedNo: document.querySelector("#identity-check-authorized-no"),
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

function renderDataCheck() {
  const offer = state.offer;
  renderDefinitionList(els.dataCheckList, [
    ["Firma", offer.customer.name],
    ["Geschäftsführer / Inhaber", contactName(offer.customer)],
    ["E-Mail", offer.customer.email],
    ["Adresse", customerAddress(offer.customer)],
  ]);
}

function renderFactGrid(items) {
  return `
    <dl class="public-service-facts">
      ${items.map(([label, value]) => `<div><dt>${escapeHtml(label)}</dt><dd>${escapeHtml(value)}</dd></div>`).join("")}
    </dl>
  `;
}

function renderServiceDetails() {
  const offer = state.offer;
  const items = [
    ["Startdatum", offer.startDate ? formatDate(offer.startDate) : "Nach Absprache"],
    ["Monatlicher Preis", `${formatCurrency(offer.price)} netto monatlich`],
  ];

  els.serviceDetails.innerHTML = `
    <div class="public-service-card">
      <h3>Vereinbarte Eckdaten</h3>
      ${renderFactGrid(items)}
    </div>
    <div class="public-service-card">
      <h3>Leistungsbeschreibung</h3>
      <p class="public-service-text">${escapeHtml(offer.notes || "Die Reinigungsleistungen sind im Vertrag beschrieben.")}</p>
    </div>
  `;
}

function renderIdentityCheck() {
  const customer = state.offer.customer;
  els.identityCheckQuestion.textContent =
    `Sind Sie ${contactName(customer)}, Geschäftsführer/in bzw. Inhaber/in von ${customer.name}?`;
  els.identityCheckName.value = "";
  els.identityCheckAuthorizedYes.disabled = true;
  els.identityCheckStep1.hidden = false;
  els.identityCheckStep2.hidden = true;
}

function renderFinalContract() {
  // Zeigt dasselbe serverseitig erzeugte Kunden-PDF, das auch per E-Mail verschickt wird.
  const pdfUrl = `contract.php?token=${encodeURIComponent(token)}&format=pdf`;
  els.finalContractFrame.src = pdfUrl;
  // Eigener Link statt nur iframe.contentWindow.print(): In mobilen In-App-Browsern (z. B.
  // WhatsApp/Instagram) und teils in Safari auf iOS wird ein per iframe eingebettetes PDF
  // oft gar nicht angezeigt und lässt sich programmatisch nicht drucken. Ein echter Link, der
  // das PDF direkt öffnet, funktioniert dagegen überall zuverlässig.
  els.printFinalContract.href = pdfUrl;
}

function routeToState(data) {
  state.offer = data.offer;
  state.contract = data.contract;

  if (data.offer.expired) {
    els.errorMessage.textContent = "Dieser Vertrag ist leider abgelaufen. Bitte kontaktieren Sie CleanTeam für einen neuen Vertrag.";
    showScreen("error");
    return;
  }

  const contract = data.contract;

  if (!contract) {
    return;
  }

  if (contract.status === "signiert") {
    renderFinalContract();
    showScreen("fertig");
    return;
  }

  if (
    contract.status === "daten_abgelehnt" ||
    contract.status === "intervall_abgelehnt" ||
    contract.status === "datenschutz_abgelehnt" ||
    contract.status === "berechtigung_abgelehnt"
  ) {
    showScreen("abgelehnt");
    return;
  }

  switch (contract.currentStep) {
    case "datenschutz":
      break;
    case "daten":
      renderDataCheck();
      break;
    case "leistung":
      renderServiceDetails();
      break;
    case "identitaet":
      renderIdentityCheck();
      break;
    default:
      break;
  }

  showScreen(contract.currentStep);
}

async function loadOffer() {
  try {
    const data = await api("offer");
    if (!data.offer.expired && !data.contract) {
      const started = await api("start", {});
      routeToState(started);
      return;
    }
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
  els.card.addEventListener("click", (event) => {
    const yesNoButton = event.target.closest("[data-yesno]");
    if (yesNoButton) {
      const screen = yesNoButton.closest(".public-screen");
      const confirmed = yesNoButton.dataset.yesno === "yes";
      if (screen.id === "screen-datenschutz") {
        handleAction("confirm-privacy", { confirmed });
      } else if (screen.id === "screen-daten") {
        handleAction("confirm-data", { confirmed });
      }
      return;
    }

    const nextButton = event.target.closest("[data-next]");
    if (nextButton) {
      handleAction("advance", {
        step: nextButton.dataset.next,
        termsAccepted: nextButton.dataset.next === "identitaet" ? true : undefined,
      });
    }
  });

  els.identityCheckYes.addEventListener("click", () => {
    handleAction("confirm-identity", { confirmed: true });
  });

  els.identityCheckNo.addEventListener("click", () => {
    els.identityCheckStep1.hidden = true;
    els.identityCheckStep2.hidden = false;
  });

  els.identityCheckName.addEventListener("input", () => {
    els.identityCheckAuthorizedYes.disabled = els.identityCheckName.value.trim() === "";
  });

  els.identityCheckAuthorizedYes.addEventListener("click", () => {
    const name = els.identityCheckName.value.trim();
    if (!name) {
      return;
    }
    handleAction("confirm-identity", { confirmed: false, authorized: true, representationNote: name });
  });

  els.identityCheckAuthorizedNo.addEventListener("click", () => {
    handleAction("confirm-identity", { confirmed: false, authorized: false });
  });

  els.clearSignature.addEventListener("click", clearSignaturePad);

  els.saveSignature.addEventListener("click", () => {
    if (!signatureHasInk) {
      showToast("Bitte zuerst im Signaturfeld unterschreiben.");
      return;
    }
    handleAction("sign", { signatureDataUrl: els.signaturePad.toDataURL("image/png") });
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
    els.errorMessage.textContent = "Dieser Link enthält keinen gültigen Vertrag.";
    showScreen("error");
    return;
  }

  bindEvents();
  setupSignaturePad();
  loadBranding();
  loadOffer();
}

init();
