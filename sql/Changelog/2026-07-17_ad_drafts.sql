-- ============================================================
-- 2026-07-17_ad_drafts.sql
-- Bozze annuncio: l'ospite compila, l'account arriva dopo (punto 2)
--
-- DECISIONE (delegata: "la soluzione migliore per l'utente")
-- Le due opzioni erano:
--   (a) bozza persistita in DB, la verifica email resta;
--   (b) attivazione email opzionale per chi arriva dal wizard: nessuna
--       interruzione, ma niente verifica.
-- Scelta: (a). Il motivo e' un fatto dello schema, non un'opinione:
--     `status` enum('pending','approved','rejected') ... DEFAULT 'approved'
-- un annuncio nuovo e' PUBBLICO ALL'ISTANTE (la moderazione esiste ma non e'
-- sul percorso obbligatorio). Con la (b) chiunque, con un'email finta,
-- pubblicherebbe subito un annuncio vero: catalogo pieno di spam e compratori
-- che mandano RFQ a indirizzi inesistenti. La verifica email e' oggi l'unica
-- difesa reale: si tiene.
-- La (a) e' migliore anche per il venditore: il suo lavoro non si perde MAI,
-- nemmeno se apre il link di attivazione su un altro dispositivo o dopo ore.
-- La sessione PHP non regge quel giro; una riga in tabella si'.
-- In piu' l'attivazione smette di essere una scocciatura generica e diventa
-- "conferma l'email per pubblicare il TUO annuncio": stessa mail, motivazione
-- diversa, tasso di attivazione piu' alto.
--
-- COME SI INCASTRA
--  1. L'ospite compila il wizard: i dati (solo testo: verificato, nessun
--     upload in quello step) finiscono qui, con un token casuale in cookie.
--  2. Al publish gli si chiede l'account. La bozza e' GIA' salva: qualunque
--     cosa faccia dopo, non ha perso nulla.
--  3. Al primo login utile la bozza viene travasata nell'annuncio vero
--     (02_free_ads / 03_ads) e la riga qui viene cancellata.
--  4. Le bozze mai reclamate scadono da sole (cron, vedi sotto): non si
--     accumulano dati personali di gente che non si e' mai registrata (GDPR).
--
-- GARANZIE
--  - NON distruttiva (dir. 9): crea una tabella nuova, non tocca nulla.
--  - Idempotente: CREATE TABLE IF NOT EXISTS, rieseguibile.
--  - MySQL 5.7 compatibile.
--  - Nessuna FK verso users: la bozza nasce SENZA utente (e' il punto).
--    Il legame e' un soft-ref, come gia' fatto per company_id in
--    quote_request_recipients.
-- ============================================================

CREATE TABLE IF NOT EXISTS `ad_drafts` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,

  -- Identifica la bozza dell'ospite. Sta in un cookie httponly, NON nell'URL:
  -- un token in query string finisce nei log del server, nei Referer verso
  -- terzi e nella cronologia condivisa. Con esso si puo' leggere e pubblicare
  -- la bozza, quindi va trattato come una credenziale.
  `draft_token` char(64) COLLATE utf8mb4_unicode_ci NOT NULL
      COMMENT 'Token casuale (bin2hex(random_bytes(32))), in cookie httponly',

  -- NULL = ospite. Valorizzato quando l'utente si registra/accede: da quel
  -- momento la bozza e' sua e il token non serve piu'.
  `user_id` int(11) UNSIGNED DEFAULT NULL
      COMMENT 'Soft-ref users.id_user. NULL = bozza di un ospite',

  -- Dove finira' l'annuncio: stessa logica di $aow_lt nel wizard unificato.
  `listing` enum('free','prem') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free'
      COMMENT 'Tabella di destinazione: free -> 02_free_ads, prem -> 03_ads',

  -- I campi del wizard, come li ha inseriti l'ospite. JSON e non una colonna
  -- per campo: il wizard cambia spesso (dir. 13) e una bozza non deve
  -- richiedere una ALTER a ogni campo nuovo. Non ci si interroga sopra: si
  -- legge, si valida e si travasa. MySQL 5.7 non ha il tipo JSON nativo
  -- affidabile su tutte le installazioni -> longtext.
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL
      COMMENT 'Campi del wizard serializzati in JSON (solo testo)',

  `step` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
      COMMENT 'Ultimo step completato: per riprendere da dove aveva lasciato',

  -- Email dichiarata nel wizard, se c'e'. Serve SOLO a poter dire
  -- "avevi un annuncio a meta'" e a collegare la bozza alla registrazione.
  `contact_email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
      COMMENT 'Email indicata nel wizard (per ricollegare la bozza)',

  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Scadenza esplicita: una bozza di un ospite contiene dati personali
  -- (email, telefono) di qualcuno che NON si e' mai registrato e non ha mai
  -- dato un consenso. Tenerla per sempre sarebbe una raccolta silenziosa.
  -- Il cron la cancella (vedi in fondo).
  `expires_at` datetime NOT NULL
      COMMENT 'Oltre questa data la bozza va cancellata (GDPR: dati di non registrati)',

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_draft_token` (`draft_token`),
  KEY `idx_draft_user` (`user_id`),
  KEY `idx_draft_email` (`contact_email`),
  KEY `idx_draft_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Bozze annuncio compilate prima di avere un account (punto 2)';

-- ---- PULIZIA (da agganciare al cron, insieme agli altri) ----
-- Le bozze scadute vanno rimosse. Una riga sola, da mettere in
-- scripts/purge_personal_data.php (che gia' esiste e gira):
--
--   DELETE FROM `ad_drafts` WHERE `expires_at` < NOW();
--
-- Suggerimento: 30 giorni di vita (impostati dal PHP alla creazione).
-- Abbastanza per chi si registra e attiva l'email con calma, non tanto da
-- diventare un archivio di dati di sconosciuti.

-- ---- VERIFICA (facoltativa) ---------------------------------
--   SHOW CREATE TABLE `ad_drafts`;
--
-- Quante bozze restano orfane (nessuno si e' mai registrato)?
-- E' la misura dell'attrito residuo del wizard: se e' alta, il problema
-- non e' la registrazione ma il form.
--   SELECT COUNT(*) AS orfane FROM `ad_drafts` WHERE `user_id` IS NULL;

-- Fine patch.
