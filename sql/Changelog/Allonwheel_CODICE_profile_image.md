# Allonwheel — CODICE: Immagine del profilo (registrazione + avatar blog)
*Generato il 3 giu 2026. Bundle con blocchi di codice (workaround rendering MIME dei .php).*

## Cosa è stato fatto
1. **Registrazione**: nel form `newregister.php` è stato aggiunto un campo *file* opzionale per l'immagine del profilo (`enctype="multipart/form-data"`). L'handler `register.php` riusa `UploadHelper` (lo stesso del blog) per validare e salvare l'immagine, e memorizza il filename nella nuova colonna `users.profile_image`. In caso di upload fallito mostra un **messaggio esplicito** (niente redirect/fallimento silenzioso).
2. **Blog**: l'avatar dei commenti (prima fisso `images/avator.jpg`) ora mostra l'**immagine del profilo dell'autore** del commento. Se l'utente non ne ha caricata una, si usa `images/avator.jpg` come fallback (sia lato PHP sia via `onerror`).

## Passo DB (obbligatorio, prima di pubblicare)
Eseguire **una sola volta** la migration `sql/profile_image.sql`:
```sql
ALTER TABLE `users`
  ADD COLUMN `profile_image` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
  COMMENT 'Filename in /upload_image/profile/; NULL = avatar di default'
  AFTER `phone`;
```
> Nessuna perdita dati (dir. 9): colonna nullable, le INSERT esistenti restano valide. Il dump `sql/allonwhe80316.sql` è stato aggiornato per le installazioni *nuove* (colonna già nel CREATE TABLE).

## Cartelle upload
Le immagini profilo vanno in `/upload_image/profile/original/` e `/upload_image/profile/thumbnail/` (thumbnail 120×120 con crop centrato). `UploadHelper` le crea da sé al primo upload; nel ZIP sono incluse vuote. Sono protette dal `.htaccess` esistente di `upload_image/` (no PHP, no indexing). La cartella `images/` **non** è stata toccata (dir. 15).

## Direttive rispettate
- **0** testi UI in EN · **5/6** PHP+MySQL, base `template`/pattern esistenti · **8** nessun nuovo CSS (riuso classi `cleaner h10`, `gravatar`) · **9** nessuna perdita dati · **11** validazione MIME/size + EXIF strip + filename randomizzato (UploadHelper) · **15** `upload`/`images` intoccate · **16** comunicazione in italiano.
- Line ending preservati: `register.php`, `newregister.php`, `blog_comments.php`, `blog.class.php` = **CRLF**; `upload_helper.class.php` = **LF**.

## Verifica (doppio passaggio, dir. 2/10)
- 1ª: `php -l` su **tutti** i file PHP → 0 errori.
- 2ª: grep mirati (enctype, campo file, INSERT, guard `PARAM_NULL`, whitelist, `profile_image` in `listComments`, src avatar dinamico, fallback `onerror`) → tutti OK. Test runtime: thumbnail profilo generata 120×120.

---

## `sql/profile_image.sql`
*NUOVO — migration DB (eseguire una volta)*

```sql
-- ============================================================
-- profile_image.sql — Immagine del profilo utente.
-- Aggiunge la colonna `profile_image` alla tabella `users`.
--   Contiene il filename salvato in /upload_image/profile/
--   (sottocartelle original/ + thumbnail/, generate da UploadHelper).
--   NULL = nessuna immagine caricata -> in UI si usa images/avator.jpg.
-- Idempotente: eseguire una sola volta. Nessuna perdita dati (dir. 9).
-- ============================================================
ALTER TABLE `users`
  ADD COLUMN `profile_image` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
  COMMENT 'Filename in /upload_image/profile/; NULL = avatar di default'
  AFTER `phone`;

```

