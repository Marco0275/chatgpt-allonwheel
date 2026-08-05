# Guida al deploy — Ask the Experts + Landing + API ChatGPT + Riordino asset

Guida passo-passo per mettere online il pacchetto `Allonwheel_ExpertHub_2026-08-03.zip`.
Ambiente reale: **PHP 8.3 · MySQL 5.7 · hosting Seeweb**. Tempo stimato: ~30–40 min.

Ordine (non saltare passaggi):
**1) Backup → 2) SQL → 3) Copia file → 4) `.env` → 5) Cron → 6) GPT → 7) Script asset.**

I passi 1–4 sono obbligatori perché il sito funzioni. 5 serve solo se userai la
pubblicazione programmata. 6 serve solo per far scrivere gli articoli a ChatGPT.
7 è pulizia, quando vuoi.

---

## Prima di iniziare — cosa ti serve

- Accesso al **pannello Seeweb** (o FTP/File Manager) per caricare i file.
- **phpMyAdmin** sul database del sito (quello `allonwhe...`).
- Il tuo **`.env`** attuale (quello che già usi, con `CRON_TOKEN` e credenziali DB):
  sta **un livello sopra la webroot** (fuori dalla cartella pubblica). Lo carica
  `config/bootstrap.php` da `.../<sopra-webroot>/.env`.
- Account **cron-job.org** (lo usi già per `rfq_deliver.php` e `saved_search_alerts.php`).
- **ChatGPT Plus/Team** (serve per creare un GPT con *Actions*), solo per il passo 6.

---

## STEP 1 — Backup (10 min, non saltarlo)

**Perché:** il passo 2 modifica la struttura della tabella `blog`. Se qualcosa va
storto vuoi poter tornare indietro in 1 minuto.

1. **Database:** phpMyAdmin → seleziona il DB `allonwhe...` → scheda **Export** →
   metodo *Quick*, formato *SQL* → **Go**. Salvi il file `.sql` da parte.
2. **File:** scarica via FTP almeno i file che stai per sovrascrivere
   (`header.php` non cambia; cambiano `_admin/admin_header.php`,
   `04_request_offer/04_request_offer.php`, `libs/blog.class.php`) — oppure fai uno
   **snapshot** dallo spazio Seeweb se disponibile.

> Non serve toccare `upload_image/` né `images/`: restano come sono.

---

## STEP 2 — Eseguire la migrazione SQL (5 min)

**File:** `sql/Changelog/2026-08-02_blog_expert_hub.sql`
**Cosa fa:** aggiunge alla tabella `blog` le colonne nuove (`slug`, `category`,
`question`, `outlines`, `faq_json`, `published_at`, `source`), estende lo stato
(`draft/pending/scheduled/published/rejected`) e crea le tabelle
`blog_categories` (4 categorie) e `blog_leads`. È **idempotente**: se la rilanci
non fa danni.

**Come lanciarla (IMPORTANTE):** il file usa `DELIMITER //` per due stored
procedure. In phpMyAdmin **usa la scheda `Import`, non `SQL`**:

1. phpMyAdmin → seleziona il DB `allonwhe...` (a sinistra).
2. Scheda **Import** → *Choose file* → seleziona
   `2026-08-02_blog_expert_hub.sql` → **Go**.
3. Deve comparire "Import has been successfully finished".

**Verifica (scheda SQL, incolla ed esegui):**
```sql
SHOW COLUMNS FROM `blog`;                    -- devi vedere slug, category, question, outlines, faq_json, published_at, source
SELECT slug, name FROM `blog_categories`;    -- 4 righe
SHOW TABLES LIKE 'blog_leads';               -- 1 riga
```

**Se dà errore:**
- *"max key length is 767 bytes"* → non deve più succedere (lo slug ora è
  `VARCHAR(191)`). Se lo vedi, dimmelo: significa un `row_format` particolare.
- Se avessi incollato il file nella scheda **SQL** invece che **Import** e desse
  errori sul `DELIMITER` o su `CREATE PROCEDURE`: rifai da **Import** (gestisce
  il `DELIMITER` da solo).

---

## STEP 3 — Copiare i file sul sito (10 min)

Carica via FTP/File Manager **rispettando le cartelle**, sovrascrivendo:

| Dove | File |
|---|---|
| root | `blog.php`, `blog_post.php`, `blog_lead_save.php`, `landing.php` |
| `libs/` | `blog.class.php` (esteso, additivo) |
| `api/` | `blog.php`, `.htaccess`, `blog_api.env.example`, `README_ChatGPT_API.md` |
| `cron/` | `blog_publish_scheduled.php` |
| `_admin/` | `admin_header.php` (modificato: +voce menu "Blog leads"), `blog_leads.php` |
| `04_request_offer/` | `04_request_offer.php` (modificato: prefill intento) |
| `sql/Changelog/` | `2026-08-02_blog_expert_hub.sql` (già eseguito allo step 2) |
| `tools/` | script e manifest asset (servono allo step 7) |

