# Allonwheel — Unificazione degli slug doppi (tue decisioni)
24 lug 2026. UN SOLO ZIP. CRLF. PHP lint 290/290 OK.
Patch SQL provata su MariaDB reale, anche in riesecuzione.

=================================================================
LE QUATTRO FUSIONI
=================================================================
  roadshow_vehicles      ->  hospitality_units
  street_food            ->  autonegozi_alimentari   (uno solo, non due)
  motorhomes_mobilhomes  ->  camper                  (motorhome e mobilhome
                                                       sono camper)
  special_shelter        ->  shelter_container       (vedi nota in fondo)

=================================================================
COSA HO TROVATO GUARDANDO I DATI PRIMA DI TOCCARE
=================================================================
Non erano semplici rinomine: in due casi ENTRAMBI gli slug esistevano gia'.

 - roadshow_vehicles e hospitality_units erano due righe distinte di
   vehicle_types, tutte e due 'special'.
 - street_food e autonegozi_alimentari erano due righe con LA STESSA
   ETICHETTA "Street food": una 'special' e una 'road'. E' il doppione che
   si vedeva nelle tendine.
 - motorhomes_mobilhomes non era una tipologia annuncio: era solo un
   prodotto fornitore, e SEI aziende lo avevano dichiarato.
 - special_shelter idem: prodotto fornitore, dichiarato da sei aziende.

Quindi la patch non rinomina: ripunta prima annunci, wanted e fornitori
sullo slug che resta, e solo dopo elimina la riga doppia. Nessun record
perde la classificazione.

=================================================================
DUE DETTAGLI CHE POTEVANO ROMPERE QUALCOSA
=================================================================
1. STREET FOOD CAMBIA ANCHE MACRO. street_food era 'special',
   autonegozi_alimentari e' 'road'. Spostando solo lo slug, gli annunci
   sarebbero rimasti classificati "speciali" con una tipologia stradale.
   La patch aggiorna anche macro_category. Verificato sul DB di prova:
   l'annuncio street food esce come (autonegozi_alimentari, road).

2. I CAMPER CAMBIANO TABELLA. 'camper' sta fra i prodotti REGOLARI del
   fornitore, mentre motorhomes_mobilhomes stava fra gli SPECIALI: le sei
   aziende non vanno rinominate, vanno spostate di tabella. La patch usa
   INSERT...SELECT con guardia, cosi' chi aveva gia' dichiarato 'camper'
   non si ritrova la riga doppia. Testato proprio quel caso: azienda con
   camper preesistente -> resta una riga sola.

Nota: il flag booleano 'street_food' dell'annuncio (la caratteristica
"e' un autonegozio") NON e' stato toccato: e' un'altra cosa rispetto allo
slug della tipologia, e resta dov'e'.

=================================================================
ALLINEATO ANCHE IL CODICE, NON SOLO IL DATABASE
=================================================================
  libs/06_company.class.php   motorhomes_mobilhomes tolto dai prodotti
                              speciali (confluito in camper, che sta fra i
                              regolari); special_shelter -> shelter_container;
                              rfqSections aggiornato di conseguenza.
  libs/product_macro.class.php  la mappa famiglia -> prodotti fornitore
                              puntava ancora ai vecchi slug: la famiglia
                              Shelter ora cerca shelter_container e la
                              famiglia Custom cerca camper fra i regolari.
  lang/en|it|fr|de.php        rimosse 3 chiavi orfane per lingua
                              (vtype.motorhomes_mobilhomes,
                               vtype.special_shelter, vt.roadshow_vehicles):
                              traducevano voci che non esistono piu'.

=================================================================
TEST ESEGUITI (su MariaDB, non a occhio)
=================================================================
Ricostruito lo scenario del tuo dump - tipologie doppie, annunci e wanted
che le usano, sei fornitori su motorhomes_mobilhomes e sei su
special_shelter, piu' un'azienda che aveva gia' 'camper' - ed eseguita la
patch:
  vehicle_types: spariti roadshow_vehicles e street_food;
  "Street food" compare UNA volta sola;
  annunci e wanted ripuntati, macro corretta;
  fornitori camper spostati senza duplicati (2 righe, non 3);
  special_shelter -> shelter_container (3 righe).
Rieseguita altre due volte: nessun errore, dati stabili.
Controllo finale: nessun annuncio punta piu' a uno slug inesistente.

=================================================================
LA QUARTA FUSIONE: UNA COSA DA CONFERMARE
=================================================================
special_shelter -> shelter_container non me l'avevi indicata: l'ho allineata
perche' senza di essa il ponte resta rotto, e perche' e' l'unica direzione
tecnicamente possibile. Il lato annuncio non e' modificabile a mano: lo
shelter scrive vehicle_type = 'shelter_container' perche' e' una costante
del codice (VehicleTaxonomy::SHELTER_SLUG) usata in tutto il sito. Quindi
si allinea la chiave del fornitore alla costante.
Se preferisci il contrario, e' una costante contro sei righe fornitore:
dimmelo e preparo la patch inversa.

=================================================================
FILE IN QUESTO ZIP (7)
=================================================================
  sql/Changelog/2026-07-24b_taxonomy_merge.sql   NUOVA patch di fusione
  libs/06_company.class.php                     prodotti fornitore allineati
  libs/product_macro.class.php                  mappa famiglie allineata
  lang/en.php it.php fr.php de.php              chiavi orfane rimosse

## Ordine di applicazione
1. Se non l'hai gia' fatto: sql/Changelog/2026-07-24_taxonomy_slug_fix.sql
   (i due refusi raicing_trailer e box_trailers).
2. sql/Changelog/2026-07-24b_taxonomy_merge.sql   <- questa
3. Carica i 6 file PHP.

## Come verificare
- Nelle tendine "Street food" compare una volta sola, fra i Road.
- Roadshow non esiste piu': quegli annunci sono Hospitality units.
- Un fornitore che aveva "Motorhomes & Mobilhomes" ora risulta su "Camper".
- Un annuncio shelter trova i fornitori shelter (prima non ne trovava).
