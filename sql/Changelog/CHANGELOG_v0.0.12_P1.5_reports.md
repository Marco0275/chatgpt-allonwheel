# Allonwheel v0.0.12 — Delta: P1.5 (status pubblico) + allineamento 3 report DOCX

## A) P1.5 — Visibilita' pubblica di browse.php
- `browse.php` ora filtra **solo `status='approved'`** su entrambi i rami del UNION
  (02_free_ads + 03_ads), via `$status_clause`. Coerente con la gallery di `index.php`
  (che gia' filtrava approved); chiude l'esposizione di annunci pending/rejected nel
  marketplace pubblico (dir. 11). 'approved' e' costante, in SQL (nessun bind).
- **Reversibile**: per tornare a mostrare tutto, rimuovere `{$status_clause}` dalle due WHERE.
- `php -l` OK, CRLF preservato.

## B) 3 report DOCX (report/) allineati allo stato reale
Aggiunta in coda a ciascun report una sezione **"Aggiornamento di stato — v0.0.12
(15 giugno 2026)"**, senza riscrivere il pregresso, con distinzione **Realizzato /
Ancora aperto**. Stile, struttura e lingua (IT) conservati. Validati (docx schema OK).

- **FASE1_IA_Wireframe_Tassonomia**: marcati come fatti header unico Marketplace, 5 macro
  product_macros, sidebar rimodellate, **home index.php 70/30 (v0.0.12)** e **copy macro
  (v0.0.12)**; restano aperti authority layer, hero_image, i18n.
- **FASE2_Marketplace_Internazionale**: fatti filtro ?macro=, vista unificata, moderazione,
  lead admin, **P1.5 approved-only**, **301 view_ads + footer ripulito**; aperti modello dati
  internazionale, ricerca a faccette, SEO usato, **i18n bloccata su architettura URL**.
- **FASE2_Parte2_Configuratore_RFQ**: confermato il motore RFQ (ponte macro->fornitori,
  lead, consenso GDPR, Mailer); da fare Step 2 tecnico (03_ads_tech_details + PDF) e
  uniformare mysqli->PDO.

## Ordine di applicazione
Sovrascrivere `browse.php` e i 3 `.docx` in `report/`. Nessuna modifica DB richiesta.
