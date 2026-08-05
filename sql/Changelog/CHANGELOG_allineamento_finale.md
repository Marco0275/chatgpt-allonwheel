# Allonwheel — Allineamento finale + verificatore schema
20 lug 2026. Un solo ZIP. CRLF. PHP lint 717/717 OK. Base: V3.1.

===========================================================
IL CHECK DI ALLINEAMENTO: BUONA NOTIZIA
===========================================================
Ho lavorato su basi diverse (V2.2 -> V3.1) e temevo che i miei pacchetti non
fossero tutti dentro la V3.1. Ho verificato uno per uno: la V3.1 li ha
incorporati QUASI TUTTI. Riscontro (codice presente in V3.1):

  badge Premium only .............. SI
  shelter -> product_macro ........ SI
  RFQ mirate + punteggio .......... SI
  card unificata (ad_card.php) .... SI
  faccette sidebar ................ SI
  ad_drafts + AdDraft + wizard .... SI
  wanted tetto/vtype/notifyBuyers . SI
  escalation cron ................. SI
  hero admin ...................... SI
  i18n guide.* (IT/FR/DE) ......... SI, gia' tradotte (13 chiavi x 4 lingue)

Quindi NON serviva un pacchetto cumulativo: la V3.1 e' gia' allineata al
codice. La tua domanda sulla "guida di benvenuto" era gia' risolta in V3.1
(email + pannello dashboard); io ho solo completato l'email.

===========================================================
L'UNICO RISCHIO REALE: IL DB
===========================================================
Il codice c'e', ma funziona solo se le TABELLE/COLONNE esistono nel DB.
Il dump sql/allonwhe80316.sql e' lo stato PRIMA delle patch; le 23 patch in
sql/Changelog/ si applicano dopo. Su questo progetto un trasferimento al
server e' gia' fallito in silenzio: non si puo' dare per scontato che le
patch siano state eseguite.

NUOVO: scripts/check_schema.php
Interroga il DB REALE (information_schema) e dice, oggetto per oggetto, cosa
c'e' e cosa manca, col nome della patch da applicare. Da lanciare dopo ogni
trasferimento:
   php scripts/check_schema.php
Verifica: quote_requests, quote_request_recipients (+match_score, +rank_pos),
product_macros (+02/03.product_macro), wanted_ads, saved_searches, ad_drafts,
site_settings, users.is_verified.
Esce 0 se tutto a posto, 1 se manca qualcosa.

Le patch piu' recenti da controllare in produzione:
  2026-07-17_ad_drafts.sql        (registrazione dopo il wizard)
  2026-07-17_rfq_match_score.sql  (punteggio RFQ)
  2026-07-20_site_settings.sql    (hero admin)

===========================================================
REPORT ALLINEATO
===========================================================
Allonwheel_Report_e_Piano.md: aggiunta la "Parte 7 - Consolidamento v3.1",
che riallinea il piano allo stato reale:
 - i 5 punti "cosa farei per primo" con lo stato finale;
 - l'elenco degli interventi consegnati;
 - perche' escalation e non claim 24h (7.3);
 - perche' le faccette tecniche non sono state fatte (7.4);
 - lo stato DB vs codice e check_schema.php (7.5);
 - cosa resta aperto, lato decisioni Marco (7.6).
(E' una copia aggiornata: l'originale nel progetto e' read-only. Sostituiscilo
tu quando vuoi.)

===========================================================
STATO COMPLESSIVO DEL PROGETTO
===========================================================
Tutti i 5 punti prioritari: chiusi o consapevolmente sostituiti con motivo.
Tutte le richieste puntuali di questo ciclo (badge, tassonomia, card,
allineamento browse, faccette, wizard ospite, wanted, hero admin, guida):
consegnate e verificate in V3.1.

Restano SOLO decisioni di prodotto (area lead fornitori, configuratore
tecnico, moderazione obbligatoria) e task infra tuoi (SMTP, HISTATS_ID,
social). Nessuna di queste e' codice che posso scrivere senza una tua scelta.

===========================================================
FILE IN QUESTO ZIP (2)
===========================================================
scripts/check_schema.php          NUOVO: verifica stato DB vs patch
Allonwheel_Report_e_Piano.md      + Parte 7 (consolidamento v3.1)

## Come usare
1. Dopo ogni trasferimento al server: php scripts/check_schema.php
   Se segnala oggetti mancanti, applica le patch che ti indica.
2. Leggi la Parte 7 del report per il quadro aggiornato.
