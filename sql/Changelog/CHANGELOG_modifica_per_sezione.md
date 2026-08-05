# Allonwheel — Modifica annuncio divisa per sezione (Road / Special / Shelter)
23 lug 2026. UN SOLO ZIP. CRLF. PHP lint 287/287 OK. Base: Allonwheel_3_3_variabili.

I vecchi file di modifica sono stati dimenticati come chiesto: questi sono
nuovi, costruiti sul wizard di inserimento di QUESTA build.

=================================================================
PRIMA COSA TROVATA: il link "Edit" era rotto
=================================================================
01_login/my_posts.php punta a 02_modify_insert_ad.php e
03_modify_insert_ad.php, che in questa build NON esistono piu': cliccando
Edit si finiva su un 404. Risolto (vedi "smistatore" piu' sotto), senza
toccare my_posts.php.

=================================================================
COME SONO DIVISE LE SEZIONI
=================================================================
La sezione si ricava dalla classificazione dell'annuncio, la stessa scelta
allo step 1 del wizard:
   item_kind = shelter_container          -> SHELTER
   item_kind = vehicle + macro = road     -> ROAD
   item_kind = vehicle + macro = special  -> SPECIAL

Cosa vede ciascuna (verificato eseguendo il codice):

  ROAD     assi: SI · tipi: i 24 stradali · campi tecnici: 40/52
           esclusi: veranda, gavone, cucina, letti, bagno, SAT (sono da
           paddock o abitativi) e telemetria/TV (da corsa).

  SPECIAL  assi: SI · tipi: i 9 speciali · campi tecnici: 48/52
           e' la sezione piu' completa: race trailer, hospitality, motorhome,
           uffici e laboratori mobili usano tutti i gruppi.

  SHELTER  assi: NO · tipo: fisso (Shelter & Container) · tecnici: 35/52
           e' una struttura statica: niente telaio (assi, MGW, ralla, step
           deck) e niente sponda idraulica; niente veranda ne' gavone, che
           sono da rimorchio. Restano impianti, arredo, finiture e misure.

=================================================================
ARCHITETTURA (perche' non 6 file copia-incolla)
=================================================================
Sei file di modifica scritti a mano divergono al primo ritocco. Qui:

 libs/ad_section_fields.class.php   NUOVO. Fonte UNICA: dichiara quali
     variabili appartengono a quale sezione (campi base e gruppi tecnici).
     Se domani cambi idea su un campo, lo cambi qui e vale ovunque.
 shared/ad_modify_page.php          NUOVO. La pagina di modifica, una sola
     implementazione: proprieta', validazione, layout.
 shared/ad_modify_fields.php        NUOVO. I campi base, filtrati per sezione,
     con lo stesso markup del form di inserimento.
 shared/tech_details_fields.php     MODIFICATO. Aggiunto un filtro di sezione
     FACOLTATIVO: se chi include non lo imposta, il partial si comporta
     esattamente come prima -> configuratore RFQ e PDF invariati.

I sei file di sezione sono volutamente sottili (3 righe utili):
  02_free_ads/02_modify_road.php      03_ads/03_modify_road.php
  02_free_ads/02_modify_special.php   03_ads/03_modify_special.php
  02_free_ads/02_modify_shelter.php   03_ads/03_modify_shelter.php

=================================================================
LO SMISTATORE (link esistenti salvi)
=================================================================
02_modify_insert_ad.php e 03_modify_insert_ad.php sono ricreati come
smistatori: leggono la classificazione dell'annuncio dal DB e reindirizzano
al file della sezione giusta. Cosi' i link gia' presenti nel sito continuano
a funzionare e non ho dovuto modificare my_posts.php.

Se apri direttamente la pagina sbagliata (es. 02_modify_shelter.php per un
veicolo stradale) vieni rimandato a quella corretta: non si modifica un
annuncio con i campi di un'altra sezione.

=================================================================
COERENZA CON L'INSERIMENTO
=================================================================
- Stessi campi, stesse etichette, stesse classi CSS del form di inserimento
  (title, subtitle, list price, misure in metri, type, conditions,
  description). Zero stili inline, zero classi nuove: verificate una per una.
- La SEZIONE non si cambia dalla modifica, esattamente come nell'inserimento
  dove si decide allo step 1: item_kind e macro_category viaggiano hidden.
  Il tipo veicolo si puo' affinare, ma solo dentro la macro dell'annuncio
  (niente piu' tendine che mescolavano Road e Special).
- La famiglia (product_macro) resta DERIVATA dall'handler, non scelta a mano.

=================================================================
GLI HANDLER RISPETTANO LA SEZIONE
=================================================================
02_01_upload_advertising_modified.php e 03_01_upload_advertising_modified.php:
la sezione viene ricavata dalla classificazione salvata (NON da cio' che
arriva nel POST) e i campi non pertinenti non vengono salvati. Esempio: su
uno shelter, axles_n resta NULL anche se qualcuno forzasse il parametro a
mano. Il salvataggio della scheda tecnica premium resta sul suo handler
(03_02_upload_tech_advertising_modified.php), come nel resto del sito:
il form tecnico e' separato da quello dei dati annuncio.

=================================================================
FILE IN QUESTO ZIP (14)
=================================================================
NUOVI (11)
  libs/ad_section_fields.class.php          mappa variabili per sezione
  shared/ad_modify_page.php                 pagina di modifica condivisa
  shared/ad_modify_fields.php               campi base filtrati
  02_free_ads/02_modify_road.php            ingresso sezione Road (free)
  02_free_ads/02_modify_special.php         ingresso sezione Special (free)
  02_free_ads/02_modify_shelter.php         ingresso sezione Shelter (free)
  02_free_ads/02_modify_insert_ad.php       smistatore free
  03_ads/03_modify_road.php                 ingresso sezione Road (premium)
  03_ads/03_modify_special.php              ingresso sezione Special (premium)
  03_ads/03_modify_shelter.php              ingresso sezione Shelter (premium)
  03_ads/03_modify_insert_ad.php            smistatore premium
MODIFICATI (3)
  shared/tech_details_fields.php            filtro sezione (facoltativo)
  02_free_ads/02_01_upload_advertising_modified.php   rispetta la sezione
  03_ads/03_01_upload_advertising_modified.php        rispetta la sezione

## Come verificare
1. My posts -> Edit su un annuncio stradale: la tendina "Vehicle type" mostra
   SOLO i 24 tipi road; c'e' il campo Axles.
2. Edit su uno shelter: NON c'e' il campo Axles, il tipo e' fisso
   "Shelter & Container", e nella scheda tecnica non compaiono i gruppi
   Chassis e Lift facilities.
3. Edit su un annuncio speciale premium: ci sono tutti i gruppi tecnici.
4. Apri a mano 02_modify_shelter.php?id_ads=<id di un annuncio stradale>:
   vieni reindirizzato a 02_modify_road.php.
