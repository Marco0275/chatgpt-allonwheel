# Allonwheel V1.1 — Audit restyle + pulizia file

Data: 2026-06-24

## 1) Copertura del restyling — ESITO: COMPLETA
- Il restyle vive in `allonwheel_style.css` (blocco RESTYLE 2026 + componenti +
  full-bleed + webfont Oswald + pagine annunci). **87 pagine** lo linkano, inclusa
  l'area **_admin** → tutte le pagine con interfaccia sono coperte.
- `fonts/oswald-var.woff2` presente; `header.php` (wordmark) e `index.php` (hero) aggiornati.
- Le **12 pagine senza CSS** sono SOLO redirect 301 (`00_first/*`) ed endpoint di
  processo (`04_send_offer.php`, `contact_submit.php`): corretto, non hanno UI.

## 2) Unica criticita': cache-version non aggiornata
- **84 pagine** referenziano ancora `allonwheel_style.css?v=20260616`; solo 3 a `?v=20260625`.
- Conseguenza: i visitatori di ritorno con il vecchio CSS in cache continuano a vedere
  il look precedente finche' non scade la cache.
- **Fix**: il `.bat` include uno step OPZIONALE che bumpa `?v=20260616` -> `?v=20260626`
  su tutti i .php preservando CRLF/UTF-8 (via PowerShell .NET ReadAllText/WriteAllText).

## 3) File non utilizzati rimossi dal .bat
Il `pulizia_allonwheel.bat` (da eseguire nella ROOT del sito) rimuove:
- **24 cartelle `_notes`** (metadati Dreamweaver) + relativi `dwsync.xml`,
  **escludendo** `images/`, `upload_image/`, `vendor/`, `libs/` (dir.15 + third-party).
- Artefatti dev nel webroot: `gen_sidebars.py`, `bump_css_version.sh`, `MD5SUMS.txt`,
  `MANIFEST_MD5.txt`, `CHANGELOG_RESTYLE_finale.md`, `htaccess_clean_urls_PROPOSTA.txt`.
- CSS a **0 riferimenti**: `css/pirobox.module.css`, `css_pirobox/css_page.css`.
- Template legacy a **0 riferimenti**: `template/full_blog_post.php`,
  `template/template_logged.php`, `template/template_not_logged.php`.
- OPZIONALE (con conferma): cartella `Changelog/` (changelog di sviluppo nel webroot).

### NON tocca (di proposito)
- `images/`, `upload_image/` (dir.15) — comprese le loro `_notes`.
- `vendor/`, `libs/` (codice third-party: mPDF, PHPMailer).
- Tutte le `sidebar_*.php`: sembrano "0 riferimenti" ma sono **vive**, incluse a runtime
  da `include_sidebar.php` per basename (dispatcher). Cancellarle romperebbe le sidebar.
- `00_first/*.php`: sono **redirect 301** (SEO), vanno mantenuti.

## 4) Sicurezza del .bat
- Chiede conferma prima di eliminare; verifica di essere nella root (presenza `index.php`).
- Le rimozioni opzionali (Changelog, bump CSS) sono separate e chieste a parte.
