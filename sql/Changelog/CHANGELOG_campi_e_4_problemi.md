# Allonwheel — Campi RFQ uniformi + i 4 problemi del report
15 lug 2026. Base: V2_2 reale dal server. Un solo ZIP.
PHP lint 786/786 OK · CSS bilanciato (465/465) · CRLF preservati.
Verifiche con getComputedStyle reale (jsdom).

===========================================================
TASK 1 — CAMPI DELLA STESSA DIMENSIONE  ✔
===========================================================
Causa (due concause, trovate entrambe):
1. I 3 campi (Name/Email/Object) stanno in TRE TABELLE separate con
   <td width="10%"> per l'etichetta: essendo "Email" piu' lungo di "Name",
   la colonna etichetta si dimensionava diversamente e gli input partivano
   da posizioni diverse.
2. LA CAUSA PRINCIPALE: la regola
       /* Fix login: campi piu' lunghi */
       #email, #password{ width:300px !important; }
   mira l'ID #email con !important. Il form RFQ ha un input id="email":
   veniva forzato a 300px mentre Name e Object stavano al 100%.
   (Era pensata per il login, ma "sfuggiva" anche a RFQ e Contact.)

Fix (solo CSS, nessuna classe nuova - dir. 8):
- Regola login CIRCOSCRITTA ai form di login/registrazione:
      form:not([name="formmail"]) #email, form:not([name="formmail"]) #password
  I form name="formmail" sono SOLO Request-a-quotation e Contact: ora non
  vengono piu' toccati. Login/registrazione/admin mantengono i loro 300px
  (verificato: 300px, invariato).
- Colonna etichetta a larghezza FISSA 90px uguale per le tre tabelle
  (table-layout:fixed), input width:100% + box-sizing:border-box.
  La griglia tecnica (.tbl_collapse) e la vista tech sono escluse.

Verifica (getComputedStyle): author/email/object -> width 100%, colonna
etichetta 90px per tutti = campi allineati e della STESSA dimensione.

===========================================================
I 4 PROBLEMI DEL REPORT — verificati UNO PER UNO
===========================================================
Come prescrive il comando di debug, ho verificato ogni segnalazione a mano
PRIMA di correggere. Risultato: 2 erano reali, 2 in gran parte FALSI
POSITIVI causati da due bug del mio stesso scanner (corretti, vedi sotto).

--- (A) CREDENZIALI — REALE (parziale)
config/env_copia: PRESENTE, 22 chiavi tra cui DB_PASSWORD, SMTP_PASS,
CRON_TOKEN. BUONA NOTIZIA: config/.htaccess c'e' gia' (fix precedente
applicato) quindi il file NON e' piu' servibile via web. Resta comunque
da rimuovere: e' nel .bat, punto [1].
FALSI POSITIVI: mail.env.example (solo placeholder vuoti) e i changelog
(citano "SMTP_PASS=..." REDATTO, nessun segreto vero).

--- (B) FUNZIONI JS MANCANTI — 1 REALE su 2
REALE: controlloForm() chiamata in 04_request_offer.php:72
       (onsubmit="return controlloForm()") ma MAI definita, ne' nei .js
       ne' inline: errore JS a ogni submit.
  Fix: rimosso l'onsubmit rotto e attivata al suo posto la validazione
  NATIVA HTML5 (required su author/email/object/msg + type="email"):
  feedback immediato all'utente, ZERO javascript nuovo. La validazione
  lato server (csrf_verify, honeypot, timing, FILTER_VALIDATE_EMAIL,
  consenso GDPR) era ed e' intatta: e' quella che conta davvero.
FALSO POSITIVO: checkPasswords() e' definita in uno <script> inline in
  fondo a reset_password.php (riga 118) e funziona: l'onsubmit viene
  valutato al submit, quando la funzione esiste gia'. Nessun intervento.

--- (C) LINK MORTI — TUTTI E 4 FALSI POSITIVI (bug del mio scanner)
  footer.php:48  -> era https://www.facebook.com/profile.php?id=...
                    cioe' un URL ESTERNO, non un file locale.
  footer.php:60, cookie_banner.php:27, privacy.php:63
                 -> puntano a cookie-policy.php, che ESISTE. Il mio regex
                    non ammetteva il TRATTINO nei nomi file e catturava
                    "policy.php" da "cookie-policy.php".
  Nessun link morto reale. Nessuna modifica fatta (giustamente).

--- (D) DUMP SQL PUBBLICI — REALE
  sql/allonwhe80316.sql (568 KB) e mondocontainer/allonwhe99119.sql erano
  scaricabili via web: sql/ non aveva alcun .htaccess (e li' dentro ci sono
  anche 112 changelog .md).
  Fix immediato: creati sql/.htaccess e mondocontainer/.htaccess che negano
  l'accesso web all'INTERA cartella (artefatti di sviluppo, mai pubblici).
  Vale subito, anche se non lanci il .bat. La rimozione dei file e' nel
  .bat, punto [2] (con avviso: se il dump ti serve, SPOSTALO fuori dal
  webroot invece di cancellarlo).

