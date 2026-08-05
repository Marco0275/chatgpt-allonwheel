# Allonwheel — Patch di fusione: la causa vera dell'errore
24 lug 2026. UN SOLO ZIP. CRLF.
Provata importando il TUO dump reale (allonwhe80316.sql) ed eseguendola.

Sostituisce le due versioni precedenti.

=================================================================
COSA STAVA FALLENDO DAVVERO
=================================================================
Non erano i nomi delle colonne (quelli erano gia' giusti dalla correzione
precedente). Importando il tuo dump ed eseguendo la query, l'errore e':

  ERROR 1452 (23000): Cannot add or update a child row: a foreign key
  constraint fails (`06_company_products`, CONSTRAINT `fk_06prod_company`
  FOREIGN KEY (`company_id`) REFERENCES `06_company` (`id`))

`06_company_products` ha una CHIAVE ESTERNA verso `06_company`.
`06_company_products_special` NON ce l'ha. Per questo la tabella speciali ha
potuto accumulare righe ORFANE, e spostandole nella tabella con il vincolo
il database le rifiutava.

Nel tuo database, in questo momento:
  06_company (aziende) ............................ 0 righe
  06_company_products_special ..................... 36 righe
  ...tutte riferite ad aziende id 2..7 che non esistono piu'

Sono residui di aziende cancellate: il sito non li mostra comunque, perche'
ogni pagina fa JOIN sull'azienda.

Avrei dovuto scoprirlo prima: la volta scorsa ho dedotto lo schema dal file
invece di importare il dump e provare la query. L'ho fatto adesso.

=================================================================
COME L'HO RISOLTO
=================================================================
La fusione dei camper ora sposta SOLO le righe di aziende realmente
esistenti (guardia EXISTS su 06_company), quindi non puo' piu' violare il
vincolo. Le righe orfane restano dove sono, intatte: cancellarle e' una tua
decisione, non una cosa da fare di nascosto dentro una migrazione. In fondo
alla patch trovi la query pronta e commentata, con quella per vederle prima.

=================================================================
COSA HO SCOPERTO SUL TUO DATABASE ATTUALE
=================================================================
Il dump che mi hai dato e' PIU' AVANTI di quello dentro lo zip della build:
gli slug erano gia' a posto (racing_trailer, box_trailer; roadshow_vehicles
e street_food gia' spariti). Quindi la patch dei refusi l'avevi gia'
applicata, e la parte 1-2 di questa non trova nulla da fare: giusto cosi',
e' idempotente.

Restava pero' un errore VISIBILE che nessuno aveva corretto: la tipologia
racing_trailer si chiamava ancora "Raicing trailer" nel campo NOME, quello
che l'utente legge nelle tendine. Lo slug era corretto, l'etichetta no.
Aggiunta la correzione (blocco 5).

Stato dopo la patch, verificato sul tuo dump:
  racing_trailer        -> "Racing trailer"   (era "Raicing trailer")
  autonegozi_alimentari -> "Street food"      (una sola voce, fra i road)
  camper                -> "Motorhomes"
  special_shelter       -> shelter_container  (6 righe fornitore rinominate)
  motorhomes_mobilhomes -> restano 6 righe, tutte orfane: saltate apposta

=================================================================
FILE IN QUESTO ZIP (1)
=================================================================
  sql/Changelog/2026-07-24b_taxonomy_merge.sql

## Come applicare
Importa il file da phpMyAdmin. Puoi eseguirlo anche se avevi gia' provato le
versioni precedenti: e' idempotente, non ripete nulla.
Verificato: importato il dump pulito, applicate entrambe le patch, poi
rieseguite - nessun errore, dati stabili.

## Una domanda per te
Le 36 righe orfane: le cancello o le tieni? Se il progetto e' pre-lancio e
quelle aziende non esistono piu', ripulirle rende il database piu' onesto.
Dimmi e ti mando la patch (e' una riga, gia' scritta in fondo al file).
