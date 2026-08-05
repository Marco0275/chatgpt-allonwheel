<?php
// ============================================================
// 02_free_ads/02_view_ads.php — lista annunci storica, ora UNIFICATA in /browse.php.
// Redirect 301 permanente (P1, dir. 14/19) + noindex. Free/Premium non e'
// piu' un asse di navigazione (dir. 14): un'unica lista per tutti.
// ============================================================
header('X-Robots-Tag: noindex', true);
header('Location: /browse.php', true, 301);
exit;
