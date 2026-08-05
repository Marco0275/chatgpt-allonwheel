# Noleggio (07_rent) — STEP 1: registrazione azienda
File modificati/nuovi (php -l OK, CRLF preservati):
- 06_company/06_10_register_company.php : rimosse le 3 colonne "Note (optional)"
  (Accessory services / Vehicle body types / Special categories); aggiunti:
  checkbox "offers_rental", nota informativa sulla ricezione delle richieste noleggio
  (free/premium/gold), e un campo "Note" UNICO in fondo (general_note).
- 06_company/06_11_save_company.php : salva offers_rental + general_note; le note
  per-riga non vengono piu' lette (passate '' alle tabelle di giunzione).
- libs/06_company.class.php : insertCompany scrive offers_rental + general_note.
- sql/Changelog/2026-07-26_rental_company.sql : ALTER idempotente su 06_company
  (aggiunge offers_rental + general_note). Esegui questa PRIMA di usare il form.

## Da fare (STEP 2, dopo tua conferma architettura): la sezione 07_rent vera e propria.
