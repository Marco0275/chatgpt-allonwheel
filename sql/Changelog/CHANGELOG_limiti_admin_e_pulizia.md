# Allonwheel — Limiti annunci da admin + allineamento tabelle + pulizia
23 lug 2026. UN SOLO ZIP. CRLF. PHP lint 288/288 OK. CSS graffe bilanciate.
Base: Allonwheel_3_3_variabili.

=================================================================
1) LIMITE ANNUNCI MODIFICABILE DA ADMIN
=================================================================
Prima i numeri erano costanti nel codice (UserTier::BASIC_TOTAL_LIMIT = 2,
SILVER_TOTAL_LIMIT = 15): per cambiarli serviva mettere le mani in un file
PHP. Ora vivono in site_settings e si cambiano da una pagina admin.

NUOVO: _admin/admin_ad_limits.php  (voce di menu "Listing limits")
  - due campi, uno per il piano Basic (tier free) e uno per Silver (premium);
  - accanto a ciascuno: quanti utenti ha quel tier e QUANTI SONO GIA' OLTRE
    il limite. Serve a vedere l'effetto di un abbassamento PRIMA di applicarlo;
  - 0 = illimitato;
  - validazione: solo interi 0-9999. Un valore vuoto o non numerico non
    azzera niente (azzerare per sbaglio renderebbe tutti illimitati).

UserTier::limitFor($pdo, $tier) legge il valore da site_settings e, se la
riga o la tabella mancano, ricade sulle costanti di fabbrica: il sito
funziona anche prima di applicare la patch SQL.

COSA CONTA IL LIMITE (invariato): il TOTALE degli annunci dell'utente,
free + premium insieme. E' la semantica che il gate applicava gia'; non l'ho
cambiata, l'ho solo resa configurabile. Admin, tier Gold e la whitelist
UNLIMITED_EMAILS restano senza limiti.

Patch: sql/Changelog/2026-07-23_ad_limits_settings.sql (idempotente, non
distruttiva: rilanciandola NON sovrascrive i valori scelti da te).
Testata su MariaDB reale, insieme alla query delle statistiche admin.

=================================================================
2) TABELLE ADMIN: INTESTAZIONI CENTRATE, RECORD A SINISTRA
=================================================================
.admin_table th aveva solo colore e sfondo, nessun allineamento; e 26 celle
<td> nelle pagine admin portavano un align="center" scritto a mano, che
centrava i dati.

Aggiunte tre righe al CSS:
  .admin_table th { ... text-align:center; }
  .admin_table td { text-align:left; }
  .admin_thead_row td, .admin_thead_row th { text-align:center; }

Gli attributi align= dell'HTML sono "presentational attributes" con
specificita' ZERO: qualunque regola CSS li batte. Quindi i record tornano a
sinistra ovunque senza dover toccare 26 punti sparsi in 7 file, con meno
rischio di regressioni.

=================================================================
3) ERRORI TROVATI CONTROLLANDO IL CODICE
=================================================================
a) CSRF rotto in due pagine. csrf_generate() restituisce l'INPUT HTML gia'
   pronto, non il token; due file lo infilavano dentro un value="":
     _admin/admin_hero.php        (cambio immagine hero)
     06_company/06_40_my_leads.php (area lead fornitori)
   Il token arrivava corrotto e il submit veniva respinto con "Request not
   allowed". Ora leggono il token dalla sessione.

b) Limiti mostrati diversi da quelli applicati. my_posts.php e
   _admin/dashboard.php stampavano le costanti DEPRECATE (FREE_AD_LIMIT = 15,
   PREMIUM_AD_LIMIT = 5) mentre il gate applicava 2 e 15: all'utente veniva
   detto "1 / 15" quando in realta' poteva pubblicare 2 annunci in tutto.
   Ora mostrano il limite REALE, quello configurato.

c) Refuso in _admin/dashboard.php: <div class="clenaer h20"> invece di
   "cleaner". La classe non esiste, quindi quel div non chiudeva i float.

d) _admin/leads.php usava class="admin_error" per i messaggi di errore, ma
   quella classe non ha nessuna regola CSS: l'errore compariva senza stile.
   Passata a .admin_bad, che esiste ed e' gia' usata per gli errori altrove.

=================================================================
4) PULIZIA CSS
=================================================================
Analisi incrociata fra classi definite nel foglio di stile e classi usate in
tutti i file php/js/html: 153 definite, 136 usate.

RIMOSSE (7, zero riscontri in tutto il progetto):
  active_filters, af_label, filter_chip, filter_clear  (chip dei filtri
      attivi: UI mai realizzata, la sidebar espone i filtri senza chip)
  badge_free   (rimasta dal passaggio a badge solo-Premium)
  ib_mr12      (utility mai usata)
  lc_body      (index.php usa .listing_card e .lc_img, non .lc_body)

NON rimosse benche' sembrassero inutilizzate - erano falsi positivi che
avrebbero rotto il sito:
  downarrowclass, rightarrowclass  -> le usa js/ddsmoothmenu.js (le frecce
      del menu). Toglierle avrebbe rotto la navigazione.
  admin_row_rejected               -> costruita in un ternario dentro
      moderate_ads.php, non appare mai come class="..." letterale.
  commentbox1 / commentbox2        -> assegnate a runtime in blog_comments.php
      alternando le righe.
  back                             -> usata in about.php e gallery.php.
Risultato: 10 righe di CSS in meno, graffe bilanciate, nessuna classe usata
rimasta senza definizione.

=================================================================
FILE IN QUESTO ZIP (10)
=================================================================
NUOVI
  _admin/admin_ad_limits.php                 pagina limiti annunci
  sql/Changelog/2026-07-23_ad_limits_settings.sql   valori iniziali
MODIFICATI
  libs/user_tier.class.php                   limitFor() da site_settings
  _admin/admin_header.php                    voce menu "Listing limits"
  _admin/dashboard.php                       limiti reali + refuso cleaner
  _admin/admin_hero.php                      fix CSRF
  _admin/leads.php                           admin_error -> admin_bad
  01_login/my_posts.php                      limiti reali
  06_company/06_40_my_leads.php              fix CSRF
  allonwheel_style.css                       allineamento tabelle + pulizia

## Ordine di applicazione
1. Applica sql/Changelog/2026-07-23_ad_limits_settings.sql
   (richiede site_settings: se non c'e', applica prima
    2026-07-20_site_settings.sql).
2. Carica i file.
3. Admin -> "Listing limits": cambia i due numeri e salva.

## Come verificare
- Admin -> Listing limits: metti 3 nel piano Basic, salva, e in My posts un
  utente free vedra' "/ 3" e verra' bloccato al quarto annuncio.
- Le tabelle admin: intestazioni centrate, dati a sinistra.
- Cambio immagine hero e "Take this lead": non danno piu' "Request not allowed".
