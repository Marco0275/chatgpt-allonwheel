# Allonwheel — Punto 2: decisione presa + fondamenta delle bozze
17 lug 2026. Un solo ZIP. CRLF. SQL MySQL 5.7, idempotente, non distruttivo.

===========================================================
LA DECISIONE (mi hai delegato: "la migliore per l'utente")
===========================================================
Scelgo (a): BOZZA PERSISTITA IN DB, la verifica email RESTA.

Non e' un'opinione: e' un fatto dello schema che ho verificato prima di
scegliere. Nelle tabelle annunci:

    `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved'

Un annuncio nuovo nasce APPROVED, cioe' e' PUBBLICO ALL'ISTANTE.
_admin/moderate_ads.php esiste, ma la moderazione NON e' sul percorso
obbligatorio: nessuno deve dare l'ok prima che l'annuncio si veda.

Conseguenza sull'opzione (b) (attivazione email opzionale dal wizard):
chiunque, con un'email inventata, pubblicherebbe SUBITO un annuncio vero e
visibile. Il catalogo si riempirebbe di spam e i compratori manderebbero
richieste a indirizzi inesistenti - e con le RFQ ora mirate, quelle
richieste finirebbero comunque ai tuoi fornitori veri, bruciando la loro
fiducia. Oggi la verifica email e' l'UNICA difesa reale su quel percorso.
Toglierla per far risparmiare 30 secondi al venditore e' un cattivo affare
per tutti, venditore serio compreso: il suo annuncio finirebbe in mezzo
allo spam.

Perche' (a) e' migliore anche per il venditore, non solo per il sito:
 - il suo lavoro non si perde MAI. Nemmeno se apre il link di attivazione
   su un altro dispositivo, o dopo tre ore, o dopo aver chiuso il browser.
   La sessione PHP non regge quel giro; una riga in tabella si'.
 - al publish gli si dice "il tuo annuncio e' salvo, conferma l'email per
   pubblicarlo": l'attivazione smette di essere una scocciatura generica e
   diventa il modo per ottenere quello che voleva. Stessa email, motivazione
   diversa, tasso di attivazione piu' alto.

Se un giorno metti la moderazione obbligatoria (status default 'pending'),
la (b) torna discutibile: dimmelo e rivalutiamo.

===========================================================
COSA C'E' IN QUESTO ZIP
===========================================================
sql/Changelog/2026-07-17_ad_drafts.sql  -> la tabella `ad_drafts`.

E' la fondazione del flusso: l'ospite compila, la bozza si salva, l'account
arriva dopo. Scelte fatte e perche':

 - draft_token in COOKIE httponly, non nell'URL. Con quel token si legge e
   si pubblica la bozza: e' una credenziale. In query string finirebbe nei
   log del server, nei Referer verso terzi e nella cronologia condivisa.
 - user_id NULL = ospite. Nessuna FK verso users: la bozza nasce SENZA
   utente, e' il punto di tutto. Soft-ref, come gia' fatto per company_id
   in quote_request_recipients.
 - payload in JSON (longtext), non una colonna per campo: il wizard cambia
   spesso (dir. 13) e una bozza non deve richiedere una ALTER a ogni campo
   nuovo. Non ci si interroga sopra: si legge, si valida, si travasa.
   Nessun tipo JSON nativo: 5.7 non e' affidabile ovunque su quel fronte.
 - listing enum('free','prem'): stessa logica di $aow_lt nel wizard.
 - step: per riprendere da dove aveva lasciato, non dall'inizio.
 - expires_at + indice: una bozza di un ospite contiene email e telefono di
   qualcuno che NON si e' mai registrato e non ha dato alcun consenso.
   Tenerla per sempre sarebbe una raccolta silenziosa di dati personali.
   Scade (30 giorni suggeriti) e il cron la cancella. La riga da aggiungere
   a scripts/purge_personal_data.php (che gia' esiste e gira) e' nella patch:
       DELETE FROM `ad_drafts` WHERE `expires_at` < NOW();

Nella patch trovi anche la query che misura l'attrito residuo:
   SELECT COUNT(*) FROM ad_drafts WHERE user_id IS NULL;
Se le bozze orfane sono tante, il problema non e' piu' la registrazione:
e' il form. E' il dato che ti dira' se questo lavoro e' servito.

===========================================================
PERCHE' SOLO LO SCHEMA, E NON GIA' IL WIZARD
===========================================================
Perche' il collegamento al wizard tocca 4 file del flusso che in produzione
si e' gia' rotto una volta ($aow_tbl), e va fatto con lo spazio per
verificarlo passo per passo: salvataggio bozza, ripresa dopo il login,
travaso nell'annuncio vero, cancellazione della bozza, piu' i casi storti
(due bozze nella stessa sessione, token scaduto, utente che si registra con
un'email diversa da quella nel wizard).
Consegnarti meta' wizard oggi significherebbe consegnarti un wizard rotto.
La tabella e' non distruttiva e non cambia nulla di quello che c'e' adesso:
puoi applicarla subito, il sito si comporta esattamente come prima.

Prossimo blocco, nell'ordine: salvataggio bozza dal wizard -> ripresa dopo
login -> travaso -> riga di cron.

STATO DEI 5 PUNTI
 1. seo_head ..... decaduto (c'era gia')
 2. Registrazione dopo il wizard .. ritorno post-login FATTO; decisione
                                    PRESA (a); schema bozze PRONTO;
                                    collegamento al wizard: prossimo blocco.
 3. Fan-out ...... targeting + punteggio + tetto FATTI. Claim 24h: da fare.
 4. Faccette ..... FATTO (sidebar). Tecniche: serve la tua decisione
                   (specifiche base anche al free?).
 5. Wanted board + scoring RFQ .... DA FARE (~10h)

===========================================================
FILE IN QUESTO ZIP (1)
===========================================================
sql/Changelog/2026-07-17_ad_drafts.sql

## Come applicarla
1. Eseguila (idempotente: CREATE TABLE IF NOT EXISTS, rieseguibile a vuoto).
2. Verifica:  SHOW CREATE TABLE `ad_drafts`;
3. Non serve altro ora: nessun file PHP la usa ancora, il sito e' identico
   a prima. La useremo nel prossimo blocco.
