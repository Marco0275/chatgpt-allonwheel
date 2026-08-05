-- 2026-07-06 M3: attribuzione della RFQ (pagina di provenienza) per la
-- dashboard KPI (M5). Run-once, MySQL 5.7.
ALTER TABLE `quote_requests`
  ADD COLUMN `source_page` VARCHAR(255) NULL DEFAULT NULL AFTER `message`;
