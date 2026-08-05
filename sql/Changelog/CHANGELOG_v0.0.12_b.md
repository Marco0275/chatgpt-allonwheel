# Allonwheel v0.0.12 — Delta: SMTP + i18n(/en/) + hero_image + fix chip(cache) + menu Marketplace

Applicato sullo ZIP V_0_0_12_Cleaned (che gia' contiene consolidamento CSS, stub
00_first, P1.5 e la regola .chip robusta).

## 1) Fix chip browse.php — causa CACHE (definitivo)
La regola .chip e' gia' corretta/presente; il "link senza bordo" era la vecchia CSS
in cache. Aggiunto `?v=20260616` al link di allonwheel_style.css in **74 pagine**:
il browser e' costretto a riscaricare il foglio aggiornato. Per i prossimi rilasci
CSS basta incrementare il numero.

## 2) SMTP attivato (lato codice)
- PHPMailer 6.9.3 vendorizzato in `libs/PHPMailer/src/` (+ LICENSE), struttura pulita.
- `libs/mailer.class.php`: require guardato -> SMTP attivo con MAIL_TRANSPORT=smtp;
  fallback mail() intatto. `mail.env.example` aggiornato.
- DA FARE (tu): compilare SMTP_* e MAIL_FROM nel `.env` fuori webroot.

## 3) i18n — fondamenta architettura /en/
- `config/i18n.php` (t(), aow_locale(), aow_locale_url(), aow_hreflang_tags()),
  `lang/en.php` + `lang/it.php`, hook in `config/bootstrap.php`, rewrite in `.htaccess`
  (/en/, /it/ -> file reale, locale in env AOW_LOCALE; default = inglese).
- Non distruttivo: URL attuali invariati. Adozione incrementale con t('chiave','EN').

## 4) hero_image — codice pronto
- `browse.php`: legge product_macros.hero_image e mostra l'immagine in cima al box
  intro (classe .macro_hero, responsive) se valorizzato; altrimenti nulla.
- `sql/Changelog/macro_hero_image.sql`: template idempotente da compilare.
- DA FARE (tu): carica 5 immagini in es. images/macros/ (manuale, dir. 15), metti i
  path nel template SQL e lancialo.

## 5) Header — menu Marketplace snellito (tua richiesta)
Rimosse le 5 voci per-famiglia dal dropdown (ridondanti col filtro a chip su
browse.php). Restano **All listings** e **Request a quotation**. Commenti aggiornati.

## Verifica
- php -l su tutti i PHP del delta -> No syntax errors.
- PHPMailer rilevato v6.9.3; i18n EN/IT testato; cache-bust 74/74; hero render OK;
  dropdown = 2 voci. CRLF preservati; .htaccess/mail.env.example restano LF.
- images/ e upload_image/ non toccati.

## Applicazione
Sovrascrivere ai percorsi indicati. Compilare il `.env` per SMTP. Nessuna ALTER DB.
