# Allonwheel V_1_2 - Fix dettagli UI + dashboard admin + pulizia - 2026-06-25

Base: tua copia V_1_2 (CSS rivisto, link CSS reso NON versionato).

## Causa radice del "CSS che non si aggiorna"
Senza piu' il `?v=`, l'`.htaccess` serviva il CSS con `max-age=2592000` (30 gg):
il browser NON rivalidava per 30 giorni -> vedevi il CSS vecchio.
- **`.htaccess`**: separato il caching. **CSS/JS -> `Cache-Control: no-cache,
  must-revalidate`** (rivalida ad ogni richiesta, 304 se invariato: le modifiche
  compaiono subito senza `?v=`). Immagini/font -> `max-age=31536000` (1 anno).
  ExpiresByType css/js portato a 0s. Cosi' "niente ?v=" funziona davvero.

## Dettagli UI (CSS, in coda al foglio)
- **MORE**: da navy (`--brand`) a **rosso accent** (`--accent`), coerente con il
  pulsante ricerca e la CTA; hover su `--accent-d`.
- **Freccia ">" del MORE**: ancoraggio `position:relative` + centratura verticale
  robusta (`top:50%; translateY(-50%)`), niente piu' freccia in alto.
- **Pulsante ricerca rosso**: centrato verticalmente e ben inset nel campo a
  pillola (`top:50%; translateY(-50%)`), non sborda piu'.
- **Barra tratteggiata**: rinforzo del reset su `.post_meta` e
  `.post_box .post_meta` (riguarda anche la nav dell'area admin).

## Dashboard admin
- **Header**: `#site_title` era vuoto (`&nbsp;`) -> ora mostra logo + wordmark
  ALL ON WHEEL + tag "Admin area" (come il sito).
- **Logout**: la voce "Sign out" -> rinominata **"Logout"**; `logout.php` ora
  reindirizza alla **home pubblica** (`/index.php`) invece che al login admin.
- Footer admin gia' presente (admin_footer.php); ora con header brandizzato il
  layout e' coerente.

## Pulizia
- `pulizia_allonwheel.bat`: rimuove _notes (Dreamweaver) + dwsync.xml +
  desktop.ini (esclusi images/upload_image/vendor/libs), gli scaffold orfani in
  `template/` (referenziati in 0 pagine) e MD5SUMS.txt. Chiede conferma, va
  eseguito dalla radice del sito. NON tocca images/ ne upload_image/.

## Impostazione CSS (tua direttiva)
- Niente `?v=`: normalizzati i 3 file `template/*` che lo avevano ancora.
  Nessuna pagina aggiunge piu' il cache-buster.

Lint PHP 8.3 OK, CRLF (LF su .htaccess), CSS bilanciato.
