# Allonwheel - Responsive mobile - 2026-06-25

## Stato di partenza (gia' presente)
Base responsive solida: viewport su tutte le pagine, wrapper fluido
(max-width:1180px), content+sidebar che impilano <=860px, menu off-canvas
hamburger, breakpoint a 1000/860/560/520px, immagini con max-width:100%.

## Buchi chiusi (solo CSS, in coda al foglio)
- **Tabelle dati admin** (`.admin_table`, 12 pagine, 8+ colonne): su <=860px
  diventano scrollabili orizzontalmente (display:block + overflow-x:auto +
  white-space:nowrap) -> non rompono piu' il layout sul telefono.
- **Larghezze fisse del vecchio template** rese fluide ovunque:
  `#comment_section` (618px), textarea RFQ `#contact_form` (638px), e le
  tabelle-messaggio `width="566"` (success/verify/login error/reset) -> tutte
  cap a `max-width:100%`.
- **Campi form** (input/select/textarea): `max-width:100%` + box-sizing ->
  niente sforamenti.
- **Media** (img/video/table/pre/iframe): cap globale a `max-width:100%`.
- **<=520px**: titoli di contenuto piu' compatti (senza toccare il brand
  nell'header), chip-bar famiglie/faccette a scroll orizzontale invece di
  andare a capo, tap target dei pulsanti piu' comodi.

## Verifica
- Scansione di tutte le pagine pubbliche: nessuna larghezza inline/attributo
  residua che sfori (le `width="566"` sono coperte dal cap globale tabelle).
- Le tabelle RFQ usano `tbl_collapse` (gia' 100%, escluse dallo scroll).
- Seller dashboard usa liste (nessuna tabella). CSS bilanciato.

Niente `?v=` (il CSS si aggiorna via no-cache nell'.htaccess gia' consegnato).
Da provare su telefono reale: home, browse, scheda annuncio, directory, form
RFQ, area admin. Se un punto specifico sfora, segnalalo e lo affino mirato.
