-- 2026-07-06 M2: badge "Founding partner" per i primi fornitori (piano v1.1).
-- Run-once (MySQL 5.7: niente IF NOT EXISTS su ADD COLUMN).
ALTER TABLE `06_company`
  ADD COLUMN `founding_partner` TINYINT(1) NOT NULL DEFAULT 0 AFTER `attiva`;
