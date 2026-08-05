# Allonwheel — I tre punti di prodotto, completati
20 lug 2026. UN SOLO ZIP cumulativo. CRLF. PHP lint 720/720 OK. SQL 5.7.
Base: V3.1.

Avevo lasciato aperti tre punti "di prodotto" (non infra). Mi hai detto di
procederli tutti e tre. Fatti. Li consegno in UN pacchetto perche' due file
(il wizard e app_settings.php) sono toccati da piu' punti: separandoli
rischieresti di sovrascrivere una modifica con l'altra. Qui ogni file e' nella
sua versione FINALE, con tutte le modifiche sovrapposte.

=================================================================
#3 MODERAZIONE OBBLIGATORIA (configurabile)
=================================================================
Interruttore AOW_MODERATION_REQUIRED (default OFF = comportamento storico,
pubblico subito). ON: gli annunci nascono 'pending' e restano invisibili
finche' l'admin non li approva da _admin/moderate_ads.php (che ha gia' il
default sulla coda pending). La notifica ai buyer parte all'approvazione, non
al publish. L'utente vede "awaiting approval" in my_posts.
Verificato: le pagine pubbliche filtrano gia' status='approved', quindi un
pending e' invisibile; vale free+premium (handler unificato).

=================================================================
#1 AREA LEAD PER FORNITORI + CLAIM REALE
=================================================================
Nuova pagina 06_company/06_40_my_leads.php: il fornitore loggato vede i lead
della sua azienda e li prende in carico ("Take this lead" -> claimed_at).
Isolamento verificato: si claima solo un lead della propria azienda.
Cron scripts/rfq_claim_reassign.php: sollecito ai non-presi dopo 24h,
escalation all'admin dopo 48h. Niente riassegnazione cieca (i pertinenti sono
gia' stati notificati tutti; il claim e' il segnale che mancava).
Patch 2026-07-20_lead_claim.sql: colonne claimed_at/claimed_by/reminded_at
+ indice. Idempotente. Link "My leads" nel box account.

=================================================================
#2 CONFIGURATORE TECNICO: MISURE + FACCETTA LUNGHEZZA
=================================================================
Scoperta chiave: la V3.1 aveva GIA' la faccetta lunghezza completa (sidebar +
browse + traduzioni), ma non funzionava perche' gli annunci non avevano la
colonna length_cm e il wizard non chiedeva le misure. Questo la accende.
Patch 2026-07-20_ad_dimensions.sql: length_cm/width_cm/height_cm/axles_n
(numeriche, in cm interi) su 02_free_ads e 03_ads. Idempotente.
Wizard: 4 campi facoltativi (metri/assi); l'handler converte metri->cm con la
STESSA convenzione di browse.php. Testata la conversione.
Solo la lunghezza si filtra (misura piu' cercata); le altre si raccolgono per
la scheda tecnica e faccette future. NON aggiunte le 52 feature booleane
(nessuno filtra "ha lo SAT si/no").

=================================================================
ORDINE DI APPLICAZIONE
=================================================================
1. Patch SQL (idempotenti, in qualsiasi ordine):
     sql/Changelog/2026-07-20_lead_claim.sql
     sql/Changelog/2026-07-20_ad_dimensions.sql
   Dopo: php scripts/check_schema.php per confermare.
2. Carica tutti i file PHP.
3. Cron (crontab), es.:
     0 9 * * * php .../scripts/rfq_claim_reassign.php >> log 2>&1
4. Facoltativo: per attivare la moderazione, in .env:
     AOW_MODERATION_REQUIRED=true

=================================================================
FILE (9)
=================================================================
config/app_settings.php                    flag prodotto (moderazione+claim+tetti)
config/bootstrap.php                       include app_settings
02_free_ads/02_insert_ad.php               + 4 campi misura
02_free_ads/02_01_upload_advertising.php   status + misure + notifyBuyers
06_company/06_40_my_leads.php              NUOVA area lead fornitori
sidebar_user_box.php                       + link My leads
scripts/rfq_claim_reassign.php             NUOVO cron sollecito+escalation
sql/Changelog/2026-07-20_lead_claim.sql    NUOVA colonne claim
sql/Changelog/2026-07-20_ad_dimensions.sql NUOVA colonne misure

## Verifiche rapide
- Moderazione: flag ON -> nuovo annuncio non compare in browse, e' in coda admin.
- Lead: da fornitore con azienda, sidebar -> My leads -> Take this lead.
- Misure: pubblica con Length 12.5; in browse sidebar Min 10/Max 15 -> compare;
  Min 20 -> sparisce.
