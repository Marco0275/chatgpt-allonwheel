-- ============================================================
-- blog.sql — Tabella articoli del blog pubblicati dagli utenti registrati.
-- Base: la struttura statica di blog.php / blog_post.php resa dinamica.
--   status: 'published' visibile a tutti, 'pending' in attesa di revisione,
--           'rejected' nascosto. La moderazione admin e' in
--           _admin/moderate_blog.php.
-- ============================================================
CREATE TABLE IF NOT EXISTS `blog` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` int(10) UNSIGNED NOT NULL COMMENT 'Autore (users.id_user)',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sommario breve (riga in corsivo)',
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Corpo articolo; paragrafi separati da newline',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cover in /upload_image/blog/',
  `status` enum('pending','published','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_blog_user` (`id_user`),
  KEY `idx_blog_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Articoli del blog pubblicati dagli utenti registrati';
