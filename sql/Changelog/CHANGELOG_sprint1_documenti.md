# Allonwheel — Sprint 1: Sicurezza + Documenti tecnici tracciabili

Base: tua ZIP. `php -l` OK su tutto il progetto. CRLF preservati. Tutto il codice
nuovo in **PDO + prepared**. Dir.8 rispettata (solo `allonwheel_style.css`).

## File nuovi
- **`libs/upload_security.class.php`** — upload sicuro documenti (zero-trust):
  validazione **magic bytes** (`finfo`), allowlist MIME (PDF/JPG/PNG/WEBP),
  dimensione max 15 MB, **nome HASH** (`random_bytes`). Non crea cartelle (dir.15).
- **`libs/ads_documents.class.php`** — modello PDO dei documenti: add / listByAd /
  get / **ownsAd** (anti-IDOR) / adIsPublic / **deleteOwned** / **logDownload** /
  **bumpPdfDownloads** (upsert su `seller_statistics`). Discriminatore `ad_table`
  (annunci in 02_free_ads e 03_ads).
- **`03_ads/03_documents.php`** — pagina gestione (solo **proprietario**):
  upload con tipo (technical_sheet/floorplan/certificate/manual/other), elenco,
  elimina. CSRF, ownership check, PRG. Stessi stili esistenti.
- **`download_doc.php`** (root) — **gateway proxy** tracciato e IDOR-safe: serve il
  file solo se l'annuncio e' pubblico (`approved`) o sei il proprietario; logga il
  download (IP hash GDPR) e incrementa il contatore; forza `Content-Disposition`.

## File modificato
- **`shared/view_ad.php`** — sezione "Technical documents" con link di download
  **tracciati** (via proxy) + link "Manage documents" per il proprietario.

## DB (patch in sql/Changelog/)
- **`2026-06-18_leadcentric_core.sql`** — schema riconciliato (vedi referto):
  `wanted_ads`, `ads_documents`, `ads_document_downloads`, `seller_statistics`,
  `seo_taxonomy_cache` + estensione di `quote_requests`. 5.7, INT UNSIGNED,
  utf8mb4_unicode_ci, niente DROP. **Esegui questa patch** prima di usare i documenti.

## DA FARE A MANO (dir.15: il codice non crea cartelle in upload_image)
1. Crea la cartella **`upload_image/ads_documents/`** sul server.
2. Mettici dentro l'`.htaccess` allegato (file
   `PLACE_AS__upload_image__ads_documents__.htaccess` → rinominalo `.htaccess`):
   nega l'accesso diretto, così i documenti passano solo dal proxy tracciato.
3. Esegui la patch SQL.

## Ordine di applicazione
1) patch SQL → 2) crea cartella + `.htaccess` → 3) copia i file nuovi/modificato.

## Prossimo (Sprint 1 cont. / Sprint 2)
- Audit CSRF/IDOR sui form legacy.
- Motore **Wanted** (richieste inverse) + algoritmo di matching (free inclusi).
