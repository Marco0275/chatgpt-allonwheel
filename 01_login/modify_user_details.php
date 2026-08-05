<?php
// ============================================================
// modify_user_details.php — Aggiornamento telefono utente
// Riceve il POST da mydetails.php (campo: phone)
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/upload_helper.class.php';

// BASE_URL definito in config/bootstrap.php

// Richiede sessione attiva
if (!isset($_SESSION['session_id'])) {
  header('Location: ' . BASE_URL . '/01_login/newlogin.php');
  exit;
}

// Accetta solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit'])) {
  header('Location: ' . BASE_URL . '/01_login/mydetails.php');
  exit;
}

csrf_verify();

$phone  = trim($_POST['phone'] ?? '');
$id_user  = (int) $_SESSION['session_id_user'];

// Validazione: il telefono non può essere vuoto e deve avere max 30 chars
if (empty($phone)) {
  $_SESSION['modify_message'] = 'Phone number cannot be empty.';
  header('Location: ' . BASE_URL . '/01_login/mydetails.php');
  exit;
}

if (mb_strlen($phone) > 30) {
  $_SESSION['modify_message'] = 'Phone number is too long (max 30 characters).';
  header('Location: ' . BASE_URL . '/01_login/mydetails.php');
  exit;
}

// Upload immagine del profilo (opzionale): riusa UploadHelper come la registrazione.
$new_image = null;
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
  $res = UploadHelper::handleImageUpload($_FILES['profile_image'], [
    'target_dir_original'  => '/upload_image/profile/original/',
    'target_dir_thumbnail' => '/upload_image/profile/thumbnail/',
    'thumb_width'          => 120,
    'thumb_height'         => 120,
    'thumb_crop'           => true,
    'max_size_bytes'       => 5 * 1024 * 1024,
    'filename_prefix'      => 'profile_' . $id_user,
  ]);
  if (!$res['ok']) {
    $_SESSION['modify_message'] = 'Profile image upload failed: ' . $res['error'];
    header('Location: ' . BASE_URL . '/01_login/mydetails.php');
    exit;
  }
  $new_image = (string)$res['filename'];
}

// Aggiorna il database (solo sui dati dell'utente loggato: isolamento, dir. 12)
try {
  if ($new_image !== null) {
    // Vecchia immagine per il cleanup
    $old = $pdo->prepare('SELECT profile_image FROM users WHERE id_user = :id_user LIMIT 1');
    $old->execute([':id_user' => $id_user]);
    $old_image = (string)($old->fetchColumn() ?: '');

    $upd = $pdo->prepare('UPDATE users SET phone = :phone, profile_image = :img WHERE id_user = :id_user');
    $upd->execute([':phone' => $phone, ':img' => $new_image, ':id_user' => $id_user]);

    // Rimuove i file della vecchia immagine (se diversa dalla nuova)
    if ($old_image !== '' && $old_image !== $new_image) {
      $base = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/profile/';
      foreach (['original/', 'thumbnail/'] as $sub) {
        $f = $base . $sub . basename($old_image);
        if (is_file($f)) { @unlink($f); }
      }
    }
    $_SESSION['modify_message'] = 'Phone number and profile image updated successfully.';
  } else {
    $upd = $pdo->prepare('UPDATE users SET phone = :phone WHERE id_user = :id_user');
    $upd->execute([':phone' => $phone, ':id_user' => $id_user]);
    $_SESSION['modify_message'] = 'Phone number updated successfully.';
  }
} catch (PDOException $e) {
  // Cleanup nuova immagine se l'UPDATE fallisce (nessun file orfano)
  if ($new_image !== null) {
    $base = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/profile/';
    foreach (['original/', 'thumbnail/'] as $sub) {
      $f = $base . $sub . basename($new_image);
      if (is_file($f)) { @unlink($f); }
    }
  }
  error_log('[Allonwheel] modify_user_details error: ' . $e->getMessage());
  $_SESSION['modify_message'] = 'Could not save your changes. Please try again.';
  header('Location: ' . BASE_URL . '/01_login/mydetails.php');
  exit;
}

// Aggiorna anche la variabile di sessione
$_SESSION['session_phone'] = $phone;

header('Location: ' . BASE_URL . '/01_login/mydetails.php');
exit;
?>