## `libs/upload_helper.class.php`
*MODIFICATO — whitelist cartella profile/*

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
    '/upload_image/profile/original/',
    '/upload_image/profile/thumbnail/',
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

## `01_login/register.php`
*MODIFICATO — upload immagine profilo + INSERT*

```php
<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';  // FIX: aggiunto CSRF
require_once __DIR__ . '/../libs/upload_helper.class.php';  // upload immagine profilo

// BASE_URL definito in config/bootstrap.php

if (isset($_POST['register'])) {

  // FIX: verifica token CSRF prima di qualsiasi elaborazione
  csrf_verify();

  $username = trim($_POST['username'] ?? '');
  $email  = trim($_POST['email']  ?? '');
  $phone  = trim($_POST['phone']  ?? '');
  $password = $_POST['password']  ?? '';

  // Validazione username: solo lettere, numeri e underscore, 3-20 caratteri
  $isUsernameValid = (bool) filter_var($username, FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^[a-z\d_]{3,20}$/i'],
  ]);

  // Validazione email
  $isEmailValid = (bool) filter_var($email, FILTER_VALIDATE_EMAIL);

  $pwdlength = mb_strlen($password);

  if (empty($username) || empty($email) || empty($password)) {
    $msg = 'Fill in all fields.';
  } elseif (!$isUsernameValid) {
    $msg = 'Invalid username. Only alphanumeric characters and underscores are allowed. Length: 3-20 chars.';
  } elseif (!$isEmailValid) {
    $msg = 'Please enter a valid email address.';
  } elseif (mb_strlen($email) > 50) {
    $msg = 'Email must be at most 50 characters.';
  } elseif ($pwdlength < 8 || $pwdlength > 20) {
    $msg = 'Password must be between 8 and 20 characters.';
  } else {
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Controllo se email già registrata
    $check = $pdo->prepare('SELECT id_user FROM users WHERE email = :email LIMIT 1');
    $check->bindParam(':email', $email, PDO::PARAM_STR);
    $check->execute();

    if ($check->rowCount() > 0) {
    header('Location: ' . BASE_URL . '/01_login/already_registered.php');
    exit;
    }

    // Controllo se username già in uso
    $checkUser = $pdo->prepare('SELECT id_user FROM users WHERE username = :username LIMIT 1');
    $checkUser->bindParam(':username', $username, PDO::PARAM_STR);
    $checkUser->execute();

    if ($checkUser->rowCount() > 0) {
    $msg = 'This username is already taken. Please choose another.';
    } else {
    // Token di verifica email (64 caratteri esadecimali)
    $email_verification_token = bin2hex(random_bytes(32));

    // Upload immagine del profilo (opzionale): riusa UploadHelper come il blog.
    // In caso di errore mostra un messaggio esplicito (niente fallimento silenzioso).
    $profile_image         = null;
    $profile_upload_failed = false;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
      $res = UploadHelper::handleImageUpload($_FILES['profile_image'], [
        'target_dir_original'  => '/upload_image/profile/original/',
        'target_dir_thumbnail' => '/upload_image/profile/thumbnail/',
        'thumb_width'          => 120,
        'thumb_height'         => 120,
        'thumb_crop'           => true,
        'max_size_bytes'       => 5 * 1024 * 1024,
        'filename_prefix'      => 'profile',
      ]);
      if (!$res['ok']) {
        $msg = 'Profile image upload failed: ' . $res['error'];
        $profile_upload_failed = true;
      } else {
        $profile_image = (string)$res['filename'];
      }
    }

    if (!$profile_upload_failed) {
    $insertQuery = '
      INSERT INTO users (username, email, phone, profile_image, password, email_verification_token, is_verified)
      VALUES (:username, :email, :phone, :profile_image, :password, :email_verification_token, 0)
    ';

    $insert = $pdo->prepare($insertQuery);
    $insert->bindParam(':username',       $username,       PDO::PARAM_STR);
    $insert->bindParam(':email',        $email,        PDO::PARAM_STR);
    $insert->bindParam(':phone',        $phone,        PDO::PARAM_STR);
    $insert->bindValue(':profile_image', $profile_image, $profile_image === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $insert->bindParam(':password',       $password_hash,    PDO::PARAM_STR);
    $insert->bindParam(':email_verification_token', $email_verification_token, PDO::PARAM_STR);

    // FIX: indentazione corretta e line endings uniformati (\n)
    try {
    if ($insert->execute()) {
      $verification_link = BASE_URL . '/01_login/verify.php?token=' . $email_verification_token;

      $to  = $email;
      $subject = 'Confirm your account on All on Wheel';

      $message  = 'Hi ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . ",\n\n";
      $message .= "Thank you for registering.\n\n";
      $message .= "To activate your account, click the following link:\n\n";
      $message .= $verification_link . "\n\n";
      $message .= "If you did not request this registration, you can ignore this email.\n";

      $headers  = "From: noreply@allonwheel.com\r\n";
      $headers .= "Reply-To: noreply@allonwheel.com\r\n";
      $headers .= 'X-Mailer: PHP/' . phpversion();

      if (mail($to, $subject, $message, $headers)) {
        header('Location: ' . BASE_URL . '/01_login/register_ok.php');
      } else {
        // Registrazione avvenuta ma email non inviata
        header('Location: ' . BASE_URL . '/01_login/register_ok_noemail.php');
      }
      exit;
    } else {
      $msg = 'Error registering user. Please try again.';
    }
    } catch (PDOException $e) {
      // Cleanup immagine profilo se l'insert fallisce (nessun file orfano)
      if ($profile_image !== null && $profile_image !== '') {
        $base = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/profile/';
        foreach (['original/', 'thumbnail/'] as $sub) {
          $f = $base . $sub . basename($profile_image);
          if (is_file($f)) { @unlink($f); }
        }
      }
      error_log('[Allonwheel] register insert error: ' . $e->getMessage());
      $msg = 'Error registering user. Please try again.';
    }
    } // end profile upload check
    } // end username check
  }

  // Mostra eventuale messaggio di errore
  if (isset($msg)) {
    printf('<p>%s <a href="newregister.php">Back</a></p>', htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'));
  }
}
?>

```

## `01_login/newregister.php`
*MODIFICATO — enctype + campo file profilo*

```php
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Register your account</title>
<meta name="keywords" content="All on Wheel Ltd - Register account" />
<meta name="description" content="All on Wheel Ltd - Register your account" />
<meta name="robots" content="index, follow" />
<meta name="revisit-after" content="3" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<!--<link href="css_pirobox/white/style.css" media="screen" title="white" rel="stylesheet" type="text/css" />
<link href="css_pirobox/black/style.css" media="screen" title="black" rel="stylesheet" type="text/css" />-->
<!--////// END  \\\\\\\-->

<!--////// INCLUDE THE JS AND PIROBOX OPTION IN YOUR HEADER  \\\\\\\-->
<!--////// END  \\\\\\\-->
<script type="text/javascript" src="../js/jquery.min.js"></script>
<script type="text/javascript" src="../js/ddsmoothmenu.js"></script>
<script type="text/javascript" src="../js/piroBox.1_2.js"></script>
<script type="text/javascript" src="../js/site_init.js"></script>
</head>
<body>
<div id="templatemo_wrapper"><div id="templatemo_header">
 <?php include ('../header.php'); ?>
</div>
<div id="content_top">
<div id="page_title">Register account</div>
<div id="search_box">
<form action="#" method="get">
<input type="text" value="Search" name="q" size="10" id="searchfield" title="searchfield" onfocus="clearText(this)" onblur="clearText(this)" />
<input type="submit" name="Search" value="" id="searchbutton" title="Search" />
</form>
</div>
<div class="cleaner"></div>
</div>
<div id="templatemo_content">
<h2>Fill with your details</h2>
		<p>Complete the following details to register your account; then publish news, photos and much more...</p>
		<div id="contact_form">
		<?php
    // FIX: carica CSRF helper e genera token
    require_once __DIR__ . '/../config/csrf.php';
    ?>
		<form method="post" action="register.php" enctype="multipart/form-data">
		 <?php echo csrf_generate(); ?>
		 <table width="100%" border="0">
		 <tbody>
		  <tr>
		  <td width="35%" rowspan="6"><img src="../images/my_profile/profile.jpg" alt="" width="220" height="163"/>
		  <div class="cleaner h10"></div>
		  <label for="profile_image">Profile image (optional, JPG/PNG/GIF)</label><br />
		  <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif" />
		  </td>
		  <td colspan="2" align="left" valign="top"><div class="float_l"></div></td>
	 </tr>
		  <tr>
		  <td width="14%" align="left" valign="top"><span class="float_l">
		 Username: 
		 
		 </span></td>
		  <td width="51%" align="left" valign="top"><span class="float_l">
		  <input type="text" id="username" placeholder="username" name="username" maxlength="20" required="required" />
		  </span></td>
	  </tr>
		  <tr>
		  <td align="left" valign="top"><span class="float_l">
		 E-mail: 
		 
		 </span></td>
		  <td align="left" valign="top"><span class="float_l">
		  <input type="text" id="email" placeholder="email" name="email" maxlength="50" required="required" />
		  </span></td>
	  </tr>
		  <tr>
		  <td align="left" valign="top"><div class="float_l"></div>
		  Phone:</td>
		  <td align="left" valign="top"><span class="float_l">
		  <input type="text" id="phone" placeholder="phone" name="phone" maxlength="30" />
		  </span></td>
	  </tr>
		  <tr>
		  <td align="left" valign="top">Password:</td>
		  <td align="left" valign="top"><span class="float_l">
		  <input type="password" id="password" placeholder="Password (8-20 chars)" name="password" required="required" />
		  </span></td>
	  </tr>
		  <tr>
		  <td colspan="2" align="left" valign="top">&nbsp;</td>
	 </tr>
	  </tbody>
	  </table>
		 <div class="cleaner h20"></div>
		<input type="submit" class="submit_btn float_r" name="register" id="submit" value="Register" />
<input type="reset" class="submit_btn float_l" name="reset" id="reset" value="Reset" />
		</form>
		</div>
<div class="cleaner"></div>
</div> <!-- end of content -->
<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
<!-- end of sidebar -->
<div class="cleaner"></div>
<!-- inizia qui il piè di pagina -->
<?php include "../footer.php"; ?>
<!-- finisce qui il piè di pagina -->
<!-- FIX: era /a> (tag di chiusura HTML malformato) -->
</body>
</html>

```

## `libs/blog.class.php`
*MODIFICATO — profile_image in listComments()*

```php
<?php
// ============================================================
// libs/blog.class.php — BlogManager
// Gestione articoli del blog (tabella `blog`). Usa PDO, coerente con i
// moduli recenti (02/03/01) e con il pannello admin.
// Le letture pubbliche degradano a vuoto se la tabella non esiste ancora
// (es. SQL non ancora eseguito), per non rompere blog.php.
// ============================================================
class BlogManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Articoli pubblicati (con nome autore), paginati. */
    public function listPublished(int $limit = 5, int $offset = 0): array
    {
        try {
            $sql = "SELECT b.*, u.username
                    FROM `blog` b
                    LEFT JOIN `users` u ON u.id_user = b.id_user
                    WHERE b.status = 'published'
                    ORDER BY b.created_at DESC
                    LIMIT :lim OFFSET :off";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':lim', max(1, $limit), PDO::PARAM_INT);
            $stmt->bindValue(':off', max(0, $offset), PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /** Conteggio articoli pubblicati (per la paginazione). */
    public function countPublished(): int
    {
        try {
            return (int)$this->pdo->query("SELECT COUNT(*) FROM `blog` WHERE status = 'published'")->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Singolo articolo. Di default solo se 'published'; passando
     * $viewer_id si consente all'autore di vedere anche i propri non pubblicati,
     * e $is_admin consente all'admin di vedere qualsiasi stato.
     */
    public function getById(int $id, ?int $viewer_id = null, bool $is_admin = false): ?array
    {
        try {
            $sql = "SELECT b.*, u.username
                    FROM `blog` b
                    LEFT JOIN `users` u ON u.id_user = b.id_user
                    WHERE b.id = :id LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }
            if ($row['status'] !== 'published'
                && !$is_admin
                && !($viewer_id !== null && (int)$row['id_user'] === $viewer_id)) {
                return null; // non visibile a chi non ne ha diritto
            }
            return $row;
        } catch (PDOException $e) {
            return null;
        }
    }

    /** Articoli di un utente (tutti gli stati). */
    public function listByUser(int $id_user): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM `blog` WHERE id_user = :u ORDER BY created_at DESC"
            );
            $stmt->execute([':u' => $id_user]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /** Inserisce un articolo. Ritorna l'id nuovo o 0 in caso di errore. */
    public function insertArticle(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO `blog` (id_user, title, excerpt, body, image, status)
             VALUES (:u, :t, :e, :b, :img, :st)"
        );
        $stmt->execute([
            ':u'   => (int)$data['id_user'],
            ':t'   => $data['title'],
            ':e'   => $data['excerpt'] !== '' ? $data['excerpt'] : null,
            ':b'   => $data['body'],
            ':img' => $data['image'] !== '' ? $data['image'] : null,
            ':st'  => $data['status'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Elenco per moderazione admin (filtro: all|pending|published|rejected). */
    public function listForModeration(string $filter = 'all'): array
    {
        try {
            $sql = "SELECT b.*, u.username
                    FROM `blog` b
                    LEFT JOIN `users` u ON u.id_user = b.id_user";
            if (in_array($filter, ['pending', 'published', 'rejected'], true)) {
                $sql .= " WHERE b.status = " . $this->pdo->quote($filter);
            }
            $sql .= " ORDER BY b.created_at DESC";
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /** Aggiorna lo stato (moderazione). */
    public function setStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['pending', 'published', 'rejected'], true)) {
            return false;
        }
        $stmt = $this->pdo->prepare("UPDATE `blog` SET status = :s WHERE id = :id LIMIT 1");
        return $stmt->execute([':s' => $status, ':id' => $id]);
    }

    /** Cancella un articolo. Ritorna il filename immagine (per cleanup) o null. */
    public function deleteArticle(int $id): ?string
    {
        $stmt = $this->pdo->prepare("SELECT image FROM `blog` WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $img = $stmt->fetchColumn();
        $this->pdo->prepare("DELETE FROM `blog` WHERE id = :id LIMIT 1")->execute([':id' => $id]);
        return $img !== false && $img !== null && $img !== '' ? (string)$img : null;
    }

    // =========================================================
    // COMMENTI (tabella `blog_comments`) — solo testo, niente immagini
    // =========================================================

    /** Commenti visibili di un articolo (con nome autore), piu' vecchi prima. */
    public function listComments(int $id_blog): array
    {
        try {
            $sql = "SELECT c.*, u.username, u.profile_image
                    FROM `blog_comments` c
                    LEFT JOIN `users` u ON u.id_user = c.id_user
                    WHERE c.id_blog = :b AND c.status = 'visible'
                    ORDER BY c.created_at ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':b' => $id_blog]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /** Numero di commenti visibili di un articolo. */
    public function countComments(int $id_blog): int
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `blog_comments` WHERE id_blog = :b AND status = 'visible'");
            $stmt->execute([':b' => $id_blog]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /** Inserisce un commento (solo testo). Ritorna l'id o 0. */
    public function insertComment(int $id_blog, int $id_user, string $body): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO `blog_comments` (id_blog, id_user, body) VALUES (:b, :u, :body)"
        );
        $stmt->execute([':b' => $id_blog, ':u' => $id_user, ':body' => $body]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Restituisce un commento (per verifica proprieta' prima del delete). */
    public function getComment(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM `blog_comments` WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /** Cancella un commento. */
    public function deleteComment(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `blog_comments` WHERE id = :id LIMIT 1");
        return $stmt->execute([':id' => $id]);
    }
}

```

## `blog_comments.php`
*MODIFICATO — avatar = immagine profilo (fallback avator.jpg)*

```php
<?php
// ============================================================
// blog_comments.php — Sezione commenti di un articolo (DA INCLUDERE in
// blog_post.php al posto del segnaposto "Comments are not available yet").
//
// Requisiti dal contesto di blog_post.php: $article (con 'id'), $blog
// (BlogManager), $pdo, e i session helper gia' inclusi.
// Commenti SOLO TESTO: nessun campo immagine, nessun HTML (output via
// htmlspecialchars + nl2br). Scrittura riservata agli utenti registrati;
// autore e admin possono cancellare. Markup con le classi del template.
// ============================================================
require_once __DIR__ . '/config/csrf.php';

$comment_blog_id = (int)($article['id'] ?? 0);
$comments        = $blog->listComments($comment_blog_id);
$can_comment     = is_user_logged_in();
$me_id           = $can_comment ? (int)current_user_id() : 0;
$me_is_admin     = !empty($_SESSION['user_tier']) && $_SESSION['user_tier'] === 'admin';

$c_ok  = $_SESSION['comment_success'] ?? '';
$c_err = $_SESSION['comment_error'] ?? '';
unset($_SESSION['comment_success'], $_SESSION['comment_error']);
?>
<h3>Comments<?php echo $comments ? ' (' . count($comments) . ')' : ''; ?></h3>

<?php if ($c_ok !== ''): ?>
  <p class="done"><?php echo htmlspecialchars($c_ok, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>
<?php if ($c_err !== ''): ?>
  <p class="error-msg"><?php echo htmlspecialchars($c_err, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<div id="comment_section">
  <?php if (empty($comments)): ?>
    <p>No comments yet.<?php echo $can_comment ? ' Be the first to comment!' : ''; ?></p>
  <?php else: ?>
  <ol class="comments first_level">
    <?php foreach ($comments as $i => $c):
      $author = trim((string)($c['username'] ?? '')) !== '' ? $c['username'] : 'User';
      // Avatar = immagine del profilo dell'autore; fallback su images/avator.jpg
      $pimg   = trim((string)($c['profile_image'] ?? ''));
      $avatar = $pimg !== '' ? '/upload_image/profile/thumbnail/' . rawurlencode($pimg) : 'images/avator.jpg';
      $box    = ($i % 2 === 0) ? 'commentbox1' : 'commentbox2';
      $can_del = $me_is_admin || ($me_id > 0 && (int)$c['id_user'] === $me_id);
    ?>
    <li>
      <div class="comment_box <?php echo $box; ?>">
        <div class="gravatar"><img src="<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($author, ENT_QUOTES, 'UTF-8'); ?>" onerror="this.onerror=null;this.src='images/avator.jpg';" /></div>
        <div class="comment_text">
          <div class="comment_author"><?php echo htmlspecialchars($author); ?>
            <span class="date"><?php echo htmlspecialchars(date('j F Y', strtotime((string)$c['created_at']))); ?></span>
            <span class="time"><?php echo htmlspecialchars(date('g:i a', strtotime((string)$c['created_at']))); ?></span>
          </div>
          <p><?php echo nl2br(htmlspecialchars((string)$c['body'])); ?></p>
          <?php if ($can_del): ?>
          <div class="reply">
            <form method="post" action="blog_comment_save.php" style="display:inline; margin:0;"
                  data-confirm="Delete this comment?">
              <?php echo csrf_generate(); ?>
              <input type="hidden" name="action" value="delete" />
              <input type="hidden" name="comment_id" value="<?php echo (int)$c['id']; ?>" />
              <input type="hidden" name="id_blog" value="<?php echo $comment_blog_id; ?>" />
              <button type="submit" class="more">Delete</button>
            </form>
          </div>
          <?php endif; ?>
        </div>
        <div class="cleaner"></div>
      </div>
    </li>
    <?php endforeach; ?>
  </ol>
  <?php endif; ?>
</div>

<div class="cleaner h20"></div>

<div id="comment_form">
  <?php if ($can_comment): ?>
  <h3>Leave your comment</h3>
  <form action="blog_comment_save.php" method="post">
    <?php echo csrf_generate(); ?>
    <input type="hidden" name="action" value="add" />
    <input type="hidden" name="id_blog" value="<?php echo $comment_blog_id; ?>" />
    <div class="form_row">
      <label>Comment (* required)</label><br />
      <textarea name="body" rows="6" cols=""></textarea>
    </div>
    <input type="submit" name="submit" value="Post" class="submit_btn float_r" />
	  </br>
  </form>
  <?php else: ?>
  <p>Please <a href="01_login/newlogin.php">log in</a> to leave a comment.</p>
  <?php endif; ?>
</div>

```
