# Allonwheel — Punto 2 COMPLETO: l'ospite compila, si registra, pubblica
17 lug 2026. Un solo ZIP. CRLF. PHP lint 795/795 OK. Tag bilanciati.
Richiede: patch SQL ad_drafts + libs/ad_draft.class.php + login.php (claim)
+ login.php (ritorno post-login) - tutti nei pacchetti precedenti.

===========================================================
IL LOOP COMPLETO, ORA CHIUSO
===========================================================
  ospite apre il wizard e compila lo step testuale
    -> preme "Register to publish"
    -> [02_save_draft.php] salva la bozza in ad_drafts, cookie httponly
    -> registrazione (verifica email: resta, e' l'unica difesa reale)
    -> al login: claim() rende la bozza sua (gia' fatto)
    -> ritorno automatico al form (redirect_after_login, gia' fatto)
    -> [02_insert_ad.php] il form si RIPOPOLA dalla bozza
    -> l'utente rivede e preme "Continue"
    -> [02_01_upload_advertising.php] pubblica NORMALMENTE (invariato)
    -> a INSERT riuscito, la bozza si cancella.

===========================================================
COME HO PROTETTO IL FILE CHE SI ERA ROTTO IN PRODUZIONE
===========================================================
Il cuore fragile dell'handler - il calcolo di $aow_tbl (righe 28-29) e
l'INSERT (riga 162) - NON e' stato toccato. Verificato riga per riga:
  28: $aow_lt  = ... ? 'prem' : 'free';      INVARIATA
  29: $aow_tbl = ($aow_lt === 'prem') ? ...   INVARIATA
 162: INSERT INTO `' . $aow_tbl . '`          INVARIATA
L'unica aggiunta all'handler e' un blocco ADDITIVO dentro if($lastId){...},
cioe' DOPO che l'INSERT e' gia' riuscito: cancella la bozza. Non bloccante
(try/catch): se fallisce, l'annuncio e' comunque pubblicato.

L'ospite NON posta mai sull'handler: il form, per un ospite, punta a
02_save_draft.php (file NUOVO, pulito, senza fragilita' legacy). L'handler
mantiene la sua require_user_logged_in(): se un ospite ci arrivasse comunque,
verrebbe rimbalzato al login SENZA perdere nulla (la bozza e' gia' salvata).

===========================================================
I 4 INNESTI, UNO A UNO
===========================================================
1) 02_00_select_type.php - guardia soft
   require_user_logged_in() -> current_user_id() (null = ospite).
   I controlli quota girano SOLO se loggato: l'ospite entra, il gate quota
   scatta al publish quando avra' un account. Free/premium selezionabili.

2) 02_insert_ad.php - guardia soft + prefill
   Stessa apertura all'ospite. author/email/phone e quota solo se loggato
   (l'ospite li fornira' in registrazione). In piu': se l'utente loggato ha
   una bozza rivendicata (AdDraft::forUser), il form si RIPOPOLA - campi
   testo, prezzo, e le tendine type/conditions con l'option giusta selected.
   Prefill difensivo: bozza vuota o campo assente -> campo vuoto, mai un
   warning. Il form, per l'ospite, posta a 02_save_draft.php e il bottone
   dice "Register to publish"; per il loggato, tutto come prima.
   Salva anche $_SESSION['ad_wizard']['draft_id'] per il delete finale.

3) 02_save_draft.php - NUOVO, dove posta l'ospite
   File minimale e isolato. CSRF verificato (stesso token del wizard).
   Raccoglie i campi con gli STESSI nomi e la stessa pulizia dell'handler,
   cosi' la bozza contiene esattamente cio' che verra' pubblicato. Rifiuta
   di salvare una bozza senza titolo/descrizione (niente righe vuote nella
   tabella). Salva via AdDraft, imposta il ritorno al form e manda a
   registrazione. Se ci arriva un utente gia' loggato, lo rimanda al form.

4) 02_01_upload_advertising.php - delete dopo INSERT
   Unico ritocco all'handler, additivo e dopo il successo: se l'annuncio
   nasce da una bozza (draft_id in sessione), la bozza si cancella. L'INSERT
   e la logica $aow_tbl restano identici.

===========================================================
VERIFICHE FATTE
===========================================================
 - LINT 795/795. Tag <?php/?> bilanciati nei due file con markup.
 - Cuore fragile ($aow_tbl, INSERT) verificato INVARIATO riga per riga.
 - Rami del form simulati: ospite -> save_draft + "Register to publish";
   loggato -> handler + "Continue".
 - Prefill difensivo simulato: bozza vuota -> campi vuoti senza warning;
   bozza piena -> valori giusti; campo assente -> vuoto.
 - Tendine: tutte le 9 option (type + conditions, inclusi i due "Project")
   ripristinano il selected dalla bozza.
 - CRLF preservato su tutti i file.

===========================================================
NOTA: SOLO FREE IN QUESTO GIRO
===========================================================
Il wizard e' unificato: $aow_lt distingue free/premium e questi 4 file
gestiscono entrambi via quel parametro. La bozza salva gia' listing='prem'
se l'ospite sceglie premium. L'unica cosa da verificare a parte, quando avrai
provato il free end-to-end, e' il ramo premium con la scheda tecnica (step
extra di 03_ads): la bozza oggi salva i campi base, non i tech_details. Se
vuoi che l'ospite compili anche quelli prima di registrarsi, e' un'aggiunta
al payload - dimmelo dopo che il free gira.

===========================================================
DOVE SIAMO CON IL PUNTO 2
===========================================================
  [x] ritorno al wizard dopo il login
  [x] decisione: bozza in DB, verifica email mantenuta
  [x] tabella ad_drafts
  [x] classe AdDraft
  [x] pulizia GDPR nel cron
  [x] claim al login
  [x] save dal wizard            <- questo pacchetto
  [x] prefill (travaso) nel form <- questo pacchetto
  [x] delete dopo il travaso     <- questo pacchetto
Il punto 2 e' FUNZIONALMENTE COMPLETO per il flusso free.

STATO DEI 5 PUNTI
 1. seo_head ..... decaduto
 2. Registrazione dopo il wizard .. COMPLETO (free). Premium tech_details:
                                    aggiunta opzionale, quando vorrai.
 3. Fan-out ...... targeting + punteggio + tetto FATTI. Claim 24h: da fare.
 4. Faccette ..... FATTO (sidebar). Tecniche: tua decisione.
 5. Wanted board + scoring RFQ .... DA FARE (~10h)

===========================================================
FILE IN QUESTO ZIP (4)
===========================================================
02_free_ads/02_00_select_type.php        guardia soft
02_free_ads/02_insert_ad.php             guardia soft + prefill + form ospite
02_free_ads/02_save_draft.php            NUOVO: salva bozza ospite
02_free_ads/02_01_upload_advertising.php + delete bozza dopo INSERT

## Come verificare (end-to-end, da NON loggato)
1. Apri /02_free_ads/02_00_select_type.php: NON vieni piu' cacciato al login.
   Scegli un tipo, arriva al form.
2. Compila titolo + descrizione + prezzo, premi "Register to publish":
   finisci sulla pagina di registrazione, con un messaggio "listing saved".
3. Registrati e attiva l'email, poi accedi.
4. Devi tornare sul FORM con i campi GIA' compilati come li avevi lasciati.
5. Premi "Continue": l'annuncio si pubblica e prosegue con le foto.
6. In DB: la riga in ad_drafts dev'essere sparita (cancellata dopo l'INSERT).
7. Controprova loggato: il flusso normale deve funzionare identico a prima.
