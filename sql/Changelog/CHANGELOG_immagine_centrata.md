# Allonwheel - Anteprima: immagine centrata nel box - 2026-07-09

Un solo ZIP. CRLF. Un solo file (allonwheel_style.css). CSS bilanciato.
Verificato con getComputedStyle reale (jsdom): immagine centrata H e V.

## Richiesta
Immagine centrata rispetto al box dell'anteprima.

## Causa per cui NON era centrata
Il contenitore .gallery li a era gia' flex con centratura, ma la regola base
`.post_box img { float:left; margin-right:40px; }` spingeva l'immagine a
sinistra/alto dentro il box, vanificando il centraggio.

## Fix
Sull'immagine dentro il contenitore anteprima (.gallery li a img e
.gallery_box .gallery li img) aggiunto esplicitamente:
  float:none; margin:0; display:block;
Cosi' restano attive solo le regole del contenitore flex:
  display:flex; align-items:center; justify-content:center;
+ object-fit:contain; object-position:center sull'immagine.
Risultato: l'immagine (proporzioni mantenute) e' centrata sia in orizzontale
sia in verticale nel box 220x150, col bordo perfettamente allineato.

## Verifica
getComputedStyle reale: float=none, margin-right=0px, align-items=center,
justify-content=center, object-position=center -> CENTRATA: SI.

## File (1)
allonwheel_style.css

## Test
Apri my_posts.php (e browse / scheda annuncio / scheda azienda): l'anteprima
mostra l'immagine centrata nel riquadro, proporzioni intatte, bordo allineato.
