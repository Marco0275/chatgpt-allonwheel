# Allonwheel — RFQ: le voci vengono dalle tabelle di riferimento
24 lug 2026. UN SOLO ZIP. CRLF. PHP lint 292/292 OK.
Base: Allonwheel_3_5_RFQ_e_Wanted.

=================================================================
COSA NON ANDAVA
=================================================================
Avevi ragione: la pagina "Request a quotation" era rimasta indietro.
Scegliendo una categoria filtrava soltanto due elenchi SCRITTI NEL CODICE
(CompanyManager::$products e $products_special), non le tabelle di
riferimento. Conseguenze concrete:

 - comparivano ancora due tabelle, "Vehicle body types" e "Special Bodies",
   anche quando la categoria scelta ne prevedeva una sola;
 - le voci non erano quelle vere: gli elenchi nel codice non sanno nulla
   delle modifiche fatte in admin (aggiunte, rinomine, duplicazioni in
   special_types), quindi mostravano una lista scollegata dal database;
 - il configuratore tecnico premium mostrava TUTTI i 52 campi, compresi
   quelli che non appartengono alla categoria scelta (telaio e sponda per
   uno shelter, veranda e telemetria per un veicolo stradale).

=================================================================
COME FUNZIONA ADESSO
=================================================================
Scelta la categoria, la pagina mostra UNA sola lista, letta dalla tabella
di riferimento:
    road     -> vehicle_types   (lista del codice della strada)
    special  -> special_types   (lista curata da te in admin)
    shelter  -> special_types   (stessa lista: stesso allestimento su
                                 container)
Prima della scelta non compare alcun elenco, solo l'invito a scegliere.
Cosi' in pagina non resta niente che appartenga alle altre due sezioni.

Le voci sono quelle vive del database: se aggiungi o rinomini una tipologia
in admin, la RFQ la mostra subito, senza toccare codice.

Il configuratore tecnico (premium) ora segue la stessa categoria: riceve
$aow_tech_section e mostra solo i gruppi pertinenti. Uno shelter non vede
piu' telaio e sponda idraulica; un veicolo stradale non vede veranda,
cucina, letti e telemetria.

=================================================================
ANCHE IL SALVATAGGIO, NON SOLO LA VISTA
=================================================================
04_send_offer.php validava le voci ricevute contro gli stessi elenchi nel
codice: una tipologia creata in admin sarebbe stata SCARTATA in silenzio.
Ora la validazione avviene contro le tabelle (vehicle_types per i road,
special_types per special e shelter), cioe' esattamente le liste che il form
ha mostrato: non puo' passare una voce di un'altra sezione, e non puo' essere
respinta una voce legittima.
Anche le etichette leggibili nella notifica arrivano dalle tabelle, con lo
slug come ripiego se una voce fosse stata nel frattempo rinominata: mai un
campo vuoto nella mail al fornitore.

=================================================================
UNA COSA CHE HO ROTTO E RIMESSO A POSTO
=================================================================
Sostituendo i due blocchi di tabelle ho tolto per errore anche il blocco del
configuratore tecnico premium che stava subito sotto. Me ne sono accorto
controllando che $is_premium fosse ancora usato: l'ho ripristinato, e
nell'occasione l'ho reso filtrato per categoria come sopra.

=================================================================
IL WANTED
=================================================================
Verificato: usa gia' il selettore gerarchico condiviso
(shared/category_hierarchy.php), che legge dalle stesse tabelle. Non aveva
bisogno di modifiche.

=================================================================
FILE IN QUESTO ZIP (2)
=================================================================
  04_request_offer/04_request_offer.php   una lista sola, dalla tabella giusta
  04_request_offer/04_send_offer.php      validazione ed etichette dalle tabelle

## Come verificare
1. Apri la RFQ senza scegliere: nessun elenco, solo le tre categorie.
2. Scegli Road: compare una sola tabella con le tipologie stradali. Nessuna
   traccia di "Special Bodies".
3. Scegli Shelter: compare la lista special (shelter e special condividono le
   tipologie) e nient'altro.
4. Da admin aggiungi una tipologia in "Special types": ricarica la RFQ su
   Special e la trovi subito nell'elenco.
5. Da utente premium: il configuratore tecnico mostra solo i gruppi della
   categoria scelta.
