# Allonwheel — P2.7: RFQ differenziata free/premium + configuratore tecnico + PDF

Data: 2026-06-20

## Modello (come confermato)
- **Utente free** -> richiesta "base" (campi della cartella 02): nome/email/oggetto,
  categorie, messaggio. Nessuna scheda tecnica.
- **Utente premium** (o admin) -> in piu' il **configuratore tecnico**: lo STESSO
  form a checkbox/campi con cui si pubblica un annuncio premium (cartella 03,
  tabella 03_ads_tech_details), vuoto, compilato dall'acquirente.
- Il **PDF** della richiesta tecnica ha gli **stessi campi e la stessa impaginazione**
  del configuratore: e' generato dallo stesso identico partial.

## Come e' garantita l'identita' configuratore<->PDF
Unica sorgente dei campi: **`shared/tech_details_fields.php`**. Definisce i gruppi e
i campi (General options, Lift facilities, Cargo facilities, Office furniture,
Electrical system, Outside finishing, Chassis, External dimension + "cars") in un solo
posto e li rende in due modalita':
- `mode='form'`  -> checkbox e input compilabili (il configuratore RFQ);
- `mode='print'` -> sola lettura con stato ☑/☐ e valori (il PDF e l'email).
Cosi' i campi e l'ordine sono identici per costruzione.

## File
- `shared/tech_details_fields.php` — NUOVO partial, sorgente unica dei campi tecnici
  (solo classi CSS esistenti: tbl_collapse, thead_row, checkbox, control, input_field).
- `04_request_offer/04_request_offer.php` — rileva il tier (UserTier::getTier); se
  premium/admin mostra la sezione "Technical configurator" (partial mode form).
- `04_request_offer/04_send_offer.php` — se l'acquirente ha compilato la scheda,
  la include (mode print) nel **corpo email** inviato a ogni fornitore (campi
  corrispondenti) e la salva in sessione per il PDF.
- `04_request_offer/04_contact-success.php` — se c'e' una scheda tecnica, mostra il
  link "Download your technical request (PDF)".
- `04_request_offer/04_rfq_pdf.php` — NUOVO endpoint: genera il PDF dalla scheda in
  sessione (stesso partial, mode print) via PdfHelper/mPDF; degrada senza errori se
  mPDF non e' installato.

## Note
- Il PDF usa un CSS minimale proprio (contesto PDF, non il sito): stessa struttura a
  gruppi + colore brand #1B2A41 sulle intestazioni; i campi e i gruppi coincidono con
  il configuratore.
- Le stringhe nuove (rfq.tech_title, rfq.tech_intro) usano fallback inline EN; se vuoi
  le aggiungo ai dizionari lang/{en,it,fr,de}.php.
- L'utente non loggato vede la versione base (free). La differenziazione e' sul tier
  reale dell'utente (users.user_tier).

## Verifiche
- Full-project `php -l`: 267 file, 0 errori. Render form e print verificati. CRLF preservati.
