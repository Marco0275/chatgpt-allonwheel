# Allonwheel — Fix cancella foto + coerenza modifica/inserimento
22 lug 2026. UN SOLO ZIP. CRLF. PHP lint 720/720 OK. Base: V3.2.

Tre punti segnalati. Tutti risolti, piu' un bug grave trovato strada facendo.

=================================================================
1) "Request not allowed" cancellando una foto dalla gallery
=================================================================
CAUSA: mismatch di token CSRF. Il progetto ha DUE varianti:
  csrf_generate()/csrf_verify()                       -> $_SESSION['csrf_token']
  csrf_generate_persistent()/csrf_verify_persistent() -> $_SESSION['csrf_persistent_token']
Sono due chiavi di sessione DIVERSE. La pagina gallery emette il token
PERSISTENTE (csrf_generate_persistent), ma 02_02_delete_image_gallery.php
verificava con la variante ONE-SHOT -> il confronto falliva SEMPRE.

Verificato che era l'unico fuori riga: 02_01_upload_gallery.php e
02_01_modify_upload_gallery.php usano gia' csrf_verify_persistent(). E tutti
e tre i chiamanti (02_insert_ad_gallery, 02_modify_insert_ad_gallery,
03_modify_insert_ad_gallery) generano il token persistente.

FIX: 02_02_delete_image_gallery.php ora usa csrf_verify_persistent().
Il persistente e' anche l'unica scelta corretta qui: nella pagina ci sono PIU'
form (uno per foto + upload) e un token one-shot, consumato dal primo submit,
invaliderebbe tutti gli altri.
NB: il premium (03_02_delete_image_gallery.php) fa require di questo file,
quindi e' risolto anche li'.

=================================================================
2) Posizione delle stringhe "Step N of ..."
=================================================================
VERIFICATO su tutto il sito (70 file con struttura content_top +
templatemo_content): NESSUNA stringa intro e' fuori posto. Tutte quelle con
quella formattazione stanno DENTRO #templatemo_content, come nel file di
riferimento 02_insert_ad_gallery.php (titolo in #content_top, stringa dopo
l'apertura di #templatemo_content).
Ho tenuto la stessa regola per tutto cio' che ho aggiunto in questo giro.

=================================================================
3) 02_modify_insert_ad.php deve rispecchiare 02_insert_ad.php
=================================================================
PROBLEMA: la modifica non rispecchiava l'inserimento.
 - una sola tendina "Vehicle type" con optgroup Road E Special MESCHIATI:
   si poteva trasformare un veicolo Road in Special (impossibile in
   inserimento, dove il tipo e' vincolato alla macro scelta allo step 1);
 - una tendina "Family" (product_macro) scelta A MANO, mentre l'inserimento
   la DERIVA con ProductMacro::forAd();
 - mancavano item_kind e macro_category (presenti in inserimento);
 - mancavano i campi misura (length/width/height/axles).

FIX (free e premium, form e handler):
 - item_kind e macro_category ora sono campi HIDDEN presi dall'annuncio,
   esattamente come l'inserimento li passa dal wizard;
 - la tendina Vehicle type e' FILTRATA sulla macro dell'annuncio: niente piu'
   Road e Special insieme. Se l'annuncio e' uno shelter, il tipo e' fisso
   (nessuna tendina), come fa l'inserimento;
 - la tendina Family e' RIMOSSA: product_macro e' derivato con
   ProductMacro::forAd(), stessa chiamata dell'inserimento;
 - l'handler valida con VehicleTaxonomy::isValidType($tipo, $macro, $pdo),
   la stessa guardia dell'inserimento;
 - aggiunti i 4 campi misura in cm, precompilati, con la STESSA validazione
   ($aow_dim: intero, cap 65000 / 20 assi, vuoto -> NULL);
 - UPDATE esteso con item_kind, macro_category e le 4 misure.

Confronto campi DOPO il fix (inserimento vs modifica):
  identici. La modifica ha in piu' solo 'id_ads' (quale annuncio) e
  'ad_image' (sostituzione immagine), che sono suoi propri.

Simulato: macro=road + tipo racing_trailer -> RESPINTO; macro=special +
racing_trailer -> accettato. forAd: shelter+racing -> shelter-container
(la priorita' shelter regge).

=================================================================
BUG GRAVE TROVATO E CORRETTO (non segnalato, ma rompeva l'inserimento)
=================================================================
In V3.2 il form di inserimento aveva i campi dimensionali DUPLICATI: una
serie in CENTIMETRI (length_cm...) e una in METRI (length_m...), con
axles_n presente DUE VOLTE nello stesso form. Nell'handler c'erano due
blocchi di lettura sovrapposti: il secondo ($aow_dim sui campi _cm)
sovrascriveva il primo, quindi i campi in metri erano chiesti all'utente ma
IGNORATI, e il doppio axles_n rendeva imprevedibile quale valore arrivasse.

E' l'esito di una fusione tra la mia implementazione (metri) e quella nativa
V3.2 (centimetri). Ho tenuto quella NATIVA V3.2 (cm, con cap sui limiti di
colonna) e rimosso i miei duplicati, sia dal form sia dall'handler. Ora ogni
misura e' chiesta una volta sola e lo stesso schema vale in modifica.

=================================================================
FILE IN QUESTO ZIP (7)
=================================================================
02_free_ads/02_02_delete_image_gallery.php         FIX CSRF persistente
02_free_ads/02_insert_ad.php                       rimossi campi duplicati
02_free_ads/02_01_upload_advertising.php           rimosso blocco duplicato
02_free_ads/02_modify_insert_ad.php                rispecchia l'inserimento
02_free_ads/02_01_upload_advertising_modified.php  idem (handler)
03_ads/03_modify_insert_ad.php                     idem premium
03_ads/03_01_upload_advertising_modified.php       idem premium (handler)

## Verifiche fatte
- lint 720/720; div bilanciati (26/26, 26/26, 24/24)
- UPDATE bilanciati: 25 placeholder = 25 binding (free e premium)
- tutte le classi usate sono require-ate (ProductMacro, VehicleTaxonomy)
- CRLF su tutti i file

## Come verificare
1. Inserisci un annuncio, arriva alla gallery, cancella una foto: funziona.
2. Nel form di inserimento le misure compaiono UNA volta sola (in cm).
3. Modifica un annuncio Road: la tendina Vehicle type mostra SOLO tipi Road.
   Non c'e' piu' la tendina "Family". Le misure sono precompilate.
4. Modifica un annuncio shelter: il tipo resta Shelter & Container.
