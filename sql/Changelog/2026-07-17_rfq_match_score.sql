-- ============================================================
-- 2026-07-17_rfq_match_score.sql
-- RFQ: punteggio di pertinenza e posizione del destinatario
--
-- CONTESTO
-- Fino al 16 lug 2026 la RFQ era un BROADCAST: andava a tutte le aziende
-- attive (04_send_offer.php usava getAllCompanies()). Ora va solo ai
-- fornitori che dichiarano quei prodotti, ordinati per pertinenza, e con un
-- tetto (default 3, vedi AOW_RFQ_MAX_RECIPIENTS).
--
-- Queste due colonne servono a poter SPIEGARE a posteriori perche' un certo
-- fornitore ha ricevuto una richiesta e un altro no:
--   match_score = quante chiavi prodotto aveva in comune con la richiesta
--   rank_pos    = in che posizione e' arrivato nella graduatoria (1 = primo)
-- Senza, il tetto sarebbe una scatola nera: se un fornitore chiede "perche'
-- non ho ricevuto quella richiesta?" non sapresti rispondere.
--
-- GARANZIE
--  - NON distruttiva (dir. 9): aggiunge due colonne, non tocca dati.
--  - IDEMPOTENTE davvero: MySQL 5.7 non ha ADD COLUMN IF NOT EXISTS, quindi
--    la guardia interroga information_schema e salta se la colonna c'e' gia'.
--    Rieseguibile senza errori (utile: su questo progetto i trasferimenti
--    verso il server sono gia' falliti in silenzio in passato).
--  - MySQL 5.7 compatibile.
--
-- PREREQUISITO: sql/Changelog/quote_requests.sql gia' applicata
-- (crea quote_request_recipients).
--
-- NOTA: il codice PHP funziona anche SENZA questa patch. L'INSERT dei
-- destinatari e' dentro try/catch: senza le colonne fallisce, viene loggato
-- e la RFQ parte lo stesso. Si perde solo la tracciabilita'.
-- ============================================================

DROP PROCEDURE IF EXISTS `aow_add_col_if_missing`;
DELIMITER $$
CREATE PROCEDURE `aow_add_col_if_missing`(
  IN p_table  VARCHAR(64),
  IN p_column VARCHAR(64),
  IN p_ddl    TEXT
)
BEGIN
  IF NOT EXISTS (
      SELECT 1 FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME   = p_table
         AND COLUMN_NAME  = p_column
  ) THEN
      SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_ddl);
      PREPARE stmt FROM @ddl;
      EXECUTE stmt;
      DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL `aow_add_col_if_missing`(
  'quote_request_recipients',
  'match_score',
  '`match_score` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''Chiavi prodotto in comune con la richiesta (pertinenza)'''
);

CALL `aow_add_col_if_missing`(
  'quote_request_recipients',
  'rank_pos',
  '`rank_pos` smallint(5) UNSIGNED NOT NULL DEFAULT 0 COMMENT ''Posizione in graduatoria al momento dell''''invio (1 = piu'''' pertinente)'''
);

-- La procedura e' solo uno strumento della patch: non lasciarla nel DB.
DROP PROCEDURE IF EXISTS `aow_add_col_if_missing`;

-- ---- VERIFICA (facoltativa) ---------------------------------
-- Le colonne ci sono?
--   SHOW COLUMNS FROM `quote_request_recipients` LIKE 'match\_score';
--   SHOW COLUMNS FROM `quote_request_recipients` LIKE 'rank\_pos';
--
-- Dopo qualche RFQ, chi ha ricevuto cosa e perche':
--   SELECT r.request_id, c.ragione_sociale, r.match_score, r.rank_pos, r.sent_ok
--     FROM `quote_request_recipients` r
--     JOIN `06_company` c ON c.id = r.company_id
--    ORDER BY r.request_id DESC, r.rank_pos ASC;
--
-- Quante email genera in media una richiesta (deve stare sotto il tetto):
--   SELECT request_id, COUNT(*) AS destinatari
--     FROM `quote_request_recipients`
--    GROUP BY request_id
--    ORDER BY request_id DESC;

-- Fine patch.
