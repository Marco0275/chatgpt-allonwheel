# Allonwheel v0.0.12 — Delta: fix stile chip browse.php + P1 (redirect 301)

## A) Fix regressione: filtri (chip) di browse.php
Convertendo i chip da stile inline a classe `.chip` (consolidamento CSS) il "pill"
arrotondato non compariva nel tuo deploy. Causa: `allonwheel_style.css` con il blocco
`.chip` non allineato / cache. **Risolto in modo robusto**:
- `allonwheel_style.css`: regola `.chip` irrobustita — `display:inline-block`, sfondo
  bianco esplicito e **specificità alta** (`.macro_filter .chip`, `a.chip:link/:visited`)
  così vince su qualunque stile generico dei link; aggiunto stato `:hover`.
- `browse.php`: invariato rispetto al consolidamento (usa `class="chip"`), incluso qui
  per garantire che CSS e markup viaggino insieme.
> Dopo il deploy fai un **hard refresh** (Ctrl/Cmd+Shift+R) per scaricare il CSS aggiornato.

## B) P1 — Consolidamento IA e link morti
- **`00_first/` (11 pagine legacy)** → trasformate in **stub di redirect 301** verso le
  pagine reali, con `X-Robots-Tag: noindex`. File conservati (non cancellati a freddo):
  - racing_trailer, box_trailer → `?macro=race-trailer`
  - paddock_trailer, hospitality → `?macro=hospitality`
  - roadshow, motorhome, mobilhome, motorhome_mobilhome → `?macro=custom-projects`
  - service, sell_or_rent, why-rent → `/browse.php`
- **`02_view_ads.php` / `03_view_ads.php`** → **301 a `/browse.php`** + noindex (lista
  unificata, dir. 14). I flussi interni che vi puntavano (preview/confirm/"back to list")
  reggono il redirect.
- **`footer.php`**: rimossi i link "Browse free/premium ads" (free/premium non è asse di
  navigazione, dir. 14). Colonna *Browse* = All listings + Road/Special/Shelter + Quotation;
  colonna *Marketplace* = famiglie motorsport + directory/portfolio/blog.

### Nota su "riferimenti residui 00_first"
Non restano **link** a pagine `00_first`: le occorrenze in `header.php`, `sidebar_*`,
`footer.php`, `session_helper.php` sono **array di rilevamento base-path** (cartelle note),
non hyperlink. Innocue (gli stub fanno `exit` prima di includere header/sidebar). Lasciate
intatte; nessuna modifica silenziosa.

## Verifica (doppio passaggio, dir. 2/10)
- `php -l` su tutti i file (16) → No syntax errors.
- CRLF preservato ovunque. Stub: nessun output prima di `header()`.
- `upload_image/` e `images/` non toccati.

## Ordine di applicazione
Sovrascrivere i file mantenendo i percorsi; hard refresh del browser per il CSS.
Nessuna modifica DB.
