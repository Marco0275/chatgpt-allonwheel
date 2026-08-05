# Allonwheel — Batch B: home fix + immagini macro + RFQ broadcast

Base: tua ZIP `Allonwheel_V_0_0_13_i18n.zip` (con PHPMailer, mPDF, hero images,
+ tutto il mio batch precedente già incluso). 4 file modificati/aggiunti.
`php -l` OK su 185 file. CRLF preservati.

---

## ✅ Fatto in questo batch

### A1 — Bug strutturali home (`index.php`)
- Rimosso il **box "Special vehicles" duplicato** (la riga ora è Road / Special / Shelter).
- Rimosso lo **`</div>` stray** dopo "Road vehicles" e aggiunta la chiusura corretta
  di `#no_sidebar` in fondo al contenuto.
- Verifica: pagina intera (header+index+footer) **bilanciata 44/44 (net 0)**, come una
  pagina sana di riferimento. `col_4` = 3 box. Lint OK.

### B5 — Immagini macro non visibili → patch SQL
- **Causa**: `product_macros.hero_image` puntava a `/images/macros/<slug>.jpg`
  (race-trailer.jpg, hospitality.jpg…) ma i file caricati si chiamano
  `IMG_1505.JPG … IMG_1519.JPG`. Nomi non combacianti → immagini rotte.
- `browse.php` rende correttamente `/images/macros/...` (root-relative) e la classe
  `macro_hero` esiste già nel CSS: il problema era **solo il nome file**.
- **Fix**: patch `sql/Changelog/2026-06-17_macro_hero_images.sql` che riallinea
  `hero_image` ai nomi reali. **Esegui questa patch** sul DB.
- ⚠️ **Abbinamento da verificare**: i nomi file sono generici, ho usato l'ordine
  macro (sort_order) come default:
  race-trailer→IMG_1505, hospitality→IMG_1516, mobile-clinic→IMG_1517,
  shelter-container→IMG_1518, custom-projects→IMG_1519.
  Se una foto non è della famiglia giusta, **scambia il nome file** nelle righe SQL.
  Su Linux i nomi sono **case-sensitive**: tieni `.JPG` maiuscolo.

### C6 — RFQ a tutte le aziende + copia a rfq@
- Nuovo metodo `CompanyManager::getAllCompanies()` (tutte le aziende **attive** con
  email valida).
- `04_send_offer.php`: la RFQ ora va in **broadcast a tutte le aziende registrate**
  (non più solo a quelle che matchano i prodotti) e una **copia va a
  `rfq@allonwheel.com`** (era `info@`). La copia rfq@ parte sempre, anche se non ci
  sono aziende; l'esito è "successo" se almeno la copia o un'azienda hanno ricevuto.
- I destinatari e l'esito invio restano tracciati in `quote_request_recipients`.

---

## ☑️ Verifiche richieste (stato)

### B2 — PHPMailer: PRONTO, manca solo la config
La classe `Mailer` carica già PHPMailer da `libs/PHPMailer/src/` via `class_exists()`.
Funziona in SMTP **solo se** nel `.env` imposti:
`MAIL_TRANSPORT=smtp`, `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`,
`SMTP_SECURE` (tls/ssl), `MAIL_FROM`. Senza, fallback su `mail()` di PHP.
**Azione tua**: compila le env (vedi `mail.env.example`) e manda una RFQ di prova.

### B3 — HISTATS_ID: ANCORA VUOTO nello zip
In `includes/histats.php` l'ID risulta vuoto sia da costante/env sia dal fallback
manuale (`$histats_id = '';`). Così il tracker **non parte**.
**Azione tua** (una riga, scegli un modo):
- definisci `define('HISTATS_ID', '4XXXXXX');` in `config/bootstrap.php`, **oppure**
- imposta la riga di fallback in `includes/histats.php`: `$histats_id = '4XXXXXX';`

### D8 — mPDF: INCOMPLETO (mancano le dipendenze)
C'è `libs/mpdf/src/Mpdf.php` ma **non** `vendor/autoload.php` né le dipendenze che
mPDF richiede (FPDI, psr/log, php-font-lib, ecc.). Così `new \Mpdf\Mpdf()` va in
fatal error.
**Azione tua**: installa mPDF con Composer (tira anche le dipendenze):
`composer require mpdf/mpdf` — poi includi `vendor/autoload.php`.
Finché non ci sono le dipendenze **non implemento la generazione PDF** (eviterei un
fatal). Dimmi quando è a posto.

---

## 🔜 Resto aperto — pianificazione (feature grandi, una alla volta)

Questi punti sono multi-file e li affronto in blocchi dedicati. Per partire mi
servono alcune decisioni:

1. **C7 — i18n contenuti DB (IT).** Confermato `/en/` `/it/`. Procedo aggiungendo le
   colonne `_it` alle tabelle con testo mostrato (`product_macros.intro_text` →
   `intro_text_it`; `vehicle_types` usa già il dict file, non serve). Da decidere:
   **quali tabelle/campi** vuoi tradurre oltre `product_macros` (es. `blog`, `06_company`
   descrizioni?). Patch SQL + fallback EN se IT vuoto.
2. **New#1 — Ruoli (Esperto / PM / Consulente) + New#2 forum.** Servono scelte di schema:
   - colonna ruolo su `users` (es. `role` enum) o tabella `user_roles` (multi-ruolo)?
   - il "blog→forum": riuso tabella `blog`/`blog_comments` esistente come thread/risposte,
     o nuove tabelle `forum_threads`/`forum_posts`?
   - notifiche email ai partecipanti del thread ad ogni risposta (dipende da SMTP attivo).
   - checkbox in registrazione azienda "ricevi elenco PM/consulenti" → colonna
     `06_company.vuole_elenco_pm` + invio elenco.
   Dammi le preferenze di schema e parto dalle fondamenta (ruoli + checkbox), poi forum,
   poi notifiche.
3. **New#2 — Revisione sidebar (regola "no link duplicati col contenuto").** È trasversale:
   rivedo ogni sidebar perché non ripeta link già presenti nel main della pagina, e
   raggruppo per categoria. Da decidere se vuoi che parta **dopo** il forum/ruoli (così
   le sidebar riflettono già le nuove sezioni) o subito sull'attuale.
4. **D8 — Configuratore Step 2 (PDF).** Sbloccato da mPDF (dipendenze). Poi implemento
   riuso `03_ads_tech_details` + generazione PDF del preventivo.
5. **D9 — Authority layer nel forum.** Si fonde con New#1 (ruolo Esperto che risponde).

**Ordine consigliato**: B3+B5+SMTP (rapidi, tuoi) → C7 → ruoli/checkbox → forum →
notifiche → sidebar → PDF. Dimmi le decisioni dei punti 1–2 e proseguo.
