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
  removeMany(ids) {
    const selectedIds = new Set(ids);
    localStorage.setItem(SVQ_STORAGE_KEY, JSON.stringify(this.list().filter((item) => !selectedIds.has(item.id))));
  },
};

const svqState = {
  screen: "home",
  draftId: null,
  customerQuery: "",
  customerSelectMode: null,
  selectedDraftIds: new Set(),
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

function svqGetPath(obj, path) {
  return path.split(".").reduce((target, key) => (target == null ? undefined : target[key]), obj);
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
    customerId: null,
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
    preferences: { lastInterval: "", lastCustomInterval: "" },
  };
}

function svqCurrentDraft() {
  return svqState.draftId ? SiteVisitQuizStore.get(svqState.draftId) : null;
}

function svqCustomerList() {
  if (typeof window.ctCustomerList !== "function") {
    return [];
  }
  return window.ctCustomerList()
    .filter(Boolean)
    .sort((a, b) => String(a.name || "").localeCompare(String(b.name || ""), "de"));
}

function svqCustomerSearchText(customer) {
  return [
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
}

function svqContactNameFromCustomer(customer) {
  return [customer.salutation, customer.contactLastName].filter(Boolean).join(" ");
}

function svqApplyCustomerToDraft(draft, customer) {
  draft.customerId = customer.id || null;
  draft.company.name = customer.name || "";
  draft.company.phone = customer.phone || "";
  draft.company.email = customer.email || "";
  draft.address.street = customer.address || "";
  draft.address.houseNumber = customer.houseNumber || "";
  draft.address.zip = customer.zip || "";
  draft.address.city = customer.city || "";
  draft.contact.firstName = "";
  draft.contact.lastName = customer.contactLastName || "";
  draft.contact.role = customer.salutation || "";
  draft.contact.phone = customer.phone || "";
  draft.contact.email = customer.email || "";
  draft.screen = "intro";
  draft.introStepIndex = SVQ_INTRO_STEPS.indexOf("area-size");
}

function svqStartFromCustomer(customer) {
  if (!customer) {
    svqShowValidation("Bitte zuerst einen Kunden auswählen.");
    return false;
  }
  const reuseDraft = svqState.customerSelectMode === "fill-current" ? svqCurrentDraft() : null;
  const draft = reuseDraft || svqNewDraft();
  svqApplyCustomerToDraft(draft, customer);
  SiteVisitQuizStore.save(draft);
  svqState.draftId = draft.id;
  svqState.screen = "quiz";
  svqState.customerQuery = "";
  svqState.customerSelectMode = null;
  svqRender();
  return true;
}

window.svqStartFromCustomer = svqStartFromCustomer;

function svqContractLinkedSiteVisitIds() {
  if (typeof window.ctContractLinkedSiteVisitIds !== "function") {
    return new Set();
  }
  return new Set(window.ctContractLinkedSiteVisitIds().filter(Boolean));
}

function svqIsContractLinkedCompletedDraft(draft, hiddenSiteVisitIds = svqContractLinkedSiteVisitIds()) {
  return draft?.status === "completed"
    && draft.linkedSiteVisitId
    && hiddenSiteVisitIds.has(draft.linkedSiteVisitId);
}

function svqVisibleDraftsByStatus(status) {
  const hiddenSiteVisitIds = svqContractLinkedSiteVisitIds();
  return SiteVisitQuizStore.list()
    .filter((draft) => draft.status === status)
    .filter((draft) => !svqIsContractLinkedCompletedDraft(draft, hiddenSiteVisitIds));
}

function svqDraftSelection() {
  if (!(svqState.selectedDraftIds instanceof Set)) {
    svqState.selectedDraftIds = new Set(Array.isArray(svqState.selectedDraftIds) ? svqState.selectedDraftIds : []);
  }
  return svqState.selectedDraftIds;
}

function svqClearDraftSelection() {
  svqDraftSelection().clear();
}

function svqSelectedVisibleDraftIds() {
  const visibleIds = new Set(svqVisibleDraftsByStatus("draft").map((draft) => draft.id));
  const selection = svqDraftSelection();
  [...selection].forEach((draftId) => {
    if (!visibleIds.has(draftId)) {
      selection.delete(draftId);
    }
  });
  return [...selection].filter((draftId) => visibleIds.has(draftId));
}

function svqPruneContractLinkedCompletedVisits() {
  const hiddenSiteVisitIds = svqContractLinkedSiteVisitIds();
  if (hiddenSiteVisitIds.size === 0) {
    return false;
  }

  const drafts = SiteVisitQuizStore.list();
  const remaining = drafts.filter((draft) => !svqIsContractLinkedCompletedDraft(draft, hiddenSiteVisitIds));
  if (remaining.length === drafts.length) {
    return false;
  }

  try {
    localStorage.setItem(SVQ_STORAGE_KEY, JSON.stringify(remaining));
  } catch (error) {
    return false;
  }
  if (svqState.draftId && !remaining.some((draft) => draft.id === svqState.draftId)) {
    svqState.draftId = null;
    svqState.screen = "home";
  }
  return true;
}

window.svqPruneContractLinkedCompletedVisits = svqPruneContractLinkedCompletedVisits;

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
      present: objectId === "floor" ? true : null,
      shouldClean: null,
      interval: null,
      customInterval: "",
      quantity: "",
      trashBag: null,
      floorMaterial: "",
      floorMethod: "",
      note: "",
    };
  }
  return room.objects[objectId];
}

