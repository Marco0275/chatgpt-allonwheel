# Allonwheel — Piano di Lavoro Unificato
*Rev. 9 giu 2026 — **nuova dir. 19** (usabilità prevale sul flowchart, con gate di approvazione della mappa IA) + revisione dir. 17/18. Basi: 2 giu 2026 (dir. 17 sidebar per-sezione + header utente) · 28 mag 2026 (flowchart + tassonomia Road/Special) · 22 mag 2026 (direttive + report su 142 file PHP).*

> **Gerarchia fonti:** in conflitto, le **direttive utente (Parte 1) prevalgono** sul report tecnico (Parte 3). I punti del report incompatibili sono annullati/riscritti e segnalati.

---

## Parte 0 — Struttura del sito (flowchart)

![Flowchart struttura Allonwheel](flowchart.jpg)

Il flowchart fissa la sitemap ufficiale; ogni pagina si colloca in quest'albero.

```
Index
├── Login
├── Marketplace
│   ├── Free Ads
│   ├── Premium Ads
│   └── Request quotation
├── Suppliers
│   ├── Company
│   │   └── Shelter / Container ──┐
│   └── Project manager           │
│       └── Vehicle types ─────► Road
│                          └────► Special ◄── (anche Shelter / Container)
└── Portfolio
```

