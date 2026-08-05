# Allonwheel - Stampa PDF annuncio: ripristino per FREE + PREMIUM - 2026-06-29

## Il motivo per cui era "smarrita"
La stampa PDF era stata implementata SOLO per i premium, come "scheda tecnica":
- l'handler `03_ads/03_tech_pdf.php` interrogava esclusivamente `03_ads`;
- in `shared/view_ad.php` il bottone "Download PDF" stava DENTRO
  `if ($tech_url !== null)`; `tech_url` e' valorizzato solo dal wrapper premium
  (`03_ads/03_view_ad.php` -> '03_view_tech_details.php'), mentre il wrapper free
  (`02_free_ads/02_view_ad.php`) ha `tech_url => null`.
Risultato: sui FREE il bottone non e' mai stato reso (gate saltato) e comunque
l'handler non li avrebbe gestiti. Il PDF era quindi legato alla scheda tecnica
(premium), non all'annuncio in se'. Sui premium invece funziona ancora.

## Fix (endpoint condiviso, free + premium)
- NUOVO `shared/ad_pdf.php`: generalizza il vecchio handler. Accetta
  `?id_ads=N&t=<tabella>` con whitelist `['02_free_ads','03_ads']` (niente SQL
  injection sul nome tabella). Carica l'annuncio dalla tabella giusta, la sua
  gallery (`<tabella>_gallery`) e le immagini da `upload_image/<tabella>/`.
  Free: titolo, sottotitolo, immagine, meta (autore/tipo/condizione/prezzo),
  Vehicle type + Family (da vehicle_type/product_macro), descrizione, gallery.
  Premium: in piu' le categorie e la scheda tecnica raggruppata (come prima).
  mPDF via PdfHelper; se mPDF manca, degrada con messaggio (nessun fatal).
- `shared/view_ad.php`: il bottone "Download PDF" e' stato spostato FUORI dal
  gate `tech_url` -> ora appare per FREE e PREMIUM, e punta a
  `shared/ad_pdf.php?id_ads=..&t=<tabella>`. "Tech details" resta solo premium.
- `03_ads/03_tech_pdf.php`: ora e' un WRAPPER retro-compatibile (forza
  `t=03_ads` e include `shared/ad_pdf.php`), cosi' eventuali vecchi link
  continuano a funzionare senza codice duplicato.

## Note
- i18n `ad.pdf` gia' presente (en/it/fr/de).
- PHP 8.3 lint OK sui 3 file. CRLF preservati. Nessun `?v=`.
- Requisito server (invariato): `vendor/` con mPDF nella radice del progetto e
  permessi di scrittura su sys_get_temp_dir(). Senza mPDF il bottone risponde
  "PDF generation is not available" (nessun crash).
- Sicurezza: come l'handler originale, il PDF si genera per id valido; se vuoi
  limitarlo ai soli `status='approved'` (o al proprietario per le bozze) lo
  aggiungo su tua conferma.

## Ordine di applicazione
Sovrascrivi i 3 file mantenendo i percorsi:
  shared/ad_pdf.php (nuovo) · shared/view_ad.php · 03_ads/03_tech_pdf.php