function svqDraftPreferences(draft) {
  if (!draft.preferences || typeof draft.preferences !== "object") {
    draft.preferences = { lastInterval: "", lastCustomInterval: "" };
  }
  return draft.preferences;
}

function svqRememberInterval(draft, interval, customInterval = "") {
  if (!interval) return;
  const preferences = svqDraftPreferences(draft);
  preferences.lastInterval = interval;
  preferences.lastCustomInterval = interval === "custom" ? customInterval || "" : "";
}

function svqHasValidQuantity(value) {
  return String(value ?? "").trim() !== "" && Number(value) > 0;
}

function svqEntryPathFromField(field) {
  return field.replace(/\.(present|shouldClean|interval|customInterval|quantity|trashBag|floorMaterial|floorMethod|note)$/, "");
}

function svqApplyCleaningDefaults(draft, field) {
  const entry = svqGetPath(draft, svqEntryPathFromField(field));
  if (!entry || entry.shouldClean !== true) return;

  if (entry.objectId === "floor" && !entry.floorMethod) {
    entry.floorMethod = svqDefaultFloorMethod(draft.activeRoomDraft);
  }
}

function svqRoomObjectIds(room) {
  const type = svqRoomType(room.type);
  if (!type) return [];
  const ids = [...type.objects];
  if (type.id !== "custom") {
    ["chairs", "tables"].forEach((id) => {
      if (!ids.includes(id) && !type.excludeUniversal?.includes(id)) ids.push(id);
    });
  }
  if (!ids.includes("trash")) ids.push("trash");
  return ids;
}

function svqRoomDefaultInterval(room) {
  const defaults = typeof SVQ_ROOM_INTERVAL_DEFAULTS === "object" ? SVQ_ROOM_INTERVAL_DEFAULTS : {};
  return defaults[room?.type] || "weekly";
}

function svqObjectDefaultInterval(room, objectId) {
  const defaults = typeof SVQ_OBJECT_INTERVAL_DEFAULTS === "object" ? SVQ_OBJECT_INTERVAL_DEFAULTS : {};
  return defaults[objectId] || svqRoomDefaultInterval(room);
}

function svqDefaultFloorMethod(room) {
  if (["wc", "kitchen", "treatment_room", "changing_room"].includes(room?.type)) return "Gewischt";
  if (["office", "waiting_room", "meeting_room"].includes(room?.type)) return "Gesaugt und gewischt";
  return "Gewischt";
}

function svqApplyCleanDefaults(room, objectId) {
  const entry = svqGetOrCreateObjectEntry(room, objectId);
  entry.present = true;
  entry.shouldClean = true;
  if (objectId === "floor" && !entry.floorMethod) entry.floorMethod = svqDefaultFloorMethod(room);
}

function svqApplyNoClean(room, objectId) {
  const entry = svqGetOrCreateObjectEntry(room, objectId);
  entry.present = true;
  entry.shouldClean = false;
  entry.interval = null;
  entry.customInterval = "";
  if (objectId === "floor") {
    entry.floorMaterial = "";
    entry.floorMethod = "";
  }
}

function svqApplyNotPresent(room, objectId) {
  const entry = svqGetOrCreateObjectEntry(room, objectId);
  entry.present = false;
  entry.shouldClean = null;
  entry.interval = null;
  entry.customInterval = "";
  entry.floorMaterial = "";
  entry.floorMethod = "";
  entry.quantity = "";
  entry.trashBag = null;
}

function svqObjectAnswerState(room, objectId) {
  const entry = svqGetOrCreateObjectEntry(room, objectId);
  const def = SVQ_OBJECTS[objectId] || { extra: [] };
  if (objectId === "floor") {
    if (entry.shouldClean === false) return "done";
    if (entry.shouldClean === true) {
      const hasInterval = entry.interval && (entry.interval !== "custom" || String(entry.customInterval || "").trim());
      return hasInterval && entry.floorMaterial && entry.floorMethod ? "done" : "partial";
    }
    return "empty";
  }
  if (entry.present === false) return "done";
  if (entry.present === true && entry.shouldClean === false) return "done";
  if (entry.present === true && entry.shouldClean === true) {
    const hasInterval = entry.interval && (entry.interval !== "custom" || String(entry.customInterval || "").trim());
    const hasQuantity = !def.extra?.includes("quantity") || svqHasValidQuantity(entry.quantity);
    const hasTrashBag = !def.extra?.includes("trashBag") || entry.trashBag != null;
    return hasInterval && hasQuantity && hasTrashBag ? "done" : "partial";
  }
  return "empty";
}

function svqRoomProgress(room) {
  const ids = svqRoomObjectIds(room);
  const done = ids.filter((objectId) => svqObjectAnswerState(room, objectId) === "done").length;
  const cleaning = ids.filter((objectId) => {
    const entry = svqGetOrCreateObjectEntry(room, objectId);
    return entry.present === true && entry.shouldClean === true;
  }).length;
  return { done, total: ids.length, cleaning };
}

function svqDraftCleaningCount(draft) {
  return draft.rooms.reduce((sum, room) => {
    const predefined = svqRoomObjectEntries(room).filter((entry) => entry.present && entry.shouldClean).length;
    return sum + predefined;
  }, 0);
}