Note:
- I fine-riga sono già **CRLF** dove serve: non convertirli.
- **Non** caricare `LEGGIMI_ExpertHub.txt`, `CHANGELOG.txt`, `MD5SUMS.txt`,
  `GUIDA_DEPLOY.md` nella webroot (sono documenti per te; se li carichi, mettili
  fuori dalla parte pubblica o cancellali dopo).
- Non toccare `upload_image/` e `images/`.

**Verifica rapida:** apri `https://www.allonwheel.com/blog.php` → deve caricare
l'hub "Ask the Experts" (anche vuoto, con la barra delle categorie).

---

## STEP 4 — Aggiungere le chiavi al `.env` (5 min)

**Dove:** il `.env` che già usi (un livello sopra la webroot). Aprilo e **aggiungi
in fondo** due righe:

```
BLOG_API_KEY=incolla_qui_una_chiave_lunga_e_casuale
BLOG_API_AUTHOR_ID=
```

- **`BLOG_API_KEY`**: è la password dell'API blog. **Finché è vuota, l'API
  risponde 503** (disattivata: nessuno può scrivere). Generala così, da terminale
  con PHP:
  ```
  php -r "echo bin2hex(random_bytes(32));"
  ```
  (in alternativa un generatore di stringhe casuali da 64 caratteri). Copiala
  dopo `BLOG_API_KEY=` senza spazi e senza virgolette.
- **`BLOG_API_AUTHOR_ID`** (opzionale): l'`id_user` che risulterà autore dei post
  creati via ChatGPT. Se lo lasci vuoto, l'API usa il primo utente admin/expert.
- Il `.env` va tenuto con fine-riga **LF** (non CRLF). `CRON_TOKEN` e le credenziali
  DB sono già lì: non toccarle.

**Verifica:** apri
`https://www.allonwheel.com/api/blog.php?meta=1` **senza** header di
autenticazione → deve rispondere **401** (chiave presente ma mancante nella
richiesta). Se risponde **503**, la chiave nel `.env` non è stata letta.

---

## STEP 5 — Cron della pubblicazione programmata (5 min, opzionale)

**Serve solo** se userai lo stato `scheduled` (articoli che si pubblicano da soli
a una data/ora). Le bozze e la pubblicazione immediata **non** ne hanno bisogno.

**Cosa fa:** `cron/blog_publish_scheduled.php` controlla gli articoli
`scheduled` con `published_at` già passato e li mette `published`.

**Come:** è **identico** ai cron che hai già. Su **cron-job.org** crea un nuovo job:
- URL:
  `https://www.allonwheel.com/cron/blog_publish_scheduled.php?token=IL_TUO_CRON_TOKEN`
  (usa lo stesso valore di `CRON_TOKEN` del `.env`, come per `rfq_deliver.php`).
- Frequenza: ogni **10 minuti**.

**Verifica:** apri l'URL con il token nel browser → deve rispondere una riga tipo
`OK ... published=0` (0 se non ci sono articoli scaduti). Senza token o con token
sbagliato → **403**.

---

## STEP 6 — Creare il Custom GPT che scrive gli articoli (10 min)

**Serve solo** se vuoi far generare/pubblicare articoli a ChatGPT. Riferimento
completo (schema + esempi): `api/README_ChatGPT_API.md`.

1. In **ChatGPT** (Plus/Team): barra laterale → **My GPTs** → **Create a GPT** →
   scheda **Configure**.
2. In fondo, **Actions** → **Create new action**.
3. **Schema:** apri `api/README_ChatGPT_API.md`, copia il blocco **OpenAPI 3.1** e
   incollalo nel campo schema.
4. **Authentication** → **API Key** → *Auth Type* = **Bearer** → nel campo chiave
   incolla la tua **`BLOG_API_KEY`** (la stessa dello step 4).
5. Salva. Nella descrizione del GPT puoi incollare i "prompt d'esempio" del README.

**Test dentro il GPT:**
> "Crea una bozza per la categoria *Costs* dal titolo *Buy vs rent: total cost of
> ownership of a race trailer*, con una scaletta di 4 punti e 3 FAQ."

Poi verifica su `https://www.allonwheel.com/blog.php` (le bozze non compaiono
pubbliche: le vedi da admin o le pubblichi con "Publish article id N").

---

## STEP 7 — Riordino immagini e pulizia file (quando vuoi)

