# Allonwheel — P2.9: Authority layer (credenziali azienda)

Data: 2026-06-20

## Cosa
Segnali di autorevolezza sulla **scheda azienda** (06_02_view_company.php), editabili
dal proprietario in una pagina dedicata. Set finale (come da tua indicazione):
- **Certificazioni = upload file**: ISO 9001, ISO 14001, ISO 45001 (PDF/JPG/PNG/WEBP, max 15 MB).
- **Associazioni / albi** (testo).
- **Area servita** (testo).
- **Referenze / clienti** (testo multilinea).
- Rimossi: anno di fondazione, garanzia, premi/riconoscimenti.
- **Pulsante "Request a quotation"** mostrato quando il proprietario dell'azienda e' premium.

## File
- `sql/Changelog/2026-06-20_company_authority.sql` — patch idempotente (guarded,
  MySQL 5.7): aggiunge a `06_company` le colonne cert_iso9001/cert_iso14001/
  cert_iso45001, associazioni, referenze, area_servita.
- `06_company/06_15_authority.php` — NUOVA pagina solo-proprietario:
  upload dei 3 certificati (UploadSecurity: magic-bytes, nome hash, allowlist),
  + associazioni/area/referenze; aggiorna SOLO le colonne authority via PDO
  (non tocca CompanyManager). Sostituendo un certificato, il vecchio file viene
  rimosso dal disco. CSRF + login richiesto.
- `06_company/06_02_view_company.php` — box **Credentials** dopo la descrizione:
  link download certificati, associazioni, area servita, referenze; pulsante RFQ
  (premium); link "Edit credentials" per il proprietario. Tier proprietario letto
  da users.user_tier.

## Da fare lato server (Marco)
1. Esegui `sql/Changelog/2026-06-20_company_authority.sql`.
2. Crea la cartella **`upload_image/06_company/certs/`** (scrivibile dal webserver;
   il codice non crea cartelle — dir.15). Consigliato un `.htaccess` nella cartella
   che disabiliti l'esecuzione PHP (i certificati sono serviti come file statici).

## Note
- Il pulsante RFQ punta a `04_request_offer/04_request_offer.php?company=<id>`:
  il comportamento differenziato free(02)/premium(03) e' P2.7 (prossimo blocco);
  qui il link e' gia' valido.
- L'Authority layer per gli utenti speciali (esperti/PM) e' previsto come estensione
  su contact_professional.php (anni esperienza, certificazioni, settori, lingue):
  lo aggiungo quando vuoi.

## Verifiche
- Full-project `php -l`: 265 file, 0 errori. Solo classi CSS esistenti
  (post_box, more, cleaner, contact_form). CRLF preservati.
