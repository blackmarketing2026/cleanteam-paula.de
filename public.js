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
  authYes: document.querySelector("#auth-yes"),
  authNo: document.querySelector("#auth-no"),
  authorizationQuestionActions: document.querySelector("#authorization-question-actions"),
  representationField: document.querySelector("#representation-field"),
  authorizationGrantorName: document.querySelector("#authorization-grantor-name"),
  authorizationAddress: document.querySelector("#authorization-address"),
  authorizationBack: document.querySelector("#authorization-back"),
  representationContinue: document.querySelector("#representation-continue"),
  serviceDetails: document.querySelector("#service-details"),
  termsConfirmation: document.querySelector("#terms-confirmation"),
  termsContinue: document.querySelector("#terms-continue"),
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
  const entries = [
    ["Kunde", offer.customer.name],
    ["Ansprechpartner", contactName(offer.customer)],
    ["Fläche", `${offer.squareMeters} m²`],
  ];
  entries.push(["Monatlicher Preis", `${formatCurrency(offer.price)} netto monatlich`]);
  renderDefinitionList(els.offerSummary, entries);
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

function renderAuthorizationAddressOptions() {
  const options = state.offer?.authorizationAddressOptions || [];
  els.authorizationAddress.innerHTML = options.length
    ? options
        .map((option) => {
          const label = `${option.label}: ${option.value}`;
          return `<option value="${escapeHtml(option.value)}">${escapeHtml(label)}</option>`;
        })
        .join("")
    : `<option value="">Keine Firmenadresse verfügbar</option>`;
  els.authorizationAddress.disabled = options.length === 0;
}

function showAuthorizationForm(visible) {
  els.authorizationQuestionActions.hidden = visible;
  els.representationField.hidden = !visible;
  if (visible) {
    renderAuthorizationAddressOptions();
    els.authorizationGrantorName.focus();
  }
}

function normalizeServiceLine(line) {
  return String(line || "")
    .trim()
    .replace(/^[\u2022*-]\s*/, "")
    .trim();
}

function isServiceNoiseLine(line) {
  return (
    /^(Begehungsergebnis|Objektdaten|Zusammenfassung|Leistungsbeschreibung \/ Dienstleistung|Etagen und R(?:ä|Ã¤)ume)$/i.test(line) ||
    /^(Firma \/ Kunde|Ansprechpartner vor Ort|E-Mail|Telefon|Objektadresse|Objektgr(?:ö|Ã¶)ße|Begehung erfasst am|Etagen\/Bereiche|R(?:ä|Ã¤)ume|Reinigungspositionen):/i.test(line)
  );
}

function splitServiceValue(line) {
  const separator = line.indexOf(":");
  if (separator === -1) {
    return ["", line];
  }

  return [line.slice(0, separator).trim(), line.slice(separator + 1).trim()];
}

function parseServiceRooms(notes) {
  const rawLines = String(notes || "")
    .split(/\r?\n/)
    .map(normalizeServiceLine)
    .filter(Boolean);
  const startIndex = rawLines.findIndex((line) => /^Etagen und R(?:ä|Ã¤)ume$/i.test(line));
  const lines = (startIndex >= 0 ? rawLines.slice(startIndex + 1) : rawLines).filter((line) => !isServiceNoiseLine(line));
  const rooms = [];
  const looseServices = [];
  let currentRoom = null;
  let readingServices = false;

  lines.forEach((line) => {
    if (/^\d+\.\s+/.test(line)) {
      return;
    }

    const roomMatch = line.match(/^\d+\.\d+\s+(.+)$/);
    if (roomMatch) {
      currentRoom = { title: roomMatch[1].trim(), details: [], services: [], notes: [] };
      rooms.push(currentRoom);
      readingServices = false;
      return;
    }

    if (/^Leistungen:?$/i.test(line)) {
      readingServices = true;
      return;
    }

    const [label, value] = splitServiceValue(line);
    if (/^(Raumart|Bodenart)$/i.test(label) && value !== "") {
      if (currentRoom) {
        currentRoom.details.push([label, value]);
      }
      return;
    }

    if (/^(Extra Vereinbarungen|Notiz)$/i.test(label) && value !== "") {
      if (currentRoom) {
        currentRoom.notes.push(value);
      }
      return;
    }

    if (readingServices || /^(Boden|Tische|Schreibtische|Fenster|M(?:ü|Ã¼)ll|Sanit(?:ä|Ã¤)r|Toiletten|Waschbecken|Spiegel|K(?:ü|Ã¼)che|Treppe|Flur|Staub):/i.test(line)) {
      const service = label && value ? { label, value } : { label: "", value: line };
      if (currentRoom) {
        currentRoom.services.push(service);
      } else {
        looseServices.push(service);
      }
    }
  });

  return { rooms, looseServices };
}

