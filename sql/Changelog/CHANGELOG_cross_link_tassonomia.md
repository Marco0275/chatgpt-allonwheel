# Allonwheel - Unificazione tassonomia: cross-link annuncio <-> fornitore

Data: 2026-06-24

Le tre tassonomie del sito (ad.vehicle_type, ad.product_macro, company.product_key)
sono ora collegate usando la **MACRO come hub**, senza inventare dati (dir.14):
- ProductMacro::supplierKeysFor(macro)  -> chiavi product_key fornitore
- CompanyManager::getCompaniesByProducts -> aziende (regular + special), dedup
- ProductMacro::macrosForSupplierKeys()  -> NUOVA reverse map (product_key -> macro)

## Cosa vede l'utente
1. **Scheda annuncio** (shared/view_ad.php): sotto la descrizione compare il box
   "Verified suppliers - <macro>" con i produttori di quella categoria (link alle
   schede azienda) + link alla directory completa. Si basa sul product_macro reale
   gia' salvato sull'annuncio.
2. **Browse** (browse.php): filtrando per una Family (macro) compare lo stesso box
   "Verified suppliers" sopra gli annunci -> ricerca di fatto unificata annunci+aziende.
3. **Scheda azienda** (06_02_view_company.php): nuovo blocco "Related marketplace
   listings" con chip per ogni macro coperta dai prodotti (regular+special)
   dell'azienda, ciascuno -> browse.php?macro=<slug>.

## Single-source
- Nuovo partial **shared/related_suppliers.php** (`aow_related_suppliers()` +
  `aow_render_related_suppliers()`), riusato sia da view_ad.php sia da browse.php:
  un solo punto per logica e markup del box fornitori.

## Robustezza
- Tutto il ponte e' avvolto in try/catch: non puo' mai rompere la scheda/listing.
- Mapping verificato: ambulanze/disabili->mobile-clinic, hospitality_units->hospitality,
  racing_trailer/box_trailer->race-trailer, special_shelter->shelter-container,
  motorhomes_mobilhomes->custom-projects. I veicoli generici (camper, frigoriferi...)
  correttamente non producono un link macro (non sono macro di brand).

## File
- `libs/product_macro.class.php` (+ macrosForSupplierKeys), `shared/related_suppliers.php`
  (nuovo), `shared/view_ad.php`, `browse.php`, `06_company/06_02_view_company.php`,
  `allonwheel_style.css` (`.rel_suppliers`, `.rel_list`, `.rel_macros`, `.rel_sub`),
  `lang/{en,it,fr,de}.php` (chiavi `bridge.*`).
- CSS bumpato a **?v=20260703** su tutte le pagine.

## Nota
Include anche le faccette gia' consegnate (stessa copia di lavoro): il pacchetto e'
cumulativo e auto-consistente.
