-- 2026-07-06 M4: ricerche salvate + alert/digest email (piano v1.1).
-- Run-once, MySQL 5.7. Collation allineata al DB (utf8mb4_unicode_ci).
CREATE TABLE IF NOT EXISTS `saved_searches` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user`      INT UNSIGNED NOT NULL,
  `email`        VARCHAR(255) NOT NULL,
  `macro`        VARCHAR(50)  DEFAULT NULL,
  `q`            VARCHAR(120) DEFAULT NULL,
  `freq`         ENUM('daily','weekly') NOT NULL DEFAULT 'daily',
  `active`       TINYINT(1) NOT NULL DEFAULT 1,
  `token`        CHAR(32) NOT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_sent_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token` (`token`),
  KEY `idx_user` (`id_user`),
  KEY `idx_due` (`active`, `freq`, `last_sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
