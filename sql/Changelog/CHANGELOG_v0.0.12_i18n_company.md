# Allonwheel v0.0.12 — Delta: i18n tassonomia lato fornitore (servizi + form azienda)

Estende il blocco tassonomia al flusso SUPPLIER, cosi' buyer (RFQ) e supplier
(registrazione/vista azienda) mostrano le stesse etichette tradotte.
Da applicare sopra il delta i18n vtype.

## config/i18n.php — helper nuovo
- `tsvc($key, $fallback)`: come `tcat()` ma per i servizi (`t('svc.'.$key, $fallback)`),
  con fallback sulla label originale.

## lang/en.php + lang/it.php
- +6 chiavi `svc.*` (i 6 servizi accessori della classe). EN = label classe; IT tradotto.

## Punti display localizzati
- **06_10_register_company.php** (form registrazione, 3 loop checkbox):
  services -> `tsvc()`, products e products_special -> `tcat()`.
  (Le 3 righe label erano identiche: differenziate dal nome del checkbox.)
- **06_02_view_company.php** (scheda azienda pubblica, 2 elenchi):
  products -> `tcat()`, services -> `tsvc()`.

## Non incluso (stesso pattern, follow-up)
- **06_20_modify_company.php** (form modifica) e **06_12_company_products.php**:
  stessi loop, da wirare con `tcat()`/`tsvc()` quando si rivisita quel form.
- I **save-handler** (06_11) NON si toccano: elaborano POST, non mostrano label.

## Verifica
- `php -l` OK sui 5 file. `tsvc()` testato EN/IT + fallback. Entrambi i file caricano
  l'i18n (header/bootstrap). CRLF preservati. Nessuna modifica a chiavi DB/query/images.

## Avanzamento i18n
Corpi: header, footer, index, browse, contact, RFQ.
Tassonomia: vtype (buyer + supplier register/view) + servizi.
Restano: FAQ/Conditions/portfolio (testo), about/what_we_do (riscrivere),
modify/products form, ramo vtype "regular" (vehicle_types per slug).
