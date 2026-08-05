# Allonwheel v0.0.12 — Delta: riscrittura about.php + what_we_do.php (contenuti reali + i18n)

Le due pagine contenevano testo SEGNAPOSTO del template (agency demo:
"shaken not stirred", "Office Cake", "Brand 360"...). Sostituito con **contenuti
reali allineati al brand**, gia' bilingue (EN sorgente + IT), wirati con `te()`.
Da applicare sopra i delta i18n precedenti.

> ⚠️ I testi sono una **prima stesura** redatta sul modello di business noto
> (marketplace + directory paddock motorsport + allestimenti B2B Road/Special,
> motore di preventivi). **Nessun dato inventato** (niente "20 anni", numeri o
> certificazioni fittizie). Rivedili/adattali pure: cambiare il copy ora e' solo
> editing dei valori in `lang/en.php` (EN) e `lang/it.php` (IT), nessun tocco al markup.

## about.php — "Chi siamo"
Body riscritto: Chi siamo · Costruito attorno al paddock (5 famiglie) · Oltre il
paddock (Road/Special) · Marketplace e directory · Perche' All on Wheel (3 punti) · CTA.
- Include i18n guardato in cima (il `<title>` e' nel <head>); title, page title,
  back link e tutto il corpo wirati. +16 chiavi `about.*`.
- Filler legacy azzerato; struttura `<div>` preservata (bilanciamento invariato).

## what_we_do.php — "Cosa facciamo"
Body riscritto: Cosa facciamo · Per gli acquirenti · Per allestitori e fornitori ·
Il motore di preventivi (con nota GDPR) · Cosa copriamo (3 punti) · CTA.
- Stesso wiring (+15 chiavi `wwd.*`). Rimossi due `<div>` wrapper vuoti del template
  (regione netto-zero: bilanciamento preservato). Filler azzerato.

## lang/en.php + lang/it.php
- +31 chiavi totali (16 about + 15 wwd). Tutte presenti nei due dizionari (verificato).

## Nota (debito separato)
Entrambe usano ancora la **search box legacy** (`value="Search"` + `clearText`),
diversa da quella pulita di home/browse. Da uniformare in un passaggio dedicato.

## Verifica
- `php -l` OK sui 4 file. Rendering IT testato. `<div>` bilanciati. CRLF preservati.
  Nessuna modifica a `images/` (nessun asset referenziato dalle nuove pagine).
