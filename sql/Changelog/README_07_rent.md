# Feature NOLEGGIO (07_rent) — pacchetto completo (STEP 1 + STEP 2)
Tutti i .php: `php -l` OK. CRLF preservati. JSON/i18n testati a runtime.

## INSTALLAZIONE (ordine)
1. **Database** — esegui, in quest'ordine:
   - `sql/Changelog/2026-07-26_rental_company.sql`  (colonne offers_rental + general_note su 06_company)
   - `sql/Changelog/2026-07-26_rental_core.sql`     (tabelle 07_rent_ads, 07_rent_requests, 07_rent_request_recipients)
   Entrambi idempotenti (rieseguibili) e non distruttivi.
2. **Cartelle upload** — crea (scrivibili dal webserver):
   - `upload_image/07_rent/original/`
   - `upload_image/07_rent/thumbnail/`
3. **File** — sovrapponi tutti i file mantenendo la struttura.

## COSA FA
- **06_10_register_company.php**: checkbox "offers rental" + nota (ricezione richieste per tier) + Note unico; rimosse le 3 colonne "Note (optional)".
- **07_rent/**:
  - `07_10 / 07_11` publish/save annuncio noleggio (solo veicoli speciali, stesse variabili dei free ads).
  - `07_20 / 07_21` vetrina + dettaglio (index,follow).
  - `07_30 / 07_31` form richiesta a CHECKBOX + matching + notifica.
  - `07_40` area lead del destinatario + "I take this" (claim).
- **libs/rent.class.php**: engine (createListing/listActive, createRequest, matchCompanies per tier gold>premium>free, notifyCompanies, leadsForUser/claimLead).
- **header.php + lang/**: voce menu "Vehicle rental" (Marketplace) in EN/IT/FR/DE. NB: header.php aggiunge 07_rent alla detection di $base_url (necessario o l'header si romperebbe nella sottocartella).

## MATCHING + MODALITA' free/premium/gold
`matchCompanies` restituisce l'UNIONE di:
 (a) utenti con annunci di noleggio APPROVATI (`07_rent_ads`) sui tipi richiesti;
 (b) aziende con `offers_rental=1` che hanno DICHIARATO in registrazione uno dei tipi
     richiesti (`06_company_products` / `06_company_products_special`), ANCHE senza annunci.
I vocabolari combaciano: gli slug del form richiesta (`special_types`) coincidono con le
chiavi delle checkbox di registrazione (racing_trailer, box_trailer, hospitality_units,
paddock_trailers, camper, laboratori_medici_mobili, uffici_mobili...).
I destinatari sono ordinati per tier (gold > premium > free). `notifyCompanies` registra
TUTTI i destinatari (visibili in 07_40) e invia l'EMAIL a gold/premium sempre + ai free entro
`AOW_RFQ_MAX_RECIPIENTS` (default 3, anti-spam).

## NOTE
- header.php e lang/*.php qui sono CUMULATIVI (contengono anche versioning ?v= e hero C dei passi precedenti): usa questi.
- Gallery multi-immagine non inclusa (annuncio con foto singola, come richiesto "stesse variabili"): estendibile.
