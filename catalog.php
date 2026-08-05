<?php
// ============================================================
// catalog.php — DEPRECATO. Catalogo e marketplace sono stati fusi in
// un'unica pagina: browse.php. Questo file resta solo come redirect 301
// permanente (dir. 19: nessuna rimozione silenziosa), preservando la
// macro selezionata per non rompere link interni/esterni esistenti.
// ============================================================

$macro  = isset($_GET['macro']) ? trim((string)$_GET['macro']) : '';
$target = 'browse.php' . ($macro !== '' ? '?macro=' . urlencode($macro) : '');

header('Location: ' . $target, true, 301);
exit;
