# Allonwheel — Cancellare i vecchi file premium MANTENENDO i link
15 lug 2026. Base: V2_2 reale. Un solo ZIP. CRLF. PHP lint 786/786 OK.
Simulazione di cancellazione eseguita: 0 include rotti, 0 href morti.

===========================================================
LA SOLUZIONE: il redirect lo fa il WEBSERVER, non un file PHP
===========================================================
Il problema: volevi cancellare i 7 file del vecchio inserimento premium,
ma i vecchi URL/bookmark devono continuare a portare al wizard nuovo.
Uno "stub PHP" non va bene: sarebbe un file da NON cancellare, quindi
non risolve la tua richiesta.

Soluzione: **03_ads/.htaccess con RewriteRule 301**. mod_rewrite agisce
nella fase di traduzione dell'URL, PRIMA che Apache cerchi il file su
disco: quindi il redirect funziona ANCHE con i file cancellati.
Zero file PHP fantasma, vecchi link vivi, dir. 19 rispettata.

mod_rewrite e' gia' attivo e in uso sul tuo sito (i18n /en/, /marketplace/),
quindi la soluzione e' garantita. E' incluso comunque un fallback
mod_alias (RedirectMatch) nel caso improbabile che non lo fosse.

Mappa dei redirect (tutti 301 -> wizard con premium preselezionato):
  03_ads/03_00_select_type.php        \
  03_ads/03_insert_ad.php              \
  03_ads/03_01_upload_advertising.php   >  /02_free_ads/02_00_select_type.php?listing=prem
  03_ads/03_insert_ad_image.php        /
  03_ads/03_01_upload_ad_image.php    /
  03_ads/03_insert_ad_gallery.php    /
  03_ads/03_01_upload_gallery.php   /

===========================================================
IL DETTAGLIO CHE POTEVA ROMPERE TUTTO (evitato)
===========================================================
Alcuni file VIVI hanno nomi quasi identici a quelli da cancellare:
  03_01_upload_advertising.php      -> DA CANCELLARE
  03_01_upload_advertising_MODIFIED.php -> VIVO (modifica annuncio!)
  03_01_upload_gallery.php          -> DA CANCELLARE
  03_01_MODIFY_upload_gallery.php   -> VIVO (modifica gallery)
  03_01_upload_TECH_advertising.php -> VIVO (scheda tecnica)
Le regole sono percio' ANCORATE (^nome\.php$), mai prefissi.
TESTATE una per una su tutti i 28 file di 03_ads/: i 7 vengono
redirezionati, i 21 vivi NON vengono toccati. (Nel .htaccess c'e' un
avviso: non trasformarle in prefissi.)

===========================================================
ALTRE DUE COSE TROVATE CON LA SIMULAZIONE
===========================================================
1. 03_ads/03_error_insert_ad.php (pagina VIVA, usata dai flussi di
   modifica) aveva 'retry_url' => '03_insert_ad.php', cioe' puntava a un
   file che stai per cancellare: il pulsante "riprova" sarebbe finito su
   un redirect inutile. Ora punta direttamente al wizard unificato.
   E' l'UNICO riferimento vivo che esisteva verso i 7 file.
2. sidebar.php e' uno shim orfano di 10 righe cha fa solo
   require sidebar_default.php (una delle 72 sidebar orfane). Cancellando
   le orfane, sidebar.php sarebbe rimasto rotto: l'ho aggiunto alla lista
   (nessuno lo include: verificato). Ora gli orfani sono 73.

===========================================================
cleanup_file_obsoleti.bat  —  con GUARDIA di sicurezza
===========================================================
Prima di cancellare qualsiasi cosa, lo script VERIFICA che
03_ads\.htaccess esista e contenga le regole giuste: se manca, si ferma
senza toccare nulla (cosi' non puoi cancellare i file lasciando 404).
Poi chiede conferma del backup.

Cosa rimuove:
 [1] config\env_copia                (credenziali in chiaro)
 [2] sql\allonwhe80316.sql           (dump DB - se ti serve, SPOSTALO prima)
 [3] 7 file vecchio inserimento premium  (URL coperti dal .htaccess)
 [4] 73 file sidebar orfani          (72 sidebar_* + shim sidebar.php)
 [5] 42 cartelle _notes\             (residui Dreamweaver)
 [6] 2 script one-shot               (patch_site_init, migrate_session_legacy)
NON tocca: upload_image\, images\, vendor\, 02_free_ads\, i 21 file vivi
di 03_ads\, sidebar_vtype_search.php, sidebar_user_box.php, i cron attivi
(expire_ads, purge_personal_data, cleanup_unused_uploads).
Decisione tua: mondocontainer\ (estranea al sito, gia' schermata).

===========================================================
VERIFICHE FATTE (simulando la cancellazione)
===========================================================
- Cancellati i 7 + i 73 orfani + il resto su una copia: LINT 704 file,
  0 errori; 0 include/require rotti; 0 href verso file inesistenti.
- Regole 301 testate su tutti i 28 file di 03_ads: 7 redirect, 21 intatti.

===========================================================
FILE IN QUESTO ZIP (3)
===========================================================
03_ads/.htaccess              (NUOVO - i 7 redirect 301 + fallback)
03_ads/03_error_insert_ad.php (retry_url -> wizard unificato)
cleanup_file_obsoleti.bat     (con guardia sul .htaccess)

## Ordine OBBLIGATORIO
1. Carica 03_ads/.htaccess e 03_ads/03_error_insert_ad.php.
2. PRIMA di cancellare, prova: www.allonwheel.com/03_ads/03_insert_ad.php
   -> deve gia' redirezionare al wizard (il file c'e' ancora, ma il
   .htaccess ha la precedenza). Se redirige, il .htaccess funziona.
3. BACKUP completo (file + DB).
4. Esegui cleanup_file_obsoleti.bat dalla radice, rispondi SI.
5. Ri-prova lo stesso URL: deve ancora redirezionare (ora senza il file).
   Prova anche che la MODIFICA di un annuncio premium funzioni
   (03_01_upload_advertising_modified.php non deve redirezionare).
