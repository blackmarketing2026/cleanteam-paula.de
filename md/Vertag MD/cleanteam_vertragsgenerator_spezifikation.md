# CleanTeam Vertragsgenerator - Fachliche Spezifikation und Vertragsvorlage

**Zweck:** Diese Datei dient als Grundlage für ein Dashboard, in dem Kunden, Reinigungsobjekte, Angebote und Gebäudereinigungsverträge angelegt, versioniert, automatisch erzeugt und digital unterschrieben werden.

**Ausgangsdokument:** Gebäudereinigungsvertrag der Clean Team Group SRLS Meisterbetrieb Gebäudereinigung, Vertragsdatum 08.07.2026.

> Wichtiger Hinweis: Diese Spezifikation übernimmt die Inhalte des vorhandenen Vertrags und strukturiert sie technisch neu. Sie ist keine anwaltliche Prüfung. Vor dem produktiven Einsatz sollten die Klauseln, die AGB-Einbeziehung, die Preisänderungsklausel, die Reklamationsregelung, der Gerichtsstand und die Regelungen bei Zahlungsverzug rechtlich geprüft werden.

---

## 1. Grundprinzip des Systems

Der Vertragsgenerator soll Daten aus fünf Bereichen zusammenführen:

1. **Auftragnehmer-Stammdaten** - CleanTeam-Firmendaten, Geschäftsführung, Anschrift, Kontaktdaten, Reklamationsportal, AGB-Version.
2. **Kundendaten** - Firma oder Privatperson, Anschrift, Ansprechpartner, Vertretungsberechtigung und Rechnungsdaten.
3. **Objektdaten** - Reinigungsort, Objektbezeichnung, Zugang, Parkmöglichkeiten und Besonderheiten.
4. **Leistungs- und Preisdaten** - Reinigungsintervalle, einzelne Tätigkeiten, Zusatzleistungen, Pauschale, Zahlungsziel und Verbrauchsmaterial.
5. **Vertrags- und Signaturdaten** - Vertragsbeginn, Version, Ort, Datum, Unterschriften, Einwilligungen und Nachweis der Dokumentausgabe.

### Platzhalter-Syntax

Alle dynamischen Inhalte werden im Vertrag mit doppelten geschweiften Klammern dargestellt:

```text
{{customer.company_name}}
{{object.street}}
{{pricing.monthly_net}}
```

Listen, zum Beispiel einzelne Reinigungsleistungen, werden als Arrays gespeichert und beim Erstellen des Vertrags automatisch ausgegeben.

---

## 2. Status- und Versionslogik

### Vertragsstatus

```text
DRAFT             = Entwurf
IN_REVIEW         = intern zur Prüfung
SENT              = an Kunden versendet
VIEWED            = vom Kunden geöffnet
SIGNED_CUSTOMER   = vom Auftraggeber unterschrieben
SIGNED_COMPLETE   = von beiden Parteien unterschrieben
ACTIVE            = aktiv
PAUSED            = pausiert
TERMINATED        = beendet
CANCELLED         = verworfen
SUPERSEDED        = durch neue Version ersetzt
```

### Dokumenttypen

```text
OFFER              = Angebot
CLEANING_CONTRACT  = Gebäudereinigungsvertrag
ADDENDUM            = Nachtrag / Vertragsänderung
SERVICE_SCHEDULE    = Leistungsverzeichnis
TERMINATION         = Kündigung
```

### Versionierung

Jede Vertragsänderung erzeugt eine neue unveränderbare Version.

Pflichtfelder:

- `contract.version_number`
- `contract.version_created_at`
- `contract.version_created_by`
- `contract.previous_version_id`
- `contract.change_reason`
- `contract.template_version`
- `contract.terms_version`
- `contract.agb_version`
- `contract.pdf_hash`

Bereits unterschriebene Versionen dürfen nicht überschrieben werden.

---

## 3. Vollständiger Variablenkatalog

## 3.1 Vertrags-Metadaten

| Variable | Typ | Pflicht | Beschreibung |
|---|---:|:---:|---|
| `contract.id` | UUID/String | Ja | Interne Vertrags-ID |
| `contract.number` | String | Ja | Sichtbare Vertragsnummer, zum Beispiel `CT-2026-00017` |
| `contract.type` | Enum | Ja | Dokumenttyp |
| `contract.title` | String | Ja | Standard: `Gebäudereinigungsvertrag` |
| `contract.status` | Enum | Ja | Vertragsstatus |
| `contract.created_at` | DateTime | Ja | Erstellungszeitpunkt |
| `contract.created_by` | String/User-ID | Ja | Ersteller im Dashboard |
| `contract.effective_date` | Date | Ja | Vertragsbeginn |
| `contract.signing_date` | Date | Nein | Datum der letzten erforderlichen Signatur |
| `contract.signing_place_contractor` | String | Nein | Unterzeichnungsort Auftragnehmer |
| `contract.signing_place_customer` | String | Nein | Unterzeichnungsort Auftraggeber |
| `contract.language` | String | Ja | Standard: `de` |
| `contract.governing_law` | String | Ja | Standard: `Recht der Bundesrepublik Deutschland` |
| `contract.term_type` | Enum | Ja | unbefristet / befristet |
| `contract.end_date` | Date | Bedingt | Pflicht bei befristetem Vertrag |
| `contract.notice_period_value` | Integer | Optional | Kündigungsfrist |
| `contract.notice_period_unit` | Enum | Optional | Tage / Wochen / Monate |
| `contract.notice_effective_to` | Enum | Optional | Monatsende / Quartalsende / beliebiger Termin |
| `contract.additional_agreements` | LongText | Nein | Individuelle Zusatzvereinbarungen |
| `contract.internal_notes` | LongText | Nein | Nur intern, nicht im Vertrag ausgeben |
| `contract.customer_visible_notes` | LongText | Nein | Zusätzliche Notizen im Vertrag |

---

## 3.2 Auftragnehmer-Stammdaten

