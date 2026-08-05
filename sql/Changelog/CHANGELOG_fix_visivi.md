# Allonwheel - Fix visivi (audit Portfolio) - 2026-06-24

Da screenshot Portfolio: barra tratteggiata + freccia "MORE" sballata.

## Cause individuate (CSS)
1. `.post_meta` conservava il background del template originale
   (`images/templatemo_footer_bottom.jpg` repeat-x) e `height:30px`: il restyle
   sovrascriveva margini/bordo ma NON il background/altezza -> barra a righe
   su ~35 pagine (portfolio, listing, schede).
2. `a.more::after` (la freccia ">") era ancorata a `top:0` del vecchio bottone;
   sul nuovo pill (padding/altezza diversi) finiva in alto a destra.
3. `.sb_list li a` puntava a `templatemo_list.png` (asset assente) -> 404 inutile.

## Correzioni (solo CSS, additive, in coda al foglio)
- `.post_meta{ background:none; height:auto; min-height:0 }` -> separatore pulito
  (resta il `border-top` gia' previsto dal restyle).
- Freccia `.more` centrata verticalmente (`top:50%; translateY(-50%)`) con
  padding destro adeguato.
- Rimosso il background-image 404 da `.sb_list li a`.

Header / footer / bottom / post_box: gia' correttamente resettati dal restyle
(verificato) -> nessun intervento necessario.

CSS bumpato a **?v=20260704** su tutte le pagine. Nessun file PHP modificato
nella logica (solo cache-buster).