===========================================================
SCANNER CORRETTO (aow_debug.sh v2)
===========================================================
I falsi positivi mi hanno fatto trovare 3 bug nel mio stesso strumento:
1. check link: ora ammette i TRATTINI nei nomi file e SALTA gli URL
   esterni (http/mailto/tel/#/javascript) e gli href php-dinamici;
2. check JS: ora cerca le funzioni anche negli <script> INLINE di
   .php/.html (prima solo nei .js) e riconosce arrow function/espressioni;
3. check credenziali: segnala solo se il VALORE e' reale (non placeholder
   ne' redatto) e indica se la cartella e' protetta da .htaccess.
Report DOPO i fix (allegato): 1 credenziale (env_copia, da .bat),
2 dump (schermati, da .bat), tutto il resto a ZERO.

===========================================================
cleanup_file_obsoleti.bat — cosa rimuove (dopo conferma "SI")
===========================================================
[1] config\env_copia            (credenziali)
[2] sql\allonwhe80316.sql       (dump DB)
[3] 72 sidebar_*.php orfane     (verificato: nessuna pagina le include
                                 piu'; il dispatcher carica solo
                                 sidebar_vtype_search + sidebar_user_box)
[4] 42 cartelle _notes\         (residui Dreamweaver)
[5] 2 script one-shot           (patch_site_init, migrate_session_legacy)
    NON rimuove expire_ads.php / purge_personal_data.php /
    cleanup_unused_uploads.php: sono cron di manutenzione ATTIVI.
NON tocca: upload_image\, images\, vendor\, 02_free_ads\, i file
tech/documents/modify di 03_ads\, le 2 sidebar vive.

RICHIEDONO LA TUA DECISIONE (segnalati nel .bat, non automatici):
 a) mondocontainer\ : cartella ESTRANEA al sito (import contatti
    "cosmetica", dump di un ALTRO database, xlsx, JPG). L'ho schermata
    con .htaccess. Puo' contenere TUOI dati: controlla prima di
    cancellarla a mano.

===========================================================
SEGNALAZIONE IMPORTANTE (non richiesta, ma la devi sapere)
===========================================================
Il pacchetto "wizard unificato" risulta applicato solo IN PARTE:
- applicati: il wizard unico in 02_free_ads (toggle Standard/Premium OK),
  gli entry-point (my_posts / sidebar_user_box -> 02_00_select_type),
  il wrapper 03_02_delete_image_gallery;
- NON applicati: i 7 file del vecchio inserimento premium
  (03_00_select_type, 03_insert_ad, 03_01_upload_advertising,
   03_insert_ad_image, 03_01_upload_ad_image, 03_insert_ad_gallery,
   03_01_upload_gallery) che sul server sono ancora i file ORIGINALI
  COMPLETI (247, 182, 220... righe), non gli stub 301.
Conseguenza: esistono DUE flussi di inserimento paralleli. Nessun link del
sito punta piu' al vecchio, ma resta raggiungibile via URL diretto o
vecchio bookmark: un utente potrebbe inserire un annuncio bypassando la
logica unificata (limiti gallery, routing tabella).
Non li ho toccati ne' messi nel .bat: cancellarli a freddo darebbe 404 e
violerebbe la dir. 19. Dimmi "procedi con gli stub" e te li converto in
redirect 301 verso il wizard unico.

===========================================================
FILE IN QUESTO ZIP (7)
===========================================================
allonwheel_style.css                 (campi uniformi + regola login circoscritta)
04_request_offer/04_request_offer.php (controlloForm rimosso + required HTML5)
sql/.htaccess                        (NUOVO - blocca dump e changelog)
mondocontainer/.htaccess             (NUOVO - blocca il dump estraneo)
cleanup_file_obsoleti.bat            (rimozione file non necessari)
aow_debug.sh                         (scanner v2, 3 bug corretti)
report_debug_DOPO_i_fix.txt          (report attuale)

## Ordine consigliato
1. Carica i 4 file del sito (css, php, i 2 .htaccess).
2. Prova: Request a quotation -> i 3 campi sono uguali; invio a campo vuoto
   -> il browser blocca con messaggio nativo; invio corretto -> arriva.
   Prova anche il login: i campi restano a 300px come prima.
3. Verifica che www.allonwheel.com/sql/allonwhe80316.sql dia 403.
4. BACKUP completo, poi esegui cleanup_file_obsoleti.bat (rispondi SI).
5. Decidi su mondocontainer\ e sui 7 file del vecchio wizard premium.
