/* Begehungs-Quiz: geführte, mobile-first Erfassung einer Objektbegehung.
   Speichert lokal (localStorage) hinter einer kleinen Store-Abstraktion,
   die sich später 1:1 durch PHP/MySQL-Endpunkte ersetzen lässt. */

const SVQ_STORAGE_KEY = "cleanteam_site_visit_quiz_drafts_v1";

const SVQ_INTRO_STEPS = ["company-name", "company-phone", "company-email", "address", "contact", "area-size"];

const SiteVisitQuizStore = {
  list() {
    try {
      const raw = localStorage.getItem(SVQ_STORAGE_KEY);
      return raw ? JSON.parse(raw) : [];
    } catch (error) {
      return [];
    }
  },
  get(id) {
    return this.list().find((draft) => draft.id === id) || null;
  },
  save(draft) {
    const all = this.list();
    const index = all.findIndex((item) => item.id === draft.id);
    if (index === -1) {
      all.push(draft);
    } else {
      all[index] = draft;
    }
    localStorage.setItem(SVQ_STORAGE_KEY, JSON.stringify(all));
    return draft;
  },
  remove(id) {
    localStorage.setItem(SVQ_STORAGE_KEY, JSON.stringify(this.list().filter((item) => item.id !== id)));
  },
};

const svqState = {
  screen: "home",
  draftId: null,
};

