# Allonwheel — Restyle grafico 2026 (ispirazione: schuler-trucks.com)

Data: 2026-06-24

## Approccio (da UI/UX + frontend)
Restyle a **rischio minimo**: invece di riscrivere 267 pagine, ho **appeso un layer
"RESTYLE 2026" a `allonwheel_style.css`** che ridisegna le classi/ID gia' esistenti
(header, nav, wrapper, content, sidebar, post_box, bottoni, tabelle, footer). Tutte le
pagine attuali ne beneficiano automaticamente, sia quelle **con** sidebar
(#templatemo_content + #templatemo_sidebar) sia quelle **senza** (#no_sidebar).

## Sistema di design
- **Colori**: ink #0e1a2b (header/footer), brand navy #1b2a41, accento **rosso
  motorsport #e4002b** (CTA/attivo), sfondo #f4f6f9, card bianche, bordi #e3e8ef.
- **Tipografia**: stack di sistema (nessun webfont -> niente modifiche alla CSP);
  titoli maiuscoli, bold, con tracking; corpo 15px piu' leggibile.
- **Layout**: wrapper centrato max 1180px e **responsive** (prima era 960px fisso);
  content+sidebar fluidi che **si impilano** su mobile; griglie a 1/2/3-4 colonne.
- **Componenti**: header scuro con wordmark, nav ddsmoothmenu ridisegnato (dropdown
  scuri), card con ombra e hover, bottoni a pillola (.more navy, .btn_accent rosso,
  .btn_ghost), sidebar a card, footer scuro multi-colonna.

## Nota tecnica sul menu
`ddsmoothmenu.css` e' caricato DOPO `allonwheel_style.css`: per vincere la cascata ho
usato selettori a specificita' maggiore (`#templatemo_header .ddsmoothmenu ...`).
jQuery 1.3.2 e ddsmoothmenu restano invariati (solo restyle CSS).

## File
- `allonwheel_style.css` — invariato fino in fondo, poi blocco **RESTYLE 2026** appeso
  (tokens, layout, header/nav, card, sidebar, footer, componenti home, responsive).
- `header.php` — aggiunto il **wordmark** "ALL ON WHEEL" + tagline (il logo era un link
  vuoto). Chiave `brand.tagline` con fallback.
- `index.php` — **home ridisegnata**: hero a tutta larghezza con CTA, griglia delle 5
  macro a card immagine, 3 value props, sezione B2B (Road/Special/Professionals),
  CTA band, ultimi annunci a card. Riusa le immagini esistenti in `images/00_first/`
  e `product_macros.hero_image` (dir.15: nessun asset modificato). Chiavi lang nuove
  con fallback EN (home.hero_*, home.vp*, home.cta_*, home.fam_sub, home.b2b_sub,
  home.all_h).
- `template_no_sidebar.php` / `template_with_sidebar.php` — **scaffold** moderni per
  nuove pagine (con e senza sidebar), gia' allineati al restyle.
- `bump_css_version.sh` — incrementa il cache-busting del CSS (`?v=20260616` ->
  `?v=20260625`) su tutte le pagine, per forzare il refresh nei browser. Da eseguire
  una volta sul server.

## Da fare lato server (Marco)
1. Sostituire `allonwheel_style.css` (contiene il restyle) e `header.php`, `index.php`.
2. Eseguire `bump_css_version.sh` dalla root (o svuotare la cache) per vedere subito il
   nuovo look su tutte le pagine.
3. Le immagini restano quelle attuali: per la home al meglio, valorizza
   `product_macros.hero_image` con foto 16:9 di buona qualita'.

## Note
- Gli unici stili inline introdotti sono i `background-image:url(...)` **dinamici**
  (immagini da DB/righe variabili), inevitabili in CSS statico: sono limitati a
  hero/card immagine. Tutto il resto e' in `allonwheel_style.css`.
- Full-project `php -l`: 269 file, 0 errori. CRLF preservati.

## Possibili passi successivi (su tua parola)
- Variante header **full-bleed** (barra scura edge-to-edge) invece che contenuta.
- Webfont (es. Inter/Archivo) con aggiornamento CSP per titoli ancora piu' "Schuler".
- Restyle mirato di pagine specifiche (browse, scheda annuncio, directory) con
  componenti dedicati.
