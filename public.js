const token = document.body.dataset.token || "";
const startsQuoteContract = new URLSearchParams(window.location.search).get("start") === "online-contract";

const state = {
  offer: null,
  contract: null,
};

const els = {
  card: document.querySelector("#public-card"),
  screens: document.querySelectorAll(".public-screen"),
  errorMessage: document.querySelector("#error-message"),
  linkValidity: document.querySelector("#link-validity"),
  dataCheckList: document.querySelector("#data-check-list"),
  serviceDetails: document.querySelector("#service-details"),
  signaturePad: document.querySelector("#signature-pad"),
  contractPreviewFrame: document.querySelector("#contract-preview-frame"),
  protectedContract: document.querySelector("#protected-contract"),
  captureShield: document.querySelector("#capture-shield"),
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
  quoteDetails: document.querySelector("#quote-details"),
  quoteAcceptanceCheck: document.querySelector("#quote-acceptance-check"),
  acceptQuote: document.querySelector("#accept-quote"),
  quotePdfLink: document.querySelector("#quote-pdf-link"),
};

const signatureInk = new WeakSet();
const additionalSigners = [];
let validityTimer = null;

const EXPIRED_LINK_MESSAGE = "Vertragslink abgelaufen. Bitte kontaktieren Sie das Clean-Team.";

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

function showExpiredLink() {
  els.errorMessage.textContent = EXPIRED_LINK_MESSAGE;
  els.linkValidity.textContent = "Link abgelaufen";
  els.linkValidity.classList.add("expired");
  els.linkValidity.hidden = false;
  showScreen("error");
}

function startValidityCountdown(expiresAt, serverNow) {
  window.clearInterval(validityTimer);
  const expiry = new Date(expiresAt).getTime();
  const serverTime = new Date(serverNow).getTime();
  const clockOffset = Number.isFinite(serverTime) ? Date.now() - serverTime : 0;
  if (!Number.isFinite(expiry)) {
    els.linkValidity.hidden = true;
    return;
  }

  const update = () => {
    const currentServerTime = Date.now() - clockOffset;
    const remainingSeconds = Math.max(0, Math.ceil((expiry - currentServerTime) / 1000));
    if (remainingSeconds <= 0) {
      window.clearInterval(validityTimer);
      showExpiredLink();
      return;
    }

    const days = Math.floor(remainingSeconds / 86400);
    const hours = Math.floor((remainingSeconds % 86400) / 3600);
    const minutes = Math.floor((remainingSeconds % 3600) / 60);
    const seconds = remainingSeconds % 60;
    const parts = [];
    if (days > 0) parts.push(`${days} ${days === 1 ? "Tag" : "Tage"}`);
    if (hours > 0 || days > 0) parts.push(`${hours} Std.`);
    parts.push(`${minutes} Min.`);
    if (days === 0 && hours === 0) parts.push(`${seconds} Sek.`);

    els.linkValidity.textContent = `Noch gültig: ${parts.join(" ")}`;
    els.linkValidity.classList.remove("expired");
    els.linkValidity.hidden = false;
  };

  update();
  validityTimer = window.setInterval(update, 1000);
}

