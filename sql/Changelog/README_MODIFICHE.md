# Allonwheel — Riepilogo modifiche e istruzioni

Versione: 28 maggio 2026

Questo pacchetto contiene il progetto con: la nuova tassonomia veicoli **Road / Special**,
la pagina **Shelter / Container**, e il **wizard di classificazione** in fase di inserimento
annunci (free e premium). Tutte le modifiche rispettano le direttive vincolanti del piano
(struttura cartelle/tabelle, solo foglio di stile esistente, nessuna perdita dati, UI inglese,
sidebar condizionale, sicurezza).

---

## 1. PRIMA DI TUTTO — eseguire la migrazione del database

Le nuove funzioni si appoggiano a colonne che **non esistono finché non lanci la migrazione**.
Le pagine sono comunque "graceful": se le colonne mancano mostrano uno stato vuoto senza errori.

Due modi equivalenti (scegline uno):

**A) Da riga di comando / phpMyAdmin (consigliato):**
Importa il file `sql/migration_road_special.sql`.

**B) Da PHP (idempotente):**
Esegui `sql/migrate_road_special.php`. È sicuro rilanciarlo: controlla
`information_schema` prima di ogni `ALTER` e non duplica nulla. Via HTTP richiede una
sessione **admin** attiva.

### Cosa fa la migrazione
- Aggiunge `vehicle_types.macro_category ENUM('road','special')` e classifica le 24 voci **Road**.
- Applica le rinomine etichette: *Mobile shops/Food* → **Street food**, *Mobile shops/Haberdashery* → **Haberdashery** (lo **slug interno resta stabile**).
- Riconcilia l'anomalia del seed `bundati` → `blindati` (**Armored**, road) propagando l'`UPDATE` a `06_company_products` (nessuna perdita dati). **⚠️ Da confermare in Fase 0.**
- Aggiunge a `02_free_ads` e `03_ads` le colonne: `item_kind ENUM('vehicle','shelter_container')`, `macro_category ENUM('road','special')`, `vehicle_type VARCHAR(50)`, più gli indici relativi.

---

## 2. File NUOVI

| File | Scopo |
|---|---|
| `libs/vehicle_taxonomy.class.php` | Punto **unico** di definizione Road/Special (24 slug Road chiusi, Special = complemento). Metodi `typesByMacro()`, `isValidType()`, `macroForSlug()`, `label()`. Data-driven dal DB con fallback statico. |
| `sql/migration_road_special.sql` | Migrazione DB (SQL puro). |
| `sql/migrate_road_special.php` | Runner PHP idempotente della migrazione. |
| `02_free_ads/02_00_select_type.php` | Wizard a 3 step per annunci **free**. |
| `03_ads/03_00_select_type.php` | Wizard a 3 step per annunci **premium**. |
| `shelter_container.php` | Nodo flowchart **Special**: listing unificato free+premium degli annunci Shelter/Container. |

## 3. File MODIFICATI

| File | Modifica |
|---|---|
| `02_free_ads/02_insert_ad.php` | Guard sul wizard (`$_SESSION['ad_wizard']`), riepilogo classificazione read-only, campi hidden; rimosso il vecchio blocco checkbox categorie. |
| `03_ads/03_insert_ad.php` | Idem per il flusso premium. |
| `02_free_ads/02_01_upload_advertising.php` | Valida la classificazione, mappa retro-compat sui flag booleani storici, `INSERT` esteso con le 3 nuove colonne, pulizia sessione wizard. |
| `03_ads/03_01_upload_advertising.php` | Idem per premium. |
| `header.php` | "Post free/premium ad" puntano ai wizard; aggiunta voce **Shelter / Container** sotto Suppliers. |
| `06_company/06_30_company_directory.php` | Filtro "Browse by vehicle type" raggruppato Road/Special + link Shelter/Container. |

---

## 4. Flusso del wizard (inserimento annuncio)

1. **Step 1 — Tipo:** Veicolo *oppure* Shelter / Container. (Shelter ⇒ macro = Special, tipo = shelter_container, salta allo step finale.)
2. **Step 2 — Macro-categoria:** Road *oppure* Special.
3. **Step 3 — Tipologia specifica:** elenco filtrato per macro (dal DB).
4. Prosegue nel normale form di inserimento (free o premium), con i `tech_details` mantenuti **solo** per il premium.

CSRF persistente attivo su tutti gli step; controllo del tier utente preventivo come negli originali.

---

## 5. Note e assunzioni da confermare

- **`bundati` → `blindati`:** il seed `vehicle_types` aveva `bundati`/`Bundati`, mentre la classe usa `blindati`/`Armored`. La migrazione riconcilia su `blindati`. Confermare che sia corretto (in `06_company_products` non risultava usato `bundati`, quindi l'operazione è sicura).
- **Flag booleani storici** (`racing`, `street_food`, ecc.): restano popolati in best-effort per retro-compatibilità di `browse.php`; la classificazione strutturata (`item_kind`/`macro_category`/`vehicle_type`) è la **nuova fonte di verità**.
- **`02_modify_insert_ad.php` / `03_modify_insert_ad.php`:** invariati. Aggiornano solo colonne specifiche, quindi le nuove colonne sono preservate (nessuna perdita dati). Un'eventuale visualizzazione read-only della classificazione in fase di modifica è opzionale e non è stata aggiunta.
- **Cartelle `upload`/`images`:** intoccate (dir. 15).
- **Stili:** nessun foglio di stile nuovo né stile inline; riusate le classi già presenti in `allonwheel_style.css` (`.step-bar`, `.step`, `.cat-grid`, `.post_box`, ecc.).

---

## 6. Verifiche eseguite

- **Lint completo:** 148 file PHP, **0 errori di sintassi**.
- **Test tassonomia:** 15/15 PASS (Road = 24 voci, Special = complemento, rinomine, `blindati`=Armored, validazione).
- **Doppia verifica** (dir. 2/10): guard presenti su entrambi i flussi, metodi/costanti esistenti, target di redirect/link esistenti, nessuno stile inline introdotto, line endings preservati.
