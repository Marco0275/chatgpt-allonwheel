# Allonwheel - Header ripristinato, sidebar uniformi (tendine), home, freccia, piroBox
2026-07-05. Base: V_1_5_Ultime_restyling. Un solo ZIP. CRLF. Nessun `?v=`.
PHP 8.3 lint OK. JS sintassi OK. CSS bilanciato (446/446).

## 1. Header riportato al penultimo step
Rimossi da `header.php` il checkbox (`.nav_chk`), l'hamburger (`.nav_toggle`) e
l'overlay (`.nav_scrim`) del menu mobile off-canvas; rimosso dal CSS l'intero
blocco relativo (27 regole). Il checkbox che vedevi era il toggle del menu
mobile: senza il CSS aggiornato sul server restava visibile come checkbox nudo.
Ora non esiste piu': header identico allo step precedente (solo ddsmoothmenu).

## 2. Sidebar uniformi su tutte le pagine con sidebar
`include_sidebar.php` non smista piu' per pagina: ora TUTTE le pagine con
sidebar mostrano, nell'ordine:
  1. **Special vehicles** - MENU A TENDINA (niente checkbox) con i tipi da
     `vehicle_types` (macro 'special') -> cerca su `special_vehicles.php`;
  2. **Road vehicles** - tendina gemella (stesse caratteristiche, macro 'road')
     -> cerca su `road_vehicles.php`;
  3. **Box utente login-aware** (`sidebar_user_box.php`, invariato):
     loggato -> My account/logout; ospite -> Login.
Nuovo partial `sidebar_vtype_search.php`: solo classi esistenti (sb_box,
submit_btn), base-path automatico (funziona anche dalle sottocartelle),
preselezione della tendina quando sei gia' su road/special con un tipo attivo,
query unica con try/catch (la sidebar non puo' rompere la pagina).
I vecchi `sidebar_<pagina>.php` restano su disco, semplicemente non piu'
richiamati: per tornare al modello per-pagina basta ripristinare il vecchio
`include_sidebar.php`. Rimosso dal CSS anche il pannello faccette a checkbox
(8 regole), sostituito dalle tendine.

## 3. Home - "Latest from the marketplace": immagini visibili
Causa trovata: `.lc_img` e' uno `<span>` (elemento inline) con `height:130px`
- l'altezza sugli inline viene IGNORATA -> il riquadro immagine collassava a 0
e restava solo il testo. Fix: `display:block` su `.listing_card .lc_img` ->
le 4 card mostrano la foto (background cover) con il titolo sotto.

## 4. Freccia ">" sui pulsanti .more
Nel CSS di questa base la freccia e' GIA' rimossa due volte (blocco base
`content:none` + guardia finale `content:none !important`): verificato che non
esistano altre sorgenti (nessun carattere > nei testi dei pulsanti .more).
Se online la vedi ancora, il server sta servendo un CSS vecchio: questo file
la elimina. (I "View >" nelle card della home sono link delle card, non
pulsanti .more: lasciati come da richiesta.)

## 5. piroBox: BUG TROVATO E PROVATO EMPIRICAMENTE (test jsdom + jQuery 1.3.2 reali)
Catena del bug (riprodotta al 100% in test automatico):
  1. Un'immagine "original" mancante/rotta -> `onerror` mostra l'errore ma NON
     rimuove la classe `loading`;
  2. La chiusura con la X o l'overlay era dentro `if($(img).is(':visible'))`:
     con l'errore a schermo l'immagine NON e' visibile -> il gate fallisce ->
     `loading` resta attivo;
  3. Da quel momento `load_img` esce subito (`if(main_cont.is('.loading')) return`)
     -> OGNI click su OGNI annuncio apre un box vuoto = "pirobox non apre
     l'immagine originale", su tutti gli annunci, per tutta la sessione.
Fix chirurgico in `js/piroBox.1_2.js` (2 punti, jQuery 1.3.2 invariato):
  - `onerror` ora rimuove subito `loading`;
  - la chiusura (X/overlay) ora chiude e sblocca SEMPRE, pulendo anche il
    messaggio d'errore.
Test ripetuto post-fix: dopo un errore il lightbox si chiude e la riapertura
mostra di nuovo le immagini. Restano validi i fix precedenti (esenzione
`.pirobox_content img` dal cap globale).
Consiglio: gli annunci con file "original" mancante restano quelli che mostrano
il messaggio d'errore (ora innocuo) - se me li segnali, bonifico i record.

## File (5)
allonwheel_style.css | header.php | include_sidebar.php |
sidebar_vtype_search.php (NUOVO) | js/piroBox.1_2.js
Sovrascrivere mantenendo i percorsi.

## Test rapidi
1. Header: nessun checkbox, menu come prima.
2. Qualsiasi pagina con sidebar: tendina Special + tendina Road + box utente.
3. Home: le 4 card "Latest" mostrano le foto.
4. Annunci: click sull'anteprima -> lightbox con l'originale; se un'immagine
   e' rotta, la X chiude e il click successivo funziona comunque.
