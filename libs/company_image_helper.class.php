<?php
// ============================================================
// libs/company_image_helper.class.php
// Upload sicuro immagini azienda in /upload_image/06_company/.
//
// FIX rispetto alla versione precedente:
//  - Ora salva ENTRAMBI: originale in /upload_image/06_company/original/
//    e thumbnail in /upload_image/06_company/thumbnail/.
//    Prima salvava un solo file nella cartella flat, perdendo l'originale
//    o sovrascrivendolo con il thumbnail.
//  - deleteCompanyImage() aggiornato per cancellare da entrambe le subdir.
//
// NOTA: nella maggior parte dei flussi, l'upload è gestito direttamente
// da UploadHelper (che è già stato aggiornato). Questa classe fornisce
// un'alternativa ad API identica per chi la usa direttamente.
// ============================================================

class CompanyImageHelper
{
  private static $ALLOWED_MIMES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
  ];

  const ORIGINAL_DIR    = '/upload_image/06_company/original/';
  const THUMBNAIL_DIR   = '/upload_image/06_company/thumbnail/';
  const DEFAULT_MAX_SIZE = 5242880; // 5 MB
  const THUMB_W          = 220;
  const THUMB_H          = 150;

  /**
   * Carica il logo aziendale.
   * Salva il file originale in original/ e una thumbnail in thumbnail/.
   * Restituisce ['ok' => bool, 'error' => string|null, 'filename' => string|null].
   */
  public static function handleLogoUpload(array $file, array $opts = []): array
  {
    if (!isset($file['error']) || is_array($file['error'])) {
      return self::fail('Invalid upload data.');
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
      return self::fail('No file selected.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
      return self::fail('Upload error (code ' . (int)$file['error'] . ').');
    }
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
      return self::fail('Possible upload spoofing detected.');
    }

    $max  = (int)($opts['max_size_bytes'] ?? self::DEFAULT_MAX_SIZE);
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0) {
      return self::fail('Empty file.');
    }
    if ($size > $max) {
      return self::fail(sprintf('File too large. Maximum size is %d MB.', (int)round($max / 1024 / 1024)));
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!isset(self::$ALLOWED_MIMES[$mime])) {
      return self::fail('File type not allowed. Only JPG, PNG and GIF are accepted.');
    }

    $imginfo = @getimagesize($file['tmp_name']);
    if ($imginfo === false || !isset($imginfo[0], $imginfo[1])) {
      return self::fail('Invalid image content.');
    }
    if ($imginfo[0] > 10000 || $imginfo[1] > 10000) {
      return self::fail('Image too large in pixels.');
    }

    $abs_orig  = self::absoluteDir(self::ORIGINAL_DIR);
    $abs_thumb = self::absoluteDir(self::THUMBNAIL_DIR);

    foreach ([$abs_orig, $abs_thumb] as $dir) {
      if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return self::fail('Unable to create upload directory.');
      }
      if (!is_writable($dir)) {
        return self::fail('Server configuration error: upload directory not writable.');
      }
    }

    $prefix   = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($opts['filename_prefix'] ?? 'company_logo'));
    if ($prefix === '') {
      $prefix = 'company_logo';
    }
    $filename  = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . self::$ALLOWED_MIMES[$mime];
    $orig_path  = $abs_orig  . $filename;
    $thumb_path = $abs_thumb . $filename;

    $thumb_w = (int)($opts['thumb_width']  ?? self::THUMB_W);
    $thumb_h = (int)($opts['thumb_height'] ?? self::THUMB_H);
    $max_w   = (int)($opts['max_width']    ?? 1600);
    $max_h   = (int)($opts['max_height']   ?? 1200);

    try {
      // Originale: re-encoding senza ridimensionamento (strip EXIF)
      self::saveStrippedImage($file['tmp_name'], $orig_path, $mime, $max_w, $max_h, false);
      // Thumbnail: ridimensionamento proporzionale + crop centrato
      self::saveStrippedImage($file['tmp_name'], $thumb_path, $mime, $thumb_w, $thumb_h, true);
    } catch (Throwable $e) {
      if (is_file($orig_path))  @unlink($orig_path);
      if (is_file($thumb_path)) @unlink($thumb_path);
      error_log('[Allonwheel] CompanyImageHelper upload failed: ' . $e->getMessage());
      return self::fail('Image processing error. Please try a different file.');
    }

    return ['ok' => true, 'error' => null, 'filename' => $filename];
  }

  /**
   * Elimina le copie originale e thumbnail di un'immagine aziendale.
   */
  public static function deleteCompanyImage(?string $filename): bool
  {
    $filename = trim((string)$filename);
    if ($filename === '' || $filename === 'no_image.jpg') {
      return true;
    }
    $safe = basename($filename);
    $ok   = true;
    foreach ([self::ORIGINAL_DIR, self::THUMBNAIL_DIR] as $rel) {
      $base = realpath(self::absoluteDir($rel));
      if (!$base) {
        continue; // dir non esiste, già assente
      }
      $candidate = $base . DIRECTORY_SEPARATOR . $safe;
      $real = realpath($candidate);
      if ($real !== false && strpos($real, $base . DIRECTORY_SEPARATOR) === 0 && is_file($real)) {
        if (!@unlink($real)) {
          $ok = false;
        }
      }
    }
    return $ok;
  }

  // ============================================================
  // Internals
  // ============================================================

  private static function saveStrippedImage(
    string $src,
    string $dst,
    string $mime,
    int    $max_w,
    int    $max_h,
    bool   $crop
  ): void {
    switch ($mime) {
      case 'image/jpeg': $img = imagecreatefromjpeg($src); break;
      case 'image/png':  $img = imagecreatefrompng($src);  break;
      case 'image/gif':  $img = imagecreatefromgif($src);  break;
      default:
        throw new RuntimeException('Unsupported MIME: ' . $mime);
    }
    if (!$img) {
      throw new RuntimeException('GD failed to read source.');
    }

    $src_w = imagesx($img);
    $src_h = imagesy($img);

    if ($crop) {
      // Crop centrato (cover)
      $ratio      = max($max_w / $src_w, $max_h / $src_h);
      $src_crop_w = (int)round($max_w / $ratio);
      $src_crop_h = (int)round($max_h / $ratio);
      $src_x      = max(0, (int)round(($src_w - $src_crop_w) / 2));
      $src_y      = max(0, (int)round(($src_h - $src_crop_h) / 2));

      $canvas = imagecreatetruecolor($max_w, $max_h);
      if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $t = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $max_w, $max_h, $t);
      }
      imagecopyresampled($canvas, $img, 0, 0, $src_x, $src_y, $max_w, $max_h, $src_crop_w, $src_crop_h);
      imagedestroy($img);
      $img = $canvas;
    } else {
      // Fit proporzionale (mai ingrandisce)
      $ratio = min($max_w / $src_w, $max_h / $src_h, 1.0);
      $new_w = (int)round($src_w * $ratio);
      $new_h = (int)round($src_h * $ratio);
      if ($new_w !== $src_w || $new_h !== $src_h) {
        $resized = imagecreatetruecolor($new_w, $new_h);
        if ($mime === 'image/png' || $mime === 'image/gif') {
          imagealphablending($resized, false);
          imagesavealpha($resized, true);
          $t = imagecolorallocatealpha($resized, 0, 0, 0, 127);
          imagefilledrectangle($resized, 0, 0, $new_w, $new_h, $t);
        }
        imagecopyresampled($resized, $img, 0, 0, 0, 0, $new_w, $new_h, $src_w, $src_h);
        imagedestroy($img);
        $img = $resized;
      }
    }

    $ok = false;
    switch ($mime) {
      case 'image/jpeg': $ok = imagejpeg($img, $dst, 90); break;
      case 'image/png':  $ok = imagepng($img, $dst, 6);   break;
      case 'image/gif':  $ok = imagegif($img, $dst);       break;
    }
    imagedestroy($img);
    if (!$ok) {
      throw new RuntimeException('Failed to save image: ' . $dst);
    }
  }

  private static function absoluteDir(string $rel_path): string
  {
    $root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
    if ($root === '') {
      $root = dirname(__DIR__);
    }
    return $root . $rel_path;
  }

  private static function fail(string $message): array
  {
    return ['ok' => false, 'error' => $message, 'filename' => null];
  }
}
?>
