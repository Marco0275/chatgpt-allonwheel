# Allonwheel V2_0 - Debug completo, piani landing, annunci - 2026-07-07

Un solo ZIP. CRLF. PHP 8.3 lint: 786/786 OK (prima e dopo le modifiche).
ORDINE: eseguire PRIMA sql/Changelog/2026-07-07_plans_gold.sql, poi i file.

===============================================================
PARTE 1 - DEBUG V2_0 + confronto con i tuoi task manuali
===============================================================
Verificato sul pacchetto caricato:
- Lint PHP 786/786: 0 errori. Tutti i blocchi M1-M5 presenti e coerenti
  (seo_head, kpi.php, saved_search_*, cron, sidebar a tendine, 3 patch SQL).

TODO LIST (task tuoi, in ordine di priorita'):
1. **SQL**: eseguire sul DB, se non gia' fatto: founding_partner.sql,
   rfq_source_page.sql, saved_searches.sql + la NUOVA 2026-07-07_plans_gold.sql
   (OBBLIGATORIA prima di caricare i file di questo giro).
2. **footer.php**: i 3 link social sono ancora href="#" -> URL reali.
3. **Server/.env**: SMTP_*/MAIL_* + record SPF/DKIM/DMARC (test mail-tester
   >=9/10); HISTATS_ID; CRON_KEY + crontab
   `0 7 * * * php /percorso/cron/saved_search_alerts.php`.
4. **Search Console**: inviare sitemap.xml; backup completo pre-lancio.
5. **landing.html**: e' una pagina Tailwind standalone, NON integrata nel
   sito (CSS diverso, jQuery assente). L'ho usata come riferimento per i
   piani. Se la vuoi come pagina prezzi nello stile del sito, la converto.
6. **Funzionalita' dei piani NON implementate qui** (fuori dallo scope
   "parametri di pubblicazione annunci", chiedono una tua conferma perche'
   toccano visibilita' di fornitori esistenti): profilo Basic NASCOSTO
   dalla directory; tasto "Contatta direttamente" solo Silver+Gold;
   portfolio video Gold; logo in pista / pass track day (offline).
   Dimmi se procedo col nascondimento Basic e il gating del contatto.

===============================================================
PARTE 2 - Implementato in questo giro
===============================================================

## A) Piani dalla landing -> parametri di pubblicazione
- **SQL** `2026-07-07_plans_gold.sql`: `users.user_tier` diventa
  ENUM('free','premium','gold','admin'). Mappatura: free=Basic,
  premium=Silver, gold=Gold Domination.
- **libs/user_tier.class.php**: limiti sul TOTALE annunci (free+premium):
  Basic max **2** - Silver max **15** - Gold **illimitati** (admin e
  whitelist invariati). Basic non puo' pubblicare premium ads (come prima).
  Messaggi con nome piano e invito all'upgrade. Nuovi metodi
  setGold/revokeGold.
- **_admin/grant_premium.php**: parametro `plan` (silver|gold); la revoca
  gestisce anche Gold (torna Basic).
- **_admin/dashboard.php**: conteggio gold; tier mostrato come
  "★ GOLD" / "premium (Silver)"; azioni per riga: free -> bottoni
  [Silver] [★ Gold]; premium -> [★ Gold] (upgrade) + [Revoke];
  gold -> [Revoke Gold].
- **Directory fornitori**: i Gold sono FISSI IN CIMA in tutte le viste
  (elenco, ricerca, per tipo veicolo, per famiglia) - JOIN su users +
  ordinamento gold-first (in PHP nel metodo per famiglia, che unisce due
  query).

## B) Annunci free = prima pagina + gallery
- **shared/view_ad.php**: la sezione "Technical documents" compare SOLO
  sugli annunci premium (03_ads); sui free ne' l'elenco ne' il tasto
  "Manage documents".
- **03_ads/03_documents.php**: enforcement server-side - richieste con
  ad_table=02_free_ads -> 403. I premium mantengono tutti i documenti
  attuali; eventuali documenti gia' caricati su free restano nel DB ma
  non sono piu' raggiungibili (nessuna cancellazione, dir. 9).

## C) browse.php: premium in cima
UNION con flag `is_prem` (1=premium, 0=free) e
`ORDER BY is_prem DESC, created_at DESC`: prima tutti i premium (dal piu'
recente), poi i free.

## D) Modifica INTEGRALE degli annunci (free e premium)
Form `02/03_modify_insert_ad.php` (+ enctype multipart):
- **Vehicle type** (tendina Road/Special da `vehicle_types`);
- **Family** (tendina dalle 5 macro);
- **Replace main image** (facoltativo, mostra il file corrente).
Handler `02/03_01_upload_advertising_modified.php`:
- vehicle_type/product_macro whitelistati dal DB (invalidi -> errore);
- immagine: stesso `UploadHelper` dell'insert (5MB, thumb 220x150),
  cleanup path-safe dei vecchi file, UPDATE con ownership check
  (`id_user` sempre nella WHERE).

## File (12 + 1 SQL)
sql/Changelog/2026-07-07_plans_gold.sql | libs/user_tier.class.php |
libs/06_company.class.php | _admin/grant_premium.php | _admin/dashboard.php |
browse.php | shared/view_ad.php | 03_ads/03_documents.php |
02_free_ads/02_modify_insert_ad.php |
02_free_ads/02_01_upload_advertising_modified.php |
03_ads/03_modify_insert_ad.php | 03_ads/03_01_upload_advertising_modified.php

## Test rapidi
1. Da admin/dashboard: assegna ★ Gold a un utente -> in directory la sua
   azienda e' prima; "Revoke Gold" -> torna Basic.
2. Con un utente Basic prova a inserire il 3° annuncio -> messaggio
   "Your Basic plan allows up to 2 listings...".
3. browse.php: i premium stanno tutti sopra i free.
4. Apri un annuncio FREE -> niente sezione documenti; URL diretto
   03_documents.php?ad_table=02_free_ads -> 403. Un premium -> tutto ok.
5. Modifica un annuncio: cambia tipo veicolo, famiglia e immagine
   principale -> la card e la scheda riflettono i nuovi valori, il vecchio
   file immagine e' stato sostituito.