| Variable | Typ | Pflicht | Beispiel aus Vorlage |
|---|---:|:---:|---|
| `contractor.legal_name` | String | Ja | Clean Team Group SRLS Meisterbetrieb Gebäudereinigung |
| `contractor.short_name` | String | Nein | CleanTeam |
| `contractor.legal_form` | String | Nein | SRLS |
| `contractor.trade_description` | String | Nein | Meisterbetrieb Gebäudereinigung |
| `contractor.managing_directors` | Array<String> | Ja | Riccardo Cuccaro, Thomas Mündlein |
| `contractor.register_number` | String | Optional | Handels-/Unternehmensregister |
| `contractor.tax_number` | String | Optional | Steuernummer |
| `contractor.vat_id` | String | Optional | Umsatzsteuer-ID |
| `contractor.street` | String | Ja | Via Dorsale 11 |
| `contractor.postal_code` | String | Ja | 54100 |
| `contractor.city` | String | Ja | Massa |
| `contractor.country` | String | Ja | Italien |
| `contractor.service_point_street` | String | Nein | Ober der Mühle 30 |
| `contractor.service_point_postal_code` | String | Nein | 42699 |
| `contractor.service_point_city` | String | Nein | Solingen |
| `contractor.email` | Email | Optional | Zentrale E-Mail-Adresse |
| `contractor.phone` | String | Optional | Zentrale Telefonnummer |
| `contractor.website` | URL | Optional | `https://cleanteam-group.com` |
| `contractor.complaint_portal_url` | URL | Ja | Kundenbereich / Reklamationsformular |
| `contractor.bank_account_holder` | String | Optional | Kontoinhaber |
| `contractor.iban` | String | Optional | IBAN |
| `contractor.bic` | String | Optional | BIC |
| `contractor.logo_url` | String | Optional | Logo für PDF/HTML |

---

## 3.3 Auftraggeber / Kundendaten

Das System muss Firmenkunden und Privatkunden unterstützen.

| Variable | Typ | Pflicht | Beschreibung |
|---|---:|:---:|---|
| `customer.id` | UUID/String | Ja | Interne Kunden-ID |
| `customer.type` | Enum | Ja | `COMPANY` oder `PRIVATE_PERSON` |
| `customer.company_name` | String | Bedingt | Pflicht bei Firma |
| `customer.legal_form` | String | Optional | GmbH, UG, e. K. usw. |
| `customer.first_name` | String | Bedingt | Pflicht bei Privatperson |
| `customer.last_name` | String | Bedingt | Pflicht bei Privatperson |
| `customer.street` | String | Ja | Rechnungs-/Firmensitz |
| `customer.house_number` | String | Ja | Hausnummer, getrennt speicherbar |
| `customer.postal_code` | String | Ja | Postleitzahl |
| `customer.city` | String | Ja | Ort |
| `customer.country` | String | Ja | Land |
| `customer.email` | Email | Ja | Vertrags- und Rechnungsadresse |
| `customer.phone` | String | Optional | Telefonnummer |
| `customer.vat_id` | String | Optional | Umsatzsteuer-ID |
| `customer.tax_number` | String | Optional | Steuernummer |
| `customer.invoice_email` | Email | Optional | Separate Rechnungsadresse |
| `customer.invoice_reference` | String | Optional | Bestellnummer, Kostenstelle, Mandatsnummer |
| `customer.payment_method` | Enum | Optional | Überweisung / Lastschrift |
| `customer.billing_notes` | LongText | Optional | Hinweise zur Rechnungsstellung |

### Vertretungsberechtigter Ansprechpartner

| Variable | Typ | Pflicht | Beispiel aus Vorlage |
|---|---:|:---:|---|
| `customer.signatory.salutation` | String | Nein | Frau |
| `customer.signatory.first_name` | String | Ja | Melanie |
| `customer.signatory.last_name` | String | Ja | Fetten |
| `customer.signatory.position` | String | Nein | Abteilung Verkauf |
| `customer.signatory.authority_type` | Enum | Ja | Geschäftsführer / Prokurist / i. A. / i. V. / sonstige Vollmacht |
| `customer.signatory.authority_text` | String | Nein | `i. A.` |
| `customer.signatory.email` | Email | Optional | Signatur-E-Mail |
| `customer.signatory.phone` | String | Optional | Telefon |
| `customer.signatory.confirmed_authorized` | Boolean | Ja | Bestätigung der Zeichnungsberechtigung |

---

## 3.4 Reinigungsobjekt

Ein Kunde kann mehrere Reinigungsobjekte besitzen. Ein Vertrag kann ein oder mehrere Objekte enthalten; für den ersten Ausbau reicht ein Objekt pro Vertrag.

| Variable | Typ | Pflicht | Beschreibung |
|---|---:|:---:|---|
| `object.id` | UUID/String | Ja | Objekt-ID |
| `object.name` | String | Ja | Objektbezeichnung, zum Beispiel `Hauptverwaltung Solingen` |
| `object.customer_object_number` | String | Optional | Interne Objektnummer des Kunden |
| `object.street` | String | Ja | Leistungsort Straße |
| `object.house_number` | String | Ja | Hausnummer |
| `object.postal_code` | String | Ja | Postleitzahl |
| `object.city` | String | Ja | Ort |
| `object.country` | String | Ja | Land |
| `object.floor_area_sqm` | Decimal | Optional | Reinigungsfläche in m² |
| `object.number_of_floors` | Integer | Optional | Anzahl Etagen |
| `object.contact_name` | String | Optional | Ansprechpartner vor Ort |
| `object.contact_phone` | String | Optional | Telefon vor Ort |
| `object.contact_email` | Email | Optional | E-Mail vor Ort |
| `object.access_method` | Enum | Optional | Schlüssel / Code / Empfang / Begleitung |
| `object.access_notes` | LongText | Optional | Zugangshinweise |
| `object.key_count` | Integer | Optional | Übergebene Schlüssel |
| `object.alarm_instructions` | LongText | Optional | Alarmanlage |
| `object.cleaning_during_operation` | Boolean | Ja | Reinigung im laufenden Betrieb |
| `object.free_parking_available` | Boolean | Ja | Kostenfreie Parkmöglichkeit vorhanden |
| `object.parking_notes` | LongText | Optional | Parkplatz, Kennzeichen, Zufahrt |
| `object.waste_disposal_notes` | LongText | Optional | Mülltrennung und Entsorgungsort |
| `object.hygiene_requirements` | LongText | Optional | Besondere Hygienevorgaben |
| `object.safety_requirements` | LongText | Optional | PSA, Sicherheitsunterweisung usw. |
| `object.special_notes` | LongText | Optional | Sonstige Objektbesonderheiten |

---

## 3.5 Reinigungsplan und Intervalle

### Intervall-Enums

```text
ONCE                 = einmalig
DAILY                = täglich
WEEKDAYS             = Montag bis Freitag
WEEKLY               = wöchentlich
TWICE_WEEKLY         = 2x wöchentlich
THREE_TIMES_WEEKLY   = 3x wöchentlich
FOUR_TIMES_WEEKLY    = 4x wöchentlich
EVERY_TWO_WEEKS      = 14-tägig
MONTHLY              = monatlich
QUARTERLY            = vierteljährlich
SEMI_ANNUALLY        = halbjährlich
ANNUALLY             = jährlich
AS_NEEDED            = nach Bedarf
BY_AGREEMENT         = nach vorheriger Abstimmung
CUSTOM               = individuelles Intervall
```

### Allgemeine Leistungsvariablen

