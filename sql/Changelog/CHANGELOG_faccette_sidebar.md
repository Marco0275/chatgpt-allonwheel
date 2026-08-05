# Allonwheel — Punto 4: le faccette tornano, nella sidebar
17 lug 2026. Un solo ZIP. CRLF. PHP lint 793/793 OK.
Nessuno stile nuovo (dir. 8): sb_box / submit_btn / cleaner / input_field.

===========================================================
PERCHE' ERA URGENTE (un buco che avevo lasciato io)
===========================================================
browse.php ha da sempre le faccette cond[] / pmin / pmax dentro la query.
La loro UNICA interfaccia era la chip-bar nel corpo pagina, che la dir. 21
mi ha fatto rimuovere. Da quel momento quei filtri esistevano ma erano
raggiungibili SOLO scrivendo l'URL a mano: codice vivo, funzionante e
invisibile. Ora hanno casa nel posto che hai indicato: la sidebar.

===========================================================
COSA C'E' NEL BOX
===========================================================
NUOVO: sidebar_facets.php, terzo box della sidebar (dopo Special e Road
vehicles, prima del box utente).

  - Condizione (menu a tendina)
  - Prezzo minimo / massimo
  - "Search" -> browse.php
  - "Clear filters" (solo se un filtro e' attivo)

Coerente con sidebar_vtype_search.php: tendine e non checkbox (tua richiesta
del 5 lug 2026), stesse classi, stessa struttura.

===========================================================
DUE SCELTE CHE HO FATTO, E PERCHE'
===========================================================
1) LE CONDIZIONI SI LEGGONO DAL DB, non da un elenco fisso.
   Copiare l'elenco di browse.php nella sidebar avrebbe creato la solita
   divergenza (e' cosi' che sono nati i 5 formati di card). Il box fa
   SELECT DISTINCT conditions sugli annunci APPROVATI: mostra solo filtri
   che danno risultati (dir. 14: solo dati reali).

   Ho verificato il rischio opposto - offrire un valore che browse.php poi
   scarta: la colonna e' un ENUM
       enum('New','As good as new','Used','Poor','Project')
   con ESATTAMENTE gli stessi 5 valori di $cond_set in browse.php. Il DB non
   puo' contenere altro: nessuna incoerenza possibile. (Se un giorno aggiungi
   un valore all'ENUM, ricordati di aggiungerlo anche a $cond_set:130.)

2) IL BOX SI NASCONDE DA SOLO se non ci sono annunci approvati.
   Il sito e' pre-lancio: una tendina vuota fa sembrare il sito rotto.
   Niente annunci -> niente box. Comparira' da solo col primo annuncio.

===========================================================
DETTAGLI VERIFICATI
===========================================================
 - Giro completo simulato: la sidebar invia cond=Used&pmin=20000 ->
   browse.php legge cond_selected=["Used"], pmin=20000 -> filtro applicato.
   (browse.php fa (array)$_GET['cond']: un valore singolo diventa ['Used'],
   quindi la tendina funziona senza toccare browse.php.)
 - La ricerca testuale in corso non si perde: se stai cercando "trailer" e
   applichi un filtro, la q viene mantenuta (hidden).
 - $pdo: stessa guardia di sidebar_vtype_search.php, il box e' autonomo e non
   dipende dall'ordine di inclusione.
 - Se la query fallisce (schema non migrato) il box semplicemente non appare:
   nessuna pagina rotta.
 - i18n: +5 chiavi x 4 lingue. Restano allineate: 319 stringhe ciascuna.

===========================================================
NOTA SULLE FACCETTE TECNICHE (03_ads_tech_details)
===========================================================
Nel piano avevo proposto faccette da 03_ads_tech_details (lunghezza, assi,
anno). NON le ho fatte, e la ragione e' sostanziale, non di tempo:
la scheda tecnica e' PREMIUM-ONLY. Un filtro "lunghezza 12m" mostrerebbe
solo annunci premium e farebbe SPARIRE tutti i free, che non hanno quei
campi compilati. Su un marketplace pre-lancio, dove gli annunci free sono
la maggioranza, sarebbe un danno.
Perche' quelle faccette abbiano senso serve prima la decisione che ti avevo
proposto: SPECIFICHE DI BASE ANCHE AL FREE (poche misure chiave), tenendo
premium la scheda completa + PDF. E' una tua scelta di prodotto: dimmi e le
implemento.

===========================================================
STATO DEI 5 PUNTI
===========================================================
 1. seo_head ........ decaduto (c'era gia'; chiuse le 6 pagine scoperte)
 2. Registrazione dopo il wizard + bozza ...... DA FARE (~6h)
 3. Fan-out limitato .. targeting + punteggio + tetto FATTI.
                        Claim 24h: da fare.
 4. Faccette .......... FATTO ORA (quelle esistenti, in sidebar).
                        Faccette tecniche: bloccate da una tua decisione.
 5. Wanted board + scoring RFQ ................ DA FARE (~10h)

===========================================================
FILE IN QUESTO ZIP (6)
===========================================================
NUOVO
  sidebar_facets.php     box faccette (condizione + fascia di prezzo)
MODIFICATI
  include_sidebar.php    aggancia il box (3o posto)
  lang/en|it|fr|de.php   +5 chiavi ciascuna

## Come verificare
1. Con almeno un annuncio approvato: in sidebar, sotto "Road vehicles",
   compare "Refine listings".
2. Scegli una condizione e/o un prezzo, premi Search: vai su browse.php
   filtrata. Compare "Clear filters".
3. Cerca "trailer" nel campo Search, poi applica un filtro dalla sidebar:
   la ricerca testuale deve essere mantenuta.
4. Senza annunci approvati il box non deve comparire.
