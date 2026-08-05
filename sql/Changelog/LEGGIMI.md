# Allineamento lang (en/it/fr/de) — dopo review del tree 3_11_Rent

## Stato trovato nel tuo tree
- La feature NOLEGGIO era applicata correttamente (14/14 punti OK, engine OK, CRLF ovunque).
- UNICO problema: file lingua disallineati.
  * lang/en.php: mancavano 15 chiavi sb.* (etichette sidebar). In inglese si vedevano
    lo stesso (fallback sul default te('sb.x','English')), ma en.php — che e' la base —
    era incompleto.
  * lang/it.php / fr.php / de.php: 3 chiavi DUPLICATE ciascuna
    (sb.my_wanted, sb.post_wanted, sb.register_company).

## Correzione
- en.php: aggiunte le 15 chiavi sb.* mancanti (valori inglesi).
- it/fr/de: rimossi i 3 duplicati (tenuta la prima occorrenza).
- Risultato: tutte e 4 le lingue = STESSO set di 361 chiavi uniche, zero duplicati.
  Lint OK, CRLF preservati.

## Applica
Sostituisci i 4 file in lang/. Nessun'altra modifica necessaria: il resto del tree
(07_rent, admin, browse, portfolio, index, sidebar) e' gia' corretto.
