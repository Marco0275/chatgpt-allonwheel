<?php
// 01_login/not_registered.php  pagina legacy dismessa: redirect 301 permanente.
// Duplicato del vecchio flusso di registrazione; non piu' referenziato.
// Reindirizza eventuali link esterni/indicizzati alla pagina reale di registrazione.
require_once __DIR__ . '/../config/bootstrap.php';
$target = (defined('BASE_URL') ? rtrim(BASE_URL, '/') : '..') . '/01_login/newregister.php';
if (!headers_sent()) {
    header('Location: ' . $target, true, 301);
    header('X-Robots-Tag: noindex', true);
}
$h = htmlspecialchars($target, ENT_QUOTES, 'UTF-8');
echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . $h . '">'
   . '<meta name="robots" content="noindex"></head><body>'
   . 'This page has moved. <a href="' . $h . '">Continue</a>.</body></html>';
exit;
