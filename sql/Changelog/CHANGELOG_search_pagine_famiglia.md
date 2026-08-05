# Allonwheel — CORREZIONE: campo Search nelle pagine famiglia
16 lug 2026. Sostituisce SOLO shared/family_page.php del pacchetto
"Allonwheel_pagine_famiglia.zip". Tutti gli altri file di quel pacchetto
restano validi e vanno applicati come sono.

===========================================================
PERCHE' QUESTA CORREZIONE
===========================================================
Nel pacchetto precedente avevo TOLTO il campo Search dalle pagine famiglia,
motivandolo con "una pagina dedicata non si filtra". Era una mia deduzione,
sbagliata: la tua regola e' che il campo Search c'e' in TUTTE le pagine
tranne index e _admin.

Verificato sul codice, la tua regola descrive esattamente lo stato reale:
  index.php                        0 campi Search  (corretto)
  _admin/*                         0 campi Search  (corretto)
  19 pagine di contenuto in radice  1 campo Search ciascuna
  le mie 4 pagine famiglia          0  <-- le UNICHE fuori standard
Corretto: ora il campo c'e' anche li'.

Ho anche riletto meglio la tua frase: "confermo niente ALTRI filtri ad
esclusione delle sidebar". Il Search non e' uno degli "altri filtri": e'
la ricerca, ammessa ovunque. Le faccette (macro/vtype/cond/prezzo) restano
fuori dal corpo pagina e vivono solo nelle sidebar. Dir. 21 rispettata.

===========================================================
COSA FA IL CAMPO NELLE PAGINE FAMIGLIA
===========================================================
Cerca DENTRO l'argomento della pagina, esattamente come gia' fanno
road_vehicles.php, special_vehicles.php e shelter_container.php
(form action="" + link "Clear"). Cosi' le pagine famiglia sono coerenti
con le pagine dedicate che avevi gia'.
La famiglia resta FISSA e non modificabile via URL: la ricerca restringe,
non cambia argomento.

Verifiche fatte:
- segnaposto SQL vs parametri: q vuoto -> 2 ? / 2 param; q valorizzato ->
  8 ? / 8 param. Nessun mismatch (sarebbe stato un errore fatale a runtime).
- ordine dei bind sulla UNION: ramo1 (macro + 3 like), ramo2 (macro + 3 like).
- canonical: punta sempre all'URL pulito della pagina, anche con ?q=, cosi'
  le ricerche non generano URL indicizzati doppi.
- lint PHP 8.3 OK, tag bilanciati, CRLF.

NON ho toccato road_vehicles.php / special_vehicles.php: nel pacchetto
precedente non erano modificati e vanno bene come sono.

===========================================================
FILE IN QUESTO ZIP (1)
===========================================================
shared/family_page.php   (sovrascrive quello del pacchetto precedente)

## Ordine
1. Applica prima "Allonwheel_pagine_famiglia.zip" (tutti i file).
2. Poi sovrascrivi shared/family_page.php con questo.
3. Prova /race_trailers.php: campo Search presente in alto a destra,
   alto ~38px col pulsante rosso dentro; cercando restringe agli annunci
   di quella famiglia; "Clear" torna alla pagina pulita.

===========================================================
RESTANO APERTE 2 DECISIONI TUE (non le ho toccate)
===========================================================
1. Badge Free/Premium sulle card: le pagine famiglia NON lo mostrano,
   browse.php SI. Dimmi da che parte allineare.
   (Mio parere: toglierlo. Dir. 13 dice che la differenza vive in
   inserimento, e al compratore non serve sapere chi ha pagato.)
2. shelter_container.php filtra per `item_kind`, le altre 4 famiglie per
   `product_macro`: due meccanismi diversi. Un annuncio con
   product_macro='shelter-container' ma item_kind diverso non comparirebbe.
   Unificarlo tocca i dati (dir. 9): serve il tuo ok.
