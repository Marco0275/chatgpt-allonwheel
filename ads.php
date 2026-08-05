<?php
// ============================================================
// Pagina legacy: duplicato obsoleto del blog/annunci.
// Fase 4.5 del piano: redirect permanente (301) verso browse.php
// per eliminare contenuto duplicato e consolidare il traffico/SEO.
// Il file resta come stub per non rompere vecchi link/bookmark.
// ============================================================
require_once __DIR__ . '/config/bootstrap.php';

header('X-Robots-Tag: noindex, follow', true);
header('Location: ' . BASE_URL . '/browse.php', true, 301);
exit;
