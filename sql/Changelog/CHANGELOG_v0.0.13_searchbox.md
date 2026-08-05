# Allonwheel v0.0.13 — Delta: uniformazione search box legacy

Sostituita ovunque la vecchia search box templatemo (`value="Search"` + hack
`clearText`/JS inline `onfocus`/`onblur`) con la versione pulita di home/browse:
`placeholder` + attributi i18n, niente JavaScript di pulizia. **52 file PHP.**

## Cosa cambia nell'input
PRIMA:
  <input type="text" value="Search" name="q" ... title="searchfield"
         onfocus="clearText(this)" onblur="clearText(this)" />
DOPO:
  <input type="text" name="q" size="10" id="searchfield"
         title="<?php te('search.listings','Search listings'); ?>"
         placeholder="<?php te('search.placeholder','Search…'); ?>" />

## Tre categorie gestite
1. **Decorative** (action="#": about, contact, FAQ, Conditions, portfolio,
   what_we_do, blog*, flussi 01_login/02/03/04, register_company, template_*…):
   rimosso `value="Search"` e clearText, aggiunto placeholder i18n. 37 + 7 file
   (incl. la variante con JS inline di `01_login/all_about_me.php`).
2. **Self-search funzionali** (listing): `road_vehicles`, `special_vehicles`,
   `shelter_container`, `shared/view_ads`: **mantenuto** il pre-fill della query
   corrente (`$search`/`$q`), eliminato solo il fallback "Search" + clearText,
   aggiunto placeholder. Comportamento di ricerca invariato.
3. **Directory fornitori** (`06_30_company_directory.php`): pre-fill `$search`
   mantenuto; placeholder/title dedicati "Search suppliers" → nuova chiave
   `search.suppliers` (IT "Cerca fornitori").

## Dizionari
- +1 chiave: `search.suppliers`. (Riusate le esistenti `search.listings`,
  `search.placeholder`.)
- **NB:** `lang/en.php` e `lang/it.php` qui sono cumulativi: includono anche le
  chiavi del delta i18n-text precedente (cond.* / faq.* / port.*). Applicare questi.
- Anche `Conditions.php`, `FAQ.php`, `portfolio.php` qui **superano** le versioni del
  delta i18n-text: contengono sia la traduzione testo sia la search box pulita.

## Note
- Le `action` dei form NON sono state toccate: le ricerche decorative restano
  `action="#"` (comportamento invariato). Se vuoi renderle operative puntandole a
  `browse.php` come la home, è una modifica separata di una riga per file.
- La funzione JS `clearText` resta definita ma ora **non è più usata** da alcun input.

## Verifica
- `php -l` OK su tutti i 52 file + i 2 dizionari. Rendering IT verificato
  (Cerca tra gli annunci / Cerca… / Cerca fornitori). 0 residui legacy
  (`clearText(this)`, `value="Search" name="q"`, `title="searchfield"`).
  CRLF preservati. Nessuna modifica a DB/query/`images/`.
