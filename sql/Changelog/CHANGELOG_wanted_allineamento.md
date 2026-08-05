# Allonwheel — Punto 5: Wanted board allineato (non era da costruire)
17 lug 2026. Un solo ZIP. CRLF. PHP lint 795/795 OK. SQL 5.7.

===========================================================
LA SCOPERTA: IL PUNTO 5 ESISTE GIA'
===========================================================
Il piano stimava il Wanted board + scoring RFQ a ~10h "da fare". Verificando
il codice, e' gia' COSTRUITO e cablato:
 - tabella `wanted_ads` (con status active/matched/closed);
 - 5 pagine in 05_wanted/ (list, post, manage, view, delete);
 - classe libs/wanted_ads.class.php completa: create, listActive,
   sellersForMacro, notifySellers (buyer -> venditori), notifyBuyers
   (annuncio approvato -> buyer con wanted attive);
 - link nel menu (header.php) e nelle sidebar.
Lo "scoring RFQ" che il piano voleva costruire e' gia' notifySellers/
notifyBuyers. Quindi il punto 5 non era da scrivere: era da ALLINEARE alle
decisioni recenti. Ecco cosa non tornava.

===========================================================
1) IL BROADCAST CHE AVEVO GIA' CORRETTO SULLE RFQ, QUI ERA ANCORA VIVO
===========================================================
notifySellers chiamava sellersForMacro, che restituiva TUTTI i venditori di
una macro, senza limite. Una richiesta "wanted" di un compratore generava
un'email a ogni venditore della categoria: con 40 venditori race-trailer,
40 email. E' lo stesso identico problema del broadcast RFQ, sullo stesso
tipo di destinatari (i fornitori, quelli che scappano per primi).

CORREZIONE, coerente con le RFQ:
 - TETTO sui destinatari, stessa costante AOW_RFQ_MAX_RECIPIENTS (default 3,
   0 = nessun tetto). Cosi' RFQ e Wanted hanno lo stesso comportamento e si
   regolano da un unico punto.
 - Perche' il tetto sia sensato serve un ORDINAMENTO (senza, taglia a caso):
   sellersForMacro ora calcola un punteggio di pertinenza. Un venditore che
   ha annunci del vehicle_type ESATTO richiesto viene prima di chi ha solo
   la macro giusta. La wanted passa il suo vehicle_type; a parita', ordine
   per id. Il tetto tiene la testa della lista.
 - SQL verificato 5.7-valido: (vehicle_type = :vt) come booleano 0/1 in
   SELECT, MAX() per aggregare, GROUP BY su tutte le colonne non aggregate,
   ORDER BY relevance DESC. Binding coerenti (vt1/vt2/m1/m2/ex).

notifyBuyers NON aveva bisogno del tetto: notifica i buyer che hanno una
wanted attiva su quella macro quando un annuncio viene approvato -> sono
loro che hanno chiesto di essere avvisati, non e' spam non richiesto.

===========================================================
2) I LINK NELLE EMAIL: FALSO ALLARME (verificato)
===========================================================
notifyBuyers linka 02_view_ad.php / 03_view_ad.php. Ho controllato temendo
fossero i file resi 301 nel pacchetto redirect: NO. Quelli erano
02_view_adS / 03_view_adS (plurale, i listing). 02_view_ad.php / 03_view_ad
(singolare) sono i wrapper VIVI della pagina dettaglio (delegano a
shared/view_ad.php). I link funzionano. Nessuna modifica necessaria.

===========================================================
3) I18N: TRE CHIAVI MANCANTI (buco reale, corretto)
===========================================================
nav.wanted, sb.post_wanted, sb.my_wanted NON esistevano in NESSUNA lingua:
il menu e le sidebar mostravano il fallback inglese hardcoded anche in
IT/FR/DE. Aggiunte in tutte e 4 le lingue. Restano allineate: 322 ciascuna.
  IT: Richieste di acquisto / Pubblica una richiesta / Le mie richieste

===========================================================
DUE COSE CHE NON HO TOCCATO (servono una tua decisione)
===========================================================
a) wanted_list.php usa #no_sidebar. Per la dir. 17 una sezione con opzioni
   proprie (post / manage wanted) dovrebbe avere la sua sidebar. Ma spostare
   una pagina da no_sidebar a con-sidebar e' una scelta di navigazione:
   dimmi se la vuoi e la applico (riuso include_sidebar, zero CSS nuovo).
b) Il matching Wanted <-> annunci oggi e' per MACRO. Con lo scoring che ho
   aggiunto potrei stringerlo al vehicle_type anche in notifyBuyers e nella
   vista dei match (adsForMacro), come per i venditori. Non l'ho fatto per
   non cambiare troppo in un colpo: se il free-text del punto 2 ti convince,
   lo estendo qui in modo simmetrico.

STATO DEI 5 PUNTI
 1. seo_head ..... decaduto
 2. Registrazione dopo il wizard .. COMPLETO (free)
 3. Fan-out ...... targeting + punteggio + tetto FATTI. Claim 24h: da fare.
 4. Faccette ..... FATTO. Tecniche: tua decisione.
 5. Wanted board .. gia' esistente; ALLINEATO ora (tetto + pertinenza + i18n).
                    Restano 2 scelte tue (sidebar list, matching vtype).

Tutti e 5 i punti sono ora chiusi o in attesa di una tua decisione.

===========================================================
FILE IN QUESTO ZIP (6)
===========================================================
libs/wanted_ads.class.php     tetto + scoring pertinenza in sellersForMacro
05_wanted/wanted_post.php     passa vehicle_type a notifySellers
lang/en|it|fr|de.php          +3 chiavi wanted ciascuna

## Come verificare
1. Posta una wanted (05_wanted/wanted_post.php) scegliendo macro + un
   vehicle_type. Con piu' di 3 venditori nella macro, ne devono ricevere
   l'email solo 3, e per primi quelli che hanno annunci di quel vehicle_type.
2. Menu e sidebar in italiano: "Richieste di acquisto", "Pubblica una
   richiesta", "Le mie richieste" (prima erano in inglese anche in IT).
3. Approva un annuncio da _admin: i buyer con wanted attiva su quella macro
   ricevono la notifica (notifyBuyers, invariato).
