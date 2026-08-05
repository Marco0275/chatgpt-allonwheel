# Allonwheel — Fix "Image upload failed: Internal configuration error" (blog)

## Causa

`UploadHelper::handleImageUpload()` accetta solo cartelle di destinazione presenti in una **whitelist** (`$ALLOWED_BASE_DIRS`). Quando ho aggiunto il blog, le cartelle `/upload_image/blog/original/` e `/upload_image/blog/thumbnail/` non erano in whitelist, quindi `isAllowedDir()` falliva e veniva restituito *"Internal configuration error."* (mio errore nella consegna del blog).

## Fix

Aggiunta una riga alla whitelist in `libs/upload_helper.class.php`:

```php
    '/upload_image/blog/',
```

È un prefisso unico che copre sia `/upload_image/blog/original/` sia `/upload_image/blog/thumbnail/` (il controllo è `strpos($dir, $allowed) === 0`). I percorsi non autorizzati e i tentativi di path-traversal restano bloccati.

La tabella `blog` è già presente nel DB live, quindi sistemato l'upload il flusso di pubblicazione funziona end-to-end.

## Possibile nota lato server

Al primo upload il helper crea `/upload_image/blog/original/` e `/thumbnail/` con `mkdir`. Se la cartella `upload_image` non fosse scrivibile, comparirebbe un messaggio **diverso** ("upload directory not writable"): in quel caso crea a mano `upload_image/blog/original` e `upload_image/blog/thumbnail` con permessi di scrittura (755).

---

## `libs/upload_helper.class.php`