function svqUid() {
  if (window.crypto?.randomUUID) {
    return window.crypto.randomUUID();
  }
  return `svq-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

function svqNowIso() {
  return new Date().toISOString();
}

function svqEsc(value) {
  return String(value == null ? "" : value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function svqFormatDate(iso) {
  if (!iso) return "-";
  try {
    return new Intl.DateTimeFormat("de-DE", { dateStyle: "medium", timeStyle: "short" }).format(new Date(iso));
  } catch (error) {
    return iso;
  }
}

function svqSetPath(obj, path, value) {
  const keys = path.split(".");
  let target = obj;
  for (let i = 0; i < keys.length - 1; i += 1) {
    if (target[keys[i]] == null || typeof target[keys[i]] !== "object") {
      target[keys[i]] = {};
    }
    target = target[keys[i]];
  }
  target[keys[keys.length - 1]] = value;
}

function svqNewDraft() {
  return {
    id: svqUid(),
    status: "draft",
    screen: "intro",
    introStepIndex: 0,
    createdAt: svqNowIso(),
    updatedAt: svqNowIso(),
    updatedBy: window.currentUserEmail || "",
    completedAt: null,
    linkedSiteVisitId: null,
    linkedSiteVisitError: null,
    version: 1,
    company: { name: "", phone: "", email: "" },
    address: { street: "", houseNumber: "", zip: "", city: "" },
    contact: { firstName: "", lastName: "", role: "", phone: "", email: "" },
    areaSize: { value: "", mode: "exact" },
    rooms: [],
    activeRoomDraft: null,
    editingRoomId: null,
    lastTouchedRoomId: null,
    roomObjectIndex: 0,
  };
}

function svqCurrentDraft() {
  return svqState.draftId ? SiteVisitQuizStore.get(svqState.draftId) : null;
}

function svqMutate(mutator, { rerender = true } = {}) {
  const draft = svqCurrentDraft();
  if (!draft) return;
  mutator(draft);
  draft.updatedAt = svqNowIso();
  draft.updatedBy = window.currentUserEmail || draft.updatedBy || "";
  SiteVisitQuizStore.save(draft);
  if (rerender) svqRender();
}

function svqNewRoomDraft(typeId, label = "") {
  const type = svqRoomType(typeId);
  const room = {
    id: svqUid(),
    type: typeId,
    typeLabel: type ? type.label : typeId,
    label,
    objects: {},
    customObjects: [],
    createdAt: svqNowIso(),
    updatedAt: svqNowIso(),
  };
  svqRoomObjectIds(room).forEach((objectId) => svqGetOrCreateObjectEntry(room, objectId));
  return room;
}

function svqNextRoomLabel(draft, typeId) {
  const type = svqRoomType(typeId);
  const baseLabel = type ? type.label : typeId;
  const count = draft.rooms.filter((room) => room.type === typeId).length;
  return `${baseLabel} ${count + 1}`;
}

function svqGetOrCreateObjectEntry(room, objectId) {
  if (!room.objects[objectId]) {
    room.objects[objectId] = {
      objectId,
      present: null,
      shouldClean: null,
      interval: null,
      customInterval: "",
      quantity: "",
      trashBag: null,
      note: "",
    };
  }
  return room.objects[objectId];
}

function svqRoomObjectIds(room) {
  const type = svqRoomType(room.type);
  if (!type) return [];
  if (type.id === "custom") return type.objects;
  const ids = [...type.objects];
  ["chairs", "tables"].forEach((id) => {
    if (!ids.includes(id)) ids.push(id);
  });
  return ids;
}

/* ---------- Fortschritt ---------- */

function svqProgressPercent(draft) {
  if (draft.screen === "intro") {
    return Math.round((draft.introStepIndex / SVQ_INTRO_STEPS.length) * 20);
  }
  if (draft.screen === "finish-success") return 100;
  if (draft.screen === "finish-confirm") return 95;
  if (draft.screen === "rooms-overview") return 90;
  const roomBonus = Math.min(draft.rooms.length, 8) / 8;
  return Math.round(20 + roomBonus * 70);
}

function svqProgressLabel(draft) {
  const labels = {
    "company-name": "Firmenname",
    "company-phone": "Telefonnummer",
    "company-email": "E-Mail-Adresse",
    address: "Objektadresse",
    contact: "Ansprechpartner vor Ort",
    "area-size": "Objektgröße",
  };
  if (draft.screen === "intro") return labels[SVQ_INTRO_STEPS[draft.introStepIndex]] || "Firmendaten";
  if (draft.screen === "room-type-select") return "Raumart wählen";
  if (draft.screen === "room-basic-info") return "Raumdaten";
  if (draft.screen === "room-object") {
    const room = draft.activeRoomDraft;
    const objectId = room ? svqRoomObjectIds(room)[draft.roomObjectIndex] : null;
    return `Raum erfassen: ${svqEsc(room?.typeLabel || "")}${objectId ? " – " + svqObjectLabel(objectId) : ""}`;
  }
  if (draft.screen === "custom-object-prompt" || draft.screen === "custom-object-form") return "Individuelle Objekte";
  if (draft.screen === "room-summary") return "Raumübersicht";
  if (draft.screen === "room-next-action") return "Nächster Schritt";
  if (draft.screen === "rooms-overview") return "Räume-Übersicht";
  if (draft.screen === "finish-confirm") return "Begehung abschließen";
  if (draft.screen === "finish-success") return "Abgeschlossen";
  return "Begehung";
}

/* ---------- Navigation: Weiter / Zurück ---------- */

function svqValidateIntroStep(draft) {
  const step = SVQ_INTRO_STEPS[draft.introStepIndex];
  if (step === "company-name") return draft.company.name.trim() !== "";
  if (step === "company-phone") return draft.company.phone.trim() !== "";
  if (step === "company-email") return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(draft.company.email.trim());
  return true;
}

function svqGoNext() {
  const draft = svqCurrentDraft();
  if (!draft) return;

  if (draft.screen === "intro") {
    if (!svqValidateIntroStep(draft)) {
      svqShowValidation("Bitte fülle dieses Feld korrekt aus, bevor du weitergehst.");
      return;
    }
    svqMutate((d) => {
      if (d.introStepIndex < SVQ_INTRO_STEPS.length - 1) {
        d.introStepIndex += 1;
      } else {
        d.screen = "room-type-select";
      }
    });
    return;
  }

  if (draft.screen === "room-basic-info") {
    svqMutate((d) => {
      const ids = svqRoomObjectIds(d.activeRoomDraft);
      if (ids.length === 0) {
        d.screen = "custom-object-prompt";
      } else {
        d.roomObjectIndex = 0;
        d.screen = "room-object";
      }
    });
    return;
  }

  if (draft.screen === "room-object") {
    svqMutate((d) => {
      const ids = svqRoomObjectIds(d.activeRoomDraft);
      if (d.roomObjectIndex < ids.length - 1) {
        d.roomObjectIndex += 1;
      } else {
        d.screen = "custom-object-prompt";
      }
    });
    return;
  }

  if (draft.screen === "room-summary") {
    svqMutate((d) => {
      svqCommitActiveRoom(d);
      d.screen = "room-next-action";
    });
    return;
  }
}

function svqGoBack() {
  const draft = svqCurrentDraft();
  if (!draft) return;

  if (draft.screen === "intro") {
    svqMutate((d) => {
      if (d.introStepIndex > 0) {
        d.introStepIndex -= 1;
      } else {
        svqState.screen = "home";
      }
    });
    if (draft.introStepIndex === 0) svqRender();
    return;
  }

  if (draft.screen === "room-type-select") {
    svqMutate((d) => {
      if (d.rooms.length === 0) {
        d.screen = "intro";
        d.introStepIndex = SVQ_INTRO_STEPS.length - 1;
      } else {
        d.screen = "room-next-action";
      }
    });
    return;
  }

  if (draft.screen === "room-basic-info") {
    svqMutate((d) => {
      d.screen = "room-type-select";
    });
    return;
  }

  if (draft.screen === "room-object") {
    svqMutate((d) => {
      if (d.roomObjectIndex > 0) {
        d.roomObjectIndex -= 1;
      } else {
        d.screen = d.activeRoomDraft.type === "custom" ? "room-basic-info" : "room-type-select";
      }
    });
    return;
  }

  if (draft.screen === "custom-object-prompt") {
    svqMutate((d) => {
      const ids = svqRoomObjectIds(d.activeRoomDraft);
      if (ids.length > 0) {
        d.roomObjectIndex = ids.length - 1;
        d.screen = "room-object";
      } else {
        d.screen = "room-basic-info";
      }
    });
    return;
  }

  if (draft.screen === "custom-object-form") {
    svqMutate((d) => {
      d.screen = "custom-object-prompt";
    });
    return;
  }

  if (draft.screen === "room-summary") {
    svqMutate((d) => {
      d.screen = "custom-object-prompt";
    });
    return;
  }

  if (draft.screen === "room-next-action") {
    svqMutate((d) => {
      d.screen = "room-summary";
    });
    return;
  }

  if (draft.screen === "rooms-overview") {
    svqMutate((d) => {
      d.screen = "room-next-action";
    });
    return;
  }

  if (draft.screen === "finish-confirm") {
    svqMutate((d) => {
      d.screen = "rooms-overview";
    });
    return;
  }
}

function svqShowValidation(message) {
  if (typeof showToast === "function") {
    showToast(message);
  } else {
    window.alert(message);
  }
}

function svqCommitActiveRoom(draft) {
  if (!draft.activeRoomDraft) return;
  draft.activeRoomDraft.updatedAt = svqNowIso();
  if (draft.editingRoomId) {
    const index = draft.rooms.findIndex((room) => room.id === draft.editingRoomId);
    if (index !== -1) {
      draft.rooms[index] = draft.activeRoomDraft;
    } else {
      draft.rooms.push(draft.activeRoomDraft);
    }
  } else {
    draft.rooms.push(draft.activeRoomDraft);
  }
  draft.lastTouchedRoomId = draft.activeRoomDraft.id;
  draft.activeRoomDraft = null;
  draft.editingRoomId = null;
  draft.roomObjectIndex = 0;
}

/* ---------- Übergabe an die echte Begehungs-Datenbank (api/site-visits.php) ---------- */

function svqLegacyFrequency(intervalId, customInterval) {
  const direct = {
    daily: "Täglich",
    every_2_days: "Alle 2 Tage",
    weekly: "Wöchentlich",
    biweekly: "14-täglich",
    monthly: "30-täglich",
  };
  if (direct[intervalId]) {
    return { frequency: direct[intervalId], customFrequency: "" };
  }
  const customLabel = intervalId === "custom" ? customInterval : svqIntervalLabel(intervalId);
  return { frequency: "Individuell", customFrequency: customLabel || "nach Bedarf" };
}

function svqRoomCleaningItems(room) {
  const items = [];
  Object.values(room.objects || {}).forEach((entry) => {
    if (!entry.present || !entry.shouldClean) return;
    const def = SVQ_OBJECTS[entry.objectId];
    if (!def) return;
    const { frequency, customFrequency } = svqLegacyFrequency(entry.interval, entry.customInterval);
    items.push({
      key: def.legacyKey,
      frequency,
      customFrequency,
      bagMode: entry.trashBag === false ? "Ohne Mülltüte" : "Mit Mülltüte",
    });
  });
  (room.customObjects || []).forEach((obj) => {
    if (!obj.shouldClean) return;
    const intervalLabel = obj.interval === "custom" ? obj.customInterval : svqIntervalLabel(obj.interval);
    items.push({
      key: "surface",
      frequency: "Individuell",
      customFrequency: [obj.name, intervalLabel].filter(Boolean).join(" – "),
    });
  });
  return items;
}

function svqBuildSiteVisitPayload(draft) {
  const rooms = draft.rooms
    .map((room) => {
      const cleaningItems = svqRoomCleaningItems(room);
      if (cleaningItems.length === 0) return null;
      return {
        name: room.label || room.typeLabel,
        roomType: svqRoomType(room.type)?.legacyType || "Sonstiger Raum",
        quantity: 1,
        squareMeters: 0,
        cleaningItems,
        notes: "",
      };
    })
    .filter(Boolean);

  const floors = rooms.length ? [{ name: "Gesamtes Objekt", rooms }] : [];
  const areaValue = Number(draft.areaSize.value) || 0;
  const address = [`${draft.address.street} ${draft.address.houseNumber}`.trim(), `${draft.address.zip} ${draft.address.city}`.trim()]
    .filter(Boolean)
    .join(", ");

  return {
    companyName: draft.company.name,
    email: draft.company.email,
    phone: draft.company.phone,
    address: address || "Adresse nicht angegeben",
    onsiteContact: `${draft.contact.firstName} ${draft.contact.lastName}`.trim() || "Ansprechpartner vor Ort",
    squareMeters: areaValue > 0 ? areaValue : 1,
    floors,
    notes: "Über das Begehungs-Quiz erfasst.",
  };
}

async function svqSubmitSiteVisitToBackend(draft) {
  const payload = svqBuildSiteVisitPayload(draft);
  if (payload.floors.length === 0) {
    return { ok: false, reason: "no-cleaning-items" };
  }
  if (typeof apiPost !== "function") {
    return { ok: false, reason: "api-error", message: "Keine Verbindung zum Server." };
  }
  try {
    const created = await apiPost("api/site-visits.php", payload);
    return { ok: true, id: created.id };
  } catch (error) {
    return { ok: false, reason: "api-error", message: error.message };
  }
}

/* ---------- Rendering ---------- */

function svqRoot() {
  return document.querySelector("#site-visit-quiz-root");
}

function svqShow() {
  svqRender();
}

function svqRender() {
  const root = svqRoot();
  if (!root) return;

  if (svqState.screen === "home") {
    root.innerHTML = svqRenderHome();
  } else if (svqState.screen === "drafts-list") {
    root.innerHTML = svqRenderList("draft");
  } else if (svqState.screen === "completed-list") {
    root.innerHTML = svqRenderList("completed");
  } else {
    const draft = svqCurrentDraft();
    if (!draft) {
      svqState.screen = "home";
      root.innerHTML = svqRenderHome();
    } else {
      root.innerHTML = svqRenderQuizShell(draft);
    }
  }

  if (window.lucide) {
    window.lucide.createIcons();
  }
}

function svqRenderHome() {
  const drafts = SiteVisitQuizStore.list().filter((d) => d.status === "draft");
  const completed = SiteVisitQuizStore.list().filter((d) => d.status === "completed");

  return `
    <div class="svq-home">
      <div class="svq-home-intro">
        <h3>Begehung erstellen</h3>
        <p class="muted">Geführtes Quiz für die digitale Objektbegehung – auf dem Tablet vor Ort oder am Schreibtisch.</p>
      </div>
      <div class="svq-home-menu">
        <button class="svq-home-card" type="button" data-svq-action="home-new">
          <i data-lucide="clipboard-plus" aria-hidden="true"></i>
          <div>
            <strong>Neue Begehung erstellen</strong>
            <span>Startet das geführte Quiz von vorn.</span>
          </div>
        </button>
        <button class="svq-home-card" type="button" data-svq-action="home-drafts">
          <i data-lucide="file-clock" aria-hidden="true"></i>
          <div>
            <strong>Entwürfe</strong>
            <span>${drafts.length} nicht abgeschlossene Begehung${drafts.length === 1 ? "" : "en"}</span>
          </div>
        </button>
        <button class="svq-home-card" type="button" data-svq-action="home-completed">
          <i data-lucide="badge-check" aria-hidden="true"></i>
          <div>
            <strong>Abgeschlossene Begehungen</strong>
            <span>${completed.length} fertiggestellt</span>
          </div>
        </button>
      </div>
    </div>
  `;
}

function svqRenderList(status) {
  const items = SiteVisitQuizStore.list()
    .filter((d) => d.status === status)
    .sort((a, b) => new Date(b.updatedAt) - new Date(a.updatedAt));

  const rows = items
    .map((draft) => {
      const address = [draft.address.street, draft.address.houseNumber].filter(Boolean).join(" ");
      const cityLine = [draft.address.zip, draft.address.city].filter(Boolean).join(" ");
      const contact = [draft.contact.firstName, draft.contact.lastName].filter(Boolean).join(" ");
      return `
        <article class="svq-list-item">
          <button class="svq-list-item-main" type="button" data-svq-action="open-draft" data-svq-id="${svqEsc(draft.id)}">
            <strong>${svqEsc(draft.company.name) || "Ohne Firmennamen"}</strong>
            <span class="record-meta">
              <span>${svqEsc(address || "Keine Adresse")}${cityLine ? ", " + svqEsc(cityLine) : ""}</span>
              <span>${svqEsc(contact || "Kein Ansprechpartner")}</span>
              <span>Erstellt: ${svqFormatDate(draft.createdAt)}</span>
              <span>Zuletzt bearbeitet: ${svqFormatDate(draft.updatedAt)}</span>
              ${status === "draft" ? `<span>Stand: ${svqEsc(svqProgressLabel(draft))}</span>` : `<span>Version ${svqEsc(draft.version)}</span>`}
            </span>
          </button>
          <span class="badge ${status === "draft" ? "warning" : "success"}">${status === "draft" ? "Entwurf" : "Abgeschlossen"}</span>
          <button class="icon-button" type="button" data-svq-action="delete-draft" data-svq-id="${svqEsc(draft.id)}" aria-label="Löschen">
            <i data-lucide="trash-2" aria-hidden="true"></i>
          </button>
        </article>
      `;
    })
    .join("");

  return `
    <div class="svq-list-view">
      <div class="svq-list-header">
        <button class="ghost-button" type="button" data-svq-action="back-home">
          <i data-lucide="arrow-left" aria-hidden="true"></i>
          Zurück
        </button>
        <h3>${status === "draft" ? "Entwürfe" : "Abgeschlossene Begehungen"}</h3>
      </div>
      <div class="svq-list-body">
        ${rows || `<div class="empty-state">${status === "draft" ? "Keine Entwürfe vorhanden." : "Noch keine abgeschlossene Begehung."}</div>`}
      </div>
    </div>
  `;
}

function svqRenderQuizShell(draft) {
  const percent = svqProgressPercent(draft);
  const showNav = !["finish-success"].includes(draft.screen);
  const showBack = !["finish-confirm", "finish-success"].includes(draft.screen);
  const showNext = ["intro", "room-basic-info", "room-object", "room-summary"].includes(draft.screen);

  return `
    <div class="svq-quiz">
      <div class="svq-progress-bar" role="progressbar" aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100">
        <div class="svq-progress-fill" style="width:${percent}%"></div>
      </div>
      <div class="svq-progress-label">${svqEsc(svqProgressLabel(draft))} · ${percent}%</div>

      <div class="svq-screen">
        ${svqRenderScreen(draft)}
      </div>

      ${
        showNav
          ? `
        <div class="svq-nav">
          <div class="svq-nav-primary">
            ${showBack ? `<button class="secondary-button" type="button" data-svq-action="quiz-back"><i data-lucide="arrow-left" aria-hidden="true"></i>Zurück</button>` : "<span></span>"}
            ${showNext ? `<button class="primary-button" type="button" data-svq-action="quiz-next">Weiter<i data-lucide="arrow-right" aria-hidden="true"></i></button>` : ""}
          </div>
          <div class="svq-nav-secondary">
            <button class="ghost-button" type="button" data-svq-action="quiz-save-draft">
              <i data-lucide="save" aria-hidden="true"></i>
              Als Entwurf speichern
            </button>
            <button class="ghost-button svq-danger-link" type="button" data-svq-action="quiz-cancel">
              <i data-lucide="x" aria-hidden="true"></i>
              Begehung abbrechen
            </button>
          </div>
        </div>
      `
          : ""
      }
    </div>
  `;
}

function svqRenderScreen(draft) {
  switch (draft.screen) {
    case "intro":
      return svqRenderIntroStep(draft);
    case "room-type-select":
      return svqRenderRoomTypeSelect();
    case "room-basic-info":
      return svqRenderRoomBasicInfo(draft);
    case "room-object":
      return svqRenderRoomObject(draft);
    case "custom-object-prompt":
      return svqRenderCustomObjectPrompt(draft);
    case "custom-object-form":
      return svqRenderCustomObjectForm();
    case "room-summary":
      return svqRenderRoomSummary(draft);
    case "room-next-action":
      return svqRenderRoomNextAction(draft);
    case "rooms-overview":
      return svqRenderRoomsOverview(draft);
    case "finish-confirm":
      return svqRenderFinishConfirm();
    case "finish-success":
      return svqRenderFinishSuccess(draft);
    default:
      return "";
  }
}

function svqRenderIntroStep(draft) {
  const step = SVQ_INTRO_STEPS[draft.introStepIndex];

  if (step === "company-name") {
    return `
      <h3>Wie lautet der Name des Unternehmens?</h3>
      <label class="svq-field">
        Firmenname
        <input type="text" data-svq-input="company.name" value="${svqEsc(draft.company.name)}" placeholder="z. B. Beispiel GmbH" autofocus />
      </label>
    `;
  }
  if (step === "company-phone") {
    return `
      <h3>Wie lautet die Telefonnummer des Unternehmens?</h3>
      <label class="svq-field">
        Telefonnummer
        <input type="tel" data-svq-input="company.phone" value="${svqEsc(draft.company.phone)}" placeholder="+49 711 123456" />
      </label>
    `;
  }
  if (step === "company-email") {
    return `
      <h3>Wie lautet die E-Mail-Adresse des Unternehmens?</h3>
      <label class="svq-field">
        E-Mail-Adresse
        <input type="email" data-svq-input="company.email" value="${svqEsc(draft.company.email)}" placeholder="kontakt@firma.de" />
      </label>
    `;
  }
  if (step === "address") {
    return `
      <h3>Wie lautet die Objektadresse?</h3>
      <div class="svq-field-grid">
        <label class="svq-field span-2">Straße<input type="text" data-svq-input="address.street" value="${svqEsc(draft.address.street)}" /></label>
        <label class="svq-field">Hausnummer<input type="text" data-svq-input="address.houseNumber" value="${svqEsc(draft.address.houseNumber)}" /></label>
        <label class="svq-field">Postleitzahl<input type="text" inputmode="numeric" data-svq-input="address.zip" value="${svqEsc(draft.address.zip)}" /></label>
        <label class="svq-field span-2">Stadt<input type="text" data-svq-input="address.city" value="${svqEsc(draft.address.city)}" /></label>
      </div>
    `;
  }
  if (step === "contact") {
    return `
      <h3>Wer ist Ansprechpartner vor Ort?</h3>
      <div class="svq-field-grid">
        <label class="svq-field">Vorname<input type="text" data-svq-input="contact.firstName" value="${svqEsc(draft.contact.firstName)}" /></label>
        <label class="svq-field">Nachname<input type="text" data-svq-input="contact.lastName" value="${svqEsc(draft.contact.lastName)}" /></label>
        <label class="svq-field span-2">Funktion / Position<input type="text" data-svq-input="contact.role" value="${svqEsc(draft.contact.role)}" /></label>
        <label class="svq-field">Telefonnummer<input type="tel" data-svq-input="contact.phone" value="${svqEsc(draft.contact.phone)}" /></label>
        <label class="svq-field">E-Mail-Adresse<input type="email" data-svq-input="contact.email" value="${svqEsc(draft.contact.email)}" /></label>
      </div>
    `;
  }
  if (step === "area-size") {
    const modes = [
      { id: "exact", label: "genaue Quadratmeterzahl" },
      { id: "estimated", label: "geschätzte Quadratmeterzahl" },
      { id: "unknown", label: "noch nicht bekannt" },
    ];
    return `
      <h3>Wie groß ist die zu reinigende Fläche?</h3>
      <label class="svq-field">
        Quadratmeter
        <input type="number" min="0" data-svq-input="areaSize.value" value="${svqEsc(draft.areaSize.value)}" placeholder="z. B. 450" />
      </label>
      <div class="svq-choice-row">
        ${modes
          .map(
            (mode) => `
          <button class="svq-choice-pill${draft.areaSize.mode === mode.id ? " active" : ""}" type="button"
            data-svq-action="pick-area-mode" data-svq-mode="${mode.id}">${mode.label}</button>
        `
          )
          .join("")}
      </div>
    `;
  }
  return "";
}

function svqRenderRoomTypeSelect() {
  return `
    <h3>Welchen Raum möchtest du erfassen?</h3>
    <div class="svq-room-type-grid">
      ${SVQ_ROOM_TYPES.map(
        (type) => `
        <button class="svq-room-type-card" type="button" data-svq-action="select-room-type" data-svq-type="${type.id}">
          <i data-lucide="${type.icon}" aria-hidden="true"></i>
          <span>${svqEsc(type.label)}</span>
        </button>
      `
      ).join("")}
    </div>
  `;
}

function svqRenderRoomBasicInfo(draft) {
  const room = draft.activeRoomDraft;
  return `
    <h3>Wie soll der Raum heißen?</h3>
    <label class="svq-field">
      Bezeichnung des Raumes
      <input type="text" data-svq-input="activeRoomDraft.label" value="${svqEsc(room.label)}" placeholder="z. B. Serverraum" autofocus />
    </label>
  `;
}

function svqRenderYesNo(question, field, value, action = "yesno") {
  return `
    <div class="svq-yesno-block">
      <p class="svq-question">${question}</p>
      <div class="svq-yesno-row">
        <button class="svq-yesno-button${value === true ? " active-yes" : ""}" type="button" data-svq-action="${action}" data-svq-field="${field}" data-svq-value="true">
          <i data-lucide="check" aria-hidden="true"></i>Ja
        </button>
        <button class="svq-yesno-button${value === false ? " active-no" : ""}" type="button" data-svq-action="${action}" data-svq-field="${field}" data-svq-value="false">
          <i data-lucide="x" aria-hidden="true"></i>Nein
        </button>
      </div>
    </div>
  `;
}

function svqRenderIntervalPicker(field, entry, action = "select-interval") {
  return `
    <div class="svq-interval-block">
      <p class="svq-question">In welchem Intervall soll gereinigt werden?</p>
      <div class="svq-interval-grid">
        ${SVQ_INTERVALS.map(
          (interval) => `
          <button class="svq-choice-pill${entry.interval === interval.id ? " active" : ""}" type="button"
            data-svq-action="${action}" data-svq-field="${field}" data-svq-interval="${interval.id}">${interval.label}</button>
        `
        ).join("")}
      </div>
      ${
        entry.interval === "custom" && action === "select-interval"
          ? `<label class="svq-field">Individuelles Intervall<input type="text" data-svq-input="${field}.customInterval" value="${svqEsc(entry.customInterval)}" placeholder="z. B. jeden Montag und Donnerstag" /></label>`
          : ""
      }
    </div>
  `;
}

function svqRenderObjectExtraFields(field, entry, extra) {
  if (!extra.length) return "";
  return `
    <div class="svq-field-grid">
      ${extra.includes("quantity") ? `<label class="svq-field">Anzahl<input type="number" min="0" data-svq-input="${field}.quantity" value="${svqEsc(entry.quantity)}" /></label>` : ""}
      ${
        extra.includes("trashBag")
          ? `
        <div class="svq-field">
          Mülltüten
          <div class="svq-choice-row">
            <button class="svq-choice-pill${entry.trashBag === true ? " active" : ""}" type="button" data-svq-action="yesno" data-svq-field="${field}.trashBag" data-svq-value="true">mit Mülltüte</button>
            <button class="svq-choice-pill${entry.trashBag === false ? " active" : ""}" type="button" data-svq-action="yesno" data-svq-field="${field}.trashBag" data-svq-value="false">ohne Mülltüte</button>
          </div>
        </div>
      `
          : ""
      }
    </div>
  `;
}

function svqRenderRoomObject(draft) {
  const room = draft.activeRoomDraft;
  const ids = svqRoomObjectIds(room);
  const objectId = ids[draft.roomObjectIndex];
  const entry = svqGetOrCreateObjectEntry(room, objectId);
  const def = SVQ_OBJECTS[objectId] || { label: objectId, extra: [] };
  const field = `activeRoomDraft.objects.${objectId}`;

  return `
    <div class="svq-counter">${draft.roomObjectIndex + 1} / ${ids.length}</div>
    <h3>${svqEsc(def.label)}</h3>
    ${svqRenderYesNo(`Ist${def.label.toLowerCase().startsWith("keine") ? "" : ""} „${svqEsc(def.label)}“ vorhanden?`, `${field}.present`, entry.present)}
    ${
      entry.present === true
        ? `
      ${svqRenderYesNo(`Soll „${svqEsc(def.label)}“ gereinigt werden?`, `${field}.shouldClean`, entry.shouldClean)}
      ${
        entry.shouldClean === true
          ? `
        ${svqRenderObjectExtraFields(field, entry, def.extra)}
        ${svqRenderIntervalPicker(field, entry)}
      `
          : ""
      }
    `
        : ""
    }
    <label class="svq-field">Notiz<input type="text" data-svq-input="${field}.note" value="${svqEsc(entry.note)}" placeholder="optional" /></label>
  `;
}

function svqRenderCustomObjectPrompt(draft) {
  const room = draft.activeRoomDraft;
  const custom = room.customObjects || [];
  return `
    <h3>Was möchtest du als Nächstes machen?</h3>
    ${
      custom.length
        ? `<div class="svq-mini-list">${custom
            .map(
              (obj) => `<div class="svq-mini-list-item"><strong>${svqEsc(obj.name)}</strong><span>${obj.shouldClean ? svqEsc(obj.interval === "custom" ? obj.customInterval : svqIntervalLabel(obj.interval)) : "wird nicht gereinigt"}</span></div>`
            )
            .join("")}</div>`
        : ""
    }
    <p class="muted">Möchtest du ein weiteres individuelles Objekt für „${svqEsc(room.label || room.typeLabel)}“ hinzufügen?</p>
    <div class="svq-choice-row">
      <button class="primary-button" type="button" data-svq-action="custom-object-yes">
        <i data-lucide="plus" aria-hidden="true"></i>Individuelles Objekt hinzufügen
      </button>
      <button class="secondary-button" type="button" data-svq-action="custom-object-no">
        Nein, weiter zur Raumübersicht
      </button>
    </div>
  `;
}

function svqRenderCustomObjectForm() {
  const formState = svqCustomObjectFormState;
  return `
    <h3>Individuelles Objekt</h3>
    <div class="svq-field-grid">
      <label class="svq-field span-2">Bezeichnung des Objekts<input type="text" id="svq-custom-name" placeholder="z. B. Kaffeeautomat" /></label>
      <label class="svq-field">Anzahl<input type="number" min="0" id="svq-custom-quantity" /></label>
    </div>
    ${svqRenderYesNo("Soll gereinigt werden?", "svq-custom-clean", formState.shouldClean, "custom-yesno")}
    <div id="svq-custom-interval-slot">${formState.shouldClean === true ? svqRenderIntervalPicker("svq-custom", formState, "custom-interval") : ""}</div>
    <label class="svq-field">Notiz<input type="text" id="svq-custom-note" placeholder="optional" /></label>
    <div class="svq-choice-row">
      <button class="primary-button" type="button" data-svq-action="custom-object-submit">
        <i data-lucide="check" aria-hidden="true"></i>Objekt hinzufügen
      </button>
    </div>
  `;
}

function svqRenderRoomSummary(draft) {
  const room = draft.activeRoomDraft;
  return `
    <h3>Raum erfasst: ${svqEsc(room.label || room.typeLabel)}</h3>
    ${svqRenderRoomCard(room, { compact: true })}
  `;
}

function svqRenderRoomCard(room, { compact = false, index = null } = {}) {
  const cleaned = svqRoomObjectEntries(room).filter((entry) => entry.present && entry.shouldClean);
  const notCleaned = svqRoomObjectEntries(room).filter((entry) => entry.present && !entry.shouldClean);

  return `
    <article class="svq-room-card">
      <header>
        <div>
          <strong>${svqEsc(room.label || room.typeLabel)}</strong>
          <span class="record-meta">
            <span>${svqEsc(room.typeLabel)}</span>
          </span>
        </div>
        ${
          !compact && index !== null
            ? `
          <div class="svq-room-card-actions">
            <button class="icon-button" type="button" data-svq-action="room-edit" data-svq-id="${svqEsc(room.id)}" aria-label="Bearbeiten"><i data-lucide="pencil" aria-hidden="true"></i></button>
            <button class="icon-button" type="button" data-svq-action="room-duplicate" data-svq-id="${svqEsc(room.id)}" aria-label="Duplizieren"><i data-lucide="copy" aria-hidden="true"></i></button>
            <button class="icon-button" type="button" data-svq-action="room-delete" data-svq-id="${svqEsc(room.id)}" aria-label="Löschen"><i data-lucide="trash-2" aria-hidden="true"></i></button>
          </div>
        `
            : ""
        }
      </header>
      <div class="svq-room-card-body">
        <p><strong>${cleaned.length}</strong> zu reinigende Objekte, ${notCleaned.length} vorhanden ohne Reinigung.</p>
        ${
          cleaned.length
            ? `<ul class="svq-object-list">${cleaned
                .map(
                  (entry) =>
                    `<li>${svqEsc(entry.name)} – ${svqEsc(entry.interval === "custom" ? entry.customInterval : svqIntervalLabel(entry.interval))}</li>`
                )
                .join("")}</ul>`
            : ""
        }
      </div>
    </article>
  `;
}

function svqRoomObjectEntries(room) {
  const predefined = Object.values(room.objects || {}).map((entry) => ({ ...entry, name: svqObjectLabel(entry.objectId) }));
  const custom = (room.customObjects || []).map((entry) => ({ ...entry, present: true, name: entry.name }));
  return [...predefined, ...custom];
}

function svqRenderRoomNextAction(draft) {
  const lastRoom = draft.rooms.find((room) => room.id === draft.lastTouchedRoomId) || draft.rooms[draft.rooms.length - 1];
  return `
    <h3>Was möchtest du als Nächstes machen?</h3>
    ${lastRoom ? svqRenderRoomCard(lastRoom, { compact: true }) : ""}
    <div class="svq-menu-list">
      <button class="svq-menu-item" type="button" data-svq-action="next-add-room">
        <i data-lucide="plus" aria-hidden="true"></i>Weiteren Raum hinzufügen
      </button>
      <button class="svq-menu-item" type="button" data-svq-action="next-view-rooms">
        <i data-lucide="list" aria-hidden="true"></i>Erfasste Räume ansehen
      </button>
      ${
        lastRoom
          ? `
        <button class="svq-menu-item" type="button" data-svq-action="room-edit" data-svq-id="${svqEsc(lastRoom.id)}">
          <i data-lucide="pencil" aria-hidden="true"></i>Raum bearbeiten
        </button>
        <button class="svq-menu-item svq-danger-link" type="button" data-svq-action="room-delete" data-svq-id="${svqEsc(lastRoom.id)}">
          <i data-lucide="trash-2" aria-hidden="true"></i>Raum löschen
        </button>
      `
          : ""
      }
      <button class="svq-menu-item svq-menu-item-primary" type="button" data-svq-action="next-finish">
        <i data-lucide="flag" aria-hidden="true"></i>Begehung fertigstellen
      </button>
    </div>
  `;
}

function svqRenderRoomsOverview(draft) {
  return `
    <h3>Übersicht der erfassten Räume</h3>
    ${
      draft.rooms.length
        ? `<div class="svq-room-grid">${draft.rooms.map((room, index) => svqRenderRoomCard(room, { index })).join("")}</div>`
        : `<div class="empty-state">Noch keine Räume erfasst.</div>`
    }
    <div class="svq-choice-row">
      <button class="secondary-button" type="button" data-svq-action="next-add-room">
        <i data-lucide="plus" aria-hidden="true"></i>Weiteren Raum hinzufügen
      </button>
      <button class="primary-button svq-finish-button" type="button" data-svq-action="rooms-overview-finish" ${draft.rooms.length ? "" : "disabled"}>
        <i data-lucide="flag" aria-hidden="true"></i>Begehung fertig
      </button>
    </div>
  `;
}

function svqRenderFinishConfirm() {
  return `
    <div class="svq-confirm-box">
      <i data-lucide="alert-triangle" aria-hidden="true"></i>
      <h3>Möchtest du die Begehung wirklich abschließen?</h3>
      <p class="muted">Bitte prüfe vorher alle Räume und Angaben.</p>
      <div class="svq-choice-row">
        <button class="secondary-button" type="button" data-svq-action="finish-cancel">Zurück zur Prüfung</button>
        <button class="primary-button" type="button" data-svq-action="finish-confirm">Begehung fertigstellen</button>
      </div>
    </div>
  `;
}

function svqRenderFinishSuccess(draft) {
  return `
    <div class="svq-success-box">
      <i data-lucide="party-popper" aria-hidden="true"></i>
      <h3>Die Begehung wurde erfolgreich fertiggestellt.</h3>
      <p class="muted">${svqEsc(draft.company.name)} · ${draft.rooms.length} Räume erfasst · Version ${svqEsc(draft.version)}</p>
      <div class="svq-menu-list">
        ${
          draft.linkedSiteVisitId
            ? `<button class="svq-menu-item svq-menu-item-primary" type="button" data-svq-action="finish-create-offer"><i data-lucide="file-plus-2" aria-hidden="true"></i>Kostenvoranschlag erstellen</button>`
            : `<p class="muted">${svqEsc(draft.linkedSiteVisitError || "Für diese Begehung wurde kein Datensatz für Kostenvoranschläge angelegt.")}</p>`
        }
        <button class="svq-menu-item" type="button" data-svq-action="finish-view"><i data-lucide="eye" aria-hidden="true"></i>Begehung ansehen</button>
        <button class="svq-menu-item" type="button" data-svq-action="finish-edit"><i data-lucide="pencil" aria-hidden="true"></i>Begehung bearbeiten</button>
        <button class="svq-menu-item" type="button" data-svq-action="finish-pdf"><i data-lucide="file-text" aria-hidden="true"></i>PDF-Bericht vorbereiten</button>
        <button class="svq-menu-item" type="button" data-svq-action="finish-new"><i data-lucide="plus" aria-hidden="true"></i>Neue Begehung starten</button>
        <button class="svq-menu-item" type="button" data-svq-action="finish-home"><i data-lucide="home" aria-hidden="true"></i>Zurück zum Hauptmenü</button>
      </div>
    </div>
  `;
}

/* ---------- Event-Delegation ---------- */

async function svqHandleClick(event) {
  const button = event.target.closest("[data-svq-action]");
  if (!button) return;
  event.preventDefault();
  const action = button.dataset.svqAction;
  const id = button.dataset.svqId;

  if (action === "home-new") {
    const draft = svqNewDraft();
    SiteVisitQuizStore.save(draft);
    svqState.draftId = draft.id;
    svqState.screen = "quiz";
    svqRender();
    return;
  }
  if (action === "home-drafts") {
    svqState.screen = "drafts-list";
    svqRender();
    return;
  }
  if (action === "home-completed") {
    svqState.screen = "completed-list";
    svqRender();
    return;
  }
  if (action === "back-home") {
    svqState.screen = "home";
    svqRender();
    return;
  }
  if (action === "open-draft") {
    const draft = SiteVisitQuizStore.get(id);
    svqState.draftId = id;
    svqState.screen = "quiz";
    if (draft && draft.status === "completed" && draft.screen !== "rooms-overview") {
      svqMutate((d) => {
        d.screen = "rooms-overview";
      });
    } else {
      svqRender();
    }
    return;
  }
  if (action === "delete-draft") {
    if (window.confirm("Diese Begehung wirklich löschen?")) {
      SiteVisitQuizStore.remove(id);
      svqRender();
    }
    return;
  }

  if (action === "quiz-back") {
    svqGoBack();
    return;
  }
  if (action === "quiz-next") {
    svqGoNext();
    return;
  }
  if (action === "quiz-save-draft") {
    svqShowValidation("Entwurf gespeichert.");
    svqState.screen = "home";
    svqRender();
    return;
  }
  if (action === "quiz-cancel") {
    if (window.confirm("Begehung abbrechen? Der bisherige Stand bleibt als Entwurf gespeichert.")) {
      svqState.screen = "home";
      svqRender();
    }
    return;
  }

  if (action === "pick-area-mode") {
    svqMutate((d) => {
      d.areaSize.mode = button.dataset.svqMode;
    });
    return;
  }

  if (action === "select-room-type") {
    const typeId = button.dataset.svqType;
    svqMutate((d) => {
      d.activeRoomDraft = svqNewRoomDraft(typeId, typeId === "custom" ? "" : svqNextRoomLabel(d, typeId));
      d.editingRoomId = null;
      if (typeId === "custom") {
        d.screen = "room-basic-info";
      } else {
        d.roomObjectIndex = 0;
        d.screen = svqRoomObjectIds(d.activeRoomDraft).length === 0 ? "custom-object-prompt" : "room-object";
      }
    });
    return;
  }

  if (action === "yesno") {
    const field = button.dataset.svqField;
    const value = button.dataset.svqValue === "true";
    svqMutate((d) => {
      svqSetPath(d, field, value);
      if (field.endsWith(".present") && value === false) {
        svqSetPath(d, field.replace(".present", ".shouldClean"), null);
      }
      if (field.endsWith(".shouldClean") && value === false) {
        svqSetPath(d, field.replace(".shouldClean", ".interval"), null);
      }
    });
    return;
  }

  if (action === "select-interval") {
    const field = button.dataset.svqField;
    svqMutate((d) => {
      svqSetPath(d, `${field}.interval`, button.dataset.svqInterval);
    });
    return;
  }

  if (action === "custom-object-yes") {
    svqMutate((d) => {
      d.screen = "custom-object-form";
    });
    return;
  }
  if (action === "custom-object-no") {
    svqMutate((d) => {
      d.screen = "room-summary";
    });
    return;
  }
  if (action === "custom-object-submit") {
    const name = document.querySelector("#svq-custom-name")?.value.trim();
    if (!name) {
      svqShowValidation("Bitte eine Bezeichnung für das Objekt eingeben.");
      return;
    }
    const quantity = document.querySelector("#svq-custom-quantity")?.value || "";
    const note = document.querySelector("#svq-custom-note")?.value || "";
    const shouldClean = svqCustomObjectFormState.shouldClean;
    const interval = svqCustomObjectFormState.interval;
    const customInterval = svqCustomObjectFormState.customInterval;
    svqMutate((d) => {
      d.activeRoomDraft.customObjects.push({
        id: svqUid(),
        name,
        quantity,
        shouldClean: shouldClean === true,
        interval: shouldClean ? interval : null,
        customInterval: shouldClean ? customInterval : "",
        note,
      });
      d.screen = "custom-object-prompt";
    });
    svqCustomObjectFormState.shouldClean = null;
    svqCustomObjectFormState.interval = null;
    svqCustomObjectFormState.customInterval = "";
    return;
  }

  if (action === "next-add-room") {
    svqMutate((d) => {
      d.screen = "room-type-select";
    });
    return;
  }
  if (action === "next-view-rooms") {
    svqMutate((d) => {
      d.screen = "rooms-overview";
    });
    return;
  }
  if (action === "next-finish") {
    svqMutate((d) => {
      d.screen = "rooms-overview";
    });
    return;
  }

  if (action === "room-edit") {
    svqMutate((d) => {
      const room = d.rooms.find((r) => r.id === id) || (d.activeRoomDraft?.id === id ? d.activeRoomDraft : null);
      if (!room) return;
      d.activeRoomDraft = JSON.parse(JSON.stringify(room));
      d.editingRoomId = room.id;
      d.roomObjectIndex = 0;
      d.screen = "room-basic-info";
    });
    return;
  }
  if (action === "room-duplicate") {
    svqMutate((d) => {
      const room = d.rooms.find((r) => r.id === id);
      if (!room) return;
      const clone = JSON.parse(JSON.stringify(room));
      clone.id = svqUid();
      clone.label = `${room.label || room.typeLabel} (Kopie)`;
      clone.createdAt = svqNowIso();
      clone.updatedAt = svqNowIso();
      d.rooms.push(clone);
    });
    return;
  }
  if (action === "room-delete") {
    if (!window.confirm("Diesen Raum wirklich löschen?")) return;
    svqMutate((d) => {
      d.rooms = d.rooms.filter((r) => r.id !== id);
      if (d.screen === "room-next-action" && d.rooms.length === 0) {
        d.screen = "rooms-overview";
      }
    });
    return;
  }

  if (action === "rooms-overview-finish") {
    svqMutate((d) => {
      d.screen = "finish-confirm";
    });
    return;
  }
  if (action === "finish-cancel") {
    svqMutate((d) => {
      d.screen = "rooms-overview";
    });
    return;
  }
  if (action === "finish-confirm") {
    const draftBeforeSubmit = svqCurrentDraft();
    if (!draftBeforeSubmit) return;
    button.disabled = true;
    button.textContent = "Wird gespeichert …";
    const result = await svqSubmitSiteVisitToBackend(draftBeforeSubmit);
    svqMutate((d) => {
      const wasCompleted = d.status === "completed";
      d.status = "completed";
      d.completedAt = svqNowIso();
      d.version = wasCompleted ? d.version + 1 : d.version;
      d.screen = "finish-success";
      d.linkedSiteVisitId = result.ok ? result.id : null;
      d.linkedSiteVisitError = result.ok
        ? null
        : result.reason === "no-cleaning-items"
          ? "Kein Raum hatte zu reinigende Objekte – es konnte kein Datensatz für einen Kostenvoranschlag angelegt werden."
          : `Begehung wurde lokal abgeschlossen, konnte aber nicht für den Kostenvoranschlag übernommen werden: ${result.message || "unbekannter Fehler"}`;
    });
    if (result.ok && typeof loadAll === "function") {
      await loadAll();
    } else if (result.reason === "api-error") {
      svqShowValidation(`Begehung wurde lokal abgeschlossen, konnte aber nicht für den Kostenvoranschlag übernommen werden: ${result.message}`);
    } else if (result.reason === "no-cleaning-items") {
      svqShowValidation("Kein Raum hat zu reinigende Objekte – für den Kostenvoranschlag wurde daher keine Begehung übernommen.");
    }
    return;
  }

  if (action === "finish-view" || action === "finish-edit") {
    svqMutate((d) => {
      if (action === "finish-edit") d.version += 1;
      d.screen = "rooms-overview";
    });
    return;
  }
  if (action === "finish-create-offer") {
    const draft = svqCurrentDraft();
    if (!draft?.linkedSiteVisitId) {
      svqShowValidation("Für diese Begehung wurde kein Datensatz für Kostenvoranschläge angelegt.");
      return;
    }
    if (typeof startOfferFromSiteVisit === "function") {
      await startOfferFromSiteVisit(draft.linkedSiteVisitId);
    }
    return;
  }
  if (action === "finish-pdf") {
    svqShowValidation("PDF-Erstellung folgt mit der Anbindung an das PHP-Backend.");
    return;
  }
  if (action === "finish-new") {
    const draft = svqNewDraft();
    SiteVisitQuizStore.save(draft);
    svqState.draftId = draft.id;
    svqRender();
    return;
  }
  if (action === "finish-home") {
    svqState.screen = "home";
    svqState.draftId = null;
    svqRender();
    return;
  }
}

const svqCustomObjectFormState = { shouldClean: null, interval: null, customInterval: "" };

function svqHandleCustomFormClick(event) {
  const button = event.target.closest('[data-svq-action="custom-yesno"]');
  if (button) {
    event.preventDefault();
    svqCustomObjectFormState.shouldClean = button.dataset.svqValue === "true";
    svqCustomObjectFormState.interval = null;
    svqCustomObjectFormState.customInterval = "";
    button.parentElement.querySelectorAll(".svq-yesno-button").forEach((btn) => btn.classList.remove("active-yes", "active-no"));
    button.classList.add(svqCustomObjectFormState.shouldClean ? "active-yes" : "active-no");
    const slot = document.querySelector("#svq-custom-interval-slot");
    if (slot) {
      slot.innerHTML = svqCustomObjectFormState.shouldClean
        ? svqRenderIntervalPicker("svq-custom", svqCustomObjectFormState, "custom-interval")
        : "";
      if (window.lucide) window.lucide.createIcons();
    }
    return;
  }
  const intervalButton = event.target.closest('[data-svq-action="custom-interval"]');
  if (intervalButton) {
    event.preventDefault();
    svqCustomObjectFormState.interval = intervalButton.dataset.svqInterval;
    intervalButton.parentElement.querySelectorAll(".svq-choice-pill").forEach((btn) => btn.classList.remove("active"));
    intervalButton.classList.add("active");
    const slot = document.querySelector("#svq-custom-interval-slot");
    if (slot) {
      let textField = slot.querySelector("#svq-custom-interval-text");
      if (svqCustomObjectFormState.interval === "custom" && !textField) {
        slot.insertAdjacentHTML(
          "beforeend",
          `<label class="svq-field">Individuelles Intervall<input type="text" id="svq-custom-interval-text" placeholder="z. B. nach Absprache" /></label>`
        );
      } else if (svqCustomObjectFormState.interval !== "custom" && textField) {
        textField.closest("label")?.remove();
      }
    }
  }
}

function svqHandleInput(event) {
  const target = event.target;
  if (target.id === "svq-custom-interval-text") {
    svqCustomObjectFormState.customInterval = target.value;
    return;
  }
  const path = target.dataset.svqInput;
  if (!path) return;
  const draft = svqCurrentDraft();
  if (!draft) return;
  svqSetPath(draft, path, target.value);
  draft.updatedAt = svqNowIso();
  SiteVisitQuizStore.save(draft);
}

function svqInitQuizModule() {
  const root = svqRoot();
  if (!root || root.dataset.svqBound === "true") return;
  root.dataset.svqBound = "true";
  root.addEventListener("click", (event) => {
    svqHandleClick(event);
    svqHandleCustomFormClick(event);
  });
  root.addEventListener("input", svqHandleInput);
}

document.addEventListener("DOMContentLoaded", svqInitQuizModule);
if (document.readyState !== "loading") {
  svqInitQuizModule();
}
