# Allonwheel — Allineamento browse + RFQ mirate (punto 3)
17 lug 2026. Un solo ZIP. CRLF. PHP lint 791/791 OK.

===========================================================
1) BROWSE NON ALLINEATA CON LA SIDEBAR — causa trovata
===========================================================
In browse.php c'era, subito dentro #templatemo_content:

    <br></br>

<br> e' un elemento VOID: non ha tag di chiusura. I browser interpretano
</br> come un SECONDO <br>. Risultato: due interruzioni di riga che
spingevano il contenuto piu' in basso, mentre la sidebar partiva subito ->
il disallineamento che hai cerchiato.

Prima era mascherato dalla chip-bar dei filtri; togliendo quella (dir. 21)
e' rimasto nudo. Rimosso: ora browse.php inizia il contenuto esattamente
come road_vehicles.php, che infatti era gia' allineata.

NOTA: lo stesso costrutto </br> esiste in altri 9 file (privacy.php x6,
_admin/dashboard.php x4, 06_02_view_company, blog_comments, 05_wanted,
02_00_select_type, 01_login/all_about_me). Li' NON causa disallineamento
in testa (sono a meta' contenuto) e toglierli cambierebbe la spaziatura di
quelle pagine: non li ho toccati. Dimmi se vuoi la bonifica completa.

===========================================================
2) PUNTO 3 — "Fan-out limitato": TROVATO IL BROADCAST
===========================================================
Nel verificare come 04_send_offer.php sceglie i destinatari ho trovato
questo (era peggio di come lo avevo ipotizzato nel piano):

    $recipients = $cm->getAllCompanies();
    // Broadcast (dir. C6): la RFQ va a TUTTE le aziende registrate attive.

OGNI richiesta di preventivo veniva inviata a TUTTE le aziende attive, a
prescindere dai prodotti richiesti. I prodotti selezionati dal compratore
($regular_keys / $special_keys) venivano usati SOLO per le etichette nel
testo dell'email, non per scegliere i destinatari.

In pratica: un costruttore di race trailer riceveva richieste di cliniche
mobili e di shelter. E' il modo piu' rapido per perdere i fornitori
migliori, che sono i primi ad andarsene. Con i 30-50 fornitori del piano di
lancio, ogni RFQ sarebbe stata 30-50 email, quasi tutte fuori bersaglio.

--- LA CORREZIONE (piu' semplice del previsto)
Il metodo giusto ESISTEVA GIA' e non veniva chiamato:
    CompanyManager::getCompaniesByProducts(array $regular, array $special)
E' lo stesso gia' usato dalla directory fornitori: fa JOIN su
06_company_products / 06_company_products_special, filtra attiva = 1 e
deduplica per azienda (una sola email a testa). Restituisce un SOVRAINSIEME
dei campi di getAllCompanies -> nessuna modifica al resto del codice.

Ora la RFQ va SOLO alle aziende che dichiarano quei prodotti.

--- Dettagli di sicurezza che ho aggiunto
 - getCompaniesByProducts NON filtra le email vuote (getAllCompanies si'):
   senza il filtro si sarebbero tentati invii senza indirizzo. Aggiunto.
 - Se nessun fornitore corrisponde NON si ripiega sul broadcast (sarebbe
   tornare al problema): il lead resta salvato in quote_requests, la copia a
   rfq@allonwheel.com parte comunque e lo gestisci da _admin/leads.php.
   Meglio un lead in triage che 40 email fuori bersaglio. Viene loggato.
 - Verificato l'ordine: $regular_keys/$special_keys sono definiti a riga
   79/83, l'uso e' a 125. La guardia di riga 102 ("almeno una categoria")
   garantisce che il match riceva sempre almeno una chiave.

--- REVERSIBILE (perche' ribalta una direttiva)
Il broadcast era marcato "dir. C6", cioe' una tua decisione passata. L'ho
cambiato perche' mi hai approvato il punto 3 ("fan-out limitato"), ma non
voglio ribaltarti una direttiva senza via d'uscita: definendo

    define('AOW_RFQ_BROADCAST', true);   // in config

si torna esattamente al comportamento di prima, senza toccare il codice.

--- COSA MANCA ANCORA DEL PUNTO 3 (blocco successivo)
Fatto ora: il TARGETING (niente piu' richieste fuori tema).
Restano: il TETTO a 3 destinatari e il CLAIM entro 24h. Non li ho fatti in
questo blocco perche' un tetto senza un criterio di ordinamento equo
sceglierebbe i 3 fornitori a caso: serve prima un punteggio di match
(quante chiavi coincidono) e la tabella dei claim. E' un blocco con
patch SQL, lo faccio a parte.

===========================================================
STATO DEI 5 PUNTI
===========================================================
 1. seo_head index/browse ....... DECADUTO: canonical/hreflang c'erano gia'
                                  (mio errore di audit). Chiuse invece le 6
                                  pagine che ne erano prive davvero.
 2. Registrazione dopo il wizard  DA FARE (~6h) - tocca il wizard: e' il
                                  punto dove in produzione si e' gia' rotto
                                  ($aow_tbl). Lo faro' con calma, a parte.
 3. Fan-out limitato + claim ..... TARGETING FATTO ORA. Tetto+claim: prossimo
                                  blocco (serve patch SQL).
 4. Faccette da tech_details ..... DA FARE (~8h). Con la dir. 21 vanno nelle
                                  SIDEBAR, non nel corpo pagina.
 5. Wanted board + scoring RFQ ... DA FARE (~10h). 05_wanted/ esiste gia':
                                  da verificare cosa c'e' prima di stimare.

===========================================================
FILE IN QUESTO ZIP (2)
===========================================================
browse.php                          rimosso <br></br> (allineamento)
04_request_offer/04_send_offer.php  RFQ mirate invece del broadcast

## Come verificare
1. /browse.php: il contenuto ora parte alla stessa altezza della sidebar
   "SPECIAL VEHICLES", senza la banda vuota.
2. RFQ: manda una richiesta selezionando UNA categoria e controlla che
   l'email arrivi solo alle aziende che hanno quel prodotto in
   06_company_products (prima arrivava a tutte). La copia a rfq@ arriva
   sempre, come prima.
