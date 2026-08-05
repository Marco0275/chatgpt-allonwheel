# Allonwheel — Allineamento completo alla nuova tassonomia
24 lug 2026. UN SOLO ZIP. CRLF. PHP lint 291/291 OK.
Completa il pacchetto "nuova tassonomia": qui ci sono i file che erano
rimasti sulla vecchia logica.

=================================================================
LA REGOLA APPLICATA OVUNQUE
=================================================================
  ROAD     -> `vehicle_types`   lista del codice della strada
  SPECIAL  -> `special_types`   lista curata dall'admin
  SHELTER  -> `special_types`   stessa lista: e' lo stesso allestimento,
                                costruito su container

Nessuno di questi file decide piu' da solo dove pescare: chiedono a
VehicleTaxonomy. Se cambierai idea, si cambia in un punto solo.

=================================================================
DUE METODI NUOVI, PER NON RIPETERE CODICE
=================================================================
libs/vehicle_taxonomy.class.php
  allTypesGrouped($pdo)     entrambe le liste in una chiamata, gia' divise
                            per categoria. La usano le pagine che devono
                            mostrarle insieme (browse, ricerca in sidebar).
  categoryOfSlug($slug,$pdo) dato uno slug, dice se e' road o special. Serve
                            a chi ha in mano solo il vehicle_type di un
                            annuncio.

=================================================================
FILE ALLINEATI (7)
=================================================================
browse.php
  Leggeva una sola tabella e divideva per macro_category. Ora unisce le due
  liste marcando ogni riga con la sua categoria, cosi' il resto della pagina
  (che filtra per macro) non e' stato toccato: modifica minima, stesso
  comportamento.

sidebar_vtype_search.php
  Idem: i due elenchi arrivano da allTypesGrouped invece che da uno split
  su macro_category.

road_vehicles.php      -> pesca da vehicle_types
special_vehicles.php   -> pesca da special_types

shared/ad_modify_page.php
  La tendina della tipologia in modifica ora usa la tabella della sezione
  dell'annuncio.

libs/ad_section_fields.class.php
  hasVehicleTypeChoice() ritornava false per lo shelter, perche' il tipo era
  fisso. Ora anche lo shelter sceglie, dalla lista special: ritorna true per
  tutte le sezioni. Annotato che macroFor() non serve piu' a scegliere la
  tabella.

05_wanted/wanted_post.php
  La validazione confrontava la tipologia con l'elenco di vehicle_types. Con
  la nuova tassonomia una voce speciale NON sta piu' li': sarebbe stata
  rifiutata a torto, e "On demand" pure. Ora si valida contro la tabella
  della categoria scelta (isValidForCategory), che accetta anche on_demand.
  Rimosse le due righe che caricavano l'elenco piatto, ormai inutilizzate.

_admin/admin_classify_vehicles.php
  Superata: serviva a marcare road/special le righe di UNA tabella. Ora le
  liste sono due e separate, quindi non c'e' piu' niente da classificare -
  una tipologia e' speciale perche' STA in special_types.
  Non l'ho cancellata (dir. 19) e non l'ho lasciata attiva: scrivere su
  macro_category adesso non avrebbe effetto sulle liste e manderebbe solo
  fuori strada. Chi ci arriva da un vecchio segnalibro trova una tabella che
  spiega dove sta ogni categoria e i link alle due pagine giuste.

=================================================================
VERIFICHE
=================================================================
- lint 291/291
- nessun file legge piu' vehicle_types con la vecchia logica per-macro
  (restano solo: la classe stessa, la pagina admin che duplica dai road, e
   il codice dopo il return: della pagina superata)
- ogni file che usa VehicleTaxonomy ha il require: verificato uno per uno

=================================================================
FILE IN QUESTO ZIP (9)
=================================================================
  libs/vehicle_taxonomy.class.php      + allTypesGrouped, categoryOfSlug
  libs/ad_section_fields.class.php     shelter sceglie la tipologia
  browse.php                           due liste unite, filtro invariato
  sidebar_vtype_search.php             due liste
  road_vehicles.php                    vehicle_types
  special_vehicles.php                 special_types
  05_wanted/wanted_post.php            validazione per categoria
  shared/ad_modify_page.php            tipologie della sezione
  _admin/admin_classify_vehicles.php   pagina superata, con rimando

## Ordine
Applica prima 2026-07-24c_special_types.sql (pacchetto precedente), poi
carica questi file.

## Verifica veloce
- browse e la ricerca in sidebar mostrano road e special come prima;
- /road_vehicles.php elenca solo tipi stradali, /special_vehicles.php solo
  speciali;
- una wanted su una tipologia speciale non viene piu' rifiutata;
- in admin, "Road/Special" rimanda alle due liste corrette.