```php
<?php
// ============================================================
// libs/upload_helper.class.php
// Helper centralizzato per l'upload sicuro di immagini.
//
// FIX (questa revisione):
//  - Corretto bug nel crop centrato: i due parametri finali di
//    imagecopyresampled() devono indicare la dimensione della
//    regione sorgente, non quella destinazione.
//  - Aggiunta alla whitelist: /upload_image/06_company/original/
//    e /upload_image/06_company/thumbnail/
// ============================================================

class UploadHelper
{
  // Whitelist hard-coded delle cartelle ammesse per l'upload.
  private static $ALLOWED_BASE_DIRS = [
    '/upload_image/02_free_ads/',
    '/upload_image/03_ads/',
    '/upload_image/06_company/original/',
    '/upload_image/06_company/thumbnail/',
    '/upload_image/blog/',
  ];

  // MIME → estensione canonica
  private static $ALLOWED_MIMES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
  ];

  const DEFAULT_MAX_SIZE = 5242880; // 5 MB

  /**
   * Gestisce un upload completo: validazione + salvataggio originale + thumbnail.
   * Ritorna ['ok' => bool, 'error' => string|null, 'filename' => string|null].
   */
  public static function handleImageUpload(array $file, array $opts): array
  {
    // 1. Pre-validazione struttura $_FILES
    if (!isset($file['error']) || is_array($file['error'])) {
      return self::fail('Invalid upload data.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
      return self::fail('Upload error (code ' . (int)$file['error'] . ').');
    }
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
      return self::fail('Possible upload spoofing detected.');
    }

    // 2. Size limit
    $max  = (int)($opts['max_size_bytes'] ?? self::DEFAULT_MAX_SIZE);
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0) {
      return self::fail('Empty file.');
    }
    if ($size > $max) {
      return self::fail(sprintf('File too large. Maximum size is %d MB.', (int)round($max / 1024 / 1024)));
    }

    // 3. MIME check via finfo (filesystem reale, non extension)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!isset(self::$ALLOWED_MIMES[$mime])) {
      return self::fail('File type not allowed. Only JPG, PNG and GIF are accepted.');
    }
    $canonical_ext = self::$ALLOWED_MIMES[$mime];

    // 4. Validazione struttura con getimagesize (defense in depth)
    $imginfo = @getimagesize($file['tmp_name']);
    if ($imginfo === false || !isset($imginfo[0], $imginfo[1])) {
      return self::fail('Invalid image content.');
    }
    if ($imginfo[0] > 10000 || $imginfo[1] > 10000) {
      return self::fail('Image too large in pixels.');
    }

    // 5. Whitelist directory destinazione
    $target_orig  = (string)($opts['target_dir_original']  ?? '');
    $target_thumb = (string)($opts['target_dir_thumbnail'] ?? '');
    if (!self::isAllowedDir($target_orig) || !self::isAllowedDir($target_thumb)) {
      error_log('[Allonwheel] UploadHelper: directory not in whitelist: ' . $target_orig);
      return self::fail('Internal configuration error.');
    }

    // Risoluzione path assoluti
    $abs_orig  = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $target_orig;
    $abs_thumb = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $target_thumb;
    if (!is_dir($abs_orig))  @mkdir($abs_orig,  0755, true);
    if (!is_dir($abs_thumb)) @mkdir($abs_thumb, 0755, true);

    if (!is_writable($abs_orig) || !is_writable($abs_thumb)) {
      return self::fail('Server configuration error: upload directory not writable.');
    }

    // 6. Filename randomizzato (non rivela ID sequenziali)
    $prefix = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($opts['filename_prefix'] ?? 'img'));
    if ($prefix === '') {
      $prefix = 'img';
    }
    $rand     = bin2hex(random_bytes(8));
    $filename = $prefix . '_' . $rand . '.' . $canonical_ext;

    $orig_path  = $abs_orig  . $filename;
    $thumb_path = $abs_thumb . $filename;

    // 7. Salvataggio originale (re-encoding → strip EXIF) + thumbnail
    $thumb_w    = (int)($opts['thumb_width']  ?? 220);
    $thumb_h    = (int)($opts['thumb_height'] ?? 150);
    $thumb_crop = (bool)($opts['thumb_crop']  ?? false);

    try {
      // Originale: nessun ridimensionamento, solo re-encoding (strip EXIF)
      self::saveStrippedImage($file['tmp_name'], $orig_path, $mime, null, null, false);
      // Thumbnail: resize proporzionale; se l'aspect ratio differisce, crop centrato
      self::saveStrippedImage($file['tmp_name'], $thumb_path, $mime, $thumb_w, $thumb_h, $thumb_crop);
    } catch (Throwable $e) {
      if (is_file($orig_path))  @unlink($orig_path);
      if (is_file($thumb_path)) @unlink($thumb_path);
      error_log('[Allonwheel] UploadHelper: image processing failed: ' . $e->getMessage());
      return self::fail('Image processing error. Please try a different file.');
    }

    return [
      'ok'       => true,
      'error'    => null,
      'filename' => $filename,
    ];
  }

  /**
   * Apre l'immagine, opzionalmente ridimensiona, e la salva rimuovendo EXIF.
   *
   * $crop = false → resize proporzionale (fit within box, mai ingrandisce)
   * $crop = true  → resize per coprire il box, poi crop centrato esatto
   */
  private static function saveStrippedImage(
    string $src,
    string $dst,
    string $mime,
    ?int   $max_w = null,
    ?int   $max_h = null,
    bool   $crop  = false
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

    if ($max_w !== null && $max_h !== null) {
      $src_w = imagesx($img);
      $src_h = imagesy($img);

      if ($crop) {
        // -------------------------------------------------------
        // Crop centrato:
        //   1. Fattore di scala per coprire il box target senza
        //      spazi vuoti (max dei due rapporti).
        //   2. Calcola la regione sorgente da ritagliare, centrata.
        //   3. imagecopyresampled(dst, src, 0,0, src_x,src_y,
        //                         dst_w,dst_h, src_crop_w,src_crop_h)
        //      I ultimi due argomenti devono essere le dimensioni
        //      della regione SORGENTE, non quelle di destinazione.
        // -------------------------------------------------------
        $ratio      = max($max_w / $src_w, $max_h / $src_h);
        $src_crop_w = (int)round($max_w / $ratio);   // larghezza regione sorgente
        $src_crop_h = (int)round($max_h / $ratio);   // altezza regione sorgente
        $src_x      = max(0, (int)round(($src_w - $src_crop_w) / 2));
        $src_y      = max(0, (int)round(($src_h - $src_crop_h) / 2));

        $canvas = imagecreatetruecolor($max_w, $max_h);
        if ($mime === 'image/png' || $mime === 'image/gif') {
          imagealphablending($canvas, false);
          imagesavealpha($canvas, true);
          $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
          imagefilledrectangle($canvas, 0, 0, $max_w, $max_h, $transparent);
        }

        imagecopyresampled(
          $canvas, $img,
          0, 0,                  // dst offset
          $src_x, $src_y,        // src offset (centrato)
          $max_w, $max_h,        // dst dimensioni
          $src_crop_w, $src_crop_h // src dimensioni regione (FIX: erano $max_w,$max_h)
        );
        imagedestroy($img);
        $img = $canvas;

      } else {
        // -------------------------------------------------------
        // Resize proporzionale (fit): mai ingrandisce, mai ritaglia.
        // -------------------------------------------------------
        $ratio = min($max_w / $src_w, $max_h / $src_h, 1.0);
        $new_w = (int)round($src_w * $ratio);
        $new_h = (int)round($src_h * $ratio);

        if ($new_w !== $src_w || $new_h !== $src_h) {
          $resized = imagecreatetruecolor($new_w, $new_h);
          if ($mime === 'image/png' || $mime === 'image/gif') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $new_w, $new_h, $transparent);
          }
          imagecopyresampled($resized, $img, 0, 0, 0, 0, $new_w, $new_h, $src_w, $src_h);
          imagedestroy($img);
          $img = $resized;
        }
      }
    }

    // Salvataggio (re-encoding rimuove automaticamente EXIF)
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

  private static function isAllowedDir(string $dir): bool
  {
    foreach (self::$ALLOWED_BASE_DIRS as $allowed) {
      if (strpos($dir, $allowed) === 0) {
        return true;
      }
    }
    return false;
  }

  private static function fail(string $message): array
  {
    return ['ok' => false, 'error' => $message, 'filename' => null];
  }
}
```
