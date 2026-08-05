# Allonwheel — Restyle 2026: affinamenti (full-bleed + webfont)

Data: 2026-06-24

Due rifiniture richieste, entrambe in `allonwheel_style.css` (+ cartella `fonts/`).

## 1) Header e footer FULL-BLEED (stile Schuler)
La barra scura ora va **edge-to-edge** (da bordo a bordo schermo), col contenuto
allineato alla colonna centrale. Tecnica **box-shadow + clip-path** (niente
`overflow-x:hidden`, niente scroll orizzontale): la barra dipinge il colore a tutta
larghezza senza allargare il box. L'header diventa anche **sticky** (resta in alto
allo scroll).

## 2) Webfont OSWALD self-hosted (GDPR-safe)
Titoli, nav, wordmark, page-title ed hero ora usano **Oswald** (grottesco condensato,
look motorsport). Il font e' **self-hosted** in `fonts/oswald-var.woff2` (variabile,
~70 KB, tutti i pesi 200-700), caricato con `@font-face` e `font-display:swap`.
- **Nessuna modifica alla CSP**: e' `font-src 'self' data:`, il font e' stessa origine.
- **GDPR-safe**: niente Google Fonts CDN, nessun IP utente verso terzi.
- Licenza **OFL** inclusa (`fonts/OFL.txt`).
Il corpo del testo resta sul font di sistema (veloce, leggibile).

## File
- `allonwheel_style.css` — aggiunti i blocchi full-bleed e @font-face/applicazione
  Oswald in coda al RESTYLE 2026 (sostituisce il CSS del pacchetto precedente).
- `fonts/oswald-var.woff2` — font (da caricare in `/fonts/` sul webroot).
- `fonts/OFL.txt` — licenza Open Font License.

## Da fare sul server (Marco)
1. Sostituire `allonwheel_style.css`.
2. Creare la cartella `/fonts/` e caricarvi `oswald-var.woff2` (+ OFL.txt).
3. Bump cache CSS (`bump_css_version.sh`) o svuota cache.

## Verifiche
- `php -l` full-project: 0 errori. CSS: graffe bilanciate (359/359). CRLF preservati.
- L'anteprima `preview_restyle.html` incorpora Oswald (base64) e mostra il full-bleed.
