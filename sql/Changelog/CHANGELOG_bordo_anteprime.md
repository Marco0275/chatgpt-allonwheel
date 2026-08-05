# Allonwheel - FIX bordo anteprime sfalsato + 220x150 con proporzioni
2026-07-09. Base: V2_2 (file reali dal server). Un solo ZIP. CRLF.
CSS bilanciato (450/450). PHP lint 786/786 OK.

## Il problema (screenshot my_posts.php)
Il bordo attorno all'anteprima era sfalsato: seguiva una forma diversa
dall'immagine. Causa: il bordo era messo sull'IMG (dimensione/proporzioni
variabili) invece che su un contenitore fisso; inoltre le anteprime usavano
object-fit:cover (che RITAGLIA, non mantiene le proporzioni).

## Analisi - tutte le anteprime e le classi
Due famiglie di anteprime nel sito:
1. LISTE (my_posts, browse, view_ads): markup .gallery > li > a.pirobox > img
   -> il bordo sfalsato era QUI.
2. SCHEDE (view_ad, scheda azienda, preview): .gallery_box > .gallery > li > a > img.
I thumbnail sono generati a 220x150 da UploadHelper.

## Fix - UNIFORME per tutte le anteprime (allonwheel_style.css)
Principio: **il bordo va sul CONTENITORE fisso 220x150, non sull'immagine.**
  .gallery li a            -> box 220x150, overflow:hidden, border 1px,
                              border-radius, box-shadow, box-sizing:border-box
  .gallery li a img        -> width/height 100%, object-fit:contain, border:0
Stessa logica applicata a .gallery_box .gallery li (schede) e adattata a
.ad_detail (vista dettaglio, box fino a 280px).
Risultato: il bordo e' SEMPRE un rettangolo 220x150 perfetto; l'immagine sta
dentro con le PROPORZIONI MANTENUTE (object-fit:contain, mai ritagliata, mai
sforata). Se le proporzioni non combaciano restano bande bianche, ma il bordo
non e' mai piu' sfalsato. Verificato con immagini 1200x400, 500x500, 220x150.

## Fix - generazione thumbnail (8 file annunci)
Gli upload annuncio passavano thumb_crop=true (RITAGLIO al centro -> proporzioni
perse). Portati a thumb_crop=false: resize proporzionale "fit" dentro 220x150,
come da tua richiesta ("resize a 220x150 mantenendo le proporzioni").
NON toccati logo/profilo azienda/utente (li' il crop quadrato ha senso).
Nota: i thumbnail GIA' caricati restano come sono, ma col nuovo CSS
(object-fit:contain) vengono comunque mostrati corretti dentro il box, senza
bordo sfalsato. I nuovi upload avranno anche il file gia' proporzionato.

## File (9)
allonwheel_style.css
02_free_ads/: 02_01_upload_gallery, 02_01_upload_advertising_modified,
  02_01_upload_ad_image, 02_01_modify_upload_gallery
03_ads/: 03_01_upload_advertising_modified, 03_01_modify_upload_gallery,
  03_01_upload_gallery, 03_01_upload_ad_image

## Test
1. Apri my_posts.php: il bordo di ogni anteprima e' un rettangolo pulito
   220x150, allineato, con l'immagine dentro senza deformazioni.
2. Vale anche per browse, scheda annuncio, scheda azienda, preview.
3. Carica una nuova foto annuncio: il thumbnail mantiene le proporzioni.
