/* Zentrale Konfiguration für das Begehungs-Quiz: Raumarten, Objekte, Intervalle.
   Neue Raumarten/Objekte werden ausschließlich hier ergänzt, nicht im HTML. */

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

/* Objekt-Definitionen: welche Zusatzfelder ein Objekt hat.
   extra kann enthalten: "quantity" (Anzahl), "trashBag" (Mülltüten-Option) */
const SVQ_OBJECTS = {
  desks: { label: "Schreibtische", extra: ["quantity"] },
  floor: { label: "Boden", extra: [] },
  trash: { label: "Mülleimer", extra: ["quantity", "trashBag"] },
  windowsills: { label: "Fensterbänke", extra: [] },
  chairs: { label: "Stühle", extra: ["quantity"] },
  cabinets: { label: "Schränke", extra: [] },
  shelves: { label: "Regale", extra: [] },
  doors: { label: "Türen", extra: [] },
  light_switches: { label: "Lichtschalter", extra: [] },

  toilets: { label: "Toiletten", extra: [] },
  urinals: { label: "Urinale", extra: [] },
  sinks: { label: "Waschbecken", extra: [] },
  mirrors: { label: "Spiegel", extra: [] },
  fixtures: { label: "Armaturen", extra: [] },
  partitions: { label: "Trennwände", extra: [] },
  door_handles: { label: "Türgriffe", extra: [] },
  radiators: { label: "Heizkörper", extra: [] },
  hygiene_bins: { label: "Hygienebehälter", extra: ["quantity", "trashBag"] },
  soap_dispensers: { label: "Seifenspender", extra: [] },
  disinfectant_dispensers: { label: "Desinfektionsmittelspender", extra: [] },
  paper_towel_dispensers: { label: "Papierhandtuchspender", extra: [] },
  toilet_paper_dispensers: { label: "Toilettenpapierspender", extra: [] },

  entrance_door: { label: "Eingangstür", extra: [] },
  glass_door: { label: "Glastür", extra: [] },
  doormats: { label: "Fußmatten", extra: [] },
  windows: { label: "Fenster", extra: [] },
  stair_railing: { label: "Treppengeländer", extra: [] },
  mailboxes: { label: "Briefkästen", extra: [] },
  intercom: { label: "Klingelanlage", extra: [] },
  seating: { label: "Sitzmöglichkeiten", extra: [] },

  baseboards: { label: "Fußleisten", extra: [] },
  handrails: { label: "Handläufe", extra: [] },
  elevator_doors: { label: "Aufzugstüren", extra: [] },

  steps: { label: "Treppenstufen", extra: [] },
  landings: { label: "Treppenabsätze", extra: [] },
  elevator: { label: "Aufzug", extra: [] },
  elevator_cabin: { label: "Aufzugskabine", extra: [] },

  desk: { label: "Schreibtisch", extra: [] },
  treatment_bed: { label: "Behandlungsbett", extra: [] },
  treatment_couch: { label: "Behandlungsliege", extra: [] },
  work_surfaces: { label: "Arbeitsflächen", extra: [] },
  stools: { label: "Hocker", extra: [] },

  benches: { label: "Sitzbänke", extra: [] },
  tables: { label: "Tische", extra: [] },
  shelving: { label: "Ablagen", extra: [] },
  magazine_rack: { label: "Zeitschriftenhalter", extra: [] },

  faucet: { label: "Wasserhahn", extra: [] },
  kitchen_cabinets: { label: "Küchenschränke", extra: [] },
  fridge: { label: "Kühlschrank", extra: [] },
  microwave: { label: "Mikrowelle", extra: [] },
  coffee_machine: { label: "Kaffeemaschine", extra: [] },
  dishwasher: { label: "Geschirrspüler", extra: [] },

  checkout_counter: { label: "Verkaufstheke/Kasse", extra: [] },
  changing_cabins: { label: "Umkleidekabinen", extra: [] },
  lockers: { label: "Spinde", extra: [] },
  benches_changing: { label: "Sitzbänke", extra: [] },
  storage_shelving: { label: "Lagerregale", extra: [] },
  whiteboard: { label: "Whiteboard/Flipchart", extra: [] },
  screen: { label: "Bildschirm/Beamer", extra: [] },
};

