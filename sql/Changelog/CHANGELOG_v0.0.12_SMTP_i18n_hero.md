# Allonwheel v0.0.12 — Delta: SMTP + i18n (/en/) + hero_image + fix chip (cache)

Applicato sulla base dello ZIP V_0_0_12_Cleaned ricaricato (che gia' contiene
consolidamento CSS, stub 00_first e P1.5).

## 1) Fix filtri chip browse.php — causa: CACHE
La regola `.chip` (pill arrotondato) e' gia' corretta e robusta nel CSS, e
browse.php usa `class="chip"`. Il "link semplice senza bordo" e' la VECCHIA
`allonwheel_style.css` ancora in cache (l'.htaccess la cachea 1 mese).
**Cura definitiva: cache-busting.** Aggiunto `?v=20260616` al link del foglio
in **74 pagine** -> il browser e' costretto a riscaricare la CSS aggiornata.
(Per i prossimi rilasci CSS, basta incrementare il numero di versione.)

## 2) SMTP — ATTIVATO (lato codice)
- **PHPMailer 6.9.3 vendorizzato** in `libs/PHPMailer/src/` (+ LICENSE).
- `libs/mailer.class.php`: require guardato -> `class_exists()` ora e' vero, il
  Mailer usa SMTP quando `MAIL_TRANSPORT=smtp`. Fallback `mail()` intatto.
- `mail.env.example`: aggiornato (MAIL_TRANSPORT=smtp + chiavi da compilare).
- **DA FARE (tu):** nel `.env` (fuori webroot) imposta SMTP_HOST/USER/PASS, PORT,
  ENCRYPTION e MAIL_FROM. Le credenziali le inserisci tu (io non le tratto).

## 3) Internazionalizzazione — FONDAMENTA (architettura /en/)
Scelta confermata: **sottocartella con rewrite** (/en/, /it/), default = inglese.
- `config/i18n.php`: `aow_locale()`, `t('chiave','Default')`, `__()`,
  `aow_locale_url()`, `aow_hreflang_tags()`. Adozione **incrementale**: nessuna
  pagina e' obbligata; le chiavi mancanti ricadono sul default EN.
- `lang/en.php` (sorgente) e `lang/it.php` (nucleo: nav, footer, azioni, famiglie).
- `config/bootstrap.php`: carica i18n.php.
- `.htaccess`: rewrite `^(en|it)/...` -> file reale, locale in env `AOW_LOCALE`.
  Le URL attuali (senza prefisso) restano invariate: zero regressioni.
- **Prossimo passo (incrementale):** sostituire le stringhe hardcoded con
  `t('chiave','Testo EN')` pagina per pagina e inserire `aow_hreflang_tags()`
  nel `<head>`. Poi tradurre `lang/it.php`.

## 4) hero_image delle macro — codice PRONTO + cosa devi fare
- `browse.php`: ora legge `product_macros.hero_image` e, se valorizzato, mostra
  l'immagine in cima al box intro (classe `.macro_hero`, responsive). Se vuoto,
  non mostra nulla -> nessuna regressione.
- `sql/Changelog/macro_hero_image.sql`: TEMPLATE idempotente da compilare.
- **DA FARE (tu):** (1) carica MANUALMENTE 5 immagini (~1200x500, JPG/WebP) in una
  cartella servita, es. `images/macros/` (il codice non tocca `images/`, dir. 15);
  (2) metti i path reali nel template SQL e lancialo; (3) verifica su
  `/browse.php?macro=race-trailer`.

## Verifica (doppio passaggio, dir. 2/10)
- `php -l` su tutti gli 82 PHP del delta -> No syntax errors.
- PHPMailer rilevato (v6.9.3); i18n testato EN/IT (t(), URL, hreflang, fallback).
- CRLF preservato; `.htaccess` e `mail.env.example` restano LF come nel pristino.
- `images/`/`upload_image/` non toccati.

## Ordine di applicazione
Sovrascrivere i file ai percorsi indicati. Hard refresh non piu' necessario per i
chip (il `?v=` lo forza). Per l'SMTP: compilare il `.env`. Nessuna ALTER DB.