| Variable | Typ | Pflicht | Beschreibung |
|---|---:|:---:|---|
| `service_schedule.default_frequency` | Enum | Ja | Hauptintervall des Vertrags |
| `service_schedule.days_of_week` | Array<Enum> | Optional | MO, DI, MI, DO, FR, SA, SO |
| `service_schedule.preferred_start_time` | Time | Optional | Gewünschter Beginn |
| `service_schedule.preferred_end_time` | Time | Optional | Zeitfenster Ende |
| `service_schedule.estimated_hours_per_visit` | Decimal | Optional | Kalkulationswert |
| `service_schedule.staff_count` | Integer | Optional | Geplante Mitarbeiterzahl |
| `service_schedule.service_start_notes` | LongText | Optional | Start- und Einweisungsnotizen |
| `service_schedule.holiday_service` | Boolean | Ja | Reinigung an Feiertagen |
| `service_schedule.holiday_makeup` | Boolean | Ja | Nachholung bei Feiertag |
| `service_schedule.flat_rate_unchanged_on_holidays` | Boolean | Ja | Pauschale bleibt gleich |

### Einzelne Reinigungsposition

Jede Leistung wird als eigener Datensatz gespeichert:

```json
{
  "id": "service-item-uuid",
  "category": "SANITARY",
  "area_name": "Sanitäranlagen",
  "task_name": "Hygienische Reinigung sämtlicher Sanitäranlagen",
  "description": "Reinigung gemäß den vereinbarten Hygienevorgaben.",
  "frequency": "TWICE_WEEKLY",
  "custom_frequency_text": null,
  "included_in_flat_rate": true,
  "requires_prior_approval": false,
  "customer_must_prepare_area": false,
  "materials_provided_by": "CONTRACTOR",
  "sort_order": 30,
  "active": true,
  "additional_notes": null
}
```

### Leistungskategorien

```text
ENTRANCE_GLASS
OFFICE_GENERAL
GUEST_ROOMS
SANITARY
CONSUMABLES
KITCHEN_BREAK_ROOM
CANTEEN
ELEVATOR
STAIRCASE_CORRIDORS
TECHNICAL_ROOM
MANAGEMENT_OFFICE
WINDOW_CLEANING
SPECIAL_CLEANING
OTHER
```

---

## 4. Leistungsverzeichnis aus der Vertragsvorlage

Die folgenden Leistungen sollen im Dashboard als auswählbare Standardpositionen hinterlegt werden. Jede Position muss individuell aktiviert, deaktiviert, ergänzt und mit einem eigenen Intervall versehen werden können.

## 4.1 Eingangsbereich und Glasflächen

- Saugen der Bodenflächen im Eingangsbereich.
- Kontrolle des Eingangsbereichs auf Spinnweben und umgehende Beseitigung.
- Entfernung von Fingerabdrücken und Verschmutzungen an den Glastüren im Eingangsbereich.

## 4.2 Büro- und Allgemeinflächen

- Entstaubung aller frei zugänglichen Einrichtungsgegenstände, soweit diese ohne Verrücken von Gegenständen erreichbar sind.
- Entleerung sämtlicher Papierkörbe und Abfallbehälter in den Büroräumen sowie Einsetzen neuer Müllbeutel.
- Entstaubung frei zugänglicher Schreibtischflächen.
- Keine Reinigung belegter Schreibtischflächen oder während der Nutzung des Arbeitsplatzes.
- Gründliches Saugen sämtlicher Bodenflächen.
- Feuchtreinigung aller Hartbodenflächen ohne Teppichbelag.
- Entstaubung sämtlicher Türen und Türrahmen.
- Reinigung und Entstaubung frei zugänglicher Fensterbänke.
- Bei belegten Fensterbänken werden nur erreichbare Bereiche gereinigt; vollständig belegte Fensterbänke bleiben unberücksichtigt.
- Entstaubung von Regalen nach vorheriger Abstimmung beziehungsweise auf Anweisung.
- Entstaubung der Stuhlgestelle und Stuhlfüße, in der Vorlage im 14-tägigen Turnus.

## 4.3 Gästezimmer, Sanitärbereiche und Verbrauchsmaterial

- Kontrolle der Gästezimmer nach Bedarf einschließlich Entstaubung, Bodenreinigung und Feuchtwischen der Bodenflächen.
- Hygienische Reinigung sämtlicher Sanitäranlagen gemäß den geltenden beziehungsweise vereinbarten Hygienevorgaben.
- Nachfüllen von Handtuchpapier, Toilettenpapier und Seifenspendern, sofern das Verbrauchsmaterial vor Ort bereitgestellt wird.

## 4.4 Küchen, Pausenraum und Kantine

- Reinigung der Küchenzeilen ausschließlich von außen, einschließlich Arbeitsflächen und Spüle.
- Reinigung sämtlicher Tische im Pausenraum.
- Reinigung der Teeküche im Pausenraum einschließlich Spüle.
- Entfernung von Kaffee- und sonstigen Gebrauchsspuren an den Außenflächen der Küchenmöbel.
- Saugen und Feuchtwischen des Bodens im Pausenraum.
- Reinigung der Demonstrationsküche in der Kantine nach vorheriger Abstimmung und entsprechend den vom Objektleiter festgelegten Bereichen.
- Reinigung der vereinbarten Küchenflächen sowie Saugen und Feuchtwischen des Bodens in der Demonstrationsküche.

## 4.5 Aufzug, Treppenhaus und Sonderbereiche

- Kontrolle des Aufzugs auf Spinnweben und deren Beseitigung.
- Reinigung des Aufzugbodens durch Saugen und Feuchtwischen.
- Reinigung des Treppenhauses einschließlich Flure durch Kehren beziehungsweise Saugen und anschließende Feuchtreinigung; in der Vorlage wöchentlich.
- Laufende Kontrolle sämtlicher Bereiche auf Spinnweben und deren sofortige Beseitigung.
- Gründliche Reinigung des Technik- beziehungsweise Elektroraums in der Produktion, soweit die Bereiche zugänglich sind; in der Vorlage wöchentlich.

## 4.6 Geschäftsführerbüro

- Saugen der Bodenflächen.
- Reinigung des Glastisches und der frei zugänglichen Schreibtischflächen.
- Entfernung von Fingerabdrücken auf Glasflächen.
- Entstaubung der Fensterbänke.
- Beseitigung vorhandener Spinnweben.
- Reinigung der Glastüren einschließlich Entfernung von Fingerabdrücken.

---

## 5. Preis-, Rechnungs- und Zahlungsvariablen

