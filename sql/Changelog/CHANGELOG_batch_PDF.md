# Allonwheel — Batch PDF: generazione PDF via mPDF (sbloccato da vendor/autoload.php)

Base: tua ZIP. 7 file (2 nuovi). `php -l` OK su 192 file. CRLF preservati.

## Cosa c'è
- **`libs/pdf_helper.class.php`** — wrapper robusto per mPDF. Carica
  `vendor/autoload.php`, controlla `\Mpdf\Mpdf`, genera il PDF da HTML e lo invia
  come download. Se mPDF manca **degrada senza fatal** (`available()` → false).
- **`03_ads/03_tech_pdf.php`** — endpoint: dato `?id_ads=N`, carica l'annuncio
  premium (`03_ads`) e la sua **scheda tecnica** (`03_ads_tech_details`), costruisce
  l'HTML (titolo, descrizione, elenco specifiche tecniche presenti) e genera il
  **PDF scaricabile** `tech-sheet-<id>.pdf`. Le voci tecniche sono elencate in modo
  generico (flag a 1 → presente; campi valore → "Etichetta: valore").
- **`shared/view_ad.php`** — bottone **"Download PDF"** accanto a "Tech details"
  (solo annunci premium con scheda tecnica). Chiave i18n `ad.pdf` (EN/IT).

> Nota dir. 8: il PDF usa CSS **inline proprio** (è un documento generato, non la UI
> del sito; non può usare `allonwheel_style.css`). Il sito resta invariato.

## Come provarlo (sul tuo server, dove esiste vendor/)
1. Apri un annuncio **premium** che abbia la scheda tecnica.
2. Clicca **"Download PDF"** accanto a "Tech details".
3. Deve scaricarsi `tech-sheet-<id>.pdf`. Se invece compare il messaggio
   *"PDF generation is not available…"*, allora `vendor/autoload.php` non viene
   trovato dal percorso `libs/../vendor/` o mancano i permessi sulla cartella temp:
   verifica che `vendor/` sia nella **radice** del progetto e che PHP possa scrivere
   in `sys_get_temp_dir()`.

## Prossimo
- Estendere il PDF al **flusso RFQ** (allegare la scheda/riepilogo alla mail delle
  aziende) — richiede aggiungere il supporto allegati a `Mailer` (PHPMailer
  `addAttachment`) e SMTP attivo.
- Forum (thread/risposte Esperto) + notifiche email ai partecipanti (SMTP) + invio
  elenco PM/consulenti alle aziende con `wants_pm_list=1`.