function svqCloneRoomForDraft(draft, room) {
  const clone = JSON.parse(JSON.stringify(room));
  clone.id = svqUid();
  clone.label = room.type === "custom" ? `${room.label || room.typeLabel} (Kopie)` : svqNextRoomLabel(draft, room.type);
  clone.createdAt = svqNowIso();
  clone.updatedAt = svqNowIso();
  return clone;
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
  if (draft.screen === "rooms-overview") return "Räume-Übersicht";
  if (draft.screen === "finish-confirm") return "Begehung abschließen";
  if (draft.screen === "finish-success") return "Abgeschlossen";
  return "Begehung";
}

function svqAdvanceRoomObject(draft) {
  const ids = svqRoomObjectIds(draft.activeRoomDraft);
  if (draft.roomObjectIndex < ids.length - 1) {
    draft.roomObjectIndex += 1;
  } else {
    draft.screen = "custom-object-prompt";
  }
}

function svqCurrentRoomObject(draft) {
  if (draft.screen !== "room-object" || !draft.activeRoomDraft) return null;
  const ids = svqRoomObjectIds(draft.activeRoomDraft);
  const objectId = ids[draft.roomObjectIndex];
  if (!objectId) return null;
  const entry = svqGetOrCreateObjectEntry(draft.activeRoomDraft, objectId);
  const def = SVQ_OBJECTS[objectId] || { label: objectId, extra: [] };
  return { objectId, entry, def, isFloor: objectId === "floor" };
}

function svqRoomObjectValidationMessage(draft) {
  const current = svqCurrentRoomObject(draft);
  if (!current) return "";

  const { entry, def, isFloor } = current;
  const label = def.label || "Objekt";
  if (isFloor) {
    if (entry.shouldClean == null) return "Bitte entscheide, ob der Boden gereinigt werden soll.";
    if (entry.shouldClean !== true) return "";
    if (!entry.floorMaterial) return "Bitte waehle die Bodenart.";
    if (!entry.floorMethod) return "Bitte waehle die Reinigungsmethode.";
  } else {
    if (entry.present == null) return `Bitte entscheide, ob "${label}" vorhanden ist.`;
    if (entry.present !== true) return "";
    if (entry.shouldClean == null) return `Bitte entscheide, ob "${label}" gereinigt werden soll.`;
    if (entry.shouldClean !== true) return "";
    if (def.extra?.includes("quantity") && !svqHasValidQuantity(entry.quantity)) {
      return `Bitte trage die Anzahl für "${label}" ein.`;
    }
    if (def.extra?.includes("trashBag") && entry.trashBag == null) {
      return "Bitte entscheide, ob mit oder ohne Mülltüte.";
    }
  }

  if (!entry.interval) return "Bitte waehle das Reinigungsintervall.";
  if (entry.interval === "custom" && !String(entry.customInterval || "").trim()) {
    return "Bitte trage das individuelle Intervall ein.";
  }
  return "";
}

function svqShouldSkipAfterYesNo(draft, field, value) {
  return draft.screen === "room-object" && ((field.endsWith(".present") && value === false) || (field.endsWith(".shouldClean") && value === false));
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
    const message = svqRoomObjectValidationMessage(draft);
    if (message) {
      svqShowValidation(message);
      return;
    }
    svqMutate((d) => {
      svqAdvanceRoomObject(d);
    });
    return;
  }

  if (draft.screen === "room-summary") {
    svqMutate((d) => {
      svqCommitActiveRoom(d);
      d.screen = "room-type-select";
    });
    return;
  }
}

function svqGoBack() {
  const draft = svqCurrentDraft();
  if (!draft) return;

  if (draft.screen === "intro") {
    const step = SVQ_INTRO_STEPS[draft.introStepIndex];
    if (draft.customerId && step === "area-size") {
      svqState.screen = "customer-select";
      svqRender();
      return;
    }

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
      d.screen = "intro";
      d.introStepIndex = SVQ_INTRO_STEPS.length - 1;
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

  if (draft.screen === "rooms-overview") {
    svqMutate((d) => {
      d.screen = "finish-success";
    });
    return;
  }

  if (draft.screen === "finish-confirm") {
    svqMutate((d) => {
      d.screen = d.status === "completed" ? "rooms-overview" : "room-type-select";
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
      method: entry.objectId === "floor" ? entry.floorMethod || "" : "",
      quantity: def.extra?.includes("quantity") ? Number(entry.quantity) || 0 : 0,
    });
  });
  (room.customObjects || []).forEach((obj) => {
    if (!obj.shouldClean) return;
    const intervalLabel = obj.interval === "custom" ? obj.customInterval : svqIntervalLabel(obj.interval);
    items.push({
      key: "surface",
      frequency: "Individuell",
      customFrequency: [obj.name, intervalLabel].filter(Boolean).join(" – "),
      quantity: Number(obj.quantity) || 0,
    });
  });
  return items;
}

function svqRoomNotes(room) {
  const notes = [];
  svqRoomObjectEntries(room).forEach((entry) => {
    const parts = [];
    if (entry.present === false) {
      parts.push("nicht vorhanden");
    } else if (entry.present === true && entry.shouldClean === false) {
      parts.push("vorhanden, keine Reinigung");
    }
    const note = String(entry.note || "").trim();
    if (note) {
      parts.push(`Notiz: ${note}`);
    }
    if (parts.length) {
      notes.push(`${entry.name}: ${parts.join("; ")}`);
    }
  });
  return notes.join("\n");
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
        floorCondition: room.objects?.floor?.floorMaterial || "",
        notes: svqRoomNotes(room),
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
    customerName: draft.company.name,
    email: draft.company.email,
    phone: draft.company.phone,
    address: address || "Adresse nicht angegeben",
    onsiteContact: `${draft.contact.firstName} ${draft.contact.lastName}`.trim() || "Ansprechpartner vor Ort",
    squareMeters: areaValue > 0 ? areaValue : 1,
    floors,
    notes: "",
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
  svqPruneContractLinkedCompletedVisits();
  svqRender();
}