function renderContractPreview() {
  els.contractPreviewFrame.src = `contract.php?token=${encodeURIComponent(token)}&preview=1`;
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
  const historicalStart = offer.originalStartDate
    ? new Intl.DateTimeFormat("de-DE", { month: "long", year: "numeric", timeZone: "UTC" })
        .format(new Date(`${offer.originalStartDate}T00:00:00Z`))
    : "Nicht angegeben";
  const items = [
    [offer.isExistingContract ? "Ursprünglicher Vertragsbeginn" : "Startdatum",
      offer.isExistingContract ? historicalStart : (offer.startDate ? formatDate(offer.startDate) : "Nach Absprache")],
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
  if (data.offer.isExistingContract) {
    window.clearInterval(validityTimer);
    els.linkValidity.hidden = true;
  } else {
    startValidityCountdown(data.offer.expiresAt, data.serverNow);
  }

  if (data.offer.isExistingContract) {
    const finalHeading = document.querySelector("#screen-fertig h2");
    const finalText = document.querySelector("#screen-fertig p");
    finalHeading.textContent = "Vertrag erfolgreich abgeschlossen";
    finalText.textContent = "Der Vertrag wurde erfolgreich unterschrieben. Den vollständigen Vertrag können Sie unten einsehen, ausdrucken oder als PDF speichern.";
  }

  if (data.offer.expired && data.offer.quoteStatus !== "accepted") {
    showExpiredLink();
    return;
  }

  const contract = data.contract;

  if (data.accessMode === "quote") {
    if (data.offer.quoteStatus === "sent") {
      document.title = "CleanTeam - Ihr Kostenvoranschlag";
      document.querySelector("#public-document-label").textContent = "Ihr persönlicher Kostenvoranschlag";
      els.quotePdfLink.href = `quote.php?token=${encodeURIComponent(token)}&v=cleanteam-3`;
      renderQuoteDetails();
      showScreen("kostenvoranschlag");
      return;
    }
    if (data.offer.quoteStatus === "accepted" && contract && contract.status === "signiert") {
      renderFinalContract();
      document.querySelector("#screen-fertig h2").textContent = "Kostenvoranschlag angenommen";
      document.querySelector("#screen-fertig p").textContent = "Vielen Dank. Mit Ihrer Unterschrift wurde der Kostenvoranschlag angenommen und der Vertrag erfolgreich abgeschlossen.";
      els.printFinalContract.textContent = "Unterschriebenen Vertrag öffnen / als PDF speichern";
      showScreen("fertig");
      return;
    }
    if (data.offer.quoteStatus === "signing") {
      document.title = "CleanTeam - Vertrag online abschließen";
      document.querySelector("#public-document-label").textContent = "Vertrag online abschließen";
    }
  }

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
    case "signatur":
      renderContractPreview();
      break;
    default:
      break;
  }

  showScreen(contract.currentStep);
}

