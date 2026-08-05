<?php
/**
 * SmartImage class
 *
 * Elaborazione immagini tramite GD Library.
 *
 * CORREZIONI rispetto alla versione 0.9.6:
 *  - [BUG FIX] resizeNormal(): cast esplicito a int per $newwidth/$newheight
 *  → in PHP 8 i float causavano TypeError in imagecreatetruecolor()
 *  → in PHP 7 il troncamento silenzioso poteva distorcere le proporzioni
 *  - [BUG FIX] Preservazione canale alpha (trasparenza) per PNG e GIF
 *  - [NEW]   Supporto WEBP (lettura e salvataggio)
 *  - [NEW]   Metodo resizeFit() — ridimensiona al massimo mantenendo
 *      le proporzioni, senza mai allargare l'immagine
 *  - [FIX]   setMemoryForBigImage(): gestione corretta quando 'channels'
 *      non è impostato (palette PNG, GIF)
 *  - [FIX]   Errori non silenziati con @ — usa exception/error_log
 *  - [FIX]   findMime(): ora riconosce anche .webp
 *
 * @author Alessandro Coscia (original), refactored
 * @license  LGPL
 * @version  1.0.0
 */
if (!class_exists('SmartImage', false)):
class SmartImage
{
  private $src;
  private $gdID;
  private $info;

  // --------------------------------------------------------
  // Costruttore
  // --------------------------------------------------------

  /**
   * @param string $src    Percorso assoluto al file immagine
   * @param bool $bigImageSize true = adatta il memory_limit per immagini grandi
   * @throws RuntimeException se il file non esiste o il formato non è supportato
   */
  public function __construct(string $src, bool $bigImageSize = false)
  {
    if (!file_exists($src)) {
    throw new RuntimeException("SmartImage: file not found: {$src}");
    }

    if ($bigImageSize) {
    $this->setMemoryForBigImage($src);
    }

    $this->src  = $src;
    $this->info = getimagesize($src);

    if ($this->info === false) {
    throw new RuntimeException("SmartImage: getimagesize() failed for: {$src}");
    }

    $this->gdID = $this->createFromFile($src, $this->info[2]);

    if ($this->gdID === false) {
    throw new RuntimeException("SmartImage: cannot create GD resource from: {$src}");
    }
  }

  // --------------------------------------------------------
  // Metodi pubblici principali
  // --------------------------------------------------------

  /**
   * Ridimensiona l'immagine.
   *
   * @param int  $width  Larghezza massima target (px)
   * @param int  $height Altezza massima target (px)
   * @param bool $cutImage true = ritaglia al centro mantenendo esatte dimensioni
   *         false = ridimensiona proporzionalmente dentro il box
   */
  public function resize(int $width, int $height, bool $cutImage = false): bool
  {
    return $cutImage
    ? $this->resizeWithCut($width, $height)
    : $this->resizeNormal($width, $height);
  }

  /**
   * Ridimensiona proporzionalmente senza MAI ingrandire l'immagine.
   * Se l'immagine è già più piccola del box, non viene modificata.
   */
  public function resizeFit(int $maxWidth, int $maxHeight): bool
  {
    $origW = $this->info[0];
    $origH = $this->info[1];

    // Già dentro i limiti — nessuna operazione necessaria
    if ($origW <= $maxWidth && $origH <= $maxHeight) {
    return true;
    }

    return $this->resizeNormal($maxWidth, $maxHeight);
  }

  /**
   * Aggiunge un watermark all'immagine corrente.
   *
   * @param string $waterMark Percorso al file watermark
   * @param int  $opacity Opacità (0–100), ignorata per PNG (usa canale alpha nativo)
   * @param int  $x   Offset X dal bordo sinistro
   * @param int  $y   Offset Y dal bordo superiore
   */
  public function addWaterMarkImage(string $waterMark, int $opacity = 35, int $x = 5, int $y = 5): void
  {
    $im    = $this->gdID;
    $waterMarkSM = new SmartImage($waterMark);
    $imWM    = $waterMarkSM->getGDid();

    if ($waterMarkSM->info[2] === IMAGETYPE_PNG) {
    // PNG: usa imagecopy che rispetta il canale alpha
    imagecopy($im, $imWM, $x, $y, 0, 0, imagesx($imWM), imagesy($imWM));
    } else {
    imagecopymerge($im, $imWM, $x, $y, 0, 0, imagesx($imWM), imagesy($imWM), $opacity);
    }

    $waterMarkSM->close();
    $this->gdID = $im;
  }