function svqRender() {
  const root = svqRoot();
  if (!root) return;

  if (svqState.screen === "home") {
    root.innerHTML = svqRenderHome();
  } else if (svqState.screen === "customer-select") {
    root.innerHTML = svqRenderCustomerSelect();
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
  const drafts = svqVisibleDraftsByStatus("draft");
  const completed = svqVisibleDraftsByStatus("completed");
  const customers = svqCustomerList();

  return `
    <div class="svq-home">
      <div class="svq-home-menu">
        <button class="svq-home-card" type="button" data-svq-action="home-new">
          <i data-lucide="clipboard-plus" aria-hidden="true"></i>
          <div>
            <strong>Neue Begehung erstellen</strong>
            <span>Startet das geführte Quiz mit neuen Kundendaten.</span>
          </div>
        </button>
        <button class="svq-home-card" type="button" data-svq-action="home-customers">
          <i data-lucide="users-round" aria-hidden="true"></i>
          <div>
            <strong>Kundenliste verwenden</strong>
            <span>${customers.length} Kunde${customers.length === 1 ? "" : "n"} verfügbar, Kundendaten werden übernommen.</span>
          </div>
        </button>
        <button class="svq-home-card" type="button" data-svq-action="home-create-customer">
          <i data-lucide="user-plus" aria-hidden="true"></i>
          <div>
            <strong>Neuen Kunden anlegen</strong>
            <span>Erst Kundendaten speichern, danach automatisch im Quiz weiterarbeiten.</span>
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

function svqRenderCustomerSelect() {
  const query = svqState.customerQuery.trim().toLowerCase();
  const customers = svqCustomerList().filter((customer) => !query || svqCustomerSearchText(customer).includes(query));
  const rows = customers
    .map((customer) => {
      const address = [customer.address, customer.houseNumber].filter(Boolean).join(" ");
      const cityLine = [customer.zip, customer.city].filter(Boolean).join(" ");
      const contact = svqContactNameFromCustomer(customer) || "Kein Ansprechpartner";
      return `
        <article class="svq-list-item">
          <button class="svq-list-item-main" type="button" data-svq-action="customer-select" data-svq-id="${svqEsc(customer.id)}">
            <strong>${svqEsc(customer.name || "Ohne Firmennamen")}</strong>
            <span class="record-meta">
              <span>${svqEsc(address || "Keine Adresse")}${cityLine ? ", " + svqEsc(cityLine) : ""}</span>
              <span>${svqEsc(contact)}</span>
              <span>${svqEsc(customer.email || "-")} · ${svqEsc(customer.phone || "-")}</span>
            </span>
          </button>
          <button class="primary-button" type="button" data-svq-action="customer-select" data-svq-id="${svqEsc(customer.id)}">
            Übernehmen
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
        <h3>Kundenliste verwenden</h3>
      </div>
      <label class="svq-field svq-customer-search">
        Kunde suchen
        <input type="search" data-svq-customer-search value="${svqEsc(svqState.customerQuery)}" placeholder="Firma, Ansprechpartner oder Adresse" />
      </label>
      <div class="svq-list-body">
        ${rows || `<div class="empty-state">Kein Kunde gefunden. Lege den Kunden zuerst neu an.</div>`}
      </div>
      <div class="form-actions">
        <button class="secondary-button" type="button" data-svq-action="home-create-customer">
          <i data-lucide="user-plus" aria-hidden="true"></i>
          Neuen Kunden anlegen
        </button>
      </div>
    </div>
  `;
}

function svqRenderList(status) {
  const items = svqVisibleDraftsByStatus(status)
    .sort((a, b) => new Date(b.updatedAt) - new Date(a.updatedAt));
  const isDraftList = status === "draft";
  const selection = isDraftList ? svqDraftSelection() : new Set();
  const itemIds = new Set(items.map((draft) => draft.id));
  if (isDraftList) {
    [...selection].forEach((draftId) => {
      if (!itemIds.has(draftId)) {
        selection.delete(draftId);
      }
    });
  }
  const selectedCount = isDraftList ? items.filter((draft) => selection.has(draft.id)).length : 0;
  const allSelected = isDraftList && items.length > 0 && selectedCount === items.length;
  const selectionBar = isDraftList && items.length
    ? `
      <div class="svq-selection-bar">
        <button class="secondary-button" type="button" data-svq-action="${allSelected ? "clear-draft-selection" : "select-all-drafts"}">
          <i data-lucide="${allSelected ? "square" : "check-square"}" aria-hidden="true"></i>
          ${allSelected ? "Auswahl aufheben" : "Alle markieren"}
        </button>
        <span class="svq-selection-count">${selectedCount} von ${items.length} markiert</span>
        <button class="ghost-button svq-danger-link" type="button" data-svq-action="delete-selected-drafts" ${selectedCount ? "" : "disabled"}>
          <i data-lucide="trash-2" aria-hidden="true"></i>
          Markierte löschen
        </button>
      </div>
    `
    : "";

  const rows = items
    .map((draft) => {
      const address = [draft.address.street, draft.address.houseNumber].filter(Boolean).join(" ");
      const cityLine = [draft.address.zip, draft.address.city].filter(Boolean).join(" ");
      const contact = [draft.contact.firstName, draft.contact.lastName].filter(Boolean).join(" ");
      const isSelected = isDraftList && selection.has(draft.id);
      return `
        <article class="svq-list-item${isSelected ? " selected" : ""}">
          ${
            isDraftList
              ? `
          <button class="svq-select-button${isSelected ? " active" : ""}" type="button" data-svq-action="toggle-draft-select" data-svq-id="${svqEsc(draft.id)}" aria-pressed="${isSelected ? "true" : "false"}">
            <i data-lucide="${isSelected ? "check-square" : "square"}" aria-hidden="true"></i>
            <span>${isSelected ? "Markiert" : "Markieren"}</span>
          </button>
        `
              : ""
          }
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
      ${selectionBar}
      <div class="svq-list-body">
        ${rows || `<div class="empty-state">${status === "draft" ? "Keine Entwürfe vorhanden." : "Noch keine abgeschlossene Begehung."}</div>`}
      </div>
    </div>
  `;
}

function svqRenderQuizContext(draft) {
  const activeRoom = draft.activeRoomDraft ? (draft.activeRoomDraft.label || draft.activeRoomDraft.typeLabel) : "";
  const area = Number(draft.areaSize.value) > 0 ? `${Number(draft.areaSize.value)} m²` : "Fläche offen";
  const cleaned = svqDraftCleaningCount(draft);
  const items = [
    { icon: "building-2", label: draft.company.name || "Kunde offen" },
    { icon: "ruler", label: area },
    { icon: "layout-list", label: `${draft.rooms.length} Raum${draft.rooms.length === 1 ? "" : "e"}` },
    { icon: "sparkles", label: `${cleaned} Leistung${cleaned === 1 ? "" : "en"}` },
  ];
  if (activeRoom) {
    items.push({ icon: "map-pin", label: activeRoom });
  }

  return `
    <div class="svq-context-strip" aria-label="Aktueller Begehungsstand">
      ${items
        .map(
          (item) => `
        <span class="svq-context-item">
          <i data-lucide="${item.icon}" aria-hidden="true"></i>
          <span>${svqEsc(item.label)}</span>
        </span>
      `
        )
        .join("")}
    </div>
  `;
}

function svqRenderQuizShell(draft) {
  const percent = svqProgressPercent(draft);
  const showNav = !["finish-success"].includes(draft.screen);
  const showBack = !["finish-confirm", "finish-success"].includes(draft.screen);
  const showNext = ["intro", "room-basic-info", "room-object"].includes(draft.screen);

  return `
    <div class="svq-quiz">
      <div class="svq-progress-bar" role="progressbar" aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100">
        <div class="svq-progress-fill" style="width:${percent}%"></div>
      </div>
      <div class="svq-progress-label">${svqEsc(svqProgressLabel(draft))} · ${percent}%</div>

      ${svqRenderQuizContext(draft)}

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
      return svqRenderRoomTypeSelect(draft);
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
      ${
        draft.customerId
          ? ""
          : `
      <button class="ghost-button svq-use-customer-link" type="button" data-svq-action="intro-use-customer">
        <i data-lucide="users-round" aria-hidden="true"></i>
        Stattdessen Daten aus der Kundenliste übernehmen
      </button>
      `
      }
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
        <input type="number" min="0" inputmode="numeric" data-svq-input="areaSize.value" value="${svqEsc(draft.areaSize.value)}" placeholder="z. B. 450" />
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

function svqRenderRoomTypeSelect(draft) {
  const lastRoom = draft.rooms.find((room) => room.id === draft.lastTouchedRoomId) || draft.rooms[draft.rooms.length - 1];
  return `
    ${
      lastRoom
        ? `
      <div class="svq-smart-strip">
        <button class="svq-smart-action" type="button" data-svq-action="duplicate-last-room">
          <i data-lucide="copy" aria-hidden="true"></i>
          <span>
            <strong>Letzten Raum kopieren</strong>
            <small>${svqEsc(lastRoom.label || lastRoom.typeLabel)} mit allen Leistungen übernehmen</small>
          </span>
        </button>
      </div>
    `
        : ""
    }
    ${
      draft.rooms.length
        ? `
      <h3>Bereits erfasste Räume</h3>
      <div class="svq-room-grid">${draft.rooms.map((room, index) => svqRenderRoomCard(room, { index })).join("")}</div>
    `
        : ""
    }
    <h3>Welchen Raum möchtest du als Nächstes erfassen?</h3>
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
    <div class="svq-choice-row">
      <button class="primary-button svq-finish-button" type="button" data-svq-action="finish-inspection" ${draft.rooms.length ? "" : "disabled"}>
        <i data-lucide="flag" aria-hidden="true"></i>
        Begehung beenden
      </button>
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

function svqRenderPillChoice(question, field, options, selectedValue, action) {
  return `
    <div class="svq-yesno-block">
      <p class="svq-question">${question}</p>
      <div class="svq-choice-row">
        ${options
          .map(
            (option) => `
          <button class="svq-choice-pill${selectedValue === option ? " active" : ""}" type="button"
            data-svq-action="${action}" data-svq-field="${field}" data-svq-choice="${svqEsc(option)}">${svqEsc(option)}</button>
        `
          )
          .join("")}
      </div>
    </div>
  `;
}

function svqRenderIntervalButton(interval, field, entry, action) {
  return `
    <button class="svq-choice-pill${entry.interval === interval.id ? " active" : ""}" type="button"
      data-svq-action="${action}" data-svq-field="${field}" data-svq-interval="${interval.id}">${interval.label}</button>
  `;
}

function svqRenderIntervalPicker(field, entry, action = "select-interval") {
  const quickIds = Array.isArray(window.SVQ_QUICK_INTERVALS) ? window.SVQ_QUICK_INTERVALS : (typeof SVQ_QUICK_INTERVALS !== "undefined" ? SVQ_QUICK_INTERVALS : []);
  const quickSet = new Set(quickIds);
  const quickIntervals = SVQ_INTERVALS.filter((interval) => quickSet.has(interval.id));
  const otherIntervals = SVQ_INTERVALS.filter((interval) => !quickSet.has(interval.id));
  const selectedIsOther = otherIntervals.some((interval) => interval.id === entry.interval);
  const primaryIntervals = quickIntervals.length ? quickIntervals : SVQ_INTERVALS;

  return `
    <div class="svq-interval-block">
      <p class="svq-question">In welchem Intervall soll gereinigt werden?</p>
      <div class="svq-interval-grid">
        ${primaryIntervals.map((interval) => svqRenderIntervalButton(interval, field, entry, action)).join("")}
      </div>
      ${
        quickIntervals.length && otherIntervals.length
          ? `
        <details class="svq-interval-more"${selectedIsOther ? " open" : ""}>
          <summary>Weitere Intervalle</summary>
          <div class="svq-interval-grid">
            ${otherIntervals.map((interval) => svqRenderIntervalButton(interval, field, entry, action)).join("")}
          </div>
        </details>
      `
          : ""
      }
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
      ${extra.includes("quantity") ? `<label class="svq-field">Anzahl<input type="number" min="0" inputmode="numeric" data-svq-input="${field}.quantity" value="${svqEsc(entry.quantity)}" /></label>` : ""}
      ${
        extra.includes("trashBag")
          ? `
        <div class="svq-field span-2">
          Mit oder ohne Mülltüte?
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

function svqRenderObjectStepper(room, ids, activeIndex) {
  return `
    <div class="svq-object-stepper" aria-label="Objekte im aktuellen Raum">
      ${ids
        .map((objectId, index) => {
          const state = svqObjectAnswerState(room, objectId);
          return `
        <button class="svq-object-step ${state}${index === activeIndex ? " active" : ""}" type="button" data-svq-action="jump-object" data-svq-index="${index}">
          <span class="svq-object-step-number">${index + 1}</span>
          <span class="svq-object-step-label">${svqEsc(svqObjectLabel(objectId))}</span>
        </button>
      `;
        })
        .join("")}
    </div>
  `;
}

function svqRenderObjectQuickActions(entry, objectId, isFloor) {
  const isTrash = objectId === "trash";
  const cleanActive = entry.present === true && entry.shouldClean === true;
  const noCleanActive = entry.present === true && entry.shouldClean === false;
  const absentActive = entry.present === false;
  const cleanLabel = isTrash ? "Leeren" : "Reinigen";
  const noCleanLabel = isTrash ? "Nicht leeren" : "Ohne Reinigung";
  const cleanIcon = isTrash ? "trash-2" : "sparkles";

  return `
    ${isTrash ? `<p class="svq-question">Soll der Mülleimer geleert werden?</p>` : ""}
    <div class="svq-quick-actions" aria-label="Schnellentscheidung">
      <button class="svq-quick-action${cleanActive ? " active-clean" : ""}" type="button" data-svq-action="object-quick" data-svq-object="${svqEsc(objectId)}" data-svq-mode="clean">
        <i data-lucide="${cleanIcon}" aria-hidden="true"></i>
        <span>${cleanLabel}</span>
      </button>
      <button class="svq-quick-action${noCleanActive ? " active-muted" : ""}" type="button" data-svq-action="object-quick" data-svq-object="${svqEsc(objectId)}" data-svq-mode="no-clean">
        <i data-lucide="minus-circle" aria-hidden="true"></i>
        <span>${noCleanLabel}</span>
      </button>
      ${
        isFloor
          ? ""
          : `
      <button class="svq-quick-action${absentActive ? " active-muted" : ""}" type="button" data-svq-action="object-quick" data-svq-object="${svqEsc(objectId)}" data-svq-mode="absent">
        <i data-lucide="eye-off" aria-hidden="true"></i>
        <span>Nicht vorhanden</span>
      </button>
      `
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
  const isFloor = objectId === "floor";
  const progress = svqRoomProgress(room);

  return `
    <div class="svq-object-header">
      <div>
        <div class="svq-counter">${draft.roomObjectIndex + 1} / ${ids.length}</div>
        <h3>${svqEsc(def.label)}</h3>
      </div>
      <span class="svq-room-progress">${progress.done} / ${progress.total} beantwortet</span>
    </div>
    ${svqRenderObjectStepper(room, ids, draft.roomObjectIndex)}
    ${svqRenderObjectQuickActions(entry, objectId, isFloor)}
    ${
      entry.present === true && entry.shouldClean === true
        ? `
      ${
        isFloor
          ? `
        ${svqRenderPillChoice("Welche Bodenart ist das?", `${field}.floorMaterial`, SVQ_FLOOR_MATERIALS, entry.floorMaterial, "select-floor-choice")}
        ${svqRenderPillChoice("Wie sollte der Boden gereinigt werden?", `${field}.floorMethod`, SVQ_FLOOR_METHODS, entry.floorMethod, "select-floor-choice")}
      `
          : ""
      }
      ${svqRenderObjectExtraFields(field, entry, def.extra)}
      ${svqRenderIntervalPicker(field, entry)}
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
    <div class="svq-menu-list">
      <button class="svq-menu-item" type="button" data-svq-action="custom-object-add-room">
        <i data-lucide="plus" aria-hidden="true"></i>Weitere Räume hinzufügen
      </button>
      <button class="svq-menu-item svq-menu-item-primary" type="button" data-svq-action="custom-object-finish">
        <i data-lucide="flag" aria-hidden="true"></i>Begehung beenden
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
      <label class="svq-field">Anzahl<input type="number" min="0" inputmode="numeric" id="svq-custom-quantity" /></label>
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
    <div class="svq-summary-actions">
      <button class="secondary-button" type="button" data-svq-action="summary-add-room">
        <i data-lucide="plus" aria-hidden="true"></i>Raum speichern und weiter
      </button>
      <button class="secondary-button" type="button" data-svq-action="summary-overview">
        <i data-lucide="list-checks" aria-hidden="true"></i>Zur Gesamtübersicht
      </button>
      <button class="primary-button" type="button" data-svq-action="summary-finish">
        <i data-lucide="flag" aria-hidden="true"></i>Speichern und abschließen
      </button>
    </div>
  `;
}

function svqRenderRoomCard(room, { compact = false, index = null } = {}) {
  const cleaned = svqRoomObjectEntries(room).filter((entry) => entry.present && entry.shouldClean);
  const notCleaned = svqRoomObjectEntries(room).filter((entry) => entry.present && !entry.shouldClean);
  const progress = svqRoomProgress(room);

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
        ${progress.total ? `<span class="svq-room-progress">${progress.done} / ${progress.total} Fragen beantwortet</span>` : ""}
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
  if (action === "home-customers") {
    if (typeof loadAll === "function") {
      button.disabled = true;
      await loadAll();
    }
    svqState.customerQuery = "";
    svqState.customerSelectMode = "new";
    svqState.screen = "customer-select";
    svqRender();
    return;
  }
  if (action === "intro-use-customer") {
    if (typeof loadAll === "function") {
      button.disabled = true;
      await loadAll();
    }
    svqState.customerQuery = "";
    svqState.customerSelectMode = "fill-current";
    svqState.screen = "customer-select";
    svqRender();
    return;
  }
  if (action === "home-create-customer") {
    if (typeof window.ctOpenCustomerCreateForQuiz === "function") {
      window.ctOpenCustomerCreateForQuiz();
    } else {
      svqShowValidation("Die Kundenanlage konnte nicht geöffnet werden.");
    }
    return;
  }
  if (action === "customer-select") {
    const customer = svqCustomerList().find((item) => item.id === id);
    svqStartFromCustomer(customer);
    return;
  }
  if (action === "home-drafts") {
    svqClearDraftSelection();
    svqState.screen = "drafts-list";
    svqRender();
    return;
  }
  if (action === "home-completed") {
    svqClearDraftSelection();
    svqState.screen = "completed-list";
    svqRender();
    return;
  }
  if (action === "back-home") {
    if (svqState.screen === "customer-select" && svqState.customerSelectMode === "fill-current" && svqCurrentDraft()) {
      svqState.customerSelectMode = null;
      svqState.screen = "quiz";
      svqRender();
      return;
    }
    svqClearDraftSelection();
    svqState.customerSelectMode = null;
    svqState.screen = "home";
    svqRender();
    return;
  }
  if (action === "open-draft") {
    const draft = SiteVisitQuizStore.get(id);
    svqClearDraftSelection();
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
  if (action === "toggle-draft-select") {
    const selection = svqDraftSelection();
    if (selection.has(id)) {
      selection.delete(id);
    } else if (SiteVisitQuizStore.get(id)?.status === "draft") {
      selection.add(id);
    }
    svqRender();
    return;
  }
  if (action === "select-all-drafts") {
    const selection = svqDraftSelection();
    svqVisibleDraftsByStatus("draft").forEach((draft) => selection.add(draft.id));
    svqRender();
    return;
  }
  if (action === "clear-draft-selection") {
    svqClearDraftSelection();
    svqRender();
    return;
  }
  if (action === "delete-selected-drafts") {
    const selectedIds = svqSelectedVisibleDraftIds();
    if (!selectedIds.length) {
      svqShowValidation("Bitte markiere mindestens einen Entwurf.");
      return;
    }
    const label = selectedIds.length === 1 ? "diesen Entwurf" : `${selectedIds.length} Entwürfe`;
    if (window.confirm(`${label} wirklich löschen?`)) {
      SiteVisitQuizStore.removeMany(selectedIds);
      if (selectedIds.includes(svqState.draftId)) {
        svqState.draftId = null;
      }
      svqClearDraftSelection();
      svqRender();
    }
    return;
  }
  if (action === "delete-draft") {
    if (window.confirm("Diese Begehung wirklich löschen?")) {
      SiteVisitQuizStore.remove(id);
      svqDraftSelection().delete(id);
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

  if (action === "duplicate-last-room") {
    svqMutate((d) => {
      const source = d.rooms.find((room) => room.id === d.lastTouchedRoomId) || d.rooms[d.rooms.length - 1];
      if (!source) return;
      const clone = svqCloneRoomForDraft(d, source);
      d.rooms.push(clone);
      d.lastTouchedRoomId = clone.id;
      d.screen = "room-type-select";
    });
    return;
  }

  if (action === "jump-object") {
    const nextIndex = Number(button.dataset.svqIndex);
    svqMutate((d) => {
      const ids = svqRoomObjectIds(d.activeRoomDraft);
      if (Number.isInteger(nextIndex) && nextIndex >= 0 && nextIndex < ids.length) {
        d.roomObjectIndex = nextIndex;
      }
    });
    return;
  }

  if (action === "object-quick") {
    const objectId = button.dataset.svqObject;
    const mode = button.dataset.svqMode;
    svqMutate((d) => {
      if (!d.activeRoomDraft || !objectId) return;
      if (mode === "clean") {
        svqApplyCleanDefaults(d.activeRoomDraft, objectId);
        const entry = svqGetOrCreateObjectEntry(d.activeRoomDraft, objectId);
        svqRememberInterval(d, entry.interval, entry.customInterval || "");
      } else if (mode === "no-clean") {
        svqApplyNoClean(d.activeRoomDraft, objectId);
      } else if (mode === "absent") {
        svqApplyNotPresent(d.activeRoomDraft, objectId);
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
        svqSetPath(d, field.replace(".shouldClean", ".floorMaterial"), "");
        svqSetPath(d, field.replace(".shouldClean", ".floorMethod"), "");
      }
      if (field.endsWith(".shouldClean") && value === true) {
        svqApplyCleaningDefaults(d, field);
      }
      if (svqShouldSkipAfterYesNo(d, field, value)) {
        svqAdvanceRoomObject(d);
      }
    });
    return;
  }

  if (action === "select-interval") {
    const field = button.dataset.svqField;
    svqMutate((d) => {
      const interval = button.dataset.svqInterval;
      const entry = svqGetPath(d, field);
      svqSetPath(d, `${field}.interval`, interval);
      if (interval === "custom" && entry && !entry.customInterval) {
        entry.customInterval = svqDraftPreferences(d).lastCustomInterval || "";
      }
      svqRememberInterval(d, interval, entry?.customInterval || "");
      if (!svqRoomObjectValidationMessage(d)) {
        svqAdvanceRoomObject(d);
      }
    });
    return;
  }

  if (action === "select-floor-choice") {
    const field = button.dataset.svqField;
    svqMutate((d) => {
      svqSetPath(d, field, button.dataset.svqChoice);
      if (!svqRoomObjectValidationMessage(d)) {
        svqAdvanceRoomObject(d);
      }
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
  if (action === "custom-object-add-room") {
    svqMutate((d) => {
      svqCommitActiveRoom(d);
      d.screen = "room-type-select";
    });
    return;
  }
  if (action === "custom-object-finish") {
    svqMutate((d) => {
      svqCommitActiveRoom(d);
      d.screen = "finish-confirm";
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
    if (shouldClean === true && !interval) {
      svqShowValidation("Bitte waehle das Reinigungsintervall.");
      return;
    }
    if (shouldClean === true && interval === "custom" && !String(customInterval || "").trim()) {
      svqShowValidation("Bitte trage das individuelle Intervall ein.");
      return;
    }
    svqMutate((d) => {
      if (shouldClean === true) {
        svqRememberInterval(d, interval, customInterval);
      }
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

  if (action === "summary-add-room" || action === "summary-overview" || action === "summary-finish") {
    svqMutate((d) => {
      svqCommitActiveRoom(d);
      if (action === "summary-add-room") {
        d.screen = "room-type-select";
      } else if (action === "summary-overview") {
        d.screen = "rooms-overview";
      } else {
        d.screen = "finish-confirm";
      }
    });
    return;
  }

  if (action === "next-add-room") {
    svqMutate((d) => {
      d.screen = "room-type-select";
    });
    return;
  }
  if (action === "finish-inspection") {
    if (!svqCurrentDraft()?.rooms.length) return;
    svqMutate((d) => {
      d.screen = "finish-confirm";
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
      const clone = svqCloneRoomForDraft(d, room);
      d.rooms.push(clone);
      d.lastTouchedRoomId = clone.id;
    });
    return;
  }
  if (action === "room-delete") {
    if (!window.confirm("Diesen Raum wirklich löschen?")) return;
    svqMutate((d) => {
      d.rooms = d.rooms.filter((r) => r.id !== id);
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
      d.screen = d.status === "completed" ? "rooms-overview" : "room-type-select";
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
  if (target.matches("[data-svq-customer-search]")) {
    svqState.customerQuery = target.value;
    svqRender();
    const search = svqRoot()?.querySelector("[data-svq-customer-search]");
    if (search) {
      search.focus();
      search.setSelectionRange(search.value.length, search.value.length);
    }
    return;
  }
  if (target.id === "svq-custom-interval-text") {
    svqCustomObjectFormState.customInterval = target.value;
    return;
  }
  const path = target.dataset.svqInput;
  if (!path) return;
  const draft = svqCurrentDraft();
  if (!draft) return;
  svqSetPath(draft, path, target.value);
  if (path.endsWith(".customInterval")) {
    const entry = svqGetPath(draft, path.replace(".customInterval", ""));
    if (entry?.interval === "custom") {
      svqRememberInterval(draft, "custom", target.value);
    }
  }
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
