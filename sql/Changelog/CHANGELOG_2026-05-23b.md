# Allonwheel — Nota di rilascio (2026-05-23, sessione debug)

Pacchetto: `allonwheel_update_2026-05-23b.zip` — **4 file modificati**.
Estrai mantenendo la cartella `Allonwheel/`, sovrascrivendo i file omonimi sul server.

> Questo upload conteneva già le modifiche delle sessioni precedenti (consolidamento `SmartImage`, sidebar condizionale, whitelist quota illimitata `marco.candian@yahoo.it`, blocco "Manage this ad" nelle pagine di modifica, ricerca `q` e filtro `vtype`, rimozione Histats, viewport, ecc.). Qui sono inclusi **solo i nuovi fix** di questa sessione.

## Debug eseguito (ambiente reale)
Ho montato un database MySQL/MariaDB caricando il dump del progetto ed eseguito le pagine **a runtime** (non solo lint), con sessioni simulate per utente, admin e proprietario azienda. Esiti:
- Pagine pubbliche, ricerca `?q=`, filtro `?vtype=` (anche combinati): nessun warning/fatal.
- Inserimento end-to-end: prezzo in formato europeo "1.500,50" → salvato correttamente `1500.50` (conferma del fix di parsing già presente nell'upload).
- Modifica annuncio: i link "Manage this ad" rendono correttamente con `id_ads` risolto — premium = gallery **+** dettagli tecnici, free = solo gallery.
- Ownership check, pagine admin (auth dedicata `AdminAuth`), pagine azienda: redirect e accessi corretti.
- Testo UI: le pagine pubbliche risultano già in inglese; le occorrenze italiane residue sono solo commenti HTML (consentiti, dir. 0) e nomi di file immagine (dir. 15).

## Fix applicati in questa sessione

### 1. `sidebar.php` — sezione troncata ripristinata (BUG)
Nell'upload la sidebar dell'utente loggato era **tagliata**: il box "Help & info" (About us, What we do, F.A.Q., Conditions & rules, Contact us) mancava perché il file terminava sul commento di sezione. Ripristinato il box completo. Mantenuta la correzione valida dello slug presente nell'upload (`bundati` → `blindati` / "Armored").

### 2. `ads.php` e `ad_post.php` — redirect 301 (Fase 4.5)
Erano duplicati legacy della pagina blog, orfani (nessun link in ingresso ad `ads.php`; `ad_post.php` linkato solo da `ads.php`) e ancora indicizzabili (`robots: index, follow`). Trasformati in **redirect permanente 301 verso `browse.php`** con header `X-Robots-Tag: noindex, follow`, eliminando contenuto duplicato. I file restano come stub per non rompere vecchi bookmark.

### 3. `footer.php` — social link morti rimossi (Fase 5)
I link "Follow us" (Facebook, Instagram, LinkedIn, YouTube, Vimeo) puntavano tutti a `href="#"` (link morti). Rimossi e sostituiti con un link reale **"Contact us"** (`/contact.php`, root-relative così funziona da qualsiasi profondità di cartella). Un commento nel file indica come reinserire i profili reali quando disponibili.

## Verifica (dir. 2 e 10)
Lint 143/143 file PHP → 0 errori; re-render anti-regressione di pagine pubbliche e autenticate → ok; redirect legacy verificati; integrità ZIP 4/4 file identici; nessun file `.env`, `_notes`, `upload_image/` o `images/` incluso (dir. 11, 15).

## Punti del piano ancora aperti (per sessioni future)
- 4.4: uniformazione chiavi di sessione legacy (~20 file, es. `mydetails.php`, `03_insert_tech_details.php`) — intervento ampio e delicato, rinviato per non rischiare regressioni.
- 2.1: estensione dell'ownership-check capillare dove non già presente.
