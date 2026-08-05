# Allonwheel - FIX: immagine piu' grande del box nella scheda annuncio - 2026-07-08

Un solo ZIP. CRLF. Un solo file modificato (allonwheel_style.css).
CSS bilanciato. Nessuna modifica a markup, DB o immagini (dir. 15).

## Il problema (dallo screenshot "Edit premium ad")
La foto principale della scheda annuncio sforava il box che la contiene (il
logo con la banda gialla usciva dai bordi).

## Causa - individuata nel CSS (non nel markup)
La scheda annuncio (shared/view_ad.php) avvolge il contenuto in `.ad_detail`.
La regola:

    .ad_detail .gallery_box .gallery li img{ width:100%; max-width:560px; height:auto; }

forzava l'immagine al 100% della colonna (fino a 560px) con altezza
automatica. I thumbnail reali sono 220x150 (generati da UploadHelper): la
regola li STIRAVA fino a 560px (~2,5x), facendo sforare il logo dal box.
Questa regola vinceva sulle altre per specificita' (.ad_detail in piu').

## Fix
    .ad_detail .gallery_box .gallery li img{
      display:block; width:auto; height:auto;
      max-width:100%; max-height:200px; object-fit:contain; ...
    }

- `width:auto; height:auto` -> l'immagine resta alla sua dimensione naturale
  (220x150), niente stiramento;
- `max-width:100%` -> non supera MAI la larghezza del box (fix del sintomo);
- `max-height:200px` -> tetto di sicurezza in altezza;
- `object-fit:contain` -> nessuna deformazione se il logo non e' proporzionato.

In piu', due reti di sicurezza globali (non guastano nulla):
- `.post_box img{ max-width:100%; height:auto; }`
- `.gallery_box .gallery li img{ ... max-width:100%; ... }`
Cosi' nessuna immagine potra' piu' sforare il proprio contenitore in nessuna
pagina.

## File (1)
allonwheel_style.css

## Test
Apri una scheda annuncio (free o premium): la foto principale sta dentro il
box, senza sforare, non deformata. Vale anche per preview e scheda azienda.
