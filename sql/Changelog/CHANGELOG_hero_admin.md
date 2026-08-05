# Allonwheel — L'admin puo' cambiare l'immagine hero della home
20 lug 2026. Un solo ZIP. CRLF. PHP lint 716/716 OK.
Base: V3.1 (Allonwheel_V3_1_Login_ospite.zip). Nessuno stile nuovo (dir. 8).

===========================================================
COSA C'ERA E COSA HO FATTO
===========================================================
L'hero della home (index.php, riga 105) aveva lo sfondo HARDCODED:
    background-image:url('images/project.png')
Ora l'immagine e' gestita dall'admin da una pagina dedicata.

Flusso:
  _admin/admin_hero.php  ->  carica/sceglie l'immagine
        salva il percorso in site_settings['hero_image']
  index.php  ->  legge site_settings['hero_image'] e la usa come sfondo hero
        (fallback a images/project.png: senza la patch, identico a prima)

===========================================================
COME RISPETTA LE DIRETTIVE
===========================================================
- dir. 15 (images/ e upload_image/ intoccabili DA CODICE nel senso di
  images/): l'upload va in upload_image/hero/, MAI in images/. Verificato:
  nessun mkdir/move/write verso images/. Stesso identico pattern di
  admin_macros.php, che gia' carica in upload_image/macros/.
- dir. 8 (solo CSS esistente, niente stile inline): la pagina admin usa solo
  classi gia' presenti (post_box, gallery, more float_r, post_meta, cleaner).
  Verificato: 0 style inline, 0 classi nuove. (Avevo usato dei style inline
  in prima battuta e li ho tolti proprio per questo.)
- Sicurezza come le altre pagine admin: AdminAuth::requireAdminSession() +
  csrf_verify() + validazione immagine con getimagesize (magic bytes, non
  solo estensione) + limite 6 MB.

===========================================================
DUE MODI PER L'ADMIN
===========================================================
1. UPLOAD di una nuova immagine (JPG/PNG/WebP, max 6 MB) -> salvata in
   upload_image/hero/ con nome hero-<timestamp>.<ext> e impostata subito.
2. RIUSA una gia' caricata: la pagina elenca le immagini presenti in
   upload_image/hero/ come thumbnail con radio; si sceglie e si conferma.
   (Cosi' non si ricarica ogni volta la stessa.)

Sicurezza sulla scelta "riusa": si accetta SOLO un percorso che matcha
  ^upload_image/hero/[A-Za-z0-9._-]+$  ed esiste su disco.
Testato: traversal (../../config/env), path assoluti, injection, e persino
"images/project.png" (fuori da hero/) vengono RESPINTI. L'admin non puo'
puntare l'hero a un file arbitrario del server.

===========================================================
site_settings: una tabella riusabile
===========================================================
Non c'era una tabella di impostazioni generiche. Creata site_settings
(chiave/valore) - servira' anche per le prossime impostazioni di sito, non
solo l'hero. Patch idempotente e non distruttiva (CREATE TABLE IF NOT EXISTS
+ INSERT ON DUPLICATE KEY che NON sovrascrive). La riga iniziale punta a
images/project.png, quindi appena applicata la home e' identica a ora.

libs/site_settings.class.php: wrapper minimale (get/set con cache di
richiesta). get() e' difensivo: se la tabella non esiste ancora, ritorna il
default e la home NON si rompe. Puoi applicare i file PHP prima del DB.

===========================================================
LA CARTELLA upload_image/hero/
===========================================================
Come admin_macros con upload_image/macros/, la cartella viene creata a
runtime al primo upload (@mkdir). Se il tuo hosting non permette la mkdir da
PHP, creala a mano una volta (come le altre sottocartelle di upload_image/)
e dalle gli stessi permessi.

===========================================================
FILE IN QUESTO ZIP (5)
===========================================================
NUOVI
  _admin/admin_hero.php                      pagina admin (upload + riuso)
  libs/site_settings.class.php               helper get/set impostazioni
  sql/Changelog/2026-07-20_site_settings.sql tabella site_settings
MODIFICATI
  index.php                                  hero legge da site_settings
  _admin/admin_header.php                    voce menu "Home hero"
                                             (+ "Hero images" rinominata
                                              "Macro heroes" per non
                                              confonderla con questa)

## Ordine di applicazione
1. Applica la patch SQL 2026-07-20_site_settings.sql (idempotente).
2. Carica i 5 file.
3. Entra in _admin -> voce "Home hero": carica un'immagine e salva.
4. Apri la home: lo sfondo dell'hero e' quello nuovo.
   (Prima di toccare nulla, la home resta su images/project.png.)
