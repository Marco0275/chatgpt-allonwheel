-- ============================================================
-- quote_requests.sql — Lead engine delle richieste di offerta (RFQ).
-- Fase 3 (CRM): trasforma l'invio fire-and-forget di 04_send_offer.php
-- in un lead tracciato + l'elenco dei fornitori destinatari con esito.
--
-- Contenuto:
--   1) `quote_requests`            -> la richiesta = il lead
--   2) `quote_request_recipients`  -> chi l'ha ricevuta e con quale esito
--
-- Note:
--   - macro = product_macros.slug se la richiesta arriva dal configuratore
--     (ProductMacro), NULL se arriva dalle sole checkbox categoria.
--   - categories_json conserva {macro, regular[], special[]} effettivamente
--     inoltrati (estendibile senza migrazioni).
--   - consent_id e' una soft-reference verso consent_log: predisposta per
--     il consenso esplicito alla condivisione del contatto (wiring futuro).
--   - company_id e' soft-ref verso 06_company.id (nessuna FK, coerente con
--     le altre relazioni dell'app).
-- Target DB: MySQL 5.7. Idempotente: CREATE TABLE IF NOT EXISTS.
-- ============================================================

CREATE TABLE IF NOT EXISTS `quote_requests` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `buyer_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `buyer_email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `macro` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'product_macros.slug se richiesta via configuratore',
  `vehicle_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categories_json` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON {macro, regular[], special[]} inoltrato',
  `message` text COLLATE utf8mb4_unicode_ci COMMENT 'Messaggio libero del richiedente',
  `consent_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Soft-ref consent_log (consenso condivisione contatto)',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new' COMMENT 'new|distributed|quoted|won|lost',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_qr_macro` (`macro`),
  KEY `idx_qr_status` (`status`),
  KEY `idx_qr_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Lead: richieste di offerta (RFQ) inoltrate ai fornitori';

CREATE TABLE IF NOT EXISTS `quote_request_recipients` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` int(10) UNSIGNED NOT NULL,
  `company_id` int(10) UNSIGNED NOT NULL COMMENT 'Soft-ref 06_company.id',
  `sent_ok` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = mail() riuscita, 0 = fallita',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_qrr_request` (`request_id`),
  KEY `idx_qrr_company` (`company_id`),
  CONSTRAINT `fk_qrr_request` FOREIGN KEY (`request_id`)
    REFERENCES `quote_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Destinatari di ciascuna RFQ con esito di invio';