**Lettura:**
- `Suppliers` → due fornitori: `Company` e `Project manager`.
- `Company` → `Shelter / Container`; `Project manager` → `Vehicle types`.
- `Vehicle types` → macro **Road**/**Special**; il ramo `Shelter / Container` confluisce in **Special**.
- `Road`/`Special` = i due valori macro del filtro `vtype` (report A1/C5).

**Modello di navigazione (dir. 17):** la sitemap del flowchart è la navigazione globale. **Ogni sezione** ha la **propria sidebar** con le **opzioni di sezione** (non l'elenco pagine). Le **pagine personali** dell'utente loggato stanno **solo nell'header** dell'area login e **non compaiono in nessuna sidebar**.

### Mappatura nodi ⇄ cartelle/tabelle *(dir. 1)*

| Nodo flowchart | Cartella / file | Tabella DB | Note |
|---|---|---|---|
| Index | `/index.php` | — | Home pubblica |
| Login | `01_login/` | `01_login` (+ `_gallery` se prevista) | Area utente; pagine personali nell'**header** (dir. 3, 17) |
| Marketplace | contenitore logico | — | Raggruppa gli annunci |
| └ Free Ads | `02_free_ads/` | `02_free_ads` (+ `_gallery`) | Inserimento *free* (dir. 13) |
| └ Premium Ads | `03_ads/` | `03_ads` (+ `_gallery`) | Inserimento *premium* con `tech_details` (dir. 13) |
| └ Request quotation | da verificare su `template.php` | da definire (+ `_gallery`) | **Nuovo**: confermare cartella/tabella (dir. 1, 6) |
| Suppliers | contenitore logico | — | Raggruppa Company + Project manager |
| └ Company | `06_company/` | `06_company` (eccezione col. `user_id`, §5) | Max **1 azienda/utente** (dir. 3) |
| &nbsp;&nbsp;└ Shelter / Container | da verificare | da definire (+ `_gallery`) | **Da mappare**; classificato **Special** |
| └ Project manager | da verificare su `template.php` | da definire (+ `_gallery`) | **Nuovo da confermare** |
| &nbsp;&nbsp;└ Vehicle types | `06_company/06_30_company_directory.php`, `browse.php` | `06_company_products` (JOIN su `vtype`) | Sorgente filtro `vtype` → Road/Special |
| &nbsp;&nbsp;&nbsp;&nbsp;├ Road | `?vtype=road` | classificazione su `06_company_products` | Tassonomia sotto |
| &nbsp;&nbsp;&nbsp;&nbsp;└ Special | `?vtype=special` | classificazione su `06_company_products` | Complemento di Road |
| Portfolio | da verificare su `template.php` | da definire (+ `_gallery`) | **Nuovo da confermare** |

> ⚠️ **Da confermare in Fase 0:** i nodi *Request quotation*, *Project manager*, *Shelter / Container*, *Portfolio* non hanno riscontro certo nei 142 file. Se la cartella/tabella omonima manca, proporli da `template.php` (dir. 6) con `*_gallery` (dir. 1), senza nuovi stili (dir. 8).

---

## Parte 0-bis — Tassonomia veicoli: **Road** / **Special**

Il campo `vtype` (filtro su company directory e browse, JOIN su `06_company_products`) si riorganizza nelle due macro del flowchart.

### **Road** — elenco chiuso (24 voci, etichette EN, dir. 0)

`1 Ambulances` · `2 Street food` *(ex Mobile shops / Food)* · `3 Haberdashery` *(ex Mobile shops / Haberdashery)* · `4 Armored` · `5 Tow trucks` · `6 Tippers` · `7 Curtain-side bodies` · `8 Insulated bodies` · `9 Disabled access vehicles` · `10 Law enforcement` · `11 Refrigerated bodies` · `12 Box vans` · `13 Isothermal bodies` · `14 Minibuses` · `15 Mobile workshops` · `16 Aerial platforms / Cranes` · `17 Public administration` · `18 School buses` · `19 Waste collection vehicles` · `20 Lifting systems` · `21 Leisure` · `22 Garment transport` · `23 Animal transport` · `24 Fire dept. / Civil protection`

### **Special** — per esclusione

> **Regola:** `Special = (tutti i vtype nel DB) − (le 24 voci Road)`, più il ramo **Shelter / Container** (flowchart).

Non è un elenco statico: si calcola a runtime/migrazione confrontando `SELECT DISTINCT vtype FROM 06_company_products` con il set chiuso Road. Niente voci inventate (dir. 14). **Esito Fase 0:** dump `vtype` distinti, marcatura `road`/`special`, elenco orfani/duplicati da bonificare.

### Implementazione (rinomine + classificazione)
- **Rinomine** (`Mobile shops / Food`→`Street food`; `Mobile shops / Haberdashery`→`Haberdashery`): cambi di **etichetta visibile** (sito EN). Aggiornare la *label* in lookup `vtype` e in tutte le UI; **mantenere stabile slug/id interno** o, se si rinomina la chiave, `UPDATE` di propagazione senza perdita dati (dir. 9).
- Macro `road`/`special` **persistente**: colonna `macro_category` su `06_company_products` **oppure** lookup `vtype → macro`, così `?vtype=road|special` resta una semplice `WHERE`.
- Nessun nuovo CSS né stile inline: `template.php` + CSS esistente (dir. 4, 6, 8).

---

## Parte 1 — Direttive vincolanti

Ogni file prodotto deve rispettarle tutte.

| # | Direttiva | Vincolo |
|---|---|---|
| 0 | **Sito in inglese.** Tradurre/correggere i testi visibili in EN. Commenti e nomi variabili restano in italiano (§8 CONTEXT). | Obbligatorio |
| 1 | **Mantenere la struttura cartelle.** Ogni cartella = sotto-sezione; nel DB la tabella ha **lo stesso nome** della cartella + tabella *gallery* (es. `02_free_ads` → `02_free_ads_gallery`). | Obbligatorio |
| 2 | **Doppia verifica.** Controllo file+DB → correzione (1ª verifica) → ricontrollo (2ª verifica) → poi download. | Processo |
| 3 | **`my_posts.php` aggregato.** Riassumere *tutti* i post dell'utente loggato cercando in **tutte le tabelle**; visualizza/modifica/cancella; *quick action* = solo opzioni disponibili. Max **1 azienda/utente**. | Obbligatorio |
| 4 | **Formattazione uniforme** e **stesso foglio di stile** delle pagine esistenti, incluse le nuove. | Obbligatorio |
| 5 | **Stack:** PHP + MySQL. | Tecnico |
| 6 | **`template.php`** = base per nuove pagine. | Obbligatorio |
| 7 | **Analizzare, proporre correzioni, uniformare** file e DB. | Processo |
| 8 | **Solo il foglio di stile esistente** — nessuno stile aggiuntivo. | Obbligatorio |
| 9 | **Conservare le informazioni** nei nuovi file, adeguando il DB alle variabili (nessuna perdita dati). | Obbligatorio |
| 10 | **Ricontrollare sempre** i file dopo la correzione, prima del download. | Processo |
| 11 | **Sicurezza** sito+DB sempre verificata. | Obbligatorio |
| 12 | **Isolamento utente:** ognuno opera **solo sui propri post**. | Sicurezza |
| 13 | **Differenza free/premium** mantenuta in inserimento. | Obbligatorio |
| 14 | **Vista unificata:** solo dati realmente nel DB; nel **titolo** nessuna distinzione free/premium. | Obbligatorio |
| 15 | **Non eliminare** `upload`/`images` né i file interni. | Obbligatorio |
| 16 | **Comunicazione con l'utente in italiano.** | Processo |
| 17 | **Sidebar per-sezione + box utente login-aware** *(rev.4, 9 giu 2026 — inverte la collocazione delle pagine personali rispetto alla rev. precedente)*: ogni sezione (`02`, `03`, `06`, …) ha la **propria sidebar** con le opzioni di sezione. L'**header è SOLO navigazione pubblica** (Home, Marketplace, Suppliers, Portfolio, About), **identico per ospite e loggato**: nessun link personale né login. I **link personali** dell'utente (e il login) vivono in **ogni sidebar di sezione** tramite il partial condiviso **`sidebar_user_box.php`**: se loggato mostra il box *My account* (My posts, profile, settings, upgrade, post free/premium, register company, write article, admin, logout); altrimenti **solo il link di Login**. *Subordinata a dir. 19.* | Obbligatorio |
| 18 | **Struttura sito = flowchart (Parte 0).** I `vtype` si classificano in **Road** (chiuso) / **Special** (complemento). *Il flowchart non è più immutabile: è il punto di partenza, derogabile per usabilità (dir. 19). La tassonomia Road/Special resta vincolante.* | Obbligatorio |
| 19 | **Usabilità prevale sul flowchart.** Navigazione e organizzazione delle pagine si strutturano **per intento/compito dell'utente**, anche derogando al flowchart. La riorganizzazione di **navigazione/IA** è libera; lo **spostamento fisico di file/cartelle** è consentito **solo** con: (a) mappa IA "prima/dopo" **approvata da Marco**, (b) aggiornamento di tutti gli `include`/link, (c) **redirect 301**, (d) se tocca cartelle mappate a tabelle DB (dir. 1), un **piano di migrazione senza perdita dati** (dir. 9). Mai rinomine/spostamenti silenziosi. | Obbligatorio |
| 20 | **Contatore Histats permanente.** Il codice del contatore Histats **non va rimosso**. Implementazione ottimale: partial unico `includes/histats.php` incluso **una sola volta** (via `footer.php`), caricamento **asincrono**, **consent-gated** sul consenso `analytics` (cookie `aow_consent` + Consent Mode v2), ID parametrico (`HISTATS_ID`), host Histats in CSP. *Inverte la Fase 2.4 che ne prevedeva la rimozione.* | Obbligatorio |

---

## Parte 2 — Riconciliazione col report tecnico

Le criticità del report valgono **solo se compatibili** con la Parte 1.

| Punto report | Decisione | Motivo |
|---|---|---|
| **D3 — rimuovere `sidebar_static.php`** | ↪️ **Superato dalla nuova dir. 17.** Il modello globale loggato/statico è abbandonato: `include_sidebar.php`, `sidebar_static.php`, `sidebar_logged.php` vanno **ridefiniti come sidebar di sezione** (riuso o sostituzione), preservando i link (dir. 9). Il pregresso lavoro condizionale (73 file) è da riallineare al nuovo modello. | Nuova dir. 17. |
| **A1 / C5 — filtro `vtype`** | ↪️ **Riformulato** su Road/Special (Parte 0-bis). | Dir. 18. |
| **A2 / C4 — search box, URL immagini** | ↪️ **Riassorbiti** nella vista unificata. | Dir. 14. |
| **D2 — link `06_12` in `my_posts.php`** | ↪️ Rivedere col nuovo `my_posts.php` aggregato (modifica azienda inline). | Dir. 3. |
| **Histats / viewport / ddsmoothmenu / clearText (B/C)** | ✅ Validi, ma niente nuovi stili (dir. 8), template comune (dir. 6). | Compatibili. |
| **D5 — `config/env` credenziali in chiaro** | ✅ Prioritario sotto dir. 11. | Compatibile. |

---

## Parte 3 — Piano operativo (per priorità utente)

Prima dati/sicurezza/funzioni richieste, poi il debito tecnico.

### Fase 0 — Verifica iniziale, mappatura DB ⇄ cartelle, tassonomia *(dir. 1, 2, 7, 18)*
- Confermare per ogni cartella attiva la tabella omonima + `*_gallery`.
- Verificare colonne critiche (§5): `id_ads`, `id_user`, `title`; eccezione `06_company.user_id`.
- Mappare **tutte** le tabelle con post utente per `my_posts.php`: `02_free_ads`, `03_ads`, `06_company`.
- **Verificare i nodi flowchart** non confermati (*Request quotation*, *Project manager*, *Shelter / Container*, *Portfolio*): se cartella/tabella mancante, proporli da `template.php`.
- **Tassonomia `vtype`:** `SELECT DISTINCT vtype FROM 06_company_products`; marcare `road` (24) / `special` (complemento); applicare rinomine; segnalare orfani/duplicati.
- **Esito:** elenco tabelle/colonne + mappa nodi + tabella `vtype → road/special` → base per `my_posts.php`, filtro Road/Special, vista unificata.

### Fase 1 — Funzionalità richieste *(dir. 3, 13, 14, 18)*
| # | Intervento | File | Vincoli |
|---|---|---|---|
| 1.1 | **`my_posts.php` aggregato:** query su tutte le tabelle dell'utente (free, premium, azienda), elenco unico, Visualizza/Modifica/Elimina, quick action filtrate. Max 1 azienda/utente. | `01_login/my_posts.php` | dir. 3, 12 |
| 1.2 | **Vista unificata:** un solo template dettaglio/listing che mostra **solo** i campi presenti nel DB; titolo senza etichetta free/premium. | `shared/view_ads.php`, `shared/view_ad.php` (+ wrapper 02/03) | dir. 14 |
| 1.3 | **Inserimento differenziato:** due flussi distinti (free vs premium), `tech_details` solo premium. | `02_free_ads/`, `03_ads/` | dir. 13 |
| 1.4 | Search box (`$_GET['q']` → `WHERE title LIKE ? OR description LIKE ?`). | `shared/view_ads.php` | A2 |
| 1.5 | **Filtro `?vtype=road|special`** su company directory e browse (JOIN su `06_company_products`, col./lookup `macro_category`); applicare rinomine. | `06_company/06_30_company_directory.php`, `browse.php` | A1, C5, dir. 18 |

### Fase 2 — Sicurezza e isolamento utente *(dir. 11, 12)*
| # | Intervento | File |
|---|---|---|
| 2.1 | **Ownership check** su ogni UPDATE/DELETE: confronto `id_user`/`user_id` con la sessione. | handler `*_modify_*`, `*_delete_*` |
| 2.2 | CSRF su tutti i form (`csrf_generate()`/`csrf_verify()` senza parametri, §9). | form 02/03/06 |
| 2.3 | Escludere `config/env` da backup/ZIP + rotazione credenziali esposte. | processo backup |
| 2.4 | ~~Rimozione Histats~~ → **superato da dir. 20**: Histats **mantenuto** e reimplementato (partial unico `includes/histats.php`, consent-gated). Resta valida la pulizia/coerenza CSP e `cookie-policy.php` (GDPR). | `includes/histats.php`, `footer.php`, `cookie_consent.js`, `security_headers.php` |

### Fase 3 — Template, sidebar per-sezione, header utente *(dir. 4, 6, 8, 17)*
| # | Intervento | File |
|---|---|---|
| 3.1 | **Sidebar per-sezione:** ogni sezione (`02`,`03`,`06`,…) include la propria sidebar con le **opzioni della sezione**; `include_sidebar.php` risolve la sidebar dalla **sezione corrente** (non dallo stato di login). Nessuna pagina utente in sidebar. | `include_sidebar.php` + sidebar di sezione |
| 3.2 | **Header utente:** in area loggata mostra **solo** le pagine personali (`my_posts.php`, gestione azienda, account), assenti da ogni sidebar. | header / `01_login/` |
| 3.3 | Nuove pagine da `template.php`, stesso CSS, nessuno stile aggiuntivo. | `template.php` |
| 3.4 | `<meta name="viewport">` dove manca (74 file) — solo meta. | script |
| 3.5 | URL immagini root-relative (`/upload_image/...`), coerenti con `browse.php`. | `shared/view_ads.php`, `gallery.php`, `view_ad.php` |

### Fase 4 — Localizzazione e debito tecnico *(dir. 0, 7)*
| # | Intervento | File |
|---|---|---|
| 4.1 | Traduzione/correzione EN dei testi UI (commenti/variabili IT); include etichette `vtype` (Road/Special, Street food, Haberdashery). | pagine pubbliche |
| 4.2 | Consolidare `class SmartImage` in `libs/smart_image.class.php` (evita doppia dichiarazione). | `libs/02_free_ads.class.php`, `libs/03_ads.class.php` |
| 4.3 | Rimuovere `ddsmoothmenu.init` (26 file) e `clearText` (21 file) inline → `js/site_init.js`. | script |
| 4.4 | Uniformare chiavi sessione legacy a standard (`$_SESSION['user_id']`, §6). | ~20 file |
| 4.5 | Flussi legacy `ads.php`/`ad_post.php` → redirect a `browse.php` + `noindex`. | 2 file |

### Fase 5 — Cleanup finale e SEO *(dir. 7)*
- Social link reali o rimossi in `footer.php`.
- Rivedere link `06_12` in `my_posts.php` (coerenza dir. 3).
- `noindex` su pagine template incomplete.
- ⚠️ **`upload`/`images` intoccabili** (dir. 15): nessuno script vi cancella contenuti.

---

## Parte 4 — Consegna *(dir. 2, 9, 10)*

1. **1ª verifica** → correzione file+DB.
2. **2ª verifica** → ricontrollo completo.
3. **Non-regressione:** dati conservati (dir. 9), `upload`/`images` integre (dir. 15), differenza free/premium (dir. 13), Road/Special coerenti col DB (dir. 14, 18), **sidebar di sezione + header utente** coerenti col nuovo modello (dir. 17).
4. Solo dopo i due passaggi → **proposta file per download**.

---

## Ore stimate

| Fase | Descrizione | Ore |
|---|---|---|
| 0 | Verifica DB ⇄ cartelle + nodi flowchart + tassonomia `vtype` | 2h |
| 1 | `my_posts` aggregato, vista unificata, search, filtro Road/Special | 6h |
| 2 | Sicurezza + isolamento + GDPR Histats | 4h |
| 3 | Template + **sidebar per-sezione + header utente** + viewport + URL immagini | **4h** |
| 4 | Localizzazione EN + debito tecnico | 5h |
| 5 | Cleanup finale e SEO | 1h |
| **Totale** | | **≈ 22h** |

> *Nota stima:* Fase 3 passa da 3h a 4h: la sidebar per-sezione + header utente (nuova dir. 17) comporta riscrivere `include_sidebar.php`, creare le sidebar di sezione e l'header utente, più ampio del singolo include condizionale precedente.

---

*Fine piano — Allonwheel v2026.06.02 (nuova dir. 17: sidebar per-sezione + header utente; direttive utente prioritarie).*

---

## Parte 5 — Aggiornamento v0.0.9 (9 giu 2026): allineamento IA + dir. 19

### 5.1 — Finding chiave
Lo stato della v0.0.9 era **incoerente**: le sidebar erano già allineate alla tassonomia commerciale Road/Special, ma `header.php`, `footer.php`, i box *Testimonial* di tutte le sidebar e l'intera **home (`index.php`)** trascinavano ancora il **contenuto "motorsport" del template originale** (racing/paddock/hospitality/motorhome), con pagine in `00_first/` e asset in `images/00_first/`. È la radice dell'errore di dominio del prompt esterno (Gemini), che leggeva il sito come marketplace motorsport.

### 5.2 — Applicato (non distruttivo, lint PHP 8.3 OK, CRLF preservati)
- **`header.php`** (Task 1): menu riscritto per intento — Marketplace (All listings / Free ads / Premium ads / Request a quotation), Suppliers (directory / Road / Special / Shelter & Container), Portfolio, About; Account/Sell login-aware; rimosse tutte le voci `00_first` dal menu.
- **`footer.php`** (Task 5): colonna "Recent posts" motorsport → "Browse" reale; rimossi link `00_first`; social dead-link (LinkedIn/YouTube/Vimeo) rimossi; legali (Privacy/Cookie) spostati nella riga inferiore sottile.
- **6 sidebar** (`default, marketplace, suppliers, special, account, blog`): copy *Testimonial* da motorsport → veicoli commerciali.

### 5.3 — GATE: in attesa di approvazione (parte distruttiva o legata ad asset)
| Item | Perché è in attesa |
|---|---|
| **Riscrittura contenuti `index.php`** (8 post_box motorsport) | Le immagini sono in `images/00_first/` (dir. 15: intoccabili). Rifare il copy senza rifare gli asset darebbe testo commerciale su foto motorsport. Serve la tua decisione su immagini/copy. |
| **Cartella `00_first/` (11 pagine motorsport)** | Candidata a rimozione/redirect 301 verso le pagine reali (browse/road/special). Distruttivo → richiede ok. |
| **Task 2 (search avanzata) e Task 4 (cross-sell Related suppliers)** | Implementazione nuova su `browse.php` / directory: la base c'è (`q` + `macro_category`), ma va costruita e verificata a parte. |
| **Spostamenti fisici file + migrazione DB** | Dir. 19: solo con mappa IA approvata + redirect + migrazione senza perdita dati. |

### 5.4 — Mappa IA "prima/dopo" (proposta, Task 6 — da approvare)
```
PRIMA (v0.0.9, incoerente)            DOPO (proposta, per intento)
Home                                  Home
Browse (motorsport 00_first) ──┐      Marketplace
  Racing/Paddock/Hospitality…  │        All listings · Free · Premium · Quotation
Suppliers                      │      Suppliers
  Quotation                    │        Directory · Road · Special · Shelter/Container
About / Account                ▼      Portfolio
00_first/* (orfane via URL)    ✗      About (Our story · What we do · Blog · FAQ · Conditions · Contact)
                                      Account/Sell (login-aware, solo header)
                                      00_first/* → redirect 301 verso pagine reali
```
Header/footer/sidebar = **già DOPO**. Mancano: home + redirect `00_first` + (eventuale) flatten Vehicle types come filtri.

### 5.5 — Rev.4 navigazione (9 giu 2026): header pubblico + box utente in sidebar
Su richiesta utente la dir. 17 è stata invertita: **header solo navigazione pubblica** (identico ospite/loggato) e **link personali spostati in ogni sidebar di sezione** via il nuovo partial `sidebar_user_box.php` (loggato → box *My account*; ospite → solo Login). Applicato e verificato (lint PHP 8.3 OK, render funzionale OK nei due stati, CRLF preservati):
- **`header.php`**: rimosso il blocco Account/Sell login-aware; menu = Home / Marketplace / Suppliers / Portfolio / About.
- **`sidebar_user_box.php`** (nuovo): box utente condiviso, login-aware.
- **6 sidebar** (`default, marketplace, suppliers, special, account, blog`): includono il partial; rimossi i box duplicati preesistenti ("Sell on Allonwheel"/"Register to sell" in marketplace; Account/My account propri in account; "Write an article" condizionale nel box Blog).

> ⚠️ Pagine `#no_sidebar` (Home, About, Portfolio): per scelta dell'utente i link utente compaiono solo dove esiste già una sidebar. Su queste pagine, da loggato, non c'è accesso diretto a logout/dashboard dalla sidebar (l'header è pubblico). Punto aperto se in futuro si vorrà coprire anche queste pagine.

*Fine aggiornamento v0.0.9.*

---

## Parte 6 — Aggiornamento v0.0.10 (UX + Histats)

### 5.6 — v0.0.10 (UX): Histats reintrodotto (dir. 20) + audit UX

**Histats (richiesta utente):** in v0.0.10 il tracker risultava già rimosso (per GDPR). Su richiesta è stato **reintrodotto e consolidato** nel modo corretto:
- nuovo partial `includes/histats.php` (ID parametrico `HISTATS_ID`; senza ID = no-op sicuro);
- incluso **una sola volta** da `footer.php` → copertura sito-wide, niente snippet duplicato;
- **consent-gated**: parte solo col consenso `analytics` (hook in `cookie_consent.js`, cookie `aow_consent`, Consent Mode v2); niente pixel `<noscript>` non gated;
- host Histats aggiunti alla CSP (`s10.histats.com`, `sstatic1.histats.com`).
- **Da fare a cura di Marco:** impostare l'ID reale (costante `HISTATS_ID`, env, o fallback nel partial).
- Lint PHP 8.3 OK; JS valido; CRLF preservati (LF su `cookie_consent.js`).

**Audit UX/UI:** prodotto documento separato `Allonwheel_UX_Audit.md` con i miglioramenti prioritizzati. Finding principale ribadito: la **home `index.php` è ancora interamente "motorsport"** (title/meta/keywords e post_box Racing/Roadshow/Hospitality, CTA verso `00_first/`), in contrasto col marketplace reale Road/Special — primo intervento per credibilità e coerenza.

*Fine aggiornamento v0.0.10.*

---

## Parte 7 — Consolidamento v3.1 (20 lug 2026): stato reale post-interventi

Questa parte riallinea il piano allo stato **effettivo** del codice dopo il ciclo
di interventi lug 2026. Base di riferimento: build **V3.1** (Login ospite), che
ha incorporato quasi tutto il lavoro delle sessioni precedenti.

### 7.1 — I 5 punti "cosa farei per primo": stato finale

| # | Punto | Stato |
|---|-------|-------|
| 1 | seo_head index/browse | **decaduto** — canonical/hreflang c'erano già; chiuse invece 6 pagine minori che ne erano prive |
| 2 | Registrazione dopo il wizard | **completo (free)** — l'ospite compila, salva bozza, si registra, la bozza si ripopola nel form e si pubblica; bozza cancellata dopo l'INSERT |
| 3 | Fan-out limitato (RFQ) | **targeting + punteggio + tetto fatti**; il "claim 24h" sostituito da escalation via cron (vedi 7.3) |
| 4 | Faccette | **fatto** (condizione + prezzo in sidebar); faccette tecniche **non fatte di proposito** (vedi 7.4) |
| 5 | Wanted board | **preesistente**, allineato (tetto + pertinenza vehicle_type + i18n + sidebar + notifyBuyers alla pubblicazione) |

### 7.2 — Interventi consegnati (tutti in V3.1 salvo diverso avviso)

- **Badge:** solo Premium, "Free" rimosso ovunque (browse, family_page, seller_dashboard). `.badge_free` resta nel CSS, inutilizzata.
- **Tassonomia Shelter:** `shelter_container.php` da `item_kind` a `product_macro` (sovrainsieme, indicizzato). Patch di allineamento idempotente.
- **RFQ mirate:** era un **broadcast a tutte le aziende**; ora `getCompaniesByProductsScored()` con punteggio di pertinenza + tetto (`AOW_RFQ_MAX_RECIPIENTS`, default 3). Colonne `match_score`/`rank_pos` per tracciabilità.
- **Card annuncio unificata:** `shared/ad_card.php`, un solo formato per browse / famiglia / shelter / road / special. Corretto un bug (descrizione doppia su "price on request"). "View details" a filo destro via flex esistente.
- **Faccette in sidebar:** `sidebar_facets.php` (condizione da ENUM reale + fascia prezzo); si nasconde se non ci sono annunci.
- **Registrazione dopo il wizard:** tabella `ad_drafts`, classe `AdDraft`, ritorno post-login con guardia open-redirect, claim al login, 4 innesti sul wizard (guardia soft, save ospite, prefill, delete), pulizia GDPR nel cron.
- **Wanted:** tetto + ordinamento per `vehicle_type`, sidebar uniforme sulle 4 pagine, `notifyBuyers` alla **pubblicazione** (prima scattava solo all'approvazione admin, che non avviene mai → i buyer non venivano mai avvisati).
- **Escalation RFQ:** `scripts/rfq_escalation.php` (vedi 7.3).
- **Hero admin:** `_admin/admin_hero.php` + tabella `site_settings` + `libs/site_settings.class.php`; l'hero della home è configurabile (upload in `upload_image/hero/`, mai in `images/`).
- **Guida di benvenuto:** email alla verifica account completata a guida integrale (tutte le opzioni reali dell'account); pannello "Getting started" già presente in dashboard.

### 7.3 — Perché "escalation" e non "claim 24h" alla lettera

Il claim (il fornitore rivendica entro 24h, altrimenti passa al successivo)
presuppone due cose **inesistenti** oggi: (a) un'area riservata dove il
fornitore vede e rivendica i lead (li riceve solo via email); (b) un segnale
strutturato di "ho risposto". Senza, un cron riassegnerebbe alla cieca.
`rfq_escalation.php` dà il valore reale — nessun lead resta a marcire —
segnalando all'admin i lead fermi da 24h+, così l'escalation la fa una persona.
Quando esisterà un'**area lead per fornitori**, questo cron diventa il claim
automatico: la logica di selezione dei lead fermi è già scritta.

### 7.4 — Perché le faccette tecniche NON sono state fatte

`03_ads_tech_details` ha 52 campi, quasi tutti booleani iper-specifici; le
poche misure dimensionali sono `varchar` (non filtrabili) e premium-only. Una
faccetta tecnica oggi darebbe risultati vuoti o farebbe sparire i free. La
strada corretta (normalizzare 3-4 misure a numeri, poi filtrare) coincide col
**punto 7 del piano — configuratore tecnico**, ed è una decisione su schema +
wizard + migrazione. Analisi completa in `ANALISI_faccette_tecniche.md`.

### 7.5 — CRITICO: stato del DB vs codice

Il codice V3.1 include tutto, ma **funziona solo se le patch SQL sono applicate**.
Il dump `sql/allonwhe80316.sql` è lo stato **prima** delle patch changelog; le
23 patch in `sql/Changelog/` si applicano dopo. Le più recenti da verificare in
produzione: `2026-07-17_ad_drafts.sql`, `2026-07-17_rfq_match_score.sql`,
`2026-07-20_site_settings.sql`.

**Strumento nuovo:** `scripts/check_schema.php` interroga il DB reale e dice,
tabella per tabella, cosa c'è e cosa manca (con il nome della patch da applicare).
Eseguirlo dopo ogni trasferimento: `php scripts/check_schema.php`.

### 7.6 — Cosa resta aperto (decisioni di Marco)

- **Area lead per fornitori** → abilita il claim 24h vero.
- **Configuratore tecnico** (punto 7) + normalizzazione misure → abilita le faccette dimensionali.
- **Moderazione obbligatoria** sì/no: oggi gli annunci nascono `approved` (pubblici subito). Se attivata, cambiano diverse scelte (anti-spam, notifyBuyers).
- **Premium tech_details nella bozza ospite**: oggi la bozza salva i campi base; se l'ospite deve compilare anche la scheda tecnica premium prima di registrarsi, è un'aggiunta al payload.
- **Task infra:** SMTP con SPF/DKIM (confermato SMTP autenticato), `HISTATS_ID` reale, URL social LinkedIn/YouTube/Vimeo (aggiornati a mano).

*Fine Parte 7 — consolidamento v3.1.*
