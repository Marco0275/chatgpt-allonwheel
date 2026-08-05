-- ============================================================
-- blog_comments.sql — Commenti degli articoli del blog.
-- Solo TESTO (nessuna immagine). Commento scritto da utenti registrati,
-- visibile da subito; autore e admin possono cancellarlo. La colonna
-- `status` consente un'eventuale moderazione futura (visible/hidden).
-- ============================================================
CREATE TABLE IF NOT EXISTS `blog_comments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_blog` int(10) UNSIGNED NOT NULL COMMENT 'Articolo (blog.id)',
  `id_user` int(10) UNSIGNED NOT NULL COMMENT 'Autore del commento (users.id_user)',
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Testo del commento (niente HTML/immagini)',
  `status` enum('visible','hidden') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'visible',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comment_blog` (`id_blog`),
  KEY `idx_comment_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Commenti agli articoli del blog (solo testo)';
