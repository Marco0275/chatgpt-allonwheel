# Allonwheel - Ottimizzazione CSS (sito corrente) - 2026-06-29

Un solo file: allonwheel_style.css (CRLF, nessun `?v=`).
Dimensione: 59.986 -> 59.109 byte (-877). Regole: 507 -> 491. Righe: 1562 -> 1504.

## Metodo (sicuro)
Verificato che il pacchetto e' la fonte COMPLETA del sito (777 file PHP): una
classe assente dal sorgente non e' resa sul sito live. Rimosse solo regole
(a) strutturali legacy COMPLETAMENTE sovrascritte dal layer RESTYLE 2026 con
!important, oppure (b) residui del vecchio template templatemo verificati non
usati in NESSUN file. Restyle, .chip/.filter_chip, fix piroBox e layer
responsive: intatti. Graffe bilanciate (491/491). Nessuna regola vuota.

## Rimosse (16)
Strutturali legacy gia' sovrascritte dal RESTYLE (valore vinto da !important):
- #templatemo_wrapper (width:960px) -> restyle width:auto/max-width:1180px
- #templatemo_header (width:900px + background url(images/templatemo_header.jpg))
  -> restyle barra scura; elimina anche una richiesta immagine 404
- #templatemo_content (width:650px) -> restyle width:calc(100% - 332px)
- #templatemo_sidebar (width:280px) -> restyle width:300px
Residui vecchio template (non usati in alcun file):
- #templatemo_slider, #templatemo_main (vecchia home)
- .Stile1, .Stile2 (classi auto-generate Dreamweaver)
- .image_frame, .image_fl, .image_fr (vecchie classi immagine)
- .col_3 (vecchia griglia; .col_4 mantenuta perche' usata)
- .h30, .h40, .h50, .h60 (spaziatori non usati; .h10/.h20 mantenuti)

## NON rimosse: candidate a una potatura piu' profonda (serve tua conferma)
Queste classi NON risultano usate nel sorgente attuale, ma sono FEATURE che hai
costruito (o utility form) e potresti voler ricollegare: non le tolgo da solo.
Confermami quali eliminare e le rimuovo:
- Ricerca a faccette: .facets .fac_grp .fac_h .fac_row .fac_price .fac_actions .fac_reset
- Menu mobile off-canvas: .nav_toggle .nav_chk .nav_scrim  (rilevante per il
  lavoro responsive in corso: l'header attuale usa ddsmoothmenu, senza toggle)
- Chip directory: .fam_bar .fam_chip (oggi la chip viva e' .filter_chip)
- Wrapper chip macro: .macro_filter (ATTENZIONE: la .chip generica e' usata,
  quindi va tolto solo il wrapper, non .chip)
- Utility form: .success_msg/.success-msg .pref-grid .section-title .select-all-row
- .aow-title-navy (titolo navy, sostituibile inline)

## Note
- Il grosso del file e' codice VIVO (base templatemo ancora in uso in parte +
  RESTYLE): oltre a questo, l'unica leva e' rimuovere le candidate qui sopra
  (tua conferma) o minificare - sconsigliato, romperebbe commenti/CRLF/leggibilita'.
- Il footer legacy NON e' stato toccato: contiene clear:both (non rimpiazzato dal
  restyle); il suo background immagine e' gia' neutralizzato da #0a131f !important
  (nessun 404).
