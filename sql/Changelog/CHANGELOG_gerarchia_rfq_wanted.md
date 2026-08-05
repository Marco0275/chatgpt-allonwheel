# Allonwheel — RFQ e Wanted allineati alla gerarchia Road/Special/Shelter
24 lug 2026. UN SOLO ZIP. CRLF. PHP lint 290/290 OK.
Base: Allonwheel_3_4_Pulizia_ed_Admin. Formattazione HTML: mantenuta la tua.

=================================================================
IL SIGNIFICATO DELLE TRE CATEGORIE (documentato nei file)
=================================================================
Come mi hai chiarito, ora e' scritto NEI FILE dove si fa la scelta
(shared/category_hierarchy.php e 04_request_offer.php), non solo qui:

  ROAD     Veicoli stradali di USO COMUNE: quelli che si incontrano
           abitualmente per strada - ambulanze, cassoni, frigoriferi,
           minibus, scuolabus, carri attrezzi, spazzatrici...
           DB: vehicle_types.macro_category = 'road'

  SPECIAL  Veicoli SPECIALI, cioe' allestimenti fuori dall'uso comune,
           definiti dall'AMMINISTRATORE (_admin/admin_vehicle_types.php):
           race trailer, hospitality, paddock trailer, uffici e laboratori
           mobili, motorhome.
           DB: vehicle_types.macro_category = 'special'

  SHELTER  Le stesse funzioni degli Special, ma costruite SU CONTAINER
           invece che su un veicolo: sono strutture, non mezzi su ruote
           (per questo non hanno assi ne' telaio).
           DB: item_kind = 'shelter_container'

=================================================================
1) RFQ: scelta guidata in testa alla pagina
=================================================================
In cima all'unica pagina di richiesta ora c'e' "1. What are you looking
for?" con le tre categorie e la loro spiegazione. La scelta determina cosa
si vede sotto: nella pagina NON restano voci di altre categorie.

  Road    -> 24 tipologie stradali. La tabella degli allestimenti speciali
             non compare affatto.
  Special -> 8 voci (le non stradali + paddock).
  Shelter -> la sola voce shelter. La tabella "Vehicle body types" sparisce.
  Nessuna scelta -> elenco completo, come prima: i link generici da header,
             footer e home continuano a funzionare.

"Special categories" e' diventato "Special Bodies", come mi hai indicato,
in tutte e quattro le lingue (IT: "Allestimenti speciali").

=================================================================
2) WANTED: stessa gerarchia dell'inserimento
=================================================================
Prima la richiesta aveva DUE tendine scollegate: la "famiglia" commerciale
(le 5 famiglie brand) e un elenco piatto con TUTTE le tipologie insieme,
stradali e speciali mescolate. Non era la gerarchia degli annunci.

Ora: prima la CATEGORIA (Road/Special/Shelter, con la spiegazione), poi la
TIPOLOGIA, filtrata dalla categoria scelta. Chi compila non vede mai voci
di un'altra categoria. Scegliendo Shelter la tipologia non viene nemmeno
chiesta: la categoria coincide col tipo, esattamente come nel wizard.

La famiglia commerciale NON si sceglie piu' a mano: viene DERIVATA da
categoria + tipologia con le stesse regole dell'inserimento (nuovo
ProductMacro::forSelection, che riusa la mappa slug->caratteristica del
wizard). Cosi' annunci, RFQ e wanted classificano allo stesso modo.
Verificato: shelter -> shelter-container, racing_trailer -> race-trailer,
hospitality_units -> hospitality, ambulanze -> mobile-clinic.

=================================================================
3) ERRORI DI CORRISPONDENZA TROVATI NEL SITO
=================================================================
Confrontando le due tassonomie ho trovato un problema serio. vehicle_types.
slug e 06_company_products.product_key sono la STESSA chiave (lo dichiara
06_company.class.php riga 576): e' il ponte fra un annuncio e i fornitori
che producono quella tipologia. Ma quattro slug non combaciano:

  vehicle_types        prodotti fornitore
  raicing_trailer  <>  racing_trailer     REFUSO di battitura
  box_trailers     <>  box_trailer        plurale vs singolare

Per queste due tipologie il ponte non agganciava NESSUN fornitore: chi
pubblicava un race trailer non riceveva contatti. Corretto con la patch
sql/Changelog/2026-07-24_taxonomy_slug_fix.sql, che sistema gli slug e
propaga la correzione agli annunci e alle wanted gia' pubblicati (non
distruttiva, idempotente).

RESTANO 4 DISALLINEAMENTI CHE NON HO TOCCATO perche' sono scelte di
prodotto, non refusi - te li elenco e mi dici come li vuoi:
  roadshow_vehicles      tipologia annuncio senza prodotto fornitore
                         corrispondente: nessun fornitore associabile.
  street_food            in vehicle_types e' 'special', ma il prodotto
                         fornitore corrispondente (autonegozi_alimentari)
                         sta fra i road: annuncio e fornitore non si
                         incontrano.
  motorhomes_mobilhomes  prodotto fornitore senza tipologia annuncio
                         (in vehicle_types il corrispondente e' 'camper').
  special_shelter        prodotto fornitore, mentre la tipologia annuncio
                         si chiama 'shelter_container'.

=================================================================
FILE IN QUESTO ZIP (9)
=================================================================
NUOVI
  shared/category_hierarchy.php              selettore gerarchico condiviso
  sql/Changelog/2026-07-24_taxonomy_slug_fix.sql   correzione slug
MODIFICATI
  libs/product_macro.class.php               + forSelection()
  05_wanted/wanted_post.php                  gerarchia + famiglia derivata
  04_request_offer/04_request_offer.php      scelta guidata + Special Bodies
  lang/en.php it.php fr.php de.php           etichette e nuove chiavi

## Ordine di applicazione
1. Applica sql/Changelog/2026-07-24_taxonomy_slug_fix.sql
2. Carica i file.

## Come verificare
1. RFQ: in testa ci sono le tre categorie con la spiegazione. Scegli Road ->
   compaiono solo le 24 stradali. Scegli Shelter -> resta una sola voce e la
   tabella "Vehicle body types" sparisce.
2. Wanted: scegli Road -> la tendina tipologia mostra solo tipi stradali;
   scegli Shelter -> la tipologia non viene chiesta.
3. Dopo la patch SQL: un annuncio "racing trailer" trova i fornitori che
   hanno registrato quel prodotto (prima non ne trovava nessuno).
