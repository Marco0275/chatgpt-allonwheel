# Allonwheel — Punto 3: escalation dei lead fermi (il "claim 24h", reale)
17 lug 2026. Un solo ZIP. CRLF. PHP lint OK. SQL 5.7.

===========================================================
PERCHE' NON IL "CLAIM 24H" ALLA LETTERA
===========================================================
Il piano voleva: il fornitore rivendica il lead entro 24h, altrimenti passa
al successivo in graduatoria. Indagando il codice, quel meccanismo presuppone
DUE cose che oggi NON esistono:

 1. un'AREA RISERVATA dove il fornitore vede i suoi lead e li rivendica.
    Oggi i fornitori ricevono il lead solo VIA EMAIL: non c'e' un portale
    fornitori (verificato: nessuna pagina lead in 06_company/).
 2. un SEGNALE di "ho risposto". Oggi il fornitore risponde rispondendo
    all'email (che va al buyer); il sistema non lo sa. Lo status del lead lo
    aggiorna l'admin a mano in _admin/leads.php.

Senza questi due, un cron che "riassegna in automatico" riassegnerebbe alla
CIECA: potrebbe scavalcare un fornitore che ha gia' risposto via email,
mandando il buyer due offerte o nessuna. Sarebbe un claim FINTO, peggio del
problema che risolve.

Costruire l'area fornitori + il tracciamento delle risposte e' un blocco
grande e una tua decisione di prodotto (non la invento). Nel frattempo il
VALORE del claim - "nessun lead resta a marcire" - lo do subito, in modo
onesto.

===========================================================
COSA FA (reale, utile da subito)
===========================================================
scripts/rfq_escalation.php: un cron giornaliero che SEGNALA all'admin (rfq@)
i lead ancora 'new' o 'distributed' dopo N ore (default 24). L'escalation la
fa una PERSONA - che puo' telefonare al buyer o sollecitare i fornitori con
cognizione - invece di un automatismo cieco.

 - Seleziona solo i lead FERMI: 'new' (nessuno li ha presi in carico) o
   'distributed' (inviati ma non quotati) e piu' vecchi della soglia.
   'quoted'/'won'/'lost' sono avanzati: si saltano. (Simulato: su 5 lead di
   test, segnala solo i 2 fermi da >24h.)
 - Manda all'admin una tabella: lead, buyer, categoria, stato, a quanti
   fornitori era andato, eta'. Con link a _admin/leads.php.
 - Riusa Mailer (SMTP quando sara' attivo, mail() come fallback) e la casella
   rfq@ gia' usata per la copia di servizio delle RFQ.
 - --dry-run: elenca i lead senza inviare nulla.
 - Configurabile: AOW_RFQ_ESCALATION_HOURS (default 24),
   AOW_RFQ_INBOX (default rfq@allonwheel.com).

MySQL 5.7: la soglia ore e' un int gia' validato (>=1), interpolato in
sicurezza (MySQL non accetta sempre un placeholder dopo INTERVAL).

===========================================================
IL PERCORSO VERSO IL CLAIM VERO
===========================================================
Quando deciderai di costruire l'area lead per fornitori (dove vedono e
rivendicano i lead, e il sistema sa chi ha risposto), questo cron diventa il
claim automatico: la logica per trovare i lead fermi e' gia' qui, cambia solo
l'AZIONE (da "segnala all'admin" a "riassegna al rank successivo"). Non ho
buttato via il lavoro: ho fatto il pezzo che ha senso oggi.

===========================================================
COSA DEVI FARE TU
===========================================================
Agganciare il cron (una riga in crontab), es. ogni mattina alle 9:
  0 9 * * * php /home/<user>/htdocs/scripts/rfq_escalation.php >> /var/log/aow_rfq_escalation.log 2>&1
Provalo prima a mano:  php scripts/rfq_escalation.php --dry-run

===========================================================
FILE IN QUESTO ZIP (1)
===========================================================
scripts/rfq_escalation.php   NUOVO cron di escalation lead

## Come verificare
1. php scripts/rfq_escalation.php --dry-run
   Elenca i lead 'new'/'distributed' piu' vecchi di 24h (nessuna email).
2. Senza --dry-run: se ce ne sono, rfq@ riceve la tabella di riepilogo.
3. Se non ce ne sono: "rfq_escalation: nulla da segnalare".
