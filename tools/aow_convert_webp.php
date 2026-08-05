<?php
/**
 * tools/aow_convert_webp.php  (cartella di destinazione: /tools/)
 *
 * P3.17 - Genera le varianti .webp delle immagini esistenti (PNG/JPG).
 * Il codice del sito (includes/aow_media.php: aow_picture/aow_webp) usa
 * automaticamente la .webp quando esiste, con fallback all'originale.
 * Questo script crea quei file .webp UNA VOLTA (o quando aggiungi immagini).
 *
 * USO:
 *   CLI:   php tools/aow_convert_webp.php
 *   Web:   apri /tools/aow_convert_webp.php dal browser (poi RIMUOVILO).
 *
 * Requisiti: estensione GD con supporto WebP (function_exists('imagewebp')).
 * Non modifica gli originali: crea solo file .webp accanto ad essi.
 */

@set_time_limit(0);
$IS_CLI = (php_sapi_name() === 'cli');
$nl = $IS_CLI ? "\n" : "<br>\n";

if (!function_exists('imagewebp')) {
    echo "ERRORE: GD senza supporto WebP (imagewebp non disponibile).$nl";
    exit(1);
}

// Cartelle da convertire (relative alla radice del sito = cartella superiore a /tools)
$ROOT = dirname(__DIR__);
$DIRS = ['images', 'upload_image'];
$QUALITY = 80;          // 0-100
$SKIP_SMALLER_THAN = 0; // byte: 0 = converti tutto

$converted = 0; $skipped = 0; $errors = 0;

foreach ($DIRS as $d) {
    $base = $ROOT . '/' . $d;
    if (!is_dir($base)) { echo "salto (assente): $d$nl"; continue; }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (!$file->isFile()) continue;
        $path = $file->getPathname();
        if (!preg_match('/\.(png|jpe?g)$/i', $path)) continue;
        if ($SKIP_SMALLER_THAN && $file->getSize() < $SKIP_SMALLER_THAN) continue;
        $webp = preg_replace('/\.(png|jpe?g)$/i', '.webp', $path);
        if (file_exists($webp)) { $skipped++; continue; }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $img = ($ext === 'png') ? @imagecreatefrompng($path) : @imagecreatefromjpeg($path);
        if (!$img) { $errors++; echo "  errore lettura: $path$nl"; continue; }
        if ($ext === 'png') { imagepalettetotruecolor($img); imagealphablending($img, false); imagesavealpha($img, true); }
        if (@imagewebp($img, $webp, $QUALITY)) { $converted++; }
        else { $errors++; echo "  errore scrittura: $webp$nl"; }
        imagedestroy($img);
    }
}

echo $nl . "FATTO. Convertite: $converted | Gia' presenti: $skipped | Errori: $errors$nl";
if (!$IS_CLI) { echo "<strong>Per sicurezza, elimina questo file dopo l'uso.</strong>$nl"; }
