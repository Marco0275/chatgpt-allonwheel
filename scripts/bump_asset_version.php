<?php
// ============================================================
// scripts/bump_asset_version.php — cache-busting asset (CSS/JS).
// Uso (CLI):  php scripts/bump_asset_version.php 20260901
// Sostituisce ?v=<vecchia> con ?v=<nuova> in tutti i .php della webroot.
// Da lanciare ad ogni deploy che modifica CSS/JS. CRLF preservati.
// ============================================================
$new = $argv[1] ?? date('Ymd');
if (!ctype_alnum($new)) { fwrite(STDERR, "Versione non valida (solo alfanumerico).\n"); exit(1); }
$root = dirname(__DIR__);
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$count = 0; $files = 0;
foreach ($it as $f) {
    if (strtolower($f->getExtension()) !== 'php') { continue; }
    $s = file_get_contents($f->getPathname());
    $n = preg_replace('/\?v=[A-Za-z0-9]+/', '?v=' . $new, $s, -1, $c);
    if ($c > 0) { file_put_contents($f->getPathname(), $n); $count += $c; $files++; }
}
echo "Aggiornati $count riferimenti in $files file -> ?v=$new\n";
