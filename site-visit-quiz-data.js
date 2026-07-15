/* Zentrale Konfiguration für das Begehungs-Quiz: Raumarten, Objekte, Intervalle.
   Bewusst schlank gehalten: pro Raum immer "Boden" plus 1-3 themenbezogene Punkte,
   dazu optional die beiden Standardfragen "Stühle" und "Tische" - mehr nicht.
   Neue Raumarten/Objekte werden ausschließlich hier ergänzt, nicht im HTML.
   legacyKey ordnet ein Objekt einem Reinigungspunkt-Schlüssel aus api/site-visits.php
   zu, damit eine fertige Begehung dort als echte Begehung gespeichert werden kann. */

const SVQ_INTERVALS = [
  { id: "daily", label: "täglich" },
  { id: "every_2_days", label: "alle zwei Tage" },
  { id: "every_3_days", label: "alle drei Tage" },
  { id: "weekly", label: "einmal pro Woche" },
  { id: "twice_weekly", label: "zweimal pro Woche" },
  { id: "three_times_weekly", label: "dreimal pro Woche" },
  { id: "four_times_weekly", label: "viermal pro Woche" },
  { id: "five_times_weekly", label: "fünfmal pro Woche" },
  { id: "biweekly", label: "14-tägig" },
  { id: "monthly", label: "monatlich" },
  { id: "quarterly", label: "quartalsweise" },
  { id: "once", label: "einmalig" },
  { id: "as_needed", label: "nach Bedarf" },
  { id: "custom", label: "individuell" },
];

/* Objekt-Definitionen: extra kann "quantity" (Anzahl) und/oder "trashBag" (Mülltüten-Option) enthalten. */
const SVQ_OBJECTS = {
  floor: { label: "Boden", extra: [], legacyKey: "floor" },
  windowsills: { label: "Fensterbänke", extra: [], legacyKey: "window" },
  desks: { label: "Schreibtische", extra: ["quantity"], legacyKey: "desk" },
  chairs: { label: "Stühle", extra: ["quantity"], legacyKey: "chairs" },
  tables: { label: "Tische", extra: ["quantity"], legacyKey: "tables" },
  trash: { label: "Mülleimer", extra: ["quantity", "trashBag"], legacyKey: "trash" },
  toilets: { label: "WC", extra: [], legacyKey: "toilet" },
  sinks: { label: "Waschbecken", extra: [], legacyKey: "washbasin" },
  mirrors: { label: "Spiegel", extra: [], legacyKey: "mirror" },
  doors: { label: "Türen", extra: [], legacyKey: "door" },
  handrail: { label: "Handlauf / Geländer", extra: [], legacyKey: "handrail" },
  kitchen_surfaces: { label: "Küchenflächen", extra: [], legacyKey: "kitchen" },
  counter: { label: "Tresen", extra: [], legacyKey: "counter" },
  treatment_surface: { label: "Behandlungsliege", extra: [], legacyKey: "treatmentTable" },
};

/* Raumarten mit kurzer, themenbezogener Objektliste (IDs aus SVQ_OBJECTS).
   "Boden" ist immer die Grundfrage; danach 1-3 typische Punkte für den Raum.
   Stühle/Tische werden - falls nicht schon Teil der Liste - automatisch als
   letzte Standardfragen ergänzt (siehe svqRoomObjectIds in site-visit-quiz.js). */
const SVQ_ROOM_TYPES = [
  { id: "office", label: "Büro", icon: "briefcase", legacyType: "Büro", objects: ["floor", "desks", "windowsills"] },
  { id: "wc", label: "WC und Sanitärbereich", icon: "shower-head", legacyType: "Sanitär", objects: ["floor", "toilets", "sinks", "mirrors"] },
  { id: "entrance", label: "Eingangsbereich", icon: "door-open", legacyType: "Empfang", objects: ["floor", "counter", "windowsills"] },
  { id: "hallway", label: "Flur", icon: "move-horizontal", legacyType: "Flur", objects: ["floor", "windowsills"] },
  { id: "staircase", label: "Treppenhaus", icon: "footprints", legacyType: "Treppenhaus", objects: ["floor", "handrail"] },
  { id: "treatment_room", label: "Behandlungsraum", icon: "stethoscope", legacyType: "Behandlungsräume", objects: ["floor", "treatment_surface", "sinks"] },
  { id: "waiting_room", label: "Warteraum", icon: "armchair", legacyType: "Sonstiger Raum", objects: ["floor", "chairs", "tables"] },
  { id: "kitchen", label: "Küche", icon: "cooking-pot", legacyType: "Küche", objects: ["floor", "kitchen_surfaces", "sinks"] },
  { id: "break_room", label: "Aufenthaltsraum", icon: "coffee", legacyType: "Sonstiger Raum", objects: ["floor", "kitchen_surfaces"] },
  { id: "storage_room", label: "Lagerraum", icon: "warehouse", legacyType: "Lager", objects: ["floor", "doors"] },
  { id: "changing_room", label: "Umkleideraum", icon: "shirt", legacyType: "Sonstiger Raum", objects: ["floor", "mirrors"] },
  { id: "meeting_room", label: "Besprechungsraum", icon: "presentation", legacyType: "Sonstiger Raum", objects: ["floor", "tables", "chairs"] },
  { id: "sales_room", label: "Verkaufsraum", icon: "store", legacyType: "Sonstiger Raum", objects: ["floor", "counter", "windowsills"] },
  { id: "custom", label: "Individueller Raum", icon: "sparkles", legacyType: "Sonstiger Raum", objects: [] },
];

function svqRoomType(typeId) {
  return SVQ_ROOM_TYPES.find((type) => type.id === typeId) || null;
}

function svqObjectLabel(objectId) {
  return SVQ_OBJECTS[objectId]?.label || objectId;
}

function svqIntervalLabel(intervalId) {
  return SVQ_INTERVALS.find((interval) => interval.id === intervalId)?.label || intervalId;
}
