# Allonwheel v0.0.12 — Delta: traduzione form RFQ (04_request_offer.php)

Corpo a piu' alto valore tra quelli rimasti: il form di richiesta offerta (funnel lead).
Da applicare sopra i delta i18n precedenti.

## 04_request_offer/04_request_offer.php
- Aggiunto **include i18n guardato** in cima (path `/../config/i18n.php`), perche'
  il `<title>` e il `page_title` sono nel <head>, prima dell'header (riga 44).
- 11 stringhe wirate con `te()`: titolo, page title, intro, label form
  (Name/Email/Object/Message -> riuso chiavi `contact.*`), i due heading di sezione
  ("Vehicle body types", "Special categories"), il **testo di consenso GDPR**,
  i bottoni "Send request" e "Reset".
- **Non tradotte (corretto):** le etichette delle categorie veicolo nelle checkbox
  vengono dai cataloghi della classe (`06_company.class`) -> tassonomia, da gestire
  in un blocco i18n dedicato (label `vtype` Road/Special).

## lang/en.php + lang/it.php
- +6 chiavi `rfq.*` (EN sorgente, IT tradotto). Tutte le chiavi del form presenti
  nei due dizionari (incl. accenti/apostrofi: "sara'", "affinche'", "un'offerta").

## Verifica
- `php -l` OK; rendering IT testato; CRLF preservati.

## Avanzamento i18n corpi pagina
Fatti: header, footer, index.php, browse.php, contact.php, **04_request_offer.php**.
Restano (testo lungo, contenuto reale): FAQ.php, Conditions.php, portfolio.php.
Da **riscrivere** prima di tradurre: about.php, what_we_do.php.
Blocco separato consigliato: traduzione delle **label tassonomia** (vtype Road/Special)
usate da browse, directory e RFQ.