> **IMPORTANTE - due condizioni:**
> 1. Questi script ora girano **in Python** (serve Python 3, che usi gia' per
>    `analyze_unused.py`). I `.bat`/`.sh` sono solo lanciatori e si
>    **auto-posizionano** nella radice del sito: lanciali da dove vuoi, anche
>    da dentro `tools\`.
> 2. **Devono girare sul SITO COMPLETO** (la cartella con `images/`), NON sul
>    solo pacchetto di update. Se li lanci nel pacchetto vedi *0 spostate* e
>    *landing1.php assente*: e' normale, li' non ci sono ne' `images/` ne' i
>    vecchi file. Scarica prima il sito da Seeweb (o usa SSH).


**Attenzione a DOVE girano gli script:** agiscono sulla cartella `images/` del
**sito**. Quindi vanno eseguiti **dove sta `images/`**, cioè sul server o su una
copia locale completa da ri-caricare. Non basta lanciarli sul tuo PC se lì non hai
l'albero del sito.

**Scenario A — hai accesso SSH su Seeweb (consigliato):**
1. Assicurati che la cartella `tools/` sia caricata sul sito.
2. Da SSH, vai nella **radice del sito** (dove ci sono `index.php` e `images/`).
3. Anteprima immagini: `bash tools/reorg_images.sh --dry-run`
4. Sposta davvero: `bash tools/reorg_images.sh`
   (sposta 161 immagini non usate in `images/not_used/`, **reversibile**; non tocca
   `upload_image/` né `images/brand/`).
5. Pulizia codice (solo elenco): `bash tools/cleanup_unused_code.sh`
   Per cancellare davvero (dopo backup): `bash tools/cleanup_unused_code.sh --delete`
   (in lista c'è solo `landing1.php`, duplicato della vecchia landing).

> Gli script ora si **posizionano da soli** nella radice del sito: puoi
> lanciarli anche da dentro `tools\` o col doppio-click, non serve fare `cd`.

**Scenario B — niente SSH (solo FTP):**
1. Scarica in locale l'intero sito (o almeno `images/` + `tools/`).
2. Da Windows, nella radice della copia locale:
   - `tools\reorg_images.bat /DRYRUN` (anteprima), poi `tools\reorg_images.bat`
   - `tools\cleanup_unused_code.bat` (elenco), poi `... /DELETE` per cancellare.
3. **Ri-carica** su Seeweb la cartella `images/` (ora con `images/not_used/`) e,
   se hai cancellato `landing1.php`, eliminalo anche dal server.

**Reversibilità:** per annullare lo spostamento immagini, sposta i file da
`images/not_used/` di nuovo sotto `images/`.

---

## (Opzionale) Fix 1 riga in `index.php`

Bug reale ma minore: `index.php` richiama `images/00_first/road_vehicles.JPG`
(maiuscolo) mentre il file è `road_vehicles.jpg`. Su Linux è un'immagine rotta
(salvata dal fallback JS). Fix: nel `index.php`, nel riferimento a
`road_vehicles`, cambia **`.JPG` → `.jpg`**. Non l'ho incluso qui perché `index.php`
è candidato a riscrittura completa (vedi sotto): meglio farlo lì o a mano.

---

## Verifiche finali (checklist)

- [ ] `blog.php` carica; le chip categoria filtrano (`?cat=costs` mostra solo *Costs*).
- [ ] Articolo di prova via GPT `published` → compare in `blog.php`.
- [ ] `scheduled` con data futura → **non** compare; con data passata + cron → compare.
- [ ] `blog_post.php`: FAQ ad accordion + form a fine articolo → invii un lead di
      prova → compare in **_admin → Blog leads** e arriva la mail.
- [ ] `landing.php`: hero + card veicolo con zoom in hover + CTA. Cliccando
      "Request a Feasibility Study" la RFQ apre con **Object** già compilato.
- [ ] `api/blog.php?meta=1` senza chiave → 401 (non 503).

---

## Punti aperti / decisioni che servono da te

Questi non li ho toccati perché richiedono una tua scelta, non un'esecuzione:

1. **Home `index.php` (P0).** È ancora la demo "motorsport" del template
   (post_box Racing/Roadshow/Hospitality, title/meta, CTA verso `00_first/`), in
   contrasto col marketplace reale. Va riscritta allineata alle famiglie/landing.
   *Mi serve la tua decisione su immagini e copy* (le foto stanno in
   `images/00_first/`, intoccabili): riuso quelle o ne fornisci di nuove? Appena
   mi dici, la riscrivo (e ci infilo anche il fix `road_vehicles`).
2. **Copy delle 5 macro** (`product_macros.intro_text`): senza, le intro di
   `browse.php?macro=` non compaiono. Se mi dai i testi (o l'ok a scriverli io in
   EN) li popolo.
3. **`00_first/` (11 pagine legacy)**: rimozione **con redirect 301** verso le
   pagine reali. Distruttivo → serve il tuo ok e la mappa "prima/dopo".
4. **SMTP / social / Histats**: attivare PHPMailer + env `SMTP_*` per l'invio
   reale, mettere gli URL social veri al posto dei `#`, impostare `HISTATS_ID`.
   Sono azioni di configurazione tue.
5. **Matching RFQ**: dal referto Gemini restava da decidere se il match
   fornitori include gli annunci **free** (`02_free_ads`) o solo **premium**
   (`03_ads`), e se vuoi il pulsante WhatsApp tracciato. Dimmi e procedo.

Dimmi da quale vuoi ripartire — il candidato naturale è il **punto 1 (home)**,
perché è quello che oggi stona di più con il resto del sito.
