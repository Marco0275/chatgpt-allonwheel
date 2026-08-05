# Allonwheel — Roadmap: copy IT macro (P0.2) + filtri famiglia directory (P2.8)

Data: 2026-06-20

## P0.2 — Copy italiana delle 5 macro
- `sql/Changelog/2026-06-20_macro_intro_it.sql`: popola `product_macros.intro_text_it`
  con la traduzione fedele degli intro EN (Race Trailer, Hospitality, Mobile Clinic,
  Shelter & Container, Custom Projects).
- **Non distruttivo**: aggiorna solo se l'intro IT e' vuoto/placeholder (< 20 char),
  quindi non sovrascrive eventuali testi gia' rifiniti dall'admin. Idempotente.
- File UTF-8, accenti corretti, apostrofi SQL raddoppiati. `browse.php?macro=` mostra
  gia' l'intro tradotto in locale IT (via `aow_i18n_field`).

## P2.8 — Directory fornitori "per famiglia" (parte fattibile)
La directory ora mostra un **chip-bar visibile** delle famiglie fornitori, riusando
lo stile `.chip` e il filtro `?special=` gia' esistente:
- `06_company/06_30_company_directory.php`: barra "All suppliers" + le 6 categorie
  speciali (`CompanyManager::$products_special`: Racing trailer, Box trailer,
  Motorhomes & Mobilhomes, Hospitality units, Paddock trailers, Special Shelter).
  Le label usano `tcat()`; gli href preservano la ricerca `q`.
- Chiave i18n `supp.all` aggiunta in en/it/fr/de.

### Nota importante su P2.8 e le "5 macro"
I fornitori NON sono mappati sulle 5 macro-brand (`product_macros`), che valgono
solo per gli annunci. Le aziende dichiarano prodotti in due tassonomie:
`$products` (vehicle types / road) e `$products_special` (categorie speciali). Il
chip-bar usa queste ultime, che sono le vere "famiglie" lato directory. Un filtro
diretto sulle 5 macro-brand richiederebbe una **mappatura nuova vtype->macro**
(decisione tua): non l'ho inventata.

## Verifiche
- Full-project `php -l`: 0 errori. CRLF preservati. Solo classi CSS esistenti.

## Roadmap residua (richiede tue decisioni / scoping)
- **P2.7** Configuratore Step 2 (scheda tecnica RFQ + PDF via mpdf): feature ampia,
  da definire i campi tecnici e il layout PDF.
- **P2.8 completo** (5 macro per fornitori): serve la mappatura vtype->macro.
- **P2.9** Authority layer (certificazioni, case study, trust signals): da progettare.
