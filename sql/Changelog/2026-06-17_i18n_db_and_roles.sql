-- ============================================================
-- 2026-06-17_i18n_db_and_roles.sql
-- C7  : colonne IT per i18n contenuti DB (macro intro + descrizione azienda)
-- Ruoli: tabella user_roles (multi-ruolo) + checkbox elenco PM/consulenti
-- MySQL 5.7 compatibile. Eseguire UNA volta.
-- ============================================================

ALTER TABLE `06_company`
  ADD COLUMN `descrizione_it` text COLLATE utf8mb4_unicode_ci NULL
      COMMENT 'Descrizione in italiano (i18n, fallback su descrizione)' AFTER `descrizione`,
  ADD COLUMN `wants_pm_list` tinyint(1) NOT NULL DEFAULT 0
      COMMENT '1=azienda vuole ricevere elenco PM/consulenti';

ALTER TABLE `product_macros`
  ADD COLUMN `intro_text_it` text COLLATE utf8mb4_unicode_ci NULL
      COMMENT 'Intro macro in italiano (i18n, fallback su intro_text)' AFTER `intro_text`;

CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL COMMENT 'FK users.id_user',
  `role` enum('expert','project_manager','consultant') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_role` (`user_id`,`role`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ruoli multipli per utente (Esperto/PM/Consulente)';
