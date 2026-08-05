# Allonwheel — Sprint 3 (P4): Dashboard venditore (lead-centric)

Base: tua ZIP. `php -l` OK su tutto il progetto. CRLF preservati. PDO+prepared. Dir.8 OK.

## File nuovo
- **`01_login/seller_dashboard.php`** — cruscotto del venditore che unisce i dati
  lead-centric costruiti negli sprint precedenti:
  - **Overview**: n. annunci, totale download documenti, RFQ aperte, Wanted compatibili.
  - **Open RFQ (received leads)**: lead realmente distribuiti alle aziende
    dell'utente (`quote_request_recipients` → `06_company.user_id` →
    `quote_requests`), con stato, buyer, macro, messaggio.
  - **Matching wanted requests**: richieste "Wanted" attive sulle **macro** dei
    propri annunci (escluse le proprie), con link alla richiesta.
  - **Document downloads per listing**: per ogni annuncio, n. download
    (`seller_statistics.pdf_downloads`) + n. documenti + link "manage".

## File modificato
- **`sidebar_user_box.php`** — voce **"Seller dashboard"** nel box My account.

## Note
- I download sono reali (tracciati dal proxy `download_doc.php`). Le **views**
  (`seller_statistics.views`) non sono ancora incrementate da codice: restano a 0
  finche' non si aggiunge il conteggio visite (possibile estensione futura).
- Le RFQ "ricevute" dipendono dalla registrazione di un'azienda; per i venditori
  senza azienda la sezione mostra un invito a registrarla.

## Punti ancora in sospeso (dopo questo)
- **P5 — Router SEO** (URL puliti per annunci/categorie + sitemap, coerente con /en//it/).
- **Home `index.php`**: ancora demo motorsport del template — bloccata sulla tua
  decisione su immagini/copy (asset in `images/00_first/`, dir.15).
- **Traduzioni** `lang/fr.php` / `lang/de.php` (placeholder EN) — a tua cura.
- **Conteggio views** annunci per `seller_statistics` (opzionale).
