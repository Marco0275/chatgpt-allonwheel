# Allonwheel v0.0.13 — Delta i18n: pagine di testo (Conditions, FAQ, portfolio)

Completa la "coda lunga" dell'i18n dei corpi pagina. Da applicare sul baseline V0.0.13.
Contenuti REALI (non segnaposto) → tradotti, non riscritti.

## Conditions.php
- Include i18n guardato in cima (title nel <head>). Wirati: title, page title,
  heading "General rules", **6 regole** del sito e il back link.
- Le regole contengono `<strong>` e un link `mailto`: per queste uso `t()` (output
  raw) invece di `te()`, mantenendo l'HTML; i valori sono statici e nostri (no XSS).
- +9 chiavi `cond.*`.

## FAQ.php
- Include i18n guardato. Wirati: title/page title (riuso `nav.faq`), **6 domande** e
  **6 risposte**, back link (riuso `about.back`).
- Le risposte erano spezzate su più `<div>` a metà frase (quirk templatemo):
  **consolidate** in un'unica chiave per risposta e tradotte in modo scorrevole.
  Bilanciamento `<div>` preservato (collassi netto-zero).
- +12 chiavi `faq.*`.

## portfolio.php
- Galleria data-driven; il suo PiroBox era gia' corretto (href=originale, src=thumbnail).
- Wirati: title, page title (riuso `nav.portfolio`); le **3 intestazioni di sezione**
  (Road / Special / Shelter) ora passano da `t()` riusando `b2b.road`, `b2b.special`,
  `macro.shelter` (aggiunta una `key` per sezione in `$sections`); i **2 messaggi
  empty-state**.
- +2 chiavi `port.*`. I dati immagine restano dal DB (non tradotti).

## Verifica
- `php -l` OK sui 5 file. Rendering IT testato su tutte e tre. `<div>` bilanciati.
  CRLF preservati. Nessuna modifica a DB/query/`images/`.

## Stato i18n corpi pagina — COMPLETO sulle pagine pubbliche
Fatti: header, footer, index, browse, contact, RFQ, **Conditions, FAQ, portfolio**,
about + what_we_do (riscritte). Tassonomia (vtype + servizi) completa buyer+supplier.

## Debito residuo (non i18n di testo)
- Search box legacy (`value="Search"` + `clearText`) ancora presente su Conditions/FAQ/
  portfolio/about/what_we_do/contact: da uniformare a quella pulita (passaggio dedicato).
- Ramo vtype "regular" della directory (label da tabella DB `vehicle_types` per slug):
  richiede un set keyed-by-slug separato (dump tabella).
