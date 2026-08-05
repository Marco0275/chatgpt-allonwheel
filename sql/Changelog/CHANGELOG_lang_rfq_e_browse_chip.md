# Allonwheel — Rifiniture: chiavi lang RFQ + rimozione chip macro in browse

Data: 2026-06-20

## 1) Chiavi lang RFQ (4 dizionari)
Aggiunte `rfq.tech_title` e `rfq.tech_intro` in lang/en.php, it.php, fr.php, de.php
(prima usavano il fallback EN inline nel configuratore P2.7). Tradotte EN/IT/FR/DE.

## 2) Rimozione chip macro in cima a browse.php
Rimossa la chip-bar delle 5 macro-famiglie in testa a `browse.php` (era l'ultimo
filtro "in cima" rimasto, coerente con la scelta P2.8 di filtrare via sidebar/ricerca).
La **logica `?macro=` resta intatta** ($active_macro, ProductMacro): il filtro per
famiglia continua a funzionare via URL e via eventuali link in sidebar; sparisce solo
la barra di chip in testa.

## Verifiche
- Full-project `php -l`: 267 file, 0 errori. CRLF preservati. Nessun residuo `macro_filter`.
