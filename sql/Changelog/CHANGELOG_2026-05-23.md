# Allonwheel — Nota di rilascio (2026-05-23)

Pacchetto: `allonwheel_update_2026-05-23.zip` — **89 file** (88 modificati + 1 nuovo).
Struttura: estrai mantenendo la cartella `Allonwheel/`, sovrascrivendo i file omonimi sul server.

> **Doppia verifica eseguita** (dir. 2 e 10): lint sintattico su tutti i 143 file PHP → 0 errori; controllo JS di `site_init.js` → OK; verifica logica delle query e della whitelist; integrità ZIP → 89/89 file identici al filesystem.
> **Non-regressione:** cartelle `upload_image/` e `images/` **intatte** (dir. 15, 0 differenze); distinzione **free / premium** preservata (dir. 13); nessuna perdita dati (dir. 9).

---

## Aggiornamenti più recenti

### Utente con quota illimitata — `marco.candian@yahoo.it`
Aggiunta una whitelist per email in `libs/user_tier.class.php` (`UNLIMITED_EMAILS` + `isUnlimitedUser()`): l'utente può pubblicare **free e premium ads illimitati**, bypassando sia il limite numerico sia la restrizione "free non può postare premium", **senza** privilegi admin. Il confronto è case-insensitive. Tutti i punti di inserimento (free e premium) passano da `canInsertFreeAd`/`canInsertPremiumAd`, quindi il bypass è effettivo ovunque. `my_posts.php` mostra ora correttamente il box quota premium e l'etichetta "(unlimited)" per questo utente, nascondendo il prompt di upgrade.

### Pagine di modifica annuncio — struttura free/premium
Le pagine di modifica non offrivano la navigazione coerente con il tipo di annuncio. Corretto:
- **Free** (`02_modify_insert_ad.php`): aggiunto il blocco "Manage this ad" con link alla **gestione gallery immagini**.
- **Premium** (`03_modify_insert_ad.php`): aggiunto il blocco "Manage this ad" con link alla **gestione gallery immagini** **e** alla **modifica dei dettagli tecnici** (collegamenti che prima mancavano).
- `03_modify_tech_details.php`: il messaggio dead-end quando mancano i dettagli tecnici era in italiano e senza uscita; ora è in inglese (dir. 0) con link "Back to ad" / "My posts".

---

## Interventi della prima fase

### Sidebar condizionale — direttiva 17 (criticità risolta)
`include_sidebar.php` usava sempre `sidebar.php` ignorando lo stato di login. Ora è un dispatcher reale: **utente loggato → `sidebar.php`**, **visitatore → `sidebar_static.php`**. `sidebar_static.php` è stata mantenuta (annullato il punto D3 del report) e riallineata: stesse classi CSS, stesso foglio di stile, "Vehicle types" da DB con fallback.

### Funzionalità utente (Fase 1)
- **Search box annunci funzionante** (`shared/view_ads.php`): il parametro `q` filtra ora su `title`/`description` con query parametrizzata; valore preservato e banner "Search results / Show all".
- **Filtro `vtype` nella directory aziende** (`06_company/06_30_company_directory.php` + `libs/06_company.class.php`): nuovo metodo `getCompaniesByVehicleType()` con `JOIN` su `06_company_products` (`product_key = vehicle_types.slug`), combinabile con la ricerca testuale; i link "Vehicle types" della sidebar ora filtrano realmente.
- **`my_posts.php`**: corretto il bug dei link-filtro che generava HTML non valido; stili inline convertiti alla classe esistente `float_r` (dir. 8).

### Sicurezza / GDPR (Fase 2.4)
- **Tracker Histats rimosso** da 40 pagine; CSP di `security_headers.php` ripulita dagli host esterni; `cookie-policy.php` aggiornata (nessun tracker di terze parti).

### Layout e debito tecnico (Fasi 3, 4)
- **`<meta name="viewport">`** aggiunto a 75 pagine che ne erano prive (solo meta, nessun CSS — dir. 8).
- **Script inline rimossi** e delegati a `js/site_init.js`: 20 definizioni di `clearText`, 26 init di `ddsmoothmenu`; aggiunto il riferimento a `site_init.js` dove serve (52 pagine totali). Corretto inoltre un bug di timing in `site_init.js` (la guardia del menu girava prima che il DOM esistesse).
- **`SmartImage` consolidata** in `libs/smart_image.class.php` (con guard `class_exists`); `libs/02_free_ads.class.php` e `libs/03_ads.class.php` sono ora wrapper retro-compatibili → eliminata la doppia dichiarazione.

---

## Note di deploy
- Nessuna modifica di schema DB richiesta da questo pacchetto.
- I commenti e i nomi delle variabili restano in italiano; i testi UI sono in inglese (dir. 0, §8 CONTEXT).
- Il filtro `vtype` assume che `vehicle_types.slug` coincida con `06_company_products.product_key` (come da schema).

## Punti del piano non ancora affrontati
Restano aperti, da pianificare separatamente: traduzione/correzione EN sistematica di tutti i testi (4.1), uniformazione chiavi di sessione legacy (4.4), gestione flussi legacy `ads.php`/`ad_post.php` (4.5), cleanup SEO/footer (Fase 5), e l'ownership-check capillare (2.1) ove non già presente.