| Variable | Typ | Pflicht | Beispiel / Standard |
|---|---:|:---:|---|
| `pricing.billing_model` | Enum | Ja | Monatspauschale / Stunden / je Einsatz / Mischmodell |
| `pricing.monthly_net` | Decimal | Bedingt | 1300.00 |
| `pricing.vat_rate` | Decimal | Ja | aktuell anzuwendender Satz |
| `pricing.monthly_vat` | Decimal | Berechnet | Netto × USt. |
| `pricing.monthly_gross` | Decimal | Berechnet | Netto + USt. |
| `pricing.currency` | String | Ja | EUR |
| `pricing.invoice_frequency` | Enum | Ja | monatlich |
| `pricing.invoice_timing` | Enum | Ja | im Voraus / im Nachhinein |
| `pricing.payment_due_value` | Integer | Ja | 5 |
| `pricing.payment_due_unit` | Enum | Ja | Arbeitstage / Kalendertage |
| `pricing.discount_allowed` | Boolean | Ja | Standard: false |
| `pricing.default_after_days` | Integer | Ja | 30 |
| `pricing.default_interest_rule` | String | Ja | Verzugszinsen gemäß Vertrag / gesetzliche Regelung |
| `pricing.extra_services_separately_charged` | Boolean | Ja | Standard: true |
| `pricing.parking_fees_separately_charged` | Boolean | Ja | Standard: true, sofern kein kostenloser Parkplatz |
| `pricing.consumables_included` | Boolean | Ja | In Vorlage: false |
| `pricing.consumables_markup_or_price_list` | String | Optional | Preisregelung Verbrauchsmaterial |
| `pricing.price_adjustment_enabled` | Boolean | Ja | Tariferhöhungen weitergeben |
| `pricing.price_adjustment_notice_months` | Integer | Ja | In Vorlage: mindestens 1 Monat |
| `pricing.price_adjustment_components` | Array | Ja | Lohn-, Betriebs- und Materialkosten |
| `pricing.expected_wage_increase_min_percent` | Decimal | Optional | 5 |
| `pricing.expected_wage_increase_max_percent` | Decimal | Optional | 10 |
| `pricing.bank_details_text` | String | Optional | Alternativ zu strukturierten Bankdaten |

### Individuelle Preispositionen

Zusätzlich zur Pauschale muss eine beliebige Liste möglich sein:

```json
[
  {
    "name": "Monatliche Unterhaltsreinigung",
    "quantity": 1,
    "unit": "Monat",
    "unit_price_net": 1300.00,
    "vat_rate": 19,
    "included_in_contract_total": true,
    "notes": "2x wöchentlich"
  },
  {
    "name": "Verbrauchsmaterial",
    "quantity": null,
    "unit": "nach Verbrauch",
    "unit_price_net": null,
    "vat_rate": 19,
    "included_in_contract_total": false,
    "notes": "Separate Berechnung"
  }
]
```

---

## 6. Vertragstext-Vorlage mit Variablen

Die nachfolgende Vorlage enthält sämtliche inhaltlichen Vertragsbedingungen aus dem Ausgangsvertrag. Die Paragraphennummerierung wurde technisch bereinigt, weil im Ausgangsdokument § 5 doppelt vorkommt und Unterpunkte uneinheitlich nummeriert sind.

# Gebäudereinigungsvertrag

zwischen

