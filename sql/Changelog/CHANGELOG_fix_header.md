# Allonwheel — FIX header (menu non visibile)

Data: 2026-06-24

## Problema
La barra scura dell'header era alta solo quanto il wordmark; il menu (HOME,
MARKETPLACE, ...) sforava SOTTO la barra, su sfondo chiaro: essendo bianco,
risultava invisibile.

## Causa
Le voci di menu (ddsmoothmenu) hanno i <li> in `float:left`. I float **collassano
l'altezza** del contenitore: il flex dell'header non li conteggiava e il menu
fuoriusciva dalla barra.

## Fix (in coda a allonwheel_style.css)
- Il menu diventa un vero **flex** (niente float sui <li> di primo livello):
  `#templatemo_header .ddsmoothmenu > ul{ display:flex }` + `> li{ float:none }`.
- La barra ha **altezza minima** su desktop: `@media (min-width:861px){ #templatemo_header{ min-height:64px; align-items:center } }`.
- Mobile invariato (colonna, menu centrato).
- I sottomenu (ul annidati) restano block e gestiti dal JS ddsmoothmenu (nessun
  `!important` sul loro display, così l'apertura/chiusura funziona).

## File
- `allonwheel_style.css` — versione corretta (sostituisce quella in uso).

## Verifiche
- Graffe bilanciate (404/404). CRLF. Anteprima `preview_header_fix.html` con il
  vero cascade (float base + fix che vince): brand e menu entrambi nella barra.
