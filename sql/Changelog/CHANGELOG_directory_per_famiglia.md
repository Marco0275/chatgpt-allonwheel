# Allonwheel - Directory fornitori per famiglia (macro) - 2026-06-24

Completa l'unificazione tassonomia: la directory fornitori ora si filtra anche
per le 5 famiglie di brand (macro), in simmetria con browse.php.

## Modifiche
- `CompanyManager::getCompaniesByMacroKeys($regular, $special, $search)`: NUOVO
  metodo che ritorna le righe azienda COMPLETE (c.*) per le chiavi product_key
  della macro (regular+special), dedup per id, con ricerca testuale opzionale.
  (getCompaniesByProducts tornava solo id+ragione sociale: insufficiente per le card.)
- `06_30_company_directory.php`:
  - nuovo parametro `?macro=<slug>` (priorita' sui filtri vtype/special),
    risolto via ProductMacro::supplierKeysFor() -> getCompaniesByMacroKeys();
  - **chip-bar famiglie** in cima al contenuto (All suppliers + 5 macro),
    con stato attivo evidenziato;
  - campo hidden `macro` nel form di ricerca (la ricerca resta dentro la famiglia);
  - messaggio "no suppliers" aggiornato per includere il filtro macro.
- CSS: `.fam_bar` / `.fam_chip` (+ stato `.sel`). Lang: `dir.all_suppliers` x4.

Ora il buyer naviga i fornitori per famiglia esattamente come gli annunci.
Lint PHP 8.3 OK, CRLF. CSS bumpato a **?v=20260707**.
