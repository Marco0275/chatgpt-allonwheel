# Allonwheel v0.0.12 — Delta: traduzione contact.php + nota su about/what_we_do

Da applicare sopra i delta i18n precedenti.

## contact.php — form reale tradotto
- Aggiunto in cima un **include i18n guardato** (`if(!function_exists('t'))...`),
  perche' contact.php carica l'header solo a riga 36 ma il `<title>` e' nel <head>.
- 10 stringhe wirate con `te()`: titolo, intro (2 paragrafi), "Send us a message",
  label del form (Name/Email/Object/Message) e bottoni (Send/Reset).
- Default inglese; su `/it/` il form compare in italiano.

## lang/en.php + lang/it.php
- +10 chiavi `contact.*` (EN sorgente, IT tradotto). Tutte presenti nei due dizionari.

## ⚠️ about.php e what_we_do.php — NON tradotti (richiedono RISCRITTURA)
Entrambe contengono **testo segnaposto legacy del template** (agency demo:
"Brain: shaken not stirred", "Office Cake", "Brand 360", "20 years as a brand
agency"...), non contenuto reale di Allonwheel. Tradurlo alla lettera sarebbe
inutile e fuorviante. Vanno **riscritte** con copy reale (storia azienda, cosa fa
davvero il marketplace), poi tradotte. Posso preparare io una bozza EN+IT su tua
indicazione dei contenuti, oppure passami i testi.
*Nota tecnica:* entrambe usano ancora la vecchia search box legacy
(`value="Search"` + `clearText`), debito separato da uniformare.

## Verifica
- `php -l` OK; rendering IT testato; CRLF preservati.

## Avanzamento i18n corpi pagina
Fatti: header, footer, index.php, browse.php, **contact.php**.
Da fare (UI reale): FAQ.php, Conditions.php, portfolio.php, 04_request_offer.php.
Da **riscrivere** prima di tradurre: about.php, what_we_do.php.
