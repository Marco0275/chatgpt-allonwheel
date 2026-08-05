# Allonwheel — Dir. 21: una pagina = una famiglia, niente filtri nel corpo
16 lug 2026. Un solo ZIP. CRLF su tutti i file. PHP lint 791/791 OK.
Tag HTML bilanciati. Nessuno stile nuovo (dir. 8): solo classi esistenti.

===========================================================
PRIMA: DUE CORREZIONI AL MIO AUDIT PRECEDENTE
===========================================================
Verificando il codice per iniziare, ho scoperto che DUE cose che ti avevo
scritto erano SBAGLIATE. Te lo dico subito:

1. "index.php e browse.php non hanno canonical/hreflang" -> FALSO.
   Ce l'hanno entrambe, emesse INLINE (index.php:69 e 83, browse.php:231-233).
   Il mio grep cercava solo la stringa "seo_head.php" e non le vedeva.
   La lacuna vera era molto piu' piccola: 6 pagine minori. Sistemate qui.
2. "manca un sistema di faccette" (mia proposta strategica) -> FALSO.
   browse.php aveva GIA' un sistema completo: q, macro, cat, vtype, cond[],
   rs[], pmin, pmax, con chip di rimozione. Non era da costruire.

Mi scuso: erano affermazioni fatte con una verifica insufficiente.

===========================================================
DIR. 21 APPLICATA
===========================================================
"Nelle pagine (escluse le sidebar) niente filtri; ogni pagina e' dedicata a
un argomento/famiglia."

Il codice diceva l'opposto: header.php:46 aveva il commento
"le 5 famiglie sono filtri a chip su browse.php, non voci di menu".
Ora e' ribaltato.