/* Raumarten mit vordefinierten Objektlisten (IDs aus SVQ_OBJECTS). */
const SVQ_ROOM_TYPES = [
  {
    id: "office",
    label: "Büro",
    icon: "briefcase",
    objects: ["desks", "floor", "trash", "windowsills", "chairs", "cabinets", "shelves", "doors", "light_switches"],
  },
  {
    id: "wc",
    label: "WC und Sanitärbereich",
    icon: "shower-head",
    objects: [
      "toilets", "urinals", "sinks", "mirrors", "fixtures", "partitions", "doors", "door_handles",
      "windowsills", "radiators", "floor", "trash", "hygiene_bins", "soap_dispensers",
      "disinfectant_dispensers", "paper_towel_dispensers", "toilet_paper_dispensers",
    ],
  },
  {
    id: "entrance",
    label: "Eingangsbereich",
    icon: "door-open",
    objects: [
      "floor", "entrance_door", "glass_door", "door_handles", "doormats", "windows", "windowsills",
      "stair_railing", "mailboxes", "intercom", "light_switches", "trash", "seating",
    ],
  },
  {
    id: "hallway",
    label: "Flur",
    icon: "move-horizontal",
    hasFloorLength: true,
    objects: [
      "floor", "doors", "door_handles", "light_switches", "windows", "windowsills", "radiators",
      "baseboards", "handrails", "trash", "elevator_doors",
    ],
  },
  {
    id: "staircase",
    label: "Treppenhaus",
    icon: "footprints",
    hasStaircaseFields: true,
    objects: [
      "steps", "landings", "floor", "stair_railing", "handrails", "windows", "windowsills",
      "entrance_door", "doors", "door_handles", "light_switches", "radiators", "baseboards",
      "elevator", "elevator_doors", "elevator_cabin", "trash",
    ],
  },
  {
    id: "treatment_room",
    label: "Behandlungsraum",
    icon: "stethoscope",
    objects: [
      "desk", "treatment_bed", "treatment_couch", "work_surfaces", "sinks", "mirrors", "cabinets",
      "shelves", "chairs", "stools", "windows", "windowsills", "doors", "door_handles",
      "light_switches", "floor", "trash",
    ],
  },
  {
    id: "waiting_room",
    label: "Warteraum",
    icon: "armchair",
    objects: [
      "floor", "chairs", "benches", "tables", "shelving", "magazine_rack", "windows", "windowsills",
      "doors", "door_handles", "light_switches", "radiators", "trash",
    ],
  },
  {
    id: "kitchen",
    label: "Küche",
    icon: "cooking-pot",
    objects: [
      "floor", "work_surfaces", "sinks", "faucet", "kitchen_cabinets", "fridge", "microwave",
      "coffee_machine", "dishwasher", "tables", "chairs", "windows", "windowsills", "doors",
      "door_handles", "trash",
    ],
  },
  {
    id: "break_room",
    label: "Aufenthaltsraum",
    icon: "coffee",
    objects: [
      "floor", "tables", "chairs", "cabinets", "fridge", "coffee_machine", "windows", "windowsills",
      "doors", "door_handles", "light_switches", "trash",
    ],
  },
  {
    id: "storage_room",
    label: "Lagerraum",
    icon: "warehouse",
    objects: ["floor", "storage_shelving", "doors", "door_handles", "light_switches", "windows", "trash"],
  },
  {
    id: "changing_room",
    label: "Umkleideraum",
    icon: "shirt",
    objects: [
      "floor", "changing_cabins", "lockers", "benches_changing", "mirrors", "doors", "door_handles",
      "light_switches", "trash",
    ],
  },
  {
    id: "meeting_room",
    label: "Besprechungsraum",
    icon: "presentation",
    objects: [
      "tables", "chairs", "whiteboard", "screen", "floor", "windows", "windowsills", "doors",
      "door_handles", "light_switches", "trash",
    ],
  },
  {
    id: "sales_room",
    label: "Verkaufsraum",
    icon: "store",
    objects: [
      "floor", "shelving", "checkout_counter", "windows", "windowsills", "doors", "door_handles",
      "light_switches", "mirrors", "trash",
    ],
  },
  {
    id: "custom",
    label: "Individueller Raum",
    icon: "sparkles",
    objects: [],
  },
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