**{{contractor.legal_name}}**  
{{#if contractor.trade_description}}{{contractor.trade_description}}{{/if}}  
Geschäftsführung: {{contractor.managing_directors_formatted}}  
{{contractor.street}}, {{contractor.postal_code}} {{contractor.city}}{{#if contractor.country}}, {{contractor.country}}{{/if}}  
{{#if contractor.service_point_street}}Service Point: {{contractor.service_point_street}}, {{contractor.service_point_postal_code}} {{contractor.service_point_city}}{{/if}}

- im Folgenden **Auftragnehmer** genannt -

und

{{#if customer.company_name}}**{{customer.company_name}}**{{else}}**{{customer.first_name}} {{customer.last_name}}**{{/if}}  
{{customer.street}} {{customer.house_number}}, {{customer.postal_code}} {{customer.city}}{{#if customer.country}}, {{customer.country}}{{/if}}  
Vertragsunterzeichnung durch: {{customer.signatory.authority_text}} {{customer.signatory.first_name}} {{customer.signatory.last_name}}{{#if customer.signatory.position}}, {{customer.signatory.position}}{{/if}}

- im Folgenden **Auftraggeber** genannt -

wird folgender Vertrag zur Gebäudereinigung abgeschlossen:

## § 1 Beginn, Rechtswahl und Vertragssprache

1. Der Vertrag zur Gebäudereinigung tritt am **{{contract.effective_date_formatted}}** in Kraft.
2. Für sämtliche Rechtsbeziehungen der Parteien gilt ausschließlich das Recht der Bundesrepublik Deutschland unter Ausschluss aller kollisionsrechtlichen Bestimmungen, die in eine andere Rechtsordnung verweisen.
3. Die Vertragssprache ist Deutsch.

{{#if contract.term_type == "FIXED"}}
4. Der Vertrag endet am **{{contract.end_date_formatted}}**, ohne dass es einer gesonderten Kündigung bedarf, sofern keine abweichende Vereinbarung getroffen wurde.
{{/if}}

{{#if contract.notice_period_value}}
5. Die Kündigungsfrist beträgt **{{contract.notice_period_value}} {{contract.notice_period_unit_formatted}}** {{contract.notice_effective_to_formatted}}.
{{/if}}

## § 2 Vertragsgegenstand und Objekt

Vertragsgegenstand sind die in § 3 und im Leistungsverzeichnis genannten Reinigungsarbeiten für das nachfolgend bezeichnete Objekt:

- Objektbezeichnung: **{{object.name}}**
- Straße: **{{object.street}} {{object.house_number}}**
- Postleitzahl und Ort: **{{object.postal_code}} {{object.city}}**
- Land: **{{object.country}}**
{{#if object.customer_object_number}}- Kundenseitige Objektnummer: **{{object.customer_object_number}}**{{/if}}
{{#if object.special_notes}}- Objektbesonderheiten: **{{object.special_notes}}**{{/if}}

## § 3 Art, Umfang und Intervalle der Reinigung

Die regelmäßige Reinigung findet **{{service_schedule.default_frequency_formatted}}** statt.

{{#if service_schedule.days_of_week_formatted}}
Vorgesehene Reinigungstage: **{{service_schedule.days_of_week_formatted}}**.
{{/if}}

{{#if service_schedule.time_window_formatted}}
Vorgesehenes Zeitfenster: **{{service_schedule.time_window_formatted}}**.
{{/if}}

### Leistungsverzeichnis

{{#each service_items_grouped}}
#### {{category_name}}
{{#each items}}
- **{{task_name}}**{{#if description}} - {{description}}{{/if}}  
  Intervall: {{frequency_formatted}}{{#if custom_frequency_text}} ({{custom_frequency_text}}){{/if}}{{#if additional_notes}}  
  Zusatzhinweis: {{additional_notes}}{{/if}}
{{/each}}
{{/each}}

Leistungen, die nicht in diesem Vertrag oder dem zugehörigen Leistungsverzeichnis aufgeführt sind, bedürfen einer gesonderten Beauftragung und werden zusätzlich berechnet.

## § 4 Vergütung und Zahlungsbedingungen

1. Die monatliche Pauschalvergütung beträgt **{{pricing.monthly_net_formatted}} netto** zuzüglich der jeweils geltenden Umsatzsteuer. Der Bruttobetrag beträgt zum Zeitpunkt der Vertragserstellung **{{pricing.monthly_gross_formatted}}**.
2. Leistungen außerhalb des vereinbarten Leistungsumfangs sind separat zu beauftragen und werden zusätzlich berechnet.
3. Rechnungen sind innerhalb von **{{pricing.payment_due_value}} {{pricing.payment_due_unit_formatted}}** nach Zugang ohne Abzug auf das vom Auftragnehmer benannte Konto zu zahlen.
4. Nach **{{pricing.default_after_days}} Tagen** fallen Verzugszinsen nach der vereinbarten beziehungsweise gesetzlichen Regelung an.

### Preisänderung bei Tarif- und Kostensteigerungen

Bei einer Tariferhöhung in der Gebäudereinigung gibt der Auftragnehmer die entsprechenden Lohnsteigerungen an sein Personal und an seine Kunden weiter. Der Auftraggeber wird mindestens **{{pricing.price_adjustment_notice_months}} Monat(e)** vor Wirksamwerden der Anpassung informiert. Der exakte Differenzbetrag wird unter Berücksichtigung der Lohn-, Betriebs- und Materialkosten mitgeteilt.

{{#if pricing.expected_wage_increase_min_percent}}
Der im Zeitpunkt der Vertragserstellung erwartete Korridor der Lohnkostensteigerung beträgt etwa **{{pricing.expected_wage_increase_min_percent}} bis {{pricing.expected_wage_increase_max_percent}} Prozent**. Maßgeblich ist die später konkret mitgeteilte Anpassung.
{{/if}}

### Zahlungsverzug und Zurückbehaltungsrecht

Geht innerhalb der Zahlungsfrist kein vollständiger Zahlungseingang ein und erfolgt keine vorherige Mitteilung oder anderweitige Vereinbarung durch den Auftraggeber, ist der Auftragnehmer berechtigt, von seinem Zurückbehaltungsrecht Gebrauch zu machen und die vertraglich geschuldeten Reinigungsleistungen bis zum vollständigen Ausgleich der offenen Forderung auszusetzen.

Die während der Ausübung des Zurückbehaltungsrechts ausfallenden Reinigungstermine gelten nach der Regelung des Ausgangsvertrags als vertraglich geschuldet und werden vergütungsrechtlich berechnet. Ein Anspruch auf Nachholung der während dieses Zeitraums ausgefallenen Reinigungsleistungen besteht nach der Regelung des Ausgangsvertrags nicht.

## § 5 Pflichten und Mitwirkung des Auftraggebers

### 5.1 Kontrolle und Reklamationen

1. Reklamationen sind über das Reklamationsportal des Auftragnehmers unter **{{contractor.complaint_portal_url}}** im Kundenbereich unter Verwendung des vorgesehenen Reklamationsformulars einzureichen.
2. Nach der Regelung des Ausgangsvertrags werden Reklamationen über andere Kommunikationswege, insbesondere telefonisch, per E-Mail, WhatsApp oder sonstige Nachrichtendienste, nicht anerkannt und nicht bearbeitet.
3. Der Auftraggeber ist verpflichtet, die erbrachte Leistung unmittelbar nach Öffnung beziehungsweise Inbetriebnahme des Objekts zu kontrollieren und erkennbare Mängel unverzüglich über das Reklamationsportal anzuzeigen.
4. Nach der Regelung des Ausgangsvertrags sind später eingehende Reklamationen ausgeschlossen, wenn eine ordnungsgemäße Überprüfung und Nachvollziehbarkeit der beanstandeten Leistung nicht mehr möglich ist.

### 5.2 Feiertage

Fällt ein gesetzlicher Feiertag auf einen regulären Reinigungstag, findet an diesem Tag keine Arbeitsleistung statt. Eine Nachholung erfolgt nicht. Die vereinbarte Pauschalvergütung bleibt davon unberührt.

> Systemlogik: Diese Klausel nur ausgeben, wenn `service_schedule.holiday_service = false`, `service_schedule.holiday_makeup = false` und `service_schedule.flat_rate_unchanged_on_holidays = true`.

### 5.3 Kartonagen und Abfall

1. Kartonagen sind durch den Auftraggeber ordnungsgemäß zu zerkleinern und in Müllsäcken zur Entsorgung bereitzustellen.
2. Karton und Kartonagen werden durch das Reinigungspersonal nur entsorgt, wenn sie zuvor zerkleinert und ordnungsgemäß in einem Müllsack bereitgestellt wurden.

### 5.4 Parkmöglichkeit und Parkkosten

Stellt der Auftraggeber keine kostenfreie Parkmöglichkeit bereit, werden die tatsächlich anfallenden Parkgebühren gesondert berechnet und transparent auf der Rechnung ausgewiesen.

### 5.5 Freiräumen und Zugänglichkeit

1. Der Auftraggeber ist verpflichtet, bewegliche Gegenstände, die eine ordnungsgemäße Reinigung beeinträchtigen oder einen unverhältnismäßigen Zeitaufwand verursachen, vor Beginn der Reinigungsarbeiten vorzubereiten beziehungsweise zu entfernen.
2. Hierzu zählen insbesondere das Hochstellen von Stühlen, Spielzeugkisten und sonstigen beweglichen Einrichtungsgegenständen.
3. Gegenstände auf Böden oder Tischen werden vom Auftragnehmer grundsätzlich nicht entfernt.
4. Werden die betreffenden Bereiche nicht freigeräumt, erfolgt die Reinigung ausschließlich in den frei zugänglichen Bereichen beziehungsweise um die vorhandenen Gegenstände herum.
5. Ein Anspruch auf Nachreinigung oder Reklamation hinsichtlich nicht zugänglicher Flächen ist nach der Regelung des Ausgangsvertrags ausgeschlossen.

### 5.6 Reinigung im laufenden Betrieb

Die Reinigungsleistungen werden im laufenden Betrieb erbracht, sofern unter `object.cleaning_during_operation` nichts Abweichendes vereinbart ist. Für Verschmutzungen, die nach Abschluss der Reinigung durch Mitarbeiter, Kunden oder Dritte entstehen, übernimmt der Auftragnehmer nach der Regelung des Ausgangsvertrags keine Haftung. Eine Nachreinigung ist nicht Bestandteil der vereinbarten Leistung, sofern sie nicht gesondert beauftragt wurde.

### 5.7 Flecken und Grenzen der Unterhaltsreinigung

Die Unterhaltsreinigung umfasst keine Garantie zur vollständigen Entfernung hartnäckiger oder eingetrockneter Flecken. Soweit solche Flecken im Rahmen der vereinbarten Feuchtreinigung nicht beseitigt werden können, ist eine Haftung nach der Regelung des Ausgangsvertrags ausgeschlossen.

### 5.8 WC-Fliesenwände

Die Reinigung von WC-Fliesenwänden ist nicht Bestandteil der vereinbarten Unterhaltsreinigung, sofern sie nicht ausdrücklich im Leistungsverzeichnis aktiviert oder gesondert beauftragt wurde.

### 5.9 Elektronische Geräte und Anlagen

1. Elektronische Geräte und Anlagen werden ausschließlich trocken mittels Staubwedel oder vergleichbarer trockener Reinigungsutensilien entstaubt.
2. Eine Feuchtreinigung elektronischer Geräte erfolgt aus Haftungsgründen nicht.
3. Für Schäden, die durch eine vom Auftraggeber ausdrücklich gewünschte oder eigenmächtig veranlasste Feuchtreinigung entstehen, wird nach der Regelung des Ausgangsvertrags keine Haftung übernommen.

### 5.10 Verbrauchsmaterialien

1. Toilettenpapier, Handtuchpapier, Papierprodukte, Seife und sonstige Verbrauchsmaterialien sind nicht in der Pauschale enthalten, sofern `pricing.consumables_included` nicht ausdrücklich aktiviert wurde.
2. Der Auftragnehmer kann Verbrauchsmaterialien bereitstellen und gesondert berechnen.
3. Das Nachfüllen erfolgt nur, soweit das benötigte Verbrauchsmaterial vor Ort vorhanden ist oder vom Auftragnehmer im Rahmen einer gesonderten Vereinbarung geliefert wird.

## § 6 Vertraulichkeit

Die Parteien verpflichten sich, während der Laufzeit dieses Vertrags die Geschäfts- und Betriebsgeheimnisse der jeweils anderen Partei, den Inhalt dieses Vertrags, jedes überlassene Know-how, ausgehändigte Materialien und sämtliche Informationen über das Geschäft der jeweils anderen Partei vertraulich zu behandeln und nicht an Dritte weiterzugeben, soweit keine gesetzliche Pflicht oder ausdrückliche Zustimmung zur Weitergabe besteht.

## § 7 Erfüllungsort, Gerichtsstand und Schlussbestimmungen

1. Der Erfüllungsort richtet sich nach dem Sitz des Auftraggebers, soweit keine abweichende Vereinbarung getroffen wurde.
2. Ausschließlicher Gerichtsstand für Streitigkeiten im Zusammenhang mit dem Vertragsverhältnis ist **{{legal.jurisdiction_city}}**, soweit eine solche Gerichtsstandsvereinbarung rechtlich zulässig ist.
3. Für sämtliche Rechtsbeziehungen der Parteien gilt ausschließlich das Recht der Bundesrepublik Deutschland unter Ausschluss aller kollisionsrechtlichen Bestimmungen, die in eine andere Rechtsordnung verweisen.
4. Sollte eine Bestimmung dieses Vertrags unwirksam sein oder werden, wird die Wirksamkeit der übrigen Bestimmungen hiervon nicht berührt. Die Parteien werden über eine Regelung verhandeln, die dem wirtschaftlichen und rechtlichen Inhalt der unwirksamen Bestimmung möglichst nahekommt. Gleiches gilt für mögliche Vertragslücken.

## § 8 Einbeziehung der Allgemeinen Geschäftsbedingungen

Zusätzlich gelten die Allgemeinen Geschäftsbedingungen des Auftragnehmers in der bei Vertragsabschluss bereitgestellten und dokumentierten Fassung **{{legal.agb_version}}** vom **{{legal.agb_date_formatted}}**.

Die AGB wurden dem Auftraggeber vor Vertragsabschluss über folgenden Weg zur Verfügung gestellt:

- Bereitstellungsart: **{{legal.agb_delivery_method_formatted}}**
- AGB-URL: **{{legal.agb_url}}**
- Bereitstellungszeitpunkt: **{{legal.agb_delivered_at_formatted}}**
- Dokument-/Datei-Hash: **{{legal.agb_document_hash}}**

Auf Wunsch kann dem Auftraggeber eine Ausfertigung der AGB postalisch zugesendet werden.

## § 9 Zusätzliche Vereinbarungen

{{#if contract.additional_agreements}}
{{contract.additional_agreements}}
{{else}}
Es bestehen keine zusätzlichen Vereinbarungen.
{{/if}}

## § 10 Ausfertigungen und elektronische Dokumentation

Beide Parteien erhalten eine Ausfertigung dieses Gebäudereinigungsvertrags. Bei elektronischer Unterzeichnung wird jeder Partei nach Abschluss des Signaturvorgangs eine unveränderbare PDF-Ausfertigung einschließlich Signaturprotokoll zur Verfügung gestellt.

---

## Unterschriften

### Auftragnehmer

Ort: {{contract.signing_place_contractor}}  
Datum: {{signature.contractor.signed_at_date_formatted}}

Name: {{signature.contractor.full_name}}  
Funktion: {{signature.contractor.position}}  
Vertretungsart: {{signature.contractor.authority_text}}

Signatur: {{signature.contractor.signature_image_or_token}}

### Auftraggeber

Ort: {{contract.signing_place_customer}}  
Datum: {{signature.customer.signed_at_date_formatted}}

Name: {{signature.customer.full_name}}  
Funktion: {{signature.customer.position}}  
Vertretungsart: {{signature.customer.authority_text}}

Bestätigung: Die unterzeichnende Person bestätigt, zur Unterzeichnung für den Auftraggeber berechtigt zu sein.

Signatur: {{signature.customer.signature_image_or_token}}

---

## 7. Signatur- und Nachweisvariablen

| Variable | Typ | Pflicht | Beschreibung |
|---|---:|:---:|---|
| `signature.customer.full_name` | String | Ja | Name Unterzeichner Auftraggeber |
| `signature.customer.position` | String | Optional | Funktion |
| `signature.customer.authority_text` | String | Ja | i. A., i. V., Geschäftsführung usw. |
| `signature.customer.signed_at` | DateTime | Ja | Signaturzeitpunkt |
| `signature.customer.ip_address` | String | Optional | IP-Nachweis, datenschutzkonform behandeln |
| `signature.customer.user_agent` | String | Optional | Browser-/Gerätenachweis |
| `signature.customer.signature_method` | Enum | Ja | gezeichnet / Klicksignatur / OTP / qualifiziert |
| `signature.customer.signature_data` | EncryptedText/File | Ja | Signaturbild oder Signaturtoken |
| `signature.customer.consent_checkbox` | Boolean | Ja | Zustimmung zum Vertrag |
| `signature.customer.authority_checkbox` | Boolean | Ja | Zeichnungsberechtigung bestätigt |
| `signature.customer.terms_checkbox` | Boolean | Ja | AGB erhalten und akzeptiert |
| `signature.customer.privacy_checkbox` | Boolean | Ja | Datenschutzhinweise bestätigt |
| `signature.contractor.*` | analog | Ja | Entsprechende Daten Auftragnehmer |
| `signature.audit_log` | Array | Ja | Öffnung, Zustimmung, OTP, Signatur, Versand |
| `signature.completed_pdf_hash` | String | Ja | Hash der finalen PDF-Datei |
| `signature.certificate_url` | String | Optional | Signaturzertifikat / Audit-PDF |

### Empfohlene Audit-Events

```text
CONTRACT_CREATED
CONTRACT_UPDATED
CONTRACT_PDF_RENDERED
CONTRACT_SENT
EMAIL_DELIVERED
CONTRACT_OPENED
AGB_OPENED
CUSTOMER_CONSENT_CONFIRMED
AUTHORITY_CONFIRMED
CUSTOMER_SIGNED
CONTRACTOR_SIGNED
FINAL_PDF_CREATED
FINAL_PDF_SENT
CONTRACT_ACTIVATED
```

---

## 8. Zusätzliche Notizen und individuelle Vereinbarungen

Das Dashboard benötigt mindestens drei getrennte Notizfelder:

1. `contract.internal_notes`  
   Nur für CleanTeam-Mitarbeiter sichtbar. Darf nie in Angebot oder Vertrag erscheinen.

2. `contract.customer_visible_notes`  
   Wird als allgemeiner Hinweis im Vertrag ausgegeben.

3. `contract.additional_agreements`  
   Rechtlich relevante, individuell vereinbarte Ergänzungen. Muss in der finalen PDF erscheinen und mitversioniert werden.

Zusätzlich sollte jede Reinigungsposition ein eigenes Feld `additional_notes` besitzen.

Beispiele:

```text
- Reinigung nur nach 18:00 Uhr.
- Alarmanlage muss vor Verlassen aktiviert werden.
- Schlüsselübergabe erfolgt an Frau Muster.
- Glastüren im Eingangsbereich bei jedem Einsatz reinigen.
- Stuhlgestelle nur alle 14 Tage entstauben.
- Demonstrationsküche ausschließlich nach Freigabe des Objektleiters reinigen.
```

---

## 9. Empfohlenes Datenbankmodell

Die Struktur ist für MySQL/MariaDB auf klassischem PHP-Hosting geeignet.

### Tabellen

```text
users
customers
customer_contacts
cleaning_objects
contracts
contract_versions
contract_service_items
service_catalog
contract_price_items
contract_notes
contract_documents
contract_signatures
contract_audit_events
company_settings
legal_text_versions
agb_versions
```

### Beziehungen

```text
customers 1 --- n customer_contacts
customers 1 --- n cleaning_objects
customers 1 --- n contracts
cleaning_objects 1 --- n contracts
contracts 1 --- n contract_versions
contract_versions 1 --- n contract_service_items
contract_versions 1 --- n contract_price_items
contract_versions 1 --- n contract_documents
contract_versions 1 --- n contract_signatures
contracts 1 --- n contract_audit_events
```

### Wichtiger technischer Grundsatz

Eine Vertragsversion muss einen vollständigen **Snapshot** aller verwendeten Daten enthalten. Spätere Änderungen an den Kundenstammdaten dürfen einen bereits erzeugten oder unterschriebenen Vertrag nicht verändern.

Beispiel:

```json
{
  "contract_version_id": "uuid",
  "customer_snapshot": {},
  "contractor_snapshot": {},
  "object_snapshot": {},
  "service_snapshot": [],
  "pricing_snapshot": {},
  "legal_text_snapshot": {},
  "agb_snapshot": {},
  "rendered_html": "...",
  "rendered_pdf_path": "...",
  "pdf_sha256": "..."
}
```

---

## 10. Empfohlene Dashboard-Masken

## 10.1 Kunde anlegen

- Kundentyp
- Firma / Vorname / Nachname
- Rechtsform
- vollständige Anschrift
- E-Mail und Telefon
- Rechnungs-E-Mail
- Umsatzsteuer-ID
- Ansprechpartner
- Unterzeichner und Vertretungsart
- interne Notizen

## 10.2 Objekt anlegen

- Objektname
- vollständige Objektanschrift
- Ansprechpartner vor Ort
- Zugang / Schlüssel / Alarm
- Parkmöglichkeit
- Reinigungsfläche
- Öffnungszeiten und Reinigungszeitfenster
- Müllentsorgung
- Hygiene- und Sicherheitsvorgaben
- Objektbesonderheiten

## 10.3 Angebot / Vertrag anlegen

- Kunde auswählen
- Objekt auswählen
- Vertragsbeginn
- Laufzeit und Kündigungsfrist
- Hauptintervall
- Wochentage und Zeitfenster
- Leistungen aus Katalog auswählen
- Intervall je Leistung anpassen
- Preispositionen
- Zahlungsziel
- Feiertagsregelung
- Verbrauchsmaterialregelung
- Parkkostenregelung
- individuelle Vereinbarungen
- AGB-Version auswählen
- Vorschau erzeugen
- intern freigeben
- versenden

## 10.4 Signaturansicht Kunde

Vor der Signatur müssen folgende Checkboxen bestätigt werden:

```text
[ ] Ich habe den vollständigen Vertrag gelesen und stimme ihm zu.
[ ] Ich habe die einbezogenen AGB erhalten und akzeptiere sie.
[ ] Ich bin berechtigt, den Auftraggeber rechtsverbindlich zu vertreten.
[ ] Ich habe die Datenschutzhinweise zur elektronischen Signatur gelesen.
```

Keine künstliche oder irreführende Zustimmungsstrecke verwenden. Jede Zustimmung muss klar bezeichnet, protokolliert und einer konkreten Dokumentversion zugeordnet werden.

---

## 11. Validierungsregeln

### Vor dem Erzeugen eines Vertrags

- Auftragnehmerdaten vollständig.
- Kunde vollständig und valide Anschrift vorhanden.
- Bei Firmenkunde ist mindestens ein Unterzeichner hinterlegt.
- Zeichnungsberechtigung beziehungsweise Vertretungsart ist ausgewählt.
- Reinigungsobjekt vollständig.
- Vertragsbeginn vorhanden.
- Mindestens eine aktive Reinigungsposition vorhanden.
- Jede aktive Position besitzt ein Intervall.
- Preis oder nachvollziehbares Preismodell vorhanden.
- Umsatzsteuersatz gesetzt.
- Zahlungsziel gesetzt.
- Feiertagsregelung eindeutig gesetzt.
- Verbrauchsmaterialregelung eindeutig gesetzt.
- AGB-Version mit Datei und Hash hinterlegt.
- Vertragstext-Version gespeichert.

### Vor dem Versenden zur Signatur

- PDF-Vorschau wurde erzeugt.
- Interne Freigabe erfolgt.
- PDF-Hash gespeichert.
- Empfänger-E-Mail validiert.
- AGB und Datenschutzinformationen sind erreichbar beziehungsweise beigefügt.

### Nach der Signatur

- Finale PDF sperren.
- Signaturprotokoll erzeugen.
- PDF-Hash erneut speichern.
- Finales Dokument an beide Parteien versenden.
- Vertragsstatus auf `SIGNED_COMPLETE` beziehungsweise `ACTIVE` setzen.
- Änderungen nur noch über Nachtrag oder neue Vertragsversion erlauben.

---

## 12. Technische Berechnungen

```text
monthly_vat   = monthly_net * vat_rate / 100
monthly_gross = monthly_net + monthly_vat
```

Geldwerte intern immer als Integer in Cent speichern:

```text
1300,00 EUR = 130000 Cent
```

Datumswerte intern im ISO-Format speichern:

```text
2026-07-08
```

Zeitstempel in UTC speichern und im Dashboard in der Benutzerzeitzone anzeigen.

---

## 13. Beispielbelegung aus dem Ausgangsvertrag

```json
{
  "contract": {
    "title": "Gebäudereinigungsvertrag",
    "effective_date": "2026-07-08",
    "language": "de",
    "governing_law": "DE",
    "signing_place_contractor": "Solingen",
    "signing_place_customer": "Solingen"
  },
  "contractor": {
    "legal_name": "Clean Team Group SRLS",
    "trade_description": "Meisterbetrieb Gebäudereinigung",
    "managing_directors": ["Riccardo Cuccaro", "Thomas Mündlein"],
    "street": "Via Dorsale 11",
    "postal_code": "54100",
    "city": "Massa",
    "service_point_street": "Ober der Mühle 30",
    "service_point_postal_code": "42699",
    "service_point_city": "Solingen",
    "website": "https://cleanteam-group.com"
  },
  "customer": {
    "type": "COMPANY",
    "company_name": "iSi Deutschland GmbH",
    "street": "Mittelitterstr.",
    "house_number": "12-16",
    "postal_code": "42719",
    "city": "Solingen",
    "country": "Deutschland",
    "signatory": {
      "first_name": "Melanie",
      "last_name": "Fetten",
      "authority_text": "i. A.",
      "position": "Abteilung Verkauf"
    }
  },
  "object": {
    "street": "Mittelitterstr.",
    "house_number": "12-16",
    "postal_code": "42719",
    "city": "Solingen",
    "country": "Deutschland",
    "cleaning_during_operation": true
  },
  "service_schedule": {
    "default_frequency": "TWICE_WEEKLY",
    "holiday_service": false,
    "holiday_makeup": false,
    "flat_rate_unchanged_on_holidays": true
  },
  "pricing": {
    "billing_model": "MONTHLY_FLAT_RATE",
    "monthly_net": 1300.00,
    "currency": "EUR",
    "payment_due_value": 5,
    "payment_due_unit": "WORKING_DAYS",
    "default_after_days": 30,
    "extra_services_separately_charged": true,
    "parking_fees_separately_charged": true,
    "consumables_included": false,
    "price_adjustment_enabled": true,
    "price_adjustment_notice_months": 1,
    "expected_wage_increase_min_percent": 5,
    "expected_wage_increase_max_percent": 10
  },
  "legal": {
    "jurisdiction_city": "Solingen",
    "agb_version": "Stand 28.07.2020",
    "agb_url": "https://cleanteam-group.com/agb/"
  }
}
```

---

## 14. Auffälligkeiten und vor der Programmierung zu klärende Punkte

1. **Dateiname und Vertragspartei passen nicht zusammen.** Der Dateiname nennt eine Pro-Familia-Beratungsstelle, im Vertrag ist jedoch die iSi Deutschland GmbH als Auftraggeber aufgeführt.
2. **Paragraphennummerierung ist fehlerhaft.** Im Ausgangsdokument gibt es zweimal § 5. Die Unterpunkte auf Seite 3 stehen in der Reihenfolge 1, 2, 3, 4, 5, 6, danach 4, 2, 8, 9, 1.
3. **Kündigungsfrist und Vertragslaufzeit fehlen.** Das Dashboard sollte diese Daten trotzdem vorsehen und optional ausgeben.
4. **Umsatzsteuersatz ist nicht konkret genannt.** Im Vertrag steht nur „zzgl. MwSt.“. Das System muss den zum Leistungszeitpunkt anzuwendenden Satz verwalten.
5. **Reklamationsportal-URL ist nur allgemein angegeben.** Die konkrete Ziel-URL zum Formular sollte gespeichert werden.
6. **AGB-Nachweis fehlt technisch.** Nur eine Website und ein Standdatum sind genannt. Für den Signaturprozess sollten die tatsächlich bereitgestellte AGB-Datei, Version, Zeitstempel und Hash gespeichert werden.
7. **Preisänderung ist nicht als mathematisch eindeutige Formel formuliert.** Für den Produktivbetrieb sollte festgelegt werden, ob die Klausel unverändert verwendet oder juristisch überarbeitet wird.
8. **Signaturdaten sind unvollständig.** Das Ausgangsdokument enthält Namen und Funktionen, aber kein technisches Audit-Protokoll.
9. **Auftragnehmeradresse enthält italienischen Sitz und deutschen Service Point.** Das Dashboard muss Hauptsitz, Service Point und gegebenenfalls ladungsfähige Anschrift getrennt speichern.
10. **Firmenbezeichnung uneinheitlich.** In der Kopfzeile steht „Clean Team Group SRLS Meisterbetrieb Gebäudereinigung“. Im System sollte ein verbindlicher rechtlicher Firmenname festgelegt werden.

---

## 15. Minimale erste Entwicklungsstufe

Für die erste funktionierende Version des Dashboards reichen folgende Module:

```text
1. Login / Benutzer
2. Kundenverwaltung
3. Objektverwaltung
4. Angebots- und Vertragseditor
5. Leistungskatalog mit individuellen Intervallen
6. Preisberechnung
7. HTML-Vorschau
8. PDF-Erzeugung
9. Versand per E-Mail
10. Kundenseitige Signatur
11. Signaturprotokoll
12. Dokumentarchiv und Versionierung
```

Diese Spezifikation sollte als zentrale Fachdatei im Projekt abgelegt werden, zum Beispiel:

```text
/docs/cleanteam-vertragsgenerator.md
```
