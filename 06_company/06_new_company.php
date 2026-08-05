<?php
// 06_company/06_new_company.php  pagina legacy dismessa: redirect 301 permanente.
// Sostituita da 06_10_register_company.php; non piu' referenziata dalla navigazione.
// Reindirizza eventuali link esterni/indicizzati alla registrazione azienda reale.
require_once __DIR__ . '/../config/bootstrap.php';
$target = (defined('BASE_URL') ? rtrim(BASE_URL, '/') : '..') . '/06_company/06_10_register_company.php';
if (!headers_sent()) {
    header('Location: ' . $target, true, 301);
    header('X-Robots-Tag: noindex', true);
}
$h = htmlspecialchars($target, ENT_QUOTES, 'UTF-8');
echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . $h . '">'
   . '<meta name="robots" content="noindex"></head><body>'
   . 'This page has moved. <a href="' . $h . '">Continue</a>.</body></html>';
exit;
