-- ============================================================
-- 2026-08-02_blog_expert_hub.sql
-- Estende il blog per l'hub editoriale "Ask the Experts":
--   - campi custom (category, question, outlines, faq_json, slug, published_at, source)
--   - stati aggiuntivi (draft, scheduled) per la pubblicazione programmata via API
--   - tabella `blog_categories` (chip di filtro DB-driven, 4 categorie seed)
--   - tabella `blog_leads` (form di conversione a fine articolo)
--
-- Stack: MySQL 5.7 — niente "ADD COLUMN IF NOT EXISTS".
-- Idempotenza: le ADD COLUMN passano da una stored procedure che verifica
-- INFORMATION_SCHEMA, cosi' la patch e' ri-eseguibile senza errori (dir. 9,
-- nessuna perdita dati; additivo, dir. 19).
-- Applicare UNA VOLTA (ma sicura anche se rilanciata).
-- ============================================================

-- ---------- 1) Colonne custom su `blog` (idempotente) ----------
DROP PROCEDURE IF EXISTS `aow_blog_add_col`;
DELIMITER //
CREATE PROCEDURE `aow_blog_add_col`(IN col VARCHAR(64), IN ddl TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'blog' AND COLUMN_NAME = col
  ) THEN
    SET @s = CONCAT('ALTER TABLE `blog` ADD COLUMN ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END //
DELIMITER ;

CALL `aow_blog_add_col`('slug',        "`slug` VARCHAR(191) NULL COMMENT 'Slug SEO univoco (utf8mb4: 191 -> 764 byte, indicizzabile su MySQL 5.7)' AFTER `title`");
CALL `aow_blog_add_col`('category',    "`category` VARCHAR(40) NULL COMMENT 'Slug categoria (blog_categories.slug)' AFTER `slug`");
CALL `aow_blog_add_col`('question',    "`question` VARCHAR(255) NULL COMMENT 'Domanda dell utente (Ask the Experts)' AFTER `excerpt`");
CALL `aow_blog_add_col`('outlines',    "`outlines` TEXT NULL COMMENT 'Scaletta / punti chiave (una riga per voce)' AFTER `body`");
CALL `aow_blog_add_col`('faq_json',    "`faq_json` TEXT NULL COMMENT 'FAQ per schema.org FAQPage: JSON [{q,a}]' AFTER `outlines`");
CALL `aow_blog_add_col`('published_at',"`published_at` DATETIME NULL COMMENT 'Data/ora di pubblicazione (scheduling)' AFTER `status`");
CALL `aow_blog_add_col`('source',      "`source` VARCHAR(20) NOT NULL DEFAULT 'web' COMMENT 'Origine: web | api' AFTER `published_at`");

DROP PROCEDURE IF EXISTS `aow_blog_add_col`;

-- ---------- 2) Nuovi stati (draft, scheduled) su `blog.status` ----------
-- MODIFY e' idempotente: rieseguirlo riscrive lo stesso enum.
ALTER TABLE `blog`
  MODIFY `status` ENUM('draft','pending','scheduled','published','rejected')
  COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending';

-- ---------- 3) Indici (idempotente via procedura) ----------
DROP PROCEDURE IF EXISTS `aow_blog_add_index`;
DELIMITER //
CREATE PROCEDURE `aow_blog_add_index`(IN idx VARCHAR(64), IN ddl TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'blog' AND INDEX_NAME = idx
  ) THEN
    SET @s = CONCAT('ALTER TABLE `blog` ADD ', ddl);
    PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END //
DELIMITER ;
CALL `aow_blog_add_index`('uq_blog_slug',   "UNIQUE KEY `uq_blog_slug` (`slug`)");
CALL `aow_blog_add_index`('ix_blog_cat',    "KEY `ix_blog_cat` (`category`)");
CALL `aow_blog_add_index`('ix_blog_sched',  "KEY `ix_blog_sched` (`status`,`published_at`)");
DROP PROCEDURE IF EXISTS `aow_blog_add_index`;

