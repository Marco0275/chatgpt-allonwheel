# Allonwheel - Debug + pulizia codice + rimozione file obsoleti - 2026-07-08

Un solo ZIP. CRLF. PHP 8.3 lint: 786/786 OK (prima e dopo). Approccio
CONSERVATIVO: nessuna riscrittura di massa dei commenti (rischio di rottura
non giustificato); solo interventi mirati e verificati file per file.

===============================================================
DEBUG - cosa ho trovato
===============================================================
1. **[SICUREZZA - GRAVE] `config/env_copia`**: copia del file `.env` con
   `DB_PASSWORD`, `SMTP_PASS`, `CRON_TOKEN` IN CHIARO, e `config/` NON aveva
   un `.htaccess` a proteggerlo. Un file senza estensione .php viene servito
   come TESTO: chiunque poteva leggerlo via URL e ottenere le credenziali del
   database. -> aggiunto `config/.htaccess` (deny su env/*.env/*.ini/*.sql) e
   messo `env_copia` in cima al .bat di rimozione.
2. **[BUG] `contact.php`**: il form chiamava `onsubmit="return controlloForm()"`,
   funzione JS che non esiste piu' (residuo del template originale). Rimosso
   l'attributo: la validazione lato server (CSRF + honeypot + campi required)
   resta intatta e ora non c'e' piu' l'errore JS.
3. **[ESTRANEO] `mondocontainer/`**: cartella NON appartenente al sito (import
   contatti "cosmetica", un dump SQL diverso `allonwhe99119.sql`, xlsx, un
   JPG). Non e' referenziata da alcun file Allonwheel. Segnalata nel .bat per
   rimozione MANUALE (puo' contenere tuoi dati).
4. **[IGIENE] Dump SQL nel webroot**: `sql/allonwhe80316.sql` (copia del DB)
   accessibile pubblicamente -> nel .bat (meglio spostarlo fuori dal webroot).
5. Include/require rotti: 0 reali (1 falso positivo in un commento di esempio).
   Link vivi a 00_first / file rimossi: 0. Lint: 786/786 OK.

===============================================================
PULIZIA CODICE (mirata e sicura)
===============================================================
- Rimossi da 19 file i **changelog interni di sviluppo obsoleti** (blocchi
  "MODIFICHE Phase X:", "FIX Phase X:", marker "PHASE_5_HANDLED", suffissi
  "(Phase N)"): 40 righe di commento storico che non servono piu'. Mantenuta
  la descrizione dello scopo di ogni file. Ogni file ri-verificato con php -l.

PERCHE' NON ho rimosso TUTTI i commenti (scelta ingegneristica):
  su 284 file applicativi, una rimozione di massa via regex (a) rischia di
  corrompere codice quando `/* */` o `//` compaiono in stringhe/URL/regex,
  (b) distrugge la documentazione delle direttive (dir. 8/13/19) e delle
  decisioni non ovvie, (c) NON da' alcun vantaggio di performance (l'opcache
  ignora gia' i commenti). Ho quindi tolto solo il rumore storico reale.

===============================================================
FILE MODIFICATI IN QUESTO ZIP
===============================================================
config/.htaccess (NUOVO - protezione credenziali)
contact.php (rimosso onsubmit JS morto)
02_free_ads/: 02_00_select_type, 02_insert_ad, 02_insert_ad_image,
  02_01_upload_ad_image, 02_01_upload_advertising, 02_insert_ad_gallery,
  02_01_upload_gallery, 02_modify_insert_ad, 02_01_modify_upload_gallery
  (changelog interni obsoleti rimossi)
03_ads/: 03_modify_insert_ad, 03_insert_tech_details (idem)

===============================================================
cleanup_file_obsoleti.bat - cosa rimuove (dopo tua conferma "SI")
===============================================================
Chiede conferma backup, poi rimuove:
  1. config\env_copia (credenziali esposte) - PRIORITARIO
  2. 7 stub premium del wizard (sostituiti dal flusso unificato)
  3. 72 sidebar_*.php per-pagina orfane (dispatcher sidebar unificato;
     restano vive solo sidebar_vtype_search.php e sidebar_user_box.php)
  4. 37 cartelle _notes\ con dwsync.xml (residui Dreamweaver, no vendor)
  5. sql\allonwhe80316.sql (dump DB nel webroot)
  6. 2 script di migrazione one-shot gia' eseguiti
NON tocca: upload_image\, images\, vendor\, il flusso 02_free_ads\, i file
tech/documents/modify di 03_ads\, le 2 sidebar vive.
RICHIEDE DECISIONE MANUALE (non automatico): mondocontainer\ (estraneo al sito).

## Ordine consigliato
1. Carica config/.htaccess e i file .php di questo ZIP.
2. Verifica che il sito funzioni (contatto, inserimento annuncio).
3. BACKUP completo (file + DB).
4. Esegui cleanup_file_obsoleti.bat dalla radice, rispondi SI.
5. Valuta a mano mondocontainer\ e sql\allonwhe80316.sql.
