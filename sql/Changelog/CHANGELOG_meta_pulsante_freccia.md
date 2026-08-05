# Allonwheel - Fix footer card annuncio (.post_meta) + rimozione freccia - 2026-06-29

Base: il TUO allonwheel_style.css caricato (con le tue modifiche manuali).
Un solo file. CRLF. Nessun `?v=`. Graffe bilanciate (470/470).

## Causa dello sbordamento (perche' non ci riuscivi)
La "sezione con linee trasversali" (.post_meta) ereditava dal vecchio template
un background a **altezza fissa 30px** (templatemo_footer_bottom.jpg, repeat-x
center): il pulsante-pill e' piu' alto di 30px, quindi usciva dalla banda.
I tentativi manuali o azzeravano il tratteggio (background:none) o lasciavano
**blocchi-regola vuoti** (#templatemo_content .post_box .post_meta {} x4 e
#templatemo_wrapper .gallery_box .post_meta {} x5). Inoltre il pulsante aveva
`padding:0px 30px` -> nessun padding verticale (pill schiacciato).

## Fix
1. **Banda tratteggiata contenitiva** (solo card annuncio, scope
   `.post_box:has(.gallery.m0) .post_meta`):
   - `display:flex; align-items:center; justify-content:space-between` ->
     autore/data a sinistra, pulsante a destra, **centrati verticalmente**;
   - `height:auto` -> la banda cresce quanto serve: il pulsante ci sta DENTRO;
   - tratteggio con **repeating-linear-gradient 135deg** (scala con l'altezza,
     niente immagine a 30px fissi);
   - margini negativi (-22/-20) per andare a **tutta larghezza** fino ai bordi
     della card, che ha `overflow:hidden`+border-radius -> **angoli inferiori
     arrotondati** ereditati, nessuno sbordamento;
   - la nav admin e le altre `.post_meta` restano SENZA tratteggio (invariate).
2. **Freccia rimossa** dai pulsanti: `a.more::after, button.more::after ->
   content:none` (base + guardia !important). Tolta anche la regola di
   posizionamento della freccia ormai inutile.
3. **Pulsante uniforme**: padding `9px 22px` (simmetrico, con padding verticale),
   al posto di `0px 30px`.
4. Rimossi i 9 blocchi-regola vuoti lasciati dai tentativi.

## Note
- Scope volutamente ristretto alle card annuncio (`.gallery.m0`) per non
  tratteggiare la nav admin (che usa lo stesso `.post_box .post_meta`).
- Se vuoi la stessa banda anche nella **scheda annuncio singola** (view_ad,
  struttura .ad_detail diversa) dimmelo e la estendo.
- Il tratteggio usa due grigi tenui (#eaeff4/#dfe6ee); se lo vuoi piu' marcato
  o di un altro colore, cambio i due valori.