-- ---------- 4) Backfill non distruttivo ----------
-- Gli articoli gia' 'published' ereditano published_at = created_at.
UPDATE `blog`
   SET `published_at` = `created_at`
 WHERE `status` = 'published' AND `published_at` IS NULL;

-- Slug SEO per gli articoli esistenti privi di slug (derivato dal titolo + id).
-- 5.7-SAFE: usa SOLO funzioni native MySQL 5.7 (LOWER/REPLACE/TRIM/LEFT/CONCAT);
-- NIENTE REGEXP_REPLACE (che in 5.7 non esiste). Rimuove i caratteri pericolosi
-- per URL/slug (? / : # & % \ " ' , . ; ecc.), converte gli spazi in '-' e
-- comprime i trattini multipli con passaggi REPLACE('--','-') ripetuti.
UPDATE `blog`
   SET `slug` = CONCAT(
        LEFT(
          TRIM(BOTH '-' FROM
            -- 4 passaggi di compressione dei trattini (gestisce lunghe sequenze)
            REPLACE(REPLACE(REPLACE(REPLACE(
              -- spazi/separatori -> '-'
              REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                -- caratteri da eliminare del tutto
                REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                  LOWER(`title`),
                  '?',''),'#',''),'%',''),'"',''),CHAR(39),''),',',''),'.',''),';',''),'(',''),')',''),'!',''),
                ' ','-'),'/','-'),'\\','-'),':','-'),'&','-'),'_','-'),'|','-'),'+','-'),
            '--','-'),'--','-'),'--','-'),'--','-')
          ), 160),
        '-', `id`)
 WHERE (`slug` IS NULL OR `slug` = '');
-- Nota: tabella blog attualmente vuota -> questo UPDATE tocca 0 righe; gli slug
-- dei nuovi articoli sono generati a INSERT da BlogManager::slugify() (PHP).

-- ---------- 5) Categorie del blog (chip di filtro, DB-driven) ----------
CREATE TABLE IF NOT EXISTS `blog_categories` (
  `id`         INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`       VARCHAR(40)  COLLATE utf8mb4_unicode_ci NOT NULL,
  `name`       VARCHAR(60)  COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Etichetta EN visibile',
  `sort_order` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_blogcat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Categorie dell hub editoriale Ask the Experts';

-- Seed delle 4 categorie richieste (idempotente).
INSERT INTO `blog_categories` (`slug`,`name`,`sort_order`) VALUES
  ('technical-design','Technical / Design',10),
  ('feasibility',     'Feasibility',       20),
  ('costs',           'Costs',             30),
  ('registration',    'Registration',      40)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `sort_order`=VALUES(`sort_order`);

-- ---------- 6) Lead a fine articolo (conversione lettore -> lead) ----------
CREATE TABLE IF NOT EXISTS `blog_leads` (
  `id`           INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_blog`      INT(10) UNSIGNED NULL COMMENT 'Articolo di provenienza (blog.id)',
  `category`     VARCHAR(40)  COLLATE utf8mb4_unicode_ci NULL COMMENT 'Categoria articolo al momento del lead',
  `name`         VARCHAR(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email`        VARCHAR(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company`      VARCHAR(160) COLLATE utf8mb4_unicode_ci NULL,
  `phone`        VARCHAR(40)  COLLATE utf8mb4_unicode_ci NULL,
  `message`      TEXT         COLLATE utf8mb4_unicode_ci NULL,
  `intent`       VARCHAR(40)  COLLATE utf8mb4_unicode_ci NULL COMMENT 'feasibility_study | custom_quote | question',
  `status`       ENUM('new','contacted','qualified','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `consent_given` TINYINT(1)  NOT NULL DEFAULT 0,
  `consent_version` VARCHAR(20) COLLATE utf8mb4_unicode_ci NULL,
  `ip_hash`      CHAR(64)     COLLATE utf8mb4_unicode_ci NULL COMMENT 'SHA-256 IP (GDPR)',
  `user_agent`   VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_leads_status` (`status`),
  KEY `ix_leads_blog` (`id_blog`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Lead B2B raccolti dai form a fine articolo del blog';
