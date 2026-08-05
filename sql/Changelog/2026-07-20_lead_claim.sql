-- ============================================================
-- 2026-07-20_lead_claim.sql
-- Claim dei lead RFQ da parte dei fornitori
--
-- CONTESTO
-- Le RFQ vanno gia' ai fornitori piu' pertinenti, ordinati per punteggio
-- (match_score/rank_pos, patch 2026-07-17). Finora il fornitore riceveva il
-- lead solo via email. Ora ha un'area riservata (06_company/06_40_my_leads.php)
-- dove vede i lead ricevuti e li "prende in carico" (claim). Servono due
-- colonne per registrare chi ha preso il lead e quando.
--
-- A COSA SERVE IL CLAIM
--  - dare al fornitore un'azione concreta ("me ne occupo io");
--  - dare al sistema un segnale reale ("questo lead ha qualcuno che lavora"),
--    che il cron di riassegnazione (rfq_claim_reassign) usa per NON scavalcare
--    chi si e' gia' mosso;
--  - dare all'admin visibilita' su quali lead sono stati raccolti e quali no.
--
-- GARANZIE
--  - NON distruttiva (dir. 9): aggiunge colonne, non tocca dati.
--  - Idempotente: procedura temporanea su information_schema (5.7 non ha
--    ADD COLUMN IF NOT EXISTS). Rieseguibile senza errori.
--  - MySQL 5.7 compatibile.
--
-- PREREQUISITO: quote_requests.sql gia' applicata (crea la tabella).
-- ============================================================

DROP PROCEDURE IF EXISTS `aow_add_col_if_missing`;
DELIMITER $$
CREATE PROCEDURE `aow_add_col_if_missing`(
  IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_ddl TEXT
)
BEGIN
  IF NOT EXISTS (
      SELECT 1 FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
  ) THEN
      SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_ddl);
      PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

-- Chi ha preso in carico il lead e quando. claimed_by = 06_company.id.
CALL `aow_add_col_if_missing`(
  'quote_request_recipients', 'claimed_at',
  '`claimed_at` datetime DEFAULT NULL COMMENT ''Quando il fornitore ha preso in carico il lead (NULL = non ancora)'''
);
CALL `aow_add_col_if_missing`(
  'quote_request_recipients', 'claimed_by',
  '`claimed_by` int(10) UNSIGNED DEFAULT NULL COMMENT ''06_company.id che ha preso in carico (di norma = company_id)'''
);

-- reminded_at: quando il cron rfq_claim_reassign ha inviato il sollecito al
-- fornitore (evita solleciti doppi). NULL = mai sollecitato.
CALL `aow_add_col_if_missing`(
  'quote_request_recipients', 'reminded_at',
  '`reminded_at` datetime DEFAULT NULL COMMENT ''Quando e-mail di sollecito e stata inviata al fornitore (cron)'''
);

DROP PROCEDURE IF EXISTS `aow_add_col_if_missing`;

-- Indice per il cron di riassegnazione (trova i non-claimati velocemente).
-- Idempotente: si crea solo se manca.
DROP PROCEDURE IF EXISTS `aow_add_idx_if_missing`;
DELIMITER $$
CREATE PROCEDURE `aow_add_idx_if_missing`()
BEGIN
  IF NOT EXISTS (
      SELECT 1 FROM information_schema.STATISTICS
       WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quote_request_recipients'
         AND INDEX_NAME = 'idx_qrr_claim'
  ) THEN
      ALTER TABLE `quote_request_recipients` ADD INDEX `idx_qrr_claim` (`claimed_at`);
  END IF;
END$$
DELIMITER ;
CALL `aow_add_idx_if_missing`();
DROP PROCEDURE IF EXISTS `aow_add_idx_if_missing`;

-- ---- VERIFICA (facoltativa) ---------------------------------
--   SHOW COLUMNS FROM `quote_request_recipients` LIKE 'claimed_%';
--   Lead presi in carico vs no:
--   SELECT claimed_at IS NOT NULL AS claimed, COUNT(*)
--     FROM `quote_request_recipients` GROUP BY 1;

-- Fine patch.
