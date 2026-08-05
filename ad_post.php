<?php
// ad_post.php  pagina legacy dismessa (Fase 4.5): redirect 301 permanente verso browse.php.
// Duplicato obsoleto del flusso annunci; resta come stub per non rompere vecchi link/bookmark.
require_once __DIR__ . '/config/bootstrap.php';
$target = (defined('BASE_URL') ? rtrim(BASE_URL, '/') : '.') . '/browse.php';
if (!headers_sent()) {
    header('Location: ' . $target, true, 301);
    header('X-Robots-Tag: noindex, follow', true);
}
$h = htmlspecialchars($target, ENT_QUOTES, 'UTF-8');
echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . $h . '">'
   . '<meta name="robots" content="noindex"></head><body>'
   . 'This page has moved. <a href="' . $h . '">Continue</a>.</body></html>';
exit;
