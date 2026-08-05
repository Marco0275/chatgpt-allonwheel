# Allonwheel — Punto 2, primo passo: il login riporta dove volevi andare
17 lug 2026. Un solo ZIP. CRLF. PHP lint 793/793 OK.

===========================================================
COSA HO TROVATO INDAGANDO IL WIZARD (prima di toccarlo)
===========================================================
Mi hai detto "procedi" sul punto 2 (registrazione dopo il wizard). Prima di
mettere le mani sul file che in produzione si e' gia' rotto, ho studiato il
flusso. Tre scoperte, due buone e una che cambia il piano.

1. BUONA: il form di testo del wizard NON carica file.
   02_insert_ad.php / 02_01_upload_advertising.php: zero enctype, zero
   $_FILES. Le immagini arrivano in uno step SUCCESSIVO. Quindi i dati
   dell'annuncio sono solo testo -> salvabili in sessione. Far compilare
   l'annuncio a un ospite e' molto piu' semplice di quanto temessi.

2. BUONA: l'impianto per tornare indietro dopo il login ESISTE GIA'.
   config/session_helper.php:99, dentro require_user_logged_in():
       $_SESSION['redirect_after_login'] = $current_url;
   ...ma NESSUNO lo legge. Verificato su tutto il codice: una sola
   occorrenza, in scrittura. login.php faceva sempre e comunque:
       header('Location: ' . BASE_URL . '/01_login/dashboard.php');
   Risultato: chi clicca "vendi il tuo veicolo" viene mandato al login e poi
   scaricato sulla DASHBOARD, non sul wizard. Deve ritrovare da solo la
   strada. Un pezzo di attrito costruito a meta' e mai finito.

3. QUELLA CHE CAMBIA IL PIANO: la registrazione richiede l'ATTIVAZIONE VIA
   EMAIL (register.php -> register_ok.php). Un ospite che compila l'annuncio
   e si registra al momento del publish NON puo' proseguire subito: deve
   aprire l'email e attivare. La bozza in sessione non sopravvive in modo
   affidabile a quel giro (l'utente puo' aprire il link altrove, o dopo ore).
   -> "Account al publish" NON e' implementabile con la sola sessione:
      serve la BOZZA PERSISTITA in DB (l'altra meta' del punto 2).
      E la bozza in DB ha un problema di uovo e gallina: le tabelle annunci
      richiedono id_user, che per un ospite non esiste ancora.
   Le opzioni sono due, ed e' una decisione tua (tocca lo schema, dir. 9):
      a) tabella ad_drafts separata (draft_token in cookie, id_user NULL),
         travasata nell'annuncio vero al primo login utile;
      b) attivazione email opzionale per chi arriva dal wizard (login
         immediato, verifica dopo): piu' semplice, ma abbassa una difesa
         anti-spam che oggi hai.
   Non scelgo io: dimmi quale e la implemento.

===========================================================
COSA HO FATTO ORA (il pezzo che era gia' li', a meta')
===========================================================
login.php ora CONSUMA redirect_after_login: dopo il login torni esattamente
alla pagina che avevi chiesto. Se non c'e' nulla in sospeso, dashboard come
prima. Il valore e' immediato: "vendi" -> login -> sei nel wizard, non
persa sulla dashboard.

E' one-shot: il valore viene consumato (unset) al primo uso, cosi' un login
successivo non ti rispedisce li' a sorpresa.

--- SICUREZZA: questo e' il punto in cui si sbaglia
Un redirect preso da una variabile e seguito ciecamente e' un OPEN REDIRECT:
un link malevolo porterebbe l'utente, gia' autenticato, su un dominio di
phishing identico al tuo. Accetto quindi SOLO percorsi relativi di questo
sito. Testato sui casi reali di attacco, estraendo la logica dal file:

  /02_free_ads/02_00_select_type.php     accettato   (wizard)
  /browse.php?q=trailer                  accettato   (query string)
  //evil.com                             RESPINTO    (protocol-relative)
  https://evil.com                       RESPINTO    (URL assoluto)
  /\evil.com                             RESPINTO    (backslash: alcuni
                                                     browser lo leggono //)
  /ok.php + CRLF + Set-Cookie            RESPINTO    (header injection)
  dashboard.php                          RESPINTO    (manca la / iniziale)

Tutti e 7 corretti: nessun dirottamento possibile. Nel dubbio si finisce
sulla dashboard, cioe' il comportamento di prima.

===========================================================
PERCHE' MI SONO FERMATO QUI
===========================================================
Il resto del punto 2 (ospite che compila e crea l'account al publish)
dipende dalla decisione sulla bozza: senza, farei un flusso che si
interrompe sull'attivazione email e perde il lavoro dell'utente - cioe'
peggio di adesso. E su un wizard che si e' gia' rotto in produzione non
faccio scommesse.

Con questo pezzo, intanto, l'attrito e' gia' sceso: l'intento dell'utente
non si perde piu' nel passaggio dal login.

STATO DEI 5 PUNTI
 1. seo_head ....... decaduto (c'era gia')
 2. Registrazione dopo il wizard .... ritorno post-login FATTO.
                                      Ospite+bozza: serve la tua decisione
                                      (a) tabella ad_drafts o (b) attivazione
                                      email opzionale dal wizard.
 3. Fan-out ........ targeting + punteggio + tetto FATTI. Claim 24h: da fare.
 4. Faccette ....... FATTO (in sidebar). Tecniche: bloccate da tua decisione
                     (specifiche base anche al free?).
 5. Wanted board + scoring RFQ ...... DA FARE (~10h)

===========================================================
FILE IN QUESTO ZIP (1)
===========================================================
01_login/login.php   consuma redirect_after_login (con guardia open redirect)

## Come verificare
1. Da NON loggato apri /02_free_ads/02_00_select_type.php: vieni mandato al
   login. Accedi: devi tornare sul WIZARD, non sulla dashboard.
2. Accedi normalmente da /01_login/newlogin.php senza nulla in sospeso:
   devi finire sulla dashboard, come prima.
3. Rifai login subito dopo: NON deve riportarti al wizard (one-shot).
