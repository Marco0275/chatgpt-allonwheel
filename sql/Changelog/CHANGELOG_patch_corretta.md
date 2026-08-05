# Allonwheel — Patch di unificazione slug: CORRETTA
24 lug 2026. UN SOLO ZIP. CRLF. Provata su schema fedele al dump reale.

SOSTITUISCE la versione consegnata poco fa: quella NON funzionava.

=================================================================
L'ERRORE CHE MI HAI SEGNALATO
=================================================================
Nel blocco 3 (e anche nel 4) avevo scritto la colonna dell'azienda come
`id_company`. Nel database reale si chiama `company_id`.

    INSERT INTO `06_company_products` (`id_company`, ...)   <-- SBAGLIATO
    INSERT INTO `06_company_products` (`company_id`, ...)   <-- corretto

Eseguendola avresti avuto "Unknown column 'id_company' in field list" e la
fusione dei camper e degli shelter non sarebbe avvenuta. Colpa mia: ho
scritto quelle due query basandomi sul nome che avevo in testa invece di
leggere lo schema, cosa che per le altre tabelle avevo fatto.

=================================================================
ALTRE DUE COSE EMERSE LEGGENDO LO SCHEMA VERO
=================================================================
1. Le due tabelle hanno una colonna `note`. Spostando un fornitore da
   06_company_products_special a 06_company_products la nota andava persa:
   ora viene portata dietro.

2. NON esiste un indice UNIQUE su (company_id, product_key): i doppioni
   sono tecnicamente possibili. Quindi la guardia NOT EXISTS non e' una
   precauzione teorica, e' necessaria. Nel blocco 4 ho sostituito la
   sottoquery auto-referenziante (che MySQL rifiuta sulla stessa tabella
   di un UPDATE) con una tabella derivata, che invece accetta.

`id` e' AUTO_INCREMENT in entrambe le tabelle: si lascia generare, l'INSERT
non lo tocca.

=================================================================
TEST RIFATTO, QUESTA VOLTA SULLO SCHEMA VERO
=================================================================
Ricostruite le due tabelle con le colonne reali (company_id, note, id
auto_increment, i flag di servizio) e questi casi limite:
  az.1  aveva GIA' 'camper' fra i regolari  -> non deve duplicarsi
  az.2  solo motorhomes_mobilhomes          -> va spostata con la sua nota
  az.3  aveva GIA' shelter_container E special_shelter -> deve restarne una

Esito:
  regolari : az.1 una sola riga camper (nota originale intatta)
             az.2 camper con 'nota B'
  speciali : nessun motorhomes_mobilhomes, nessun special_shelter
             az.3 una sola shelter_container
  riesecuzione: nessun errore, conteggi stabili (2 regolari, 3 speciali)

=================================================================
FILE IN QUESTO ZIP (1)
=================================================================
  sql/Changelog/2026-07-24b_taxonomy_merge.sql

Se avevi gia' provato ad applicare la versione precedente: i blocchi 1 e 2
(roadshow_vehicles e street_food) erano corretti e potrebbero essere andati
a segno prima dell'errore. Non e' un problema: questa patch e' idempotente,
rieseguirla non ripete nulla e completa cio' che mancava.

I 6 file PHP del pacchetto precedente restano validi: l'errore era solo
nella patch SQL.
