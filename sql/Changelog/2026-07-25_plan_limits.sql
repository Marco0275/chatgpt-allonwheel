-- ============================================================
-- 2026-07-25_plan_limits.sql
-- Migrazione per i limiti dei piani Free/Premium/Gold.
-- 1) RFQ differita: colonna deliver_at su quote_request_recipients.
-- 2) Contatore social: tabella social_posts (le righe le inserisce l'IA esterna).
-- 3) Allineamento limite annunci Premium = 10 (Free resta 2).
-- ============================================================

ALTER TABLE `quote_request_recipients`
  ADD COLUMN `deliver_at` datetime DEFAULT NULL
  COMMENT 'Quando il lead diventa inviabile/visibile al fornitore (ritardo per piano)' AFTER `sent_ok`;

-- Retro-compat: i lead gia' esistenti sono considerati gia' consegnati.
UPDATE `quote_request_recipients` SET `deliver_at` = `created_at` WHERE `deliver_at` IS NULL;

CREATE TABLE IF NOT EXISTS `social_posts` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'users.id_user (proprietario azienda)',
  `company_id` int(10) UNSIGNED DEFAULT NULL COMMENT '06_company.id (opzionale)',
  `platform` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'facebook|instagram|linkedin|...',
  `permalink` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `posted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'quando l''IA ha pubblicato',
  PRIMARY KEY (`id`),
  KEY `idx_user_year` (`user_id`, `posted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pubblicazioni social gestite dall''IA (contatore quota per piano)';

-- Limite annunci Premium = 10 (Free = 2). No-op se le righe non esistono (default gia' 2/10).
UPDATE `site_settings` SET `setting_value` = '10' WHERE `setting_key` = 'ad_limit_premium';
UPDATE `site_settings` SET `setting_value` = '2'  WHERE `setting_key` = 'ad_limit_free';
