# Allonwheel — Punto 3 completato: punteggio di pertinenza + tetto sui destinatari
17 lug 2026. Un solo ZIP. CRLF. PHP lint 792/792 OK. SQL MySQL 5.7, idempotente.

===========================================================
DOVE ERAVAMO
===========================================================
Blocco precedente: la RFQ non e' piu' un broadcast a TUTTE le aziende
attive, va solo a chi dichiara quei prodotti (getCompaniesByProducts).
Restava il TETTO. Non l'avevo fatto di proposito: un tetto senza un criterio
di ordinamento sceglie i fornitori A CASO, che e' ingiusto e indifendibile
("perche' non ho ricevuto quella richiesta?" - "non lo so").
Serviva prima il punteggio. Ora c'e'.

===========================================================
1) PUNTEGGIO DI PERTINENZA
===========================================================
NUOVO metodo: CompanyManager::getCompaniesByProductsScored()

Calcola per ogni azienda match_count = quante chiavi prodotto ha in comune
con la richiesta, e ordina per pertinenza decrescente (a parita', per nome).

Dettagli tecnici:
 - UNA sola query (sottoquery correlate sulle due tabelle prodotti):
   niente N+1, niente una query per azienda.
 - Se un ramo non ha chiavi, il suo contributo e' la costante 0: senza questa
   guardia si genererebbe "IN ()", che e' SQL non valido.
 - HAVING match_count > 0 -> solo chi corrisponde davvero.
 - MySQL 5.7: HAVING su alias e' consentito.
 - Binding verificato su 3 combinazioni (solo regular / solo special /
   miste): segnaposto e parametri sempre allineati, types coerente.
 - Il vecchio getCompaniesByProducts NON e' stato toccato: lo usa la
   directory fornitori. Metodo nuovo, nessuna regressione altrove.

===========================================================
2) TETTO SUI DESTINATARI (default 3)
===========================================================
Un lead che arriva a tutti non vale nulla per nessuno: nessuno si sente
responsabile di rispondere e i fornitori percepiscono spam. Limitandolo ai
piu' pertinenti, la richiesta torna a essere un privilegio.

Comportamento:
  8 fornitori corrispondenti -> email ai 3 con match_score piu' alto
  2 corrispondenti           -> email a entrambi (sotto il tetto)
  0 corrispondenti           -> nessuna email ai fornitori; il lead resta in
                                quote_requests, la copia a rfq@ parte
                                comunque, lo gestisci da _admin/leads.php

Configurabile senza toccare il codice:
  define('AOW_RFQ_MAX_RECIPIENTS', 5);   // alza il tetto
  define('AOW_RFQ_MAX_RECIPIENTS', 0);   // nessun tetto
  define('AOW_RFQ_BROADCAST', true);     // torna al broadcast di prima

===========================================================
3) TRACCIABILITA' (patch SQL)
===========================================================
sql/Changelog/2026-07-17_rfq_match_score.sql aggiunge a
quote_request_recipients:
  match_score  quante chiavi in comune aveva quel fornitore
  rank_pos     posizione in graduatoria all'invio (1 = piu' pertinente)

Senza, il tetto sarebbe una scatola nera: se un fornitore ti chiede "perche'
non ho ricevuto quella richiesta?" non sapresti rispondere. Con queste due
colonne la risposta e' una query.

La patch e' IDEMPOTENTE davvero: MySQL 5.7 non ha ADD COLUMN IF NOT EXISTS,
quindi usa una procedura temporanea che interroga information_schema e salta
se la colonna c'e' gia'. Rieseguibile senza errori - non e' pedanteria: su
questo progetto i trasferimenti verso il server sono gia' falliti in
silenzio in passato. La procedura viene rimossa a fine patch.

IMPORTANTE: il codice PHP funziona ANCHE SENZA la patch. L'INSERT dei
destinatari e' dentro try/catch: senza le colonne fallisce, viene loggato e
la RFQ parte lo stesso. Si perde solo la tracciabilita'. Nessun rischio di
rompere le richieste se applichi i file prima del DB.

Nella patch trovi pronte: la verifica delle colonne, la query "chi ha
ricevuto cosa e perche'", e quella che misura quante email genera in media
una richiesta (deve stare sotto il tetto).

===========================================================
COSA MANCA ANCORA DEL PUNTO 3: IL CLAIM
===========================================================
Fatto: targeting, punteggio, tetto, tracciabilita'.
Manca: il CLAIM entro 24h (il lead va rivendicato; se nessuno lo prende,
passa al successivo in graduatoria).

Non l'ho incluso perche' non e' una modifica isolata: servono una colonna di
stato + un'interfaccia dove il fornitore rivendica + un cron che riassegna
allo scadere. E' un blocco a se'. Con il tetto a 3 e _admin/leads.php che
gia' mostra i lead senza risposta, oggi l'escalation la fai a mano: non e'
bloccante per il lancio.

STATO DEI 5 PUNTI
 1. seo_head ............... decaduto (c'era gia'; chiuse le 6 pagine scoperte)
 2. Registrazione dopo il wizard + bozza ....... DA FARE (~6h)
 3. Fan-out limitato ....... targeting + punteggio + tetto FATTI.
                             Claim 24h: blocco successivo.
 4. Faccette da tech_details nelle sidebar ..... DA FARE (~8h)
 5. Wanted board + scoring RFQ ................. DA FARE (~10h)

===========================================================
FILE IN QUESTO ZIP (3)
===========================================================
libs/06_company.class.php                      + getCompaniesByProductsScored()
04_request_offer/04_send_offer.php             punteggio + tetto + audit
sql/Changelog/2026-07-17_rfq_match_score.sql   match_score + rank_pos

## Ordine
1. Carica i due file PHP (funzionano subito, anche senza la patch).
2. Esegui la patch SQL (idempotente: se la rilanci non fa danno).
3. Manda una RFQ di prova selezionando una categoria coperta da piu' di 3
   fornitori: devono riceverla solo 3. Poi:
     SELECT request_id, COUNT(*) FROM quote_request_recipients GROUP BY 1;
   e controlla match_score / rank_pos.
