# Allonwheel — Wanted: sidebar uniforme + matching per vehicle_type
17 lug 2026. Un solo ZIP. CRLF. PHP lint 795/795 OK. SQL 5.7.
Le due cose che avevo lasciato in sospeso sul punto 5.

===========================================================
1) SIDEBAR SULLE PAGINE WANTED (dir. 17)
===========================================================
Tutte e 4 le pagine (list, post, manage, view) usavano #no_sidebar, cioe'
larghezza piena senza sidebar. Per la dir. 17 una sezione con opzioni proprie
deve avere la sidebar. Convertite al layout standard delle pagine di sezione:
  #templatemo_content (650px, float-left) + #templatemo_sidebar (280px,
  float-right) con include_sidebar.php + un .cleaner prima del footer.

Nota importante: NON ho creato una sidebar wanted dedicata, e volutamente.
include_sidebar.php e' UNIFORME sito-wide (decisione 5 lug): mostra ovunque
gli stessi box (ricerca Special/Road + faccette + box utente). Le pagine
wanted ora ricevono quella, coerente con tutto il resto del sito. Se un
domani vorrai box specifici del wanted in sidebar, si aggiungono li' una
volta e valgono ovunque.

Verificato: div bilanciati in tutte e 4 (11/11, 10/10, 11/11, 13/13);
sequenza content -> sidebar -> cleaner -> footer identica a
shelter_container.php (pagina di sezione funzionante); il .cleaner chiude i
float, altrimenti il footer risalirebbe accanto alla sidebar.

NON toccata la lista-categorie nel corpo di wanted_list (i link
All|RaceTrailer|...). E' una navigazione per categoria, non un filtro-form:
la dir. 21 riguarda i filtri. Se la vuoi comunque spostare in sidebar,
dimmelo, ma e' un'altra cosa.

===========================================================
2) MATCHING WANTED <-> ANNUNCI PER VEHICLE_TYPE
===========================================================
Prima il match era solo per MACRO. Ora, simmetrico a quanto fatto per i
venditori (sellersForMacro), anche i buyer sono ordinati per pertinenza:
chi cerca il vehicle_type ESATTO dell'annuncio viene notificato per primo;
chi cerca solo la macro resta comunque notificato (il vtype raffina
l'ordine, non esclude nessuno).

Catena aggiornata:
 - _admin/moderate_ads.php: la SELECT dell'annuncio ora prende anche
   vehicle_type e lo passa a notifyBuyers.
 - notifyBuyers($macro, ..., $vtype): nuovo parametro, passato oltre.
 - activeWantedForMacro($macro, $ex, $vtype): (w.vehicle_type = :vt) come
   booleano 0/1, ORDER BY relevance DESC, created_at DESC.
SQL verificato 5.7-valido; binding vt/m/ex coerenti.

===========================================================
UN BUCO REALE CHE HO TROVATO (e non forzo)
===========================================================
notifyBuyers scatta SOLO sull'azione "approve" in _admin/moderate_ads.php.
Ma gli annunci nascono status='approved' (DEFAULT) e NON passano dalla
moderazione: sono pubblici all'istante. Conseguenza: oggi notifyBuyers,
in pratica, non viene MAI chiamato sul percorso normale -> i buyer con una
wanted attiva NON vengono avvisati dei nuovi annunci corrispondenti. Il
matching che ho appena raffinato e' pronto ma non si attiva.

La correzione naturale: chiamare notifyBuyers anche alla PUBBLICAZIONE, cioe'
nel wizard dopo l'INSERT riuscito (02_01_upload_advertising.php, subito
accanto al punto dove gia' cancello la bozza). Non l'ho fatto ORA perche':
 a) tocca di nuovo il file fragile del wizard, e in un solo giro ho gia'
    fatto molto li' (punto 2);
 b) va deciso se notificare a OGNI pubblicazione o solo quando l'admin
    approva - il che dipende da se attiverai la moderazione obbligatoria
    (oggi 'approved' di default). Sono due prodotti diversi.
Con la moderazione com'e' adesso, la scelta pulita e' una riga in fondo
all'INSERT del wizard. Dimmi "vai" e la aggiungo, con la stessa cautela
del punto 2 (blocco additivo, non bloccante, INSERT invariato).

STATO DEI 5 PUNTI
 1. seo_head ..... decaduto
 2. Registrazione dopo il wizard .. COMPLETO (free)
 3. Fan-out ...... targeting + punteggio + tetto FATTI. Claim 24h: da fare.
 4. Faccette ..... FATTO. Tecniche: tua decisione.
 5. Wanted board .. allineato + sidebar + matching vtype. Resta: agganciare
                    notifyBuyers alla pubblicazione (1 riga nel wizard).

===========================================================
FILE IN QUESTO ZIP (6)
===========================================================
05_wanted/wanted_list.php     no_sidebar -> content + sidebar
05_wanted/wanted_post.php     idem
05_wanted/wanted_manage.php   idem
05_wanted/wanted_view.php     idem
libs/wanted_ads.class.php     notifyBuyers + activeWantedForMacro con vtype
_admin/moderate_ads.php       passa vehicle_type a notifyBuyers

## Come verificare
1. Apri /05_wanted/wanted_list.php: ora ha la sidebar a destra (ricerca +
   faccette + box utente), come le pagine annuncio. Stessa cosa su post,
   manage, view. Il footer resta sotto, non risale.
2. Matching: da _admin approva un annuncio con un vehicle_type; i buyer con
   una wanted su quel vehicle_type esatto vengono notificati per primi.
   (Sul percorso normale la notifica non parte ancora: vedi "buco reale".)
