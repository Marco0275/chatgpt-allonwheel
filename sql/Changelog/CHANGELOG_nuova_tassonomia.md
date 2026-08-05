# Allonwheel — Nuova tassonomia: Road da codice della strada, Special da admin
24 lug 2026. UN SOLO ZIP. CRLF. PHP lint 291/291 OK.
Patch SQL provata importando il tuo dump reale.

Questa logica SOSTITUISCE tutte le regole precedenti.

=================================================================
LA REGOLA, UNA VOLTA SOLA
=================================================================
  ROAD     -> tabella `vehicle_types`
              la lista estratta dal CODICE DELLA STRADA italiano.
              Elenco chiuso di riferimento, non si cura a mano.

  SPECIAL  -> tabella `special_types`  (NUOVA)
              la lista decisa dall'AMMINISTRATORE. Puo' contenere voci
              scritte a mano e voci DUPLICATE da vehicle_types.

  SHELTER  -> ancora `special_types`
              uno shelter e' lo stesso allestimento speciale, costruito su
              container invece che su un veicolo: stessa lista.

Nessun file decide piu' da solo dove pescare le tipologie: si chiede a
VehicleTaxonomy::typesForCategory(). Se domani cambi idea, si cambia li'.

=================================================================
1) DATABASE
=================================================================
sql/Changelog/2026-07-24c_special_types.sql
 - crea `special_types`, identica a vehicle_types piu' una colonna
   `source_slug` (ricorda da quale voce road una riga e' stata duplicata:
   cosi' vedi l'origine e non la ridupliqui per sbaglio);
 - MIGRA in special_types le 7 voci oggi marcate 'special' dentro
   vehicle_types (copia prima, cancella dopo: nessun dato perso);
 - lascia vehicle_types con le sole 24 voci road;
 - inserisce la voce `on_demand` ("On demand").
Idempotente: UNIQUE sullo slug + INSERT IGNORE.

Provata sul tuo dump: prima 24 road + 7 special -> dopo 24 road in
vehicle_types e 8 voci in special_types (7 migrate + On demand).

=================================================================
2) ADMIN: la lista Special e la duplicazione che hai chiesto
=================================================================
_admin/admin_special_types.php (NUOVA, voce di menu "Special types")
 - elenco delle tipologie speciali: rinomina, riordina, elimina;
 - aggiunta manuale di una nuova voce;
 - "Copy from the road list": le voci di vehicle_types compaiono con una
   CHECKBOX e si copiano in special_types in un colpo solo. Quelle gia'
   copiate non vengono riproposte.
Protezione: non si elimina una tipologia ancora usata da un annuncio (il
messaggio dice quanti annunci la usano), altrimenti resterebbero annunci
con una classificazione inesistente.

=================================================================
3) FILE DI INSERIMENTO E DI RICHIESTA
=================================================================
02_free_ads/02_00_select_type.php (wizard)
 - lo step 3 pesca dalla tabella della categoria scelta, non piu' da una
   colonna macro dentro un'unica tabella;
 - lo SHELTER non e' piu' terminale: ora sceglie anche lui la tipologia,
   dalla stessa lista degli special.

shared/category_hierarchy.php (usato da RFQ e Wanted)
 - stessa fonte dati del wizard, tramite VehicleTaxonomy;
 - shelter usa la lista special.

=================================================================
DUE ERRORI TROVATI NEL WIZARD MENTRE LO ADATTAVO
=================================================================
a) Lo step 3 non portava `item_kind` nel form, e l'handler lo forzava a
   'vehicle'. Finche' lo shelter era terminale non si notava; ora che passa
   dallo step 3, uno shelter sarebbe stato salvato come VEICOLO. Corretto:
   il form porta item_kind e l'handler lo legge.
b) (falso allarme che ho verificato) il tag hidden di macro_category era
   gia' chiuso correttamente: l'avevo visto troncato in una mia stampa.

=================================================================
"ON DEMAND"
=================================================================
La voce esiste in special_types con slug `on_demand` ed e' accettata come
scelta valida in qualunque categoria (VehicleTaxonomy::isValidForCategory):
e' la via d'uscita per chi non si riconosce negli elenchi. Comparira' quindi
in fondo alle liste Special/Shelter delle pagine di richiesta.

=================================================================
FILE IN QUESTO ZIP (6)
=================================================================
NUOVI
  sql/Changelog/2026-07-24c_special_types.sql   tabella + migrazione
  _admin/admin_special_types.php                gestione + duplicazione
MODIFICATI
  libs/vehicle_taxonomy.class.php               la nuova regola, in un posto solo
  shared/category_hierarchy.php                 selettore su nuova tassonomia
  02_free_ads/02_00_select_type.php             wizard + fix item_kind
  _admin/admin_header.php                       voce di menu

## Ordine di applicazione
1. sql/Changelog/2026-07-24c_special_types.sql
2. Carica i file.
3. Admin -> "Special types": verifica la lista migrata e, se vuoi, copia
   altre voci dalla lista road.

## Cosa resta da rifinire (te lo dico invece di darlo per fatto)
Ho aggiornato il cuore: tassonomia, wizard, selettore condiviso, admin.
Restano da riallineare alla nuova regola i file che leggono ancora
vehicle_types con la vecchia logica per-macro: browse.php,
sidebar_vtype_search.php, road_vehicles.php, special_vehicles.php,
05_wanted/wanted_post.php, shared/ad_modify_page.php e
_admin/admin_classify_vehicles.php. Funzionano ancora (vehicle_types esiste
e contiene i road), ma per Special e Shelter devono passare da
special_types. Dimmi e li faccio nel prossimo giro.
