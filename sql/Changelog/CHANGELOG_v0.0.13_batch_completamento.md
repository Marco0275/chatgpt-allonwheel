# Allonwheel v0.0.13 — Batch completamento (da dump DB `allonwhe80316.sql`)

Lavorazione di tutti i punti rimasti **azionabili via codice**, partendo dal dump
del database reale. 16 file modificati. `php -l` OK su tutti i 187 file del progetto.

---

## Già verificati come FATTI nel dump (nessuna azione)
- **P0.2 Copy macro**: `product_macros.intro_text` è già popolato con copy EN reale
  per tutte e 5 le famiglie (Race Trailer, Hospitality, Mobile Clinic,
  Shelter & Container, Custom Projects) + `hero_image`. Le landing `browse.php?macro=`
  pescano già questi testi.
- **P1.4 Liste storiche**: `02_free_ads/02_view_ads.php` e `03_ads/03_view_ads.php`
  sono già redirect 301 → `/browse.php` + `noindex`.
- **P1.5 Filtro pubblico**: `browse.php` filtra già `status = 'approved'`
  (enum `pending/approved/rejected`, default `approved`). Pending/rejected non pubblici.

---

## Task 1 — Directory: i18n dei tipi veicolo (SBLOCCATO dal dump)
Il ramo "regular" della directory mostrava i nomi dei tipi presi da
`vehicle_types.name` (solo EN). Con i 32 record del dump ho creato un dizionario
per-slug e wirato i punti di display.

- **+32 chiavi `vt.<slug>`** (EN+IT) per tutti i `vehicle_types`. Corretto a display
  il refuso del DB: slug `raicing_trailer` → label **"Racing trailer"** (lo slug,
  chiave DB, resta invariato).
- **+5 chiavi `supp.*`** (Vehicle types, Testimonial, testo testimonial, cite, Contact us).
- **`sidebar_suppliers.php`**: il loop dei tipi ora stampa
  `t('vt.'.$vt['slug'], $vt['name'])` (copre lista da DB **e** fallback hardcoded,
  stessi slug). Wirate anche le label statiche (h3 Suppliers, 4 link, h3 Vehicle types,
  blocco Testimonial, Contact us) riusando `nav.*` / `b2b.*` / `macro.shelter`.
- **`06_company/06_30_company_directory.php`**: titolo pagina
  `$vtype_name = t('vt.'.$vtype, $cm->getVehicleTypeName($vtype) ?? $vtype);`.

Fallback sempre garantito: chiave assente → nome dal DB.

## Task 2 — `00_first/` → redirect 301 (P1.3)
Le 11 pagine legacy del template originale sono **orfane**: zero link `.php` le
referenziano in tutto il codice (verificato). Restano solo riferimenti a immagini
`images/00_first/*.jpg` (on-brand, motorsport, lasciati) e qualche commento. Quindi
nessuna bonifica link necessaria: ho sostituito le 11 pagine con stub di redirect
301 permanente + `X-Robots-Tag: noindex`, verso la destinazione reale più pertinente:

| Pagina legacy | Redirect 301 → |
|---|---|
| racing_trailer.php / paddock_trailer.php | `../browse.php?macro=race-trailer` |
| hospitality.php | `../browse.php?macro=hospitality` |
| roadshow.php | `../browse.php?macro=custom-projects` |
| box_trailer.php | `../browse.php?macro=shelter-container` |
| motorhome.php / mobilhome.php / motorhome_mobilhome.php | `../special_vehicles.php` |
| service.php / why-rent.php | `../what_we_do.php` |
| sell_or_rent.php | `../04_request_offer/04_request_offer.php` |

Nessun file in `images/` toccato (dir. 15).

## Task 3 — Home `index.php`: i18n completo (P0.1, completamento)
La home era già riscritta al brand reale ma **hardcoded in inglese** (0 `te()`), con
35 chiavi `home.*` **stale** di una rewrite precedente.

- Rimosse le 35 chiavi `home.*` orfane (la home non le usava più).
- **+44 chiavi `home.*`** fresche (EN+IT): title, hero login-aware (dashboard / intro
  guest), box Marketplace, box Supplier directory, sezione "Motorsport paddock
  solutions", le 5 famiglie (h2 + em + paragrafo lungo ciascuna), sezione B2B,
  box col_4 (Road/Special/Shelter), gallery "Latest from the marketplace".
- **`index.php` wirato → 60 `te()`**. Riusate `nav.request_quote`, `nav.portfolio`,
  `b2b.road`, `b2b.special`, `macro.shelter`. Zero residui EN; tutte le chiavi
  presenti in EN+IT; `te()` disponibile già nel `<title>` (bootstrap caricato a
  inizio file). Struttura `<div>` **invariata** (37/36, pre-esistente).

---

## ⚠️ Da approvare prima di toccare (dir. 19 — modifiche strutturali)
Due bug **pre-esistenti** nella home, lasciati intatti (i18n applicato così com'è):
1. **Box "Special vehicles" duplicato** nella riga col_4 (compare 2 volte identico).
2. **Squilibrio `<div>` 37/36** (un `</div>` di troppo dopo il box "Road vehicles",
   probabilmente residuo di un edit del template; la pagina rende comunque).
Entrambi richiedono una micro-ristrutturazione: dimmi se procedo a rimuovere il
duplicato e riquadrare la riga col_4 (Road / Special / Shelter / +1).

## Ancora aperti — NON azionabili via codice ora (richiedono te/infra/decisione)
- **SMTP / HISTATS_ID / URL social reali**: credenziali e ID di terze parti (`.env`).
- **Configuratore Step 2 (generazione PDF)**: richiede una libreria PDF vendorizzata;
  è una feature a sé, da pianificare.
- **Directory fornitori per famiglia (5 macro)**: i fornitori sono indicizzati per
  `vehicle_types`, non per macro; serve una regola di mappatura macro↔tipo prima.
- **Authority layer**: richiede contenuti/decisione di prodotto.
- **i18n architettura URL**: `.htaccess` già riscrive `/en/` e `/it/` (prefisso di
  percorso di fatto scelto). L'i18n dei contenuti DB a colonna singola (`intro_text`)
  resta fuori scope.

---

## Nota di applicazione
`lang/en.php` e `lang/it.php` sono **cumulativi**: contengono tutte le chiavi dei
delta precedenti **più** `vt.*`, `supp.*`, `home.*` (e senza le `home.*` stale).
Applicare queste versioni (superano quelle dei delta precedenti per i lang file).
