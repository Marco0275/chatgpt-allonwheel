# Allonwheel - M2: Attivazione fornitori - 2026-07-06

Secondo blocco del piano v1.1. Un solo ZIP. CRLF. PHP 8.3 lint OK (12 file).
ORDINE: eseguire PRIMA la patch SQL, poi caricare i file.

## 1. Percorso "primo annuncio in 10 minuti" (8 pagine wizard)
Indicatore di avanzamento testuale su entrambi i wizard (free 02_ e premium 03_),
solo markup esistente, nessun CSS nuovo:
- Step 1 of 4 - Choose the listing type (select_type)
- Step 2 of 4 - Describe your vehicle (insert_ad)
- Step 3 of 4 - Main photo (insert_ad_image) + nota "Optional: you can add
  photos later from My posts" -> il fornitore capisce che puo' pubblicare
  subito e completare dopo (riduce l'abbandono del wizard)
- Step 4 of 4 - Photo gallery (insert_ad_gallery) + stessa nota

## 2. Email di benvenuto (01_login/verify.php)
Alla verifica dell'account parte l'email "Welcome to All on Wheel" con i 3
passi (pubblica il primo annuncio / registra l'azienda in directory / oppure
richiedi un preventivo), con link diretti + link a My posts.
Best-effort in try/catch: se l'SMTP non e' ancora configurato, la verifica
dell'account NON si blocca (l'email usa Mailer -> fallback mail()).

## 3. Badge "Founding partner" (programma lancio)
- **SQL** `sql/Changelog/2026-07-06_founding_partner.sql`: colonna
  `founding_partner` TINYINT(1) default 0 su `06_company` (run-once, MySQL 5.7).
- **Admin** `_admin/edit_company.php`: checkbox "Founding partner badge
  (launch program)" accanto ad Active; salvata su add e update.
- **Directory**: badge dorato "* Founding partner" (classe esistente
  `.badge_premium`) accanto al badge Certified; blocco badge unificato.
- **Scheda azienda**: stesso badge sotto il titolo.
Assegnazione: la fai tu da admin ai fornitori del programma concierge.

## Parte commerciale di M2 (non-codice, dal piano v1.1)
Il seeding concierge resta lavoro tuo: 15 contatti/settimana di qualita',
concentrati su race-trailer + hospitality fino a >=25 annunci ciascuna
(rimedio 1: densita' prima della larghezza). Il codice qui sopra ti da':
wizard piu' fluido da mostrare al telefono, welcome email automatica,
badge da promettere ai founding.

## File (12 PHP + 1 SQL)
01_login/verify.php | 02_free_ads/{02_00_select_type,02_insert_ad,
02_insert_ad_image,02_insert_ad_gallery}.php | 03_ads/{03_00_select_type,
03_insert_ad,03_insert_ad_image,03_insert_ad_gallery}.php |
_admin/edit_company.php | 06_company/{06_30_company_directory,
06_02_view_company}.php | sql/Changelog/2026-07-06_founding_partner.sql

Prossimo blocco al tuo "procedi": **M3 - Conversione RFQ** (CTA sopra la
piega, blocco fiducia, rate-limit anti-abuso, pagina di successo con next steps).
