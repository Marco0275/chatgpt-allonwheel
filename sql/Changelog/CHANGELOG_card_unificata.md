# Allonwheel — Card annuncio: un formato unico + "View details" a destra
17 lug 2026. Un solo ZIP. CRLF. PHP lint 792/792 OK. Tag bilanciati.
Nessuno stile nuovo (dir. 8): usa solo classi gia' esistenti.

===========================================================
PERCHE' LE PAGINE ERANO DIVERSE
===========================================================
Ogni pagina disegnava la card a modo suo: 5 pagine, 5 markup diversi.

  pagina                 badge   gallery.m0   footer
  browse.php (rif.)       si       SI         post_meta + <table>
  shelter_container.php   no       NO         post_meta senza tabella
  road_vehicles.php       no       NO         post_meta + <table>
  special_vehicles.php    no       NO         post_meta + <table>
  pagine famiglia         si       SI         nessun post_meta

La chiave e' `gallery m0`: NON e' decorativa, e' l'aggancio della regola
    .post_box:has(.gallery.m0) .post_meta { ... banda tratteggiata ... }
Le pagine senza quella classe non ricevevano la banda footer: ecco perche'
shelter_container.php (la tua Image 2) sembrava tutta un'altra cosa.

===========================================================
"VIEW DETAILS" NON ARRIVAVA A DESTRA (nemmeno nel riferimento)
===========================================================
Il CSS era gia' progettato bene:
    .post_box:has(.gallery.m0) .post_meta{
      display:flex !important; justify-content:space-between !important; }
cioe': primo figlio a sinistra, ultimo a destra.

Ma browse.php infilava dentro post_meta una <table width="100%"> come UNICO
figlio: con un solo elemento, space-between non ha nulla da distribuire. E
il float era comunque annullato da due regole:
    riga 1144: .post_meta .float_r{ float:none !important; }
    riga 1499: .post_box:has(.gallery.m0) .post_meta a.more{ float:none !important; }
Risultato: il pulsante restava a meta' card. Per questo non era a destra
nemmeno nella pagina che hai indicato come riferimento.

CORREZIONE: il footer ha ora DUE figli diretti (span autore/data | a.more).
Con due figli, space-between manda il pulsante a filo del bordo destro.
Nessun float, nessuna tabella, nessuna regola CSS nuova: si sfrutta il
flex che c'era gia'.

===========================================================
LA SOLUZIONE: UNA CARD SOLA (shared/ad_card.php)
===========================================================
Copiare il markup di browse.php nelle altre 4 pagine avrebbe funzionato
oggi e sarebbe divergito di nuovo alla prima modifica: e' esattamente come
si e' arrivati a 5 formati diversi.
Ora il markup della card vive in UN SOLO file, shared/ad_card.php, incluso
da tutte e cinque. La formattazione e' identica PER COSTRUZIONE: una
modifica alla card vale ovunque, e non e' piu' possibile che divergano.

La card e' difensiva: legge ogni campo con ?? '' e mostra solo quello che
c'e' (una pagina senza autore o senza immagine non va in errore).

Formato (quello del riferimento browse.php):
  titolo (link) - sottotitolo - immagine con pirobox - badge
  (Premium/tipo/condizione/Certified supplier) - prezzo o "Price on request"
  - descrizione - banda tratteggiata: "By autore | Published: data"  a sx,
  "View details" a DESTRA.

===========================================================
BUG TROVATO NEL RIFERIMENTO (corretto)
===========================================================
browse.php stampava la DESCRIZIONE DUE VOLTE quando il prezzo era
"on request": una volta nel ramo else e una volta subito dopo.
Si vede in entrambi i tuoi screenshot ("Premium ad form" ripetuto due
volte; nella Image 2 il testo e' ripetuto piu' volte).
Nella card unica e' stampata una volta sola.

===========================================================
ALTRE COSE SISTEMATE STRADA FACENDO
===========================================================
- shelter_container / road / special non selezionavano ad_source: il badge
  Premium ora si deduce dal detail_url (se punta a 03_ads -> premium).
- road_vehicles / special_vehicles hanno una loro funzione vehiclesBadge()
  per l'etichetta del tipo: la card la riceve e la usa (ogni pagina puo'
  passare la propria).
- La query delle pagine famiglia NON selezionava `author`: il footer avrebbe
  mostrato la data senza il "By ...". Aggiunta la colonna (verificata: esiste
  in 02_free_ads e 03_ads).
- Rimosso l'inline style="text-align:left" della vecchia tabella (dir. 8).

===========================================================
FILE IN QUESTO ZIP (6)
===========================================================
NUOVO
  shared/ad_card.php          la card, unica per tutto il sito
MODIFICATI (tutti includono la card)
  browse.php
  shelter_container.php
  road_vehicles.php
  special_vehicles.php
  shared/family_page.php      (+ colonna author nella query)

## Come verificare
1. Apri /browse.php e /shelter_container.php affiancate: le card devono
   essere IDENTICHE (stessa banda tratteggiata, stessi badge, stessa
   spaziatura). Prima erano due formati diversi.
2. "View details" deve stare a filo del bordo destro della card, dentro la
   banda tratteggiata, in tutte le pagine.
3. Un annuncio "Price on request": la descrizione deve comparire UNA volta.
4. Stessa verifica su /road_vehicles.php, /special_vehicles.php,
   /race_trailers.php.
