# Allonwheel v0.0.12 — Delta: i18n tassonomia categorie (vtype)

Blocco trasversale: traduce le ETICHETTE delle categorie veicolo, condivise da piu'
pagine, senza toccare valori salvati ne' query. Da applicare sopra i delta precedenti.

## Approccio (sicuro e reversibile)
Le categorie sono `chiave_stabile => "Label EN"` nella classe `06_company.class`.
La CHIAVE e' salvata nel DB (product_key) e usata nelle WHERE; la label e' solo display.
-> Traduco **solo la label a video**, per chiave, lasciando intatti chiavi/valori/query.

## config/i18n.php — helper nuovo
- `tcat($key, $fallback)`: ritorna `t('vtype.'.$key, $fallback)`; se la chiave non e'
  tradotta, ricade sulla label originale (nessun buco).

## lang/en.php + lang/it.php
- +33 chiavi `vtype.*` (27 categorie `$products` + 6 `$products_special`).
  EN = label esatta della classe; IT tradotto a mano.

## Punti display localizzati
- **04_request_offer.php** (form RFQ): entrambi i loop checkbox
  (`$products` e `$products_special`) ora mostrano `tcat($key, $label)`.
- **06_company/06_30_company_directory.php**: ramo categoria SPECIALE
  (`$vtype_name = tcat($special, ...)`), usato nel titolo "fornitori di X".

## Non incluso (motivato)
- **Ramo vtype "regular" della directory**: la label arriva da una tabella DB
  (`vehicle_types.name` per slug) -> spazio-chiavi diverso dalle chiavi di classe.
  Va tradotto con un set keyed-by-slug separato (serve dump della tabella). Lasciato EN.
- **`$services` (6)** e il **form registrazione azienda**: stessa tecnica `tcat()`/
  un set `svc.*`; da fare quando si traduce quel form.

## Verifica
- `php -l` OK sui 5 file. `tcat()` testato EN/IT + fallback. CRLF preservati.
  Nessuna modifica a chiavi DB, query o `images/`.

## Avanzamento i18n
Corpi: header, footer, index, browse, contact, RFQ. Tassonomia: vtype (display).
Restano: FAQ/Conditions/portfolio (testo), about/what_we_do (da riscrivere),
vtype "regular" via vehicle_types, servizi + form azienda.
