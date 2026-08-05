# Allonwheel - Fix apertura immagine originale (piroBox) - 2026-06-29

## Sintomo
Cliccando un'immagine in 02_free_ads/02_gallery.php?id_ads=74 (e pagine simili)
il lightbox non mostra l'immagine alle dimensioni originali.

## Diagnosi (cosa ho verificato, tutto OK)
- HTML live corretto: image_original popolato (gal_74_*.png), href agli originali
  in /upload_image/02_free_ads/original/ ben formati; thumbnail corretti.
- Path identico e coerente su shared/gallery.php, view_ad.php, view_ads.php e
  con il codice di upload. Nessun originale mancante (16 thumb = 16 original).
- .htaccess di upload_image nega solo i .php, NON le immagini.
- JS: site_init.js inizializza piroBox; il plugin lega i click sia ad
  a.pirobox sia ad a[class^="pirobox_gall"]; start_pirobox() e' invocata
  incondizionatamente. Init e binding corretti.

## Causa
Conflitto CSS: la regola globale `img{ max-width:100%; height:auto }`
(allonwheel_style.css) cascata anche sull'immagine DENTRO il lightbox
(`.pirobox_content img`, che nel CSS del plugin non dichiara max-width/height).
Risultato: l'immagine viene vincolata al contenitore invece di aprirsi a
dimensione piena. Effetto piu' marcato su mobile, dove il viewport stringe il
contenitore del lightbox (coerente con i test su cellulare).

## Fix (solo CSS, globale)
Esentata l'immagine del lightbox dal cap globale:
  .pirobox_content img, .pirobox_content .c_c img, .pirobox_content .c_c div img{
    max-width:none !important; height:auto !important;
  }
Vale per TUTTE le pagine con piroBox (gallery free/premium, scheda annuncio,
gallery azienda): un'unica correzione, "anche le altre pagine".
I thumbnail della griglia (.gallery li img) NON sono toccati.

## Note
- Nessun `?v=` (il CSS si aggiorna via no-cache nell'.htaccess).
- Se dopo questo aggiornamento il problema persistesse, dimmi ESATTAMENTE cosa
  vedi cliccando l'immagine (su desktop): (a) compare lo sfondo scuro ma niente
  immagine, (b) compare un riquadro piccolo/vuoto, (c) un testo "There seems to
  be an Error", (d) non succede nulla. Quel dettaglio isola l'eventuale causa
  residua (lato server). In quel caso utile anche un'occhiata alla console (F12)
  per eventuali errori in rosso.