  /**
   * Ruota l'immagine.
   *
   * @param float $degrees Gradi di rotazione (senso antiorario, default 180)
   */
  public function rotate(float $degrees = 180): void
  {
    $this->gdID = imagerotate($this->gdID, $degrees, 0);
    $this->updateInfo();
  }

  /**
   * Invia l'immagine direttamente al browser con il corretto Content-Type.
   */
  public function printImage(int $jpegQuality = 90): void
  {
    $this->outputImage('', $jpegQuality);
  }

  /**
   * Salva l'immagine su file.
   *
   * @param string $destination Percorso assoluto di destinazione
   * @param int  $jpegQuality Qualità JPEG (1–100)
   */
  public function saveImage(string $destination, int $jpegQuality = 90): void
  {
    $this->outputImage($destination, $jpegQuality);
  }

  public function getGDid()
  {
    return $this->gdID;
  }

  /** @return array{x: int, y: int} */
  public function getSize(): array
  {
    return ['x' => $this->info[0], 'y' => $this->info[1]];
  }

  public function setGDid($value): void
  {
    $this->gdID = $value;
  }

  public function close(): void
  {
    if (is_resource($this->gdID) || $this->gdID instanceof \GdImage) {
    imagedestroy($this->gdID);
    }
  }

  // --------------------------------------------------------
  // Metodi privati
  // --------------------------------------------------------

  /**
   * Ridimensionamento proporzionale dentro un box (width × height).
   *
   * CORREZIONE PRINCIPALE: $newwidth e $newheight vengono calcolati come float
   * durante la proporzione e DEVONO essere convertiti a int prima di essere
   * passati a imagecreatetruecolor() / imagecopyresampled().
   * In PHP 8 i float causano TypeError; in PHP 7 venivano troncati (floor),
   * causando distorsioni sub-pixel visibili su immagini grandi.
   */
  private function resizeNormal(int $width, int $height): bool
  {
    $origW = (float) $this->info[0];
    $origH = (float) $this->info[1];

    // Calcolo proporzionale — rimane in float fino alla fine
    $newW = $origW;
    $newH = $origH;

    if ($newW > $width) {
    $newH = ($width / $newW) * $newH;
    $newW = (float) $width;
    }
    if ($newH > $height) {
    $newW = ($height / $newH) * $newW;
    $newH = (float) $height;
    }

    // *** FIX: arrotondamento corretto prima di passare alle funzioni GD ***
    $newW = (int) round($newW);
    $newH = (int) round($newH);

    // Garanzia dimensioni minime di 1px
    $newW = max(1, $newW);
    $newH = max(1, $newH);

    $new  = $this->createCanvas($newW, $newH, $this->info[2]);
    $result = imagecopyresampled($new, $this->gdID, 0, 0, 0, 0, $newW, $newH, (int) $origW, (int) $origH);

    imagedestroy($this->gdID);
    $this->gdID = $new;
    $this->updateInfo();

    return $result;
  }

  /**
   * Ritaglia e ridimensiona al centro per ottenere esattamente width × height.
   */
  private function resizeWithCut(int $width, int $height): bool
  {
    $origW = $this->info[0];
    $origH = $this->info[1];

    if ($origW <= $width && $origH <= $height) {
    // Immagine già abbastanza piccola — nessun taglio necessario
    $this->gdID = $this->gdID;
    $this->updateInfo();
    return true;
    }

    $centerX = $origW / 2;
    $centerY = $origH / 2;
    $propX = $width  / $origW;
    $propY = $height / $origH;

    if ($propX < $propY) {
    $src_x = (int) round($centerX - ($width  * (1 / $propY) / 2));
    $src_y = 0;
    $src_w = (int) ceil($width  / $propY);
    $src_h = $origH;
    } else {
    $src_x = 0;
    $src_y = (int) round($centerY - ($height * (1 / $propX) / 2));
    $src_w = $origW;
    $src_h = (int) ceil($height / $propX);
    }

    $new  = $this->createCanvas($width, $height, $this->info[2]);
    $result = imagecopyresampled($new, $this->gdID, 0, 0, $src_x, $src_y, $width, $height, $src_w, $src_h);

    imagedestroy($this->gdID);
    $this->gdID = $new;
    $this->updateInfo();

    return (bool) $result;
  }