function renderFactGrid(items) {
  return `
    <dl class="public-service-facts">
      ${items.map(([label, value]) => `<div><dt>${escapeHtml(label)}</dt><dd>${escapeHtml(value)}</dd></div>`).join("")}
    </dl>
  `;
}

function renderServiceRooms(rooms, looseServices) {
  if (rooms.length === 0 && looseServices.length === 0) {
    return `<p class="public-service-empty">Die Reinigungsleistungen erfolgen nach der dokumentierten Begehung.</p>`;
  }

  const roomCards = rooms
    .map((room) => {
      const details = room.details.length
        ? `<div class="public-room-meta">${room.details.map(([label, value]) => `<span>${escapeHtml(label)}: ${escapeHtml(value)}</span>`).join("")}</div>`
        : "";
      const services = room.services.length
        ? `<div class="public-room-services">${room.services
            .map((service) => `<div><strong>${escapeHtml(service.label || "Leistung")}</strong><span>${escapeHtml(service.value)}</span></div>`)
            .join("")}</div>`
        : `<p class="public-service-empty">Reinigung nach Begehung.</p>`;
      const notes = room.notes.length ? `<p class="public-room-note">${escapeHtml(room.notes.join(" "))}</p>` : "";

      return `
        <article class="public-room-card">
          <h4>${escapeHtml(room.title)}</h4>
          ${details}
          ${services}
          ${notes}
        </article>
      `;
    })
    .join("");

  const loose = looseServices.length
    ? `<div class="public-room-services public-room-services-standalone">
        ${looseServices.map((service) => `<div><strong>${escapeHtml(service.label || "Leistung")}</strong><span>${escapeHtml(service.value)}</span></div>`).join("")}
      </div>`
    : "";

  return roomCards + loose;
}

function renderServiceDetails() {
  const offer = state.offer;
  const items = [
    ["Fläche", `${offer.squareMeters} m²`],
    ["Startdatum", offer.startDate ? formatDate(offer.startDate) : "Nach Absprache"],
    ["Monatlicher Preis", `${formatCurrency(offer.price)} netto monatlich`],
    ["Leistung", offer.service || "Reinigungsleistung"],
  ];

  const service = parseServiceRooms(offer.notes || "");

  els.serviceDetails.innerHTML = `
    <div class="public-service-card">
      <h3>Vereinbarte Eckdaten</h3>
      ${renderFactGrid(items)}
    </div>
    <div class="public-service-card">
      <h3>Leistungsbeschreibung</h3>
      <p class="public-service-intro">Die Reinigung erfolgt nach der dokumentierten Begehung. Vereinbart sind die folgenden Bereiche und Leistungen.</p>
      <div class="public-room-list">
        ${renderServiceRooms(service.rooms, service.looseServices)}
      </div>
    </div>
  `;
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
    case "vollmacht":
      renderAuthorizationAddressOptions();
      showAuthorizationForm(false);
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
      }
      return;
    }

    const nextButton = event.target.closest("[data-next]");
    if (nextButton) {
      if (nextButton.dataset.next === "signatur" && !els.termsConfirmation.checked) {
        showToast("Bitte bestätigen Sie zuerst den Auftrag.");
        return;
      }
      handleAction("advance", {
        step: nextButton.dataset.next,
        termsAccepted: nextButton.dataset.next === "signatur" ? els.termsConfirmation.checked : undefined,
      });
    }
  });

  els.termsConfirmation.addEventListener("change", () => {
    els.termsContinue.disabled = !els.termsConfirmation.checked;
  });

  els.authYes.addEventListener("click", () => {
    showAuthorizationForm(false);
    handleAction("authorization", { authorized: true });
  });

  els.authNo.addEventListener("click", () => {
    showAuthorizationForm(true);
  });

  els.authorizationBack.addEventListener("click", () => {
    showAuthorizationForm(false);
  });

  els.representationContinue.addEventListener("click", () => {
    const grantorName = els.authorizationGrantorName.value.trim();
    const companyAddress = els.authorizationAddress.value.trim();
    if (!grantorName) {
      showToast("Bitte geben Sie den Ansprechpartner an, der die Vollmacht erteilt.");
      els.authorizationGrantorName.focus();
      return;
    }
    if (!companyAddress) {
      showToast("Bitte wählen Sie die Firmenadresse aus.");
      els.authorizationAddress.focus();
      return;
    }
    handleAction("authorization", {
      authorized: false,
      authorizationGrantorName: grantorName,
      authorizationCompanyAddress: companyAddress,
    });
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
