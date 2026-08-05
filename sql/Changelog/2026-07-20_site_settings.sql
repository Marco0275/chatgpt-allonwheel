-- ============================================================
-- 2026-07-20_site_settings.sql
-- Impostazioni di sito chiave/valore (prima esigenza: immagine hero index)
--
-- CONTESTO
-- L'immagine di sfondo dell'hero in index.php era hardcoded
-- (images/project.png). Richiesta: darla in gestione all'admin.
-- Serviva un posto dove salvarla; non esisteva una tabella di impostazioni
-- generiche. Questa e' quella tabella: chiave/valore, riusabile per le
-- prossime impostazioni (non solo l'hero).
--
-- GARANZIE
--  - NON distruttiva (dir. 9): crea una tabella nuova, non tocca nulla.
--  - Idempotente: CREATE TABLE IF NOT EXISTS + INSERT ... ON DUPLICATE KEY
--    (rieseguibile, non sovrascrive un valore gia' impostato dall'admin).
--  - MySQL 5.7 compatibile.
-- ============================================================

CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL
      COMMENT 'Chiave univoca dell impostazione (es. hero_image)',
  `setting_value` text COLLATE utf8mb4_unicode_ci NOT NULL
      COMMENT 'Valore. Per hero_image: percorso relativo tipo upload_image/hero/xxx.jpg',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) UNSIGNED DEFAULT NULL COMMENT 'Soft-ref: admin che ha modificato',
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Impostazioni di sito chiave/valore';

-- Riga iniziale per l'hero: il DEFAULT punta all'immagine attuale, cosi' il
-- comportamento resta IDENTICO finche' l'admin non ne sceglie un'altra.
-- ON DUPLICATE KEY: se la riga esiste gia' (patch rilanciata, o valore gia'
-- impostato dall'admin) NON viene sovrascritta.
INSERT INTO `site_settings` (`setting_key`, `setting_value`)
VALUES ('hero_image', 'images/project.png')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

-- ---- VERIFICA (facoltativa) ---------------------------------
--   SELECT * FROM `site_settings`;

-- Fine patch.