async function loadOffer() {
  try {
    const data = await api("offer");
    if (data.accessMode === "quote" && !data.offer.expired) {
      if (startsQuoteContract && data.offer.quoteStatus === "sent") {
        const started = await api("accept-quote", {});
        routeToState(started);
        return;
      }
      routeToState(data);
      return;
    }
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

function setupSignaturePad(canvas) {
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
    signatureInk.add(canvas);
  });

  canvas.addEventListener("pointermove", (event) => {
    if (!drawing) {
      return;
    }
    const point = positionFromEvent(event);
    context.lineTo(point.x, point.y);
    context.stroke();
    signatureInk.add(canvas);
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

function clearSignaturePad(canvas = els.signaturePad) {
  const context = canvas.getContext("2d");
  context.clearRect(0, 0, canvas.width, canvas.height);
  signatureInk.delete(canvas);
}

function updateSignerControls() {
  const button = document.querySelector("#add-signer");
  button.hidden = additionalSigners.length >= 4;
  button.textContent = "Weitere Personen hinzufügen";
  els.saveSignature.textContent = "Vertrag abschließen";
  additionalSigners.forEach((signer, index) => {
    signer.heading.textContent = `Person ${index + 2}`;
    signer.canvas.setAttribute("aria-label", `Unterschrift Person ${index + 2}`);
  });
}

function renderQuoteDetails() {
  const offer = state.offer;
  const gross = offer.vatApplicable === false ? offer.price : offer.price * 1.19;
  els.quoteDetails.innerHTML = `<div class="public-service-card"><h3>Leistung und Preis</h3>${renderFactGrid([["Leistungsbeginn", offer.startDate ? formatDate(offer.startDate) : "Nach Absprache"], ["Reinigungsintervall", offer.interval], ["Monatlicher Preis netto", formatCurrency(offer.price)], ["Monatlicher Preis brutto", formatCurrency(gross)]])}</div><div class="public-service-card"><h3>Leistungsbeschreibung</h3><p class="public-service-text">${escapeHtml(offer.notes || "")}</p></div>`;
  els.quoteAcceptanceCheck.checked = false;
  els.acceptQuote.disabled = true;
}

function addSigner() {
  if (additionalSigners.length >= 4) return;
  const section = document.createElement("section");
  section.className = "additional-signer";
  section.innerHTML = `
    <div class="signer-heading"><h3></h3><button class="ghost-button remove-signer" type="button">Entfernen</button></div>
    <label class="modal-field">Vollständiger Name
      <input class="signer-name" type="text" maxlength="190" autocomplete="off" />
    </label>
    <div class="signature-area">
      <canvas class="additional-signature-pad" width="900" height="260"></canvas>
      <div class="form-actions"><button class="ghost-button clear-signer" type="button">Leeren</button></div>
    </div>`;
  const signer = {section, heading: section.querySelector("h3"), name: section.querySelector(".signer-name"),
    canvas: section.querySelector("canvas")};
  additionalSigners.push(signer);
  document.querySelector("#additional-signers").append(section);
  setupSignaturePad(signer.canvas);
  section.querySelector(".clear-signer").addEventListener("click", () => clearSignaturePad(signer.canvas));
  section.querySelector(".remove-signer").addEventListener("click", () => {
    additionalSigners.splice(additionalSigners.indexOf(signer), 1);
    section.remove();
    updateSignerControls();
  });
  updateSignerControls();
  signer.name.focus();
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

  els.clearSignature.addEventListener("click", () => clearSignaturePad());
  els.quoteAcceptanceCheck.addEventListener("change", () => {
    els.acceptQuote.disabled = !els.quoteAcceptanceCheck.checked;
  });
  els.acceptQuote.addEventListener("click", async () => {
    if (!els.quoteAcceptanceCheck.checked) return;
    els.acceptQuote.disabled = true;
    try { await handleAction("accept-quote", {}); } finally { els.acceptQuote.disabled = false; }
  });
  const addButton = document.querySelector("#add-signer");
  addButton.addEventListener("click", () => {
    addSigner();
  });

  els.saveSignature.addEventListener("click", async () => {
    if (!signatureInk.has(els.signaturePad)) {
      showToast("Bitte zuerst im Signaturfeld unterschreiben.");
      return;
    }
    const signers = additionalSigners.map(({name, canvas}) => ({
      name: name.value.trim(), confirmed: true,
      signatureDataUrl: canvas.toDataURL("image/png"),
    }));
    if (additionalSigners.some((signer, index) => !signers[index].name || !signatureInk.has(signer.canvas))) {
      showToast("Bitte Namen und Unterschrift jeder weiteren Person angeben.");
      return;
    }
    els.saveSignature.disabled = true;
    try {
      await handleAction("sign", {
        signatureDataUrl: els.signaturePad.toDataURL("image/png"),
        additionalSigners: signers,
      });
    } finally {
      els.saveSignature.disabled = false;
    }
  });
}

function setupCaptureProtection() {
  const preventCaptureAction = (event) => event.preventDefault();
  els.protectedContract.addEventListener("contextmenu", preventCaptureAction);
  els.protectedContract.addEventListener("copy", preventCaptureAction);
  els.protectedContract.addEventListener("dragstart", preventCaptureAction);

  document.addEventListener("keydown", (event) => {
    const captureShortcut = event.key === "PrintScreen"
      || ((event.ctrlKey || event.metaKey) && event.shiftKey && ["3", "4", "5", "s"].includes(event.key.toLowerCase()));
    if (!captureShortcut) return;
    event.preventDefault();
    els.captureShield.hidden = false;
    showToast("Bildschirmaufnahmen der Vertragsvorschau sind deaktiviert.");
    window.setTimeout(() => { els.captureShield.hidden = true; }, 1600);
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
  setupCaptureProtection();
  setupSignaturePad(els.signaturePad);

  loadBranding();
  loadOffer();
}

init();