--- NUOVO: 4 pagine famiglia dedicate
  race_trailers.php    -> famiglia race-trailer
  hospitality.php      -> famiglia hospitality
  mobile_clinics.php   -> famiglia mobile-clinic
  custom_projects.php  -> famiglia custom-projects
  (shelter_container.php esisteva gia' = 5a famiglia)

Sono file THIN (17 righe): fissano la famiglia e delegano a
shared/family_page.php. Le pagine dedicate esistenti (road_vehicles,
special_vehicles) sono da ~285 righe l'una, quasi identiche: replicare
quel modello per 4 volte avrebbe creato 1.140 righe da tenere allineate
a mano. Cosi' una modifica vale per tutte le famiglie insieme.

--- NUOVO: shared/family_page.php (il renderer)
Query UNION 02_free_ads + 03_ads con UN SOLO vincolo: la famiglia.
NON legge alcun filtro da query string (verificato: 0 occorrenze di
$_GET['macro'|'cat'|'vtype'|'cond'|'pmin']). La famiglia e' fissata dalla
pagina e non e' manipolabile via URL. Nel corpo: titolo, intro della
famiglia (da product_macros), elenco annunci. Nessun filtro, nessuna chip.
Solo annunci status='approved' (come le altre pagine dedicate).
Stato vuoto onesto (dir. 14): se non ci sono annunci lo dice e offre la RFQ,
niente dati inventati.

--- browse.php: 301 + chip-bar rimossa
  browse.php?macro=race-trailer      -> 301 -> /race_trailers.php
  browse.php?macro=hospitality       -> 301 -> /hospitality.php
  browse.php?macro=mobile-clinic     -> 301 -> /mobile_clinics.php
  browse.php?macro=shelter-container -> 301 -> /shelter_container.php
  browse.php?macro=custom-projects   -> 301 -> /custom_projects.php
I vecchi URL e i bookmark non si rompono (dir. 19) e ogni famiglia ha UN
solo URL canonico. browse.php resta "All listings".
RIMOSSA la chip-bar "Filters: X x" dal corpo (era l'unica UI di filtro nel
body). I filtri restano validi via URL: e' sparita solo la loro UI.

--- header.php: le 5 famiglie diventano voci di menu
Non piu' filtri a chip, ma navigazione: Marketplace > All listings +
Race Trailers / Hospitality / Mobile Clinics / Shelter & Container /
Custom Projects + Request a quotation + Wanted.

===========================================================
CONSEGUENZE CHE HO DOVUTO SISTEMARE (trovate verificando)
===========================================================
1. sitemap.php generava browse.php?macro=<slug>: ora quegli URL rispondono
   301, e una sitemap NON deve contenere URL che redirigono (Search Console
   li segnala). Ora dichiara direttamente le pagine dedicate.
2. sitemap.php: la funzione $add NON deduplicava e shelter_container.php
   sarebbe finito DUE volte (voce statica + mappa famiglie): un <loc>
   ripetuto e' un errore. Aggiunta la deduplica per path.
3. CATENA 301 -> 301: le 8 pagine legacy 00_first/ redirigevano a
   browse.php?macro=..., che ora redirige ancora. Google penalizza le
   catene: ora puntano DIRETTE alla pagina famiglia.
4. index.php: le 5 card della home puntavano a ?macro= (301 inutile su ogni
   click dalla home): ora vanno dritte alla pagina dedicata.
5. footer.php: idem per Race trailers e Hospitality.
6. 06_company/06_02_view_company.php: i chip "Related marketplace listings"
   usavano la classe filter_chip e puntavano a ?macro=. Sono navigazione
   verso l'argomento, non filtri della scheda fornitore: ora usano la classe
   esistente "more" (dir. 8) e puntano alla pagina famiglia.

===========================================================
ALTRO INCLUSO
===========================================================
- Canonical + hreflang aggiunti alle 6 pagine che ne erano prive:
  shelter_container, contact, FAQ, Conditions, privacy, cookie-policy.
  Copertura ora: 17 pagine pubbliche su 17.
- i18n: +7 chiavi x 4 lingue (macro.custom_projects, family.empty,
  family.empty_rfq, family.empty_tail, ad.price_request, ad.details,
  ad.cert_supplier). Le 4 lingue restano allineate: 313 stringhe ciascuna.
  Nota: ad.cert_supplier era usata da browse.php ma non esisteva in nessuna
  lingua (mostrava il fallback inglese anche in IT/FR/DE): ora e' tradotta.

===========================================================
DECISIONI CHE HO PRESO — dimmi se le cambio
===========================================================
1. CAMPO SEARCH nel corpo: le pagine famiglia NON lo hanno (una pagina
   dedicata non si filtra). browse.php lo mantiene (e' la pagina "tutti gli
   annunci", e me l'hai appena fatto sistemare). Se per "filtri" intendi
   anche il campo Search, dimmelo: sta nel #content_top di OGNI pagina del
   sito e va tolto ovunque.
2. Le pagine famiglia mostrano solo status='approved' (come shelter_container
   e le altre dedicate). browse.php faceva gia' lo stesso.
3. Ho tolto il badge Free/Premium dalle card delle pagine famiglia: dir. 13
   dice che la differenza vive in INSERIMENTO, e al compratore non serve
   sapere chi ha pagato. browse.php lo mostra ancora: se vuoi coerenza,
   dimmi da che parte.

===========================================================
DA SAPERE (non toccato, richiede una tua decisione)
===========================================================
- shelter_container.php filtra per `item_kind = 'shelter_container'`, mentre
  le altre 4 famiglie filtrano per `product_macro`. Sono DUE meccanismi
  diversi: un annuncio con product_macro='shelter-container' ma item_kind
  diverso NON comparirebbe. Non l'ho unificato perche' e' una modifica ai
  dati e va decisa (dir. 9). Dimmi e la allineo.
- sitemap.xml statica (163 URL) resta obsoleta: robots.txt punta
  giustamente a sitemap.php (dinamica). La statica non conoscera' mai le
  nuove pagine famiglia. Consiglio di rimuoverla.

===========================================================
FILE IN QUESTO ZIP
===========================================================
NUOVI (5)
  shared/family_page.php        renderer condiviso delle pagine famiglia
  race_trailers.php             pagina famiglia
  hospitality.php               pagina famiglia
  mobile_clinics.php            pagina famiglia
  custom_projects.php           pagina famiglia
MODIFICATI
  browse.php                    301 famiglie + chip-bar rimossa
  header.php                    5 famiglie come voci di menu
  index.php                     card home -> pagine dedicate
  footer.php                    link famiglia -> pagine dedicate
  sitemap.php                   pagine dedicate + deduplica
  06_company/06_02_view_company.php   chip -> link famiglia
  shelter_container.php, contact.php, FAQ.php, Conditions.php,
  privacy.php, cookie-policy.php      + canonical + hreflang
  lang/en.php, it.php, fr.php, de.php +7 chiavi ciascuna
  00_first/*.php                niente catena 301

## Ordine di applicazione
1. Carica tutto (nessuna patch SQL: le colonne usate esistono gia' -
   product_macro, intro_text_it da 2026-06-17_i18n_db_and_roles.sql).
2. Prova: /race_trailers.php si apre col titolo della famiglia, l'intro (se
   hai popolato product_macros.intro_text) e SOLO annunci di quella famiglia.
3. Prova il 301: /browse.php?macro=hospitality deve portare a /hospitality.php
4. Menu Marketplace: le 5 famiglie devono esserci.
5. /sitemap.php: deve contenere le 5 pagine famiglia e NESSUN ?macro=.
