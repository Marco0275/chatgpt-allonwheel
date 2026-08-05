# Allonwheel - M4: Email lifecycle (ricerca salvata + digest) + rate-limit rivisto
2026-07-06. Quarto blocco del piano v1.1. Un solo ZIP. CRLF. PHP 8.3 lint OK.
ORDINE: eseguire PRIMA la patch SQL, poi caricare i file, poi configurare il cron.

## 0. Revisione richiesta: rate-limit RFQ -> 1 a settimana per IP
`04_send_offer.php`: la soglia passa da 5/ora a **1 richiesta ogni 7 giorni per
IP** (stesso hash GDPR, nessun IP in chiaro; fail-open se il DB non risponde).
Nota tecnica da tenere presente: IP aziendali/uffici condivisi (NAT) colpiscono
la stessa soglia - se in futuro vedessi lamentele di utenti legittimi bloccati,
si puo' passare a 1/settimana per email invece che per IP: dimmelo e lo cambio.

## 1. Ricerca salvata con alert email (il "motivo per tornare")
- **SQL** `2026-07-06_saved_searches.sql`: tabella `saved_searches`
  (utente, email, famiglia, testo, frequenza daily/weekly, token unsubscribe,
  last_sent_at). Collation e tipi allineati al DB (utf8mb4, INT UNSIGNED).
- **browse.php**: sotto le chip dei filtri, box "Get an email when new
  matching listings arrive" con tendina *As they arrive / Weekly digest* e
  pulsante Save - visibile SOLO quando c'e' un criterio attivo (famiglia o
  testo) e l'utente e' loggato; per gli ospiti, invito al login. Il
  salvataggio stesso e' il consenso (azione esplicita), disiscrizione
  one-click in ogni email.
- **saved_search_save.php** (nuovo): POST+CSRF+login; macro whitelistata
  (`ProductMacro::exists`), testo max 120c; dedupe per (utente, macro, testo)
  con riattivazione; email presa dalla sessione con fallback dal DB.
- **cron/saved_search_alerts.php** (nuovo): per ogni ricerca "in scadenza"
  (daily >20h, weekly >6 giorni) cerca gli annunci **approved** pubblicati
  dopo l'ultimo invio che matchano famiglia/testo (UNION free+premium, stessi
  vincoli di visibilita' di browse) e invia SOLO se ce ne sono (max 10 titoli
  con prezzo e link assoluti + link "See all" + unsubscribe). `last_sent_at`
  si aggiorna sempre: niente doppioni.
  Esecuzione: **crontab** `0 7 * * * php /percorso/cron/saved_search_alerts.php`
  oppure via URL `cron/saved_search_alerts.php?key=<CRON_KEY>` (imposta la
  variabile d'ambiente `CRON_KEY` nel .env: senza chiave l'URL risponde 403).
- **saved_search_unsubscribe.php** (nuovo): disiscrizione one-click dal token
  (pagina brand con header/footer, noindex, idempotente).

## 2. Il digest settimanale per famiglia
E' la stessa meccanica con `freq=weekly`: l'utente sceglie "Weekly digest"
nella tendina. Oggetto email: "Weekly digest: <famiglia>".

## Prerequisito infra (dal piano, task tuo - NON incluso qui)
Le email di M2/M3/M4 usano `Mailer` (SMTP da env, fallback `mail()`).
**Senza SMTP autenticato (SPF+DKIM+DMARC sul dominio) finiranno in spam**:
configura le env `SMTP_*`/`MAIL_*` nel `.env` e i record DNS, poi verifica
con mail-tester.com (obiettivo >=9/10). E' il prerequisito del gate di lancio.

## File (5 PHP + 1 SQL)
browse.php | saved_search_save.php (NUOVO) | saved_search_unsubscribe.php
(NUOVO) | cron/saved_search_alerts.php (NUOVO) |
04_request_offer/04_send_offer.php | sql/Changelog/2026-07-06_saved_searches.sql

## Test rapidi
1. Da loggato, filtra una famiglia su browse -> box "Save this search" ->
   salva: flash verde di conferma; riga in `saved_searches`.
2. Pubblica (o approva) un annuncio di quella famiglia; lancia il cron a mano:
   `php cron/saved_search_alerts.php` -> arriva l'email con l'annuncio;
   rilancialo subito -> "no_news" (niente doppioni).
3. Click su Unsubscribe nell'email -> pagina di conferma; il cron non invia piu'.
4. Invia 2 RFQ nello stesso giorno dallo stesso IP -> la seconda va in retry.

Prossimo blocco al tuo "procedi": **M5 - Misurazione** (dashboard KPI admin
`_admin/kpi.php` con baseline: registrati, annunci, RFQ, tassi/tempi di
risposta, sorgenti lead da `source_page`).