  /**
   * Crea una canvas GD vuota con gestione della trasparenza per PNG/GIF/WEBP.
   */
  private function createCanvas(int $width, int $height, int $imageType)
  {
    $canvas = imagecreatetruecolor($width, $height);

    // Preserva trasparenza per PNG, GIF, WEBP
    if (in_array($imageType, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
    imagealphablending($canvas, true);
    }

    return $canvas;
  }

  /**
   * Crea una risorsa GD dal file sorgente in base al tipo immagine.
   * Supporta JPEG, PNG, GIF, WEBP.
   */
  private function createFromFile(string $src, int $imageType)
  {
    switch ($imageType) {
    case IMAGETYPE_JPEG:
      return imagecreatefromjpeg($src);
    case IMAGETYPE_PNG:
      return imagecreatefrompng($src);
    case IMAGETYPE_GIF:
      return imagecreatefromgif($src);
    case IMAGETYPE_WEBP:
      if (!function_exists('imagecreatefromwebp')) {
        throw new RuntimeException('SmartImage: WEBP support not available in this GD build.');
      }
      return imagecreatefromwebp($src);
    default:
      throw new RuntimeException("SmartImage: unsupported image type ({$imageType}) for: {$src}");
    }
  }

  /**
   * Salva o invia l'immagine al browser.
   */
  private function outputImage(string $dest, int $jpegQuality): void
  {
    $imageType = $this->info[2];

    // Se la destinazione ha un'estensione diversa dall'originale, rilevala
    if ($dest !== '') {
    [, $imageType] = $this->findMime($dest);
    }

    if ($dest === '') {
    header('Content-Type: ' . $this->info['mime']);
    }

    switch ($imageType) {
    case IMAGETYPE_JPEG:
      imagejpeg($this->gdID, $dest ?: null, $jpegQuality);
      break;
    case IMAGETYPE_PNG:
      // Per PNG imposta alpha saving prima dell'output
      imagesavealpha($this->gdID, true);
      imagepng($this->gdID, $dest ?: null);
      break;
    case IMAGETYPE_GIF:
      imagegif($this->gdID, $dest ?: null);
      break;
    case IMAGETYPE_WEBP:
      if (!function_exists('imagewebp')) {
        throw new RuntimeException('SmartImage: WEBP output not supported by this GD build.');
      }
      imagewebp($this->gdID, $dest ?: null, $jpegQuality);
      break;
    default:
      // Fallback JPEG
      imagejpeg($this->gdID, $dest ?: null, $jpegQuality);
    }
  }

  /**
   * Rileva il tipo immagine dall'estensione del percorso di destinazione.
   *
   * @return array{0: string, 1: int} [mime_type, IMAGETYPE_* constant]
   */
  private function findMime(string $file): array
  {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    $map = [
    'jpg'  => ['image/jpeg', IMAGETYPE_JPEG],
    'jpeg' => ['image/jpeg', IMAGETYPE_JPEG],
    'gif'  => ['image/gif',  IMAGETYPE_GIF],
    'png'  => ['image/png',  IMAGETYPE_PNG],
    'webp' => ['image/webp', IMAGETYPE_WEBP],
    ];

    return $map[$ext] ?? ['image/jpeg', IMAGETYPE_JPEG];
  }

  /**
   * Aumenta il memory_limit se necessario per elaborare immagini molto grandi.
   */
  private function setMemoryForBigImage(string $filename): void
  {
    $imageInfo = getimagesize($filename);
    if ($imageInfo === false) {
    return;
    }

    $bits   = $imageInfo['bits']   ?? 8;
    $channels = $imageInfo['channels'] ?? 3; // default RGB se non disponibile (es. PNG palette)

    $memoryNeeded = (int) round(
    ($imageInfo[0] * $imageInfo[1] * $bits * $channels / 8 + 65536) * 1.65
    );

    $currentLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

    if ($currentLimit !== -1 && (memory_get_usage() + $memoryNeeded) > $currentLimit) {
    $newLimitMB = (int) ceil(($currentLimit + $memoryNeeded) / 1048576);
    ini_set('memory_limit', $newLimitMB . 'M');
    }
  }

  /**
   * Converte una stringa come "128M" in byte.
   * Ritorna -1 se il limite è illimitato.
   */
  private function parseMemoryLimit(string $val): int
  {
    if ($val === '-1') {
    return -1;
    }

    $val  = trim($val);
    $last = strtolower($val[strlen($val) - 1]);
    $num  = (int) $val;

    switch ($last) {
    case 'g': return $num * 1073741824;
    case 'm': return $num * 1048576;
    case 'k': return $num * 1024;
    }

    return $num;
  }

  /**
   * Aggiorna le informazioni sull'immagine corrente dopo un resize/rotate.
   */
  private function updateInfo(): void
  {
    $this->info[0] = imagesx($this->gdID);
    $this->info[1] = imagesy($this->gdID);
  }
}
endif;
