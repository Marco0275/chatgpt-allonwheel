# Allonwheel — Bozze: la classe + la pulizia GDPR
17 lug 2026. Un solo ZIP. CRLF. PHP lint 794/794 OK.
Richiede: sql/Changelog/2026-07-17_ad_drafts.sql (pacchetto precedente).

===========================================================
COSA C'E'
===========================================================
1. NUOVO libs/ad_draft.class.php
   Tutta la logica delle bozze in un posto solo. I file del wizard dovranno
   solo CHIAMARLA, non contenere regole proprie: il wizard e' il punto piu'
   fragile del sito (in produzione si e' gia' rotto una volta), meno codice
   gli si mette dentro meglio e'.

   API:
     AdDraft::currentToken()               legge/crea il cookie
     AdDraft::save($pdo,$t,$payload,...)   salva/aggiorna (UPSERT)
     AdDraft::load($pdo,$t)                riprende dove aveva lasciato
     AdDraft::claim($pdo,$t,$user_id)      al login: la bozza diventa sua
     AdDraft::forUser($pdo,$user_id)       bozze da pubblicare
     AdDraft::delete($pdo,$id)             dopo il travaso (svuota il cookie)
     AdDraft::purgeExpired($pdo)           la chiama il cron

2. MODIFICATO scripts/purge_personal_data.php
   Aggiunta la cancellazione delle bozze scadute, nel cron GDPR che gia'
   gira. In un blocco try/catch SEPARATO: se la tabella non c'e' ancora
   (patch non applicata), il purge delle altre tabelle continua a funzionare
   e il cron non va in errore. Non ho toccato nulla di quello che faceva.

===========================================================
LE DECISIONI DENTRO LA CLASSE (e perche')
===========================================================
- COOKIE httponly + SameSite=Lax, mai il token in URL.
  Con quel token si legge e si pubblica la bozza: e' una credenziale.
  In query string finirebbe nei log del server, nei Referer verso terzi e
  nella cronologia condivisa. Lax e non Strict perche' si torna qui dal link
  di attivazione ricevuto via email: con Strict il cookie non verrebbe
  inviato e la bozza sembrerebbe persa proprio nel momento chiave.
- TOKEN VALIDATO come 64 esadecimali prima di qualsiasi uso.
  Testato: 64 hex -> accettato; 63 caratteri, non-hex, "../../etc/passwd",
  "' OR 1=1--", vuoto -> tutti scartati.
- random_bytes, e se fallisce si RINUNCIA.
  Nessun ripiego su rand(): un token indovinabile permetterebbe di leggere
  la bozza di un altro. Meglio nessuna bozza che una bozza insicura.
- UPSERT sul token (ON DUPLICATE KEY).
  L'ospite che torna indietro e riavanza aggiorna la SUA bozza, non ne crea
  una nuova a ogni passaggio.
- expires_at si sposta in avanti a ogni salvataggio: chi sta lavorando non
  se la vede scadere sotto le mani.
- claim() assegna SOLO bozze ancora senza proprietario (user_id IS NULL).
  Senza questo vincolo, un token rubato o riciclato permetterebbe di
  appropriarsi della bozza di un altro utente.
- load() controlla la scadenza anche in lettura, non solo nel cron: fra due
  giri di cron una bozza scaduta non deve tornare a galla.
- Ogni metodo cattura PDOException e logga: una bozza che non si salva non
  deve mai buttare giu' la pagina del wizard.

===========================================================
COSA MANCA (e perche' non l'ho forzato)
===========================================================
Il collegamento vero e proprio ai 4 file del wizard: salvataggio dal form,
ripresa dopo il login, travaso nell'annuncio vero, piu' i casi storti
(due bozze nella stessa sessione, token scaduto a meta' compilazione,
registrazione con un'email diversa da quella scritta nel wizard).

Con questo pacchetto le fondamenta sono complete e verificate: tabella,
classe, pulizia. Il pezzo che resta e' quello che tocca il file che si e'
gia' rotto in produzione, e va fatto con lo spazio per provarlo passo per
passo. Preferisco dirtelo che consegnarti un wizard a meta'.

Nel frattempo non hai nulla di rotto: nessun file del wizard e' stato
toccato, e il cron e' piu' pulito di prima.

STATO DEI 5 PUNTI
 1. seo_head ..... decaduto (c'era gia')
 2. Registrazione dopo il wizard .. ritorno post-login FATTO; decisione (a)
                                    PRESA; tabella + classe + cron PRONTI;
                                    resta il collegamento al wizard.
 3. Fan-out ...... targeting + punteggio + tetto FATTI. Claim 24h: da fare.
 4. Faccette ..... FATTO (sidebar). Tecniche: serve la tua decisione
                   (specifiche base anche al free?).
 5. Wanted board + scoring RFQ .... DA FARE (~10h)

===========================================================
FILE IN QUESTO ZIP (2)
===========================================================
libs/ad_draft.class.php            NUOVO
scripts/purge_personal_data.php    + pulizia bozze scadute

## Come verificare
1. Applica prima la patch SQL del pacchetto precedente (ad_drafts).
2. Carica i due file.
3. Lancia il cron a mano:
     php scripts/purge_personal_data.php
   Deve stampare anche: "purge ad_drafts: 0 rows removed".
   Se stampa "skipped (...)" vuol dire che la tabella non c'e' ancora:
   applica la patch SQL. Il resto del purge funziona comunque.
4. Il sito si comporta esattamente come prima: nessun file del wizard
   e' stato toccato.
