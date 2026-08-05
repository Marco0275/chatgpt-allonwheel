<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';  // FIX: aggiunto CSRF
require_once __DIR__ . '/../libs/upload_helper.class.php';  // upload immagine profilo
require_once __DIR__ . '/../libs/mailer.class.php';         // invio email (SMTP se configurato)
require_once __DIR__ . '/../libs/antispam.php';

// BASE_URL definito in config/bootstrap.php

// ------------------------------------------------------------
// 27 lug 2026 — gestione degli errori.
// Prima ogni errore veniva stampato come una riga di testo su pagina bianca e
// l'utente perdeva tutto quello che aveva digitato: a quel punto la maggior
// parte non ricomincia. Ora si torna al modulo, con il messaggio e i campi
// gia' riempiti (mai la password, che non va rimessa in sessione).
// ------------------------------------------------------------
$reg_fail = static function (string $message): void {
    $_SESSION['reg_error'] = $message;
    $_SESSION['reg_old'] = [
        'username'             => trim((string)($_POST['username'] ?? '')),
        'email'                => trim((string)($_POST['email'] ?? '')),
        'phone'                => trim((string)($_POST['phone'] ?? '')),
        'intent'               => (string)($_POST['intent'] ?? ''),
        'public_contact'       => !empty($_POST['public_contact']),
        'accept_terms'         => !empty($_POST['accept_terms']),
        'accept_privacy'       => !empty($_POST['accept_privacy']),
        'accept_marketing'     => !empty($_POST['accept_marketing']),
        'role_expert'          => isset($_POST['role']['expert']),
        'role_project_manager' => isset($_POST['role']['project_manager']),
        'role_consultant'      => isset($_POST['role']['consultant']),
    ];
    header('Location: ' . BASE_URL . '/01_login/newregister.php');
    exit;
};

if (isset($_POST['register'])) {

  // FIX: verifica token CSRF prima di qualsiasi elaborazione
  csrf_verify();

  // Honeypot: campo nascosto compilato = bot. Si esce come se fosse andata
  // bene, senza creare nulla.
  // P3.19: rate-limit leggero per sessione (l'antispam copre il resto).
  $aow_now = time();
  $_SESSION['rl_reg'] = array_values(array_filter($_SESSION['rl_reg'] ?? [], function ($t) use ($aow_now) { return $t > $aow_now - 1800; }));
  if (count($_SESSION['rl_reg']) >= 5) { $_SESSION['reg_error'] = 'Too many attempts. Please try again later.'; header('Location: newregister.php'); exit; }
  $_SESSION['rl_reg'][] = $aow_now;

  if (trim((string)($_POST['website'] ?? '')) !== '' || aow_is_spam()) {
    header('Location: ' . BASE_URL . '/01_login/register_ok.php');
    exit;
  }

  $username = trim($_POST['username'] ?? '');
  $email  = trim($_POST['email']  ?? '');
  $phone  = trim($_POST['phone']  ?? '');
  $password = $_POST['password']  ?? '';
  $password2 = $_POST['password2'] ?? '';

  // Validazione username: solo lettere, numeri e underscore, 3-20 caratteri
  $isUsernameValid = (bool) filter_var($username, FILTER_VALIDATE_REGEXP, [
    'options' => ['regexp' => '/^[a-z\d_]{3,20}$/i'],
  ]);

  // Validazione email
  $isEmailValid = (bool) filter_var($email, FILTER_VALIDATE_EMAIL);

  $pwdlength = mb_strlen($password);

  if (empty($username) || empty($email) || empty($password)) {
    $reg_fail('Please fill in username, email and password.');
  } elseif (!$isUsernameValid) {
    $reg_fail('Invalid username. Only letters, numbers and underscores, 3 to 20 characters.');
  } elseif (!$isEmailValid) {
    $reg_fail('Please enter a valid email address.');
  } elseif (mb_strlen($email) > 50) {
    $reg_fail('Email must be at most 50 characters.');
  // Il tetto di 20 caratteri escludeva le passphrase e i gestori di password,
  // cioe' proprio le password piu' robuste. Il nuovo limite e' quello tecnico
  // di bcrypt (72 byte), oltre il quale i caratteri verrebbero ignorati.
  } elseif ($pwdlength < 8 || strlen($password) > 72) {
    $reg_fail('Password must be at least 8 characters (maximum 72).');
  } elseif ($password !== $password2) {
    $reg_fail('The two passwords do not match.');
  // Consensi: senza, non c'e' base giuridica per creare l'account.
  } elseif (empty($_POST['accept_terms']) || empty($_POST['accept_privacy'])) {
    $reg_fail('Please accept the terms and conditions and the privacy policy to continue.');
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
    $reg_fail('This username is already taken. Please choose another one.');
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
        $reg_fail($msg);
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
      // Ruoli scelti in registrazione + consenso contatti pubblici (non bloccante)
      try {
          require_once __DIR__ . '/../libs/user_roles.class.php';
          $new_uid = (int)$pdo->lastInsertId();
          $ur_reg  = new UserRoles($pdo);
          $chosen  = (array)($_POST['role'] ?? []);
          foreach (UserRoles::ROLES as $rr) { if (isset($chosen[$rr])) { $ur_reg->addRole($new_uid, $rr); } }
          if (!empty($_POST['public_contact'])) {
              $pdo->prepare('UPDATE users SET public_contact = 1 WHERE id_user = :id')->execute([':id' => $new_uid]);
          }
      } catch (Throwable $e) { /* non bloccare la registrazione */ }

      // Prova del consenso (GDPR art. 7 c.1): stessa tabella gia' usata dal
      // banner cookie, con l'IP solo in forma di hash. Se la scrittura
      // fallisce la registrazione prosegue: l'account e' gia' creato.
      try {
          $consent_cats = json_encode([
              'terms'     => true,
              'privacy'   => true,
              'marketing' => !empty($_POST['accept_marketing']),
              'scope'     => 'registration',
              'user_id'   => (int)($new_uid ?? 0),
          ], JSON_UNESCAPED_UNICODE);
          $pdo->prepare('INSERT INTO consent_log (consent_id, ip_hash, user_agent, categories, consent_version, action)
                         VALUES (UUID(), :ip, :ua, :cats, :ver, :act)')
              ->execute([
                  ':ip'   => hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|aow-salt'),
                  ':ua'   => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                  ':cats' => $consent_cats,
                  ':ver'  => 'reg-1.0',
                  ':act'  => 'grant',
              ]);
      } catch (Throwable $e) {
          error_log('[Allonwheel] registration consent log error: ' . $e->getMessage());
      }

      // Intento dichiarato in registrazione: serve a portare l'utente al passo
      // giusto (pubblica annuncio / profilo azienda) dopo la conferma email.
      $intent_valid = ['buy', 'sell', 'build', 'rent'];
      if (in_array((string)($_POST['intent'] ?? ''), $intent_valid, true)) {
          $_SESSION['aow_intent'] = (string)$_POST['intent'];
      }
      $verification_link = BASE_URL . '/01_login/verify.php?token=' . $email_verification_token;

      // L'email di conferma passa dal Mailer dell'app (SMTP autenticato quando
      // configurato) invece che da mail(): un messaggio inviato senza SMTP
      // dall'IP del server finisce nello spam, e questa e' l'email da cui
      // dipende l'attivazione di ogni account.
      $safe_user = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
      $safe_link = htmlspecialchars($verification_link, ENT_QUOTES, 'UTF-8');
      $body  = '<p>Hi ' . $safe_user . ',</p>'
             . '<p>Thank you for registering on All on Wheel.</p>'
             . '<p>To activate your account, click the link below:</p>'
             . '<p><a href="' . $safe_link . '">Confirm my account</a></p>'
             . '<p>Or copy this address into your browser:<br />' . $safe_link . '</p>'
             . '<p>If you did not request this registration, you can ignore this email.</p>';

      if (Mailer::send($email, 'Confirm your account on All on Wheel', $body, '', $username)) {
        header('Location: ' . BASE_URL . '/01_login/register_ok.php');
      } else {
        // Registrazione avvenuta ma email non inviata
        header('Location: ' . BASE_URL . '/01_login/register_ok_noemail.php');
      }
      exit;
    } else {
      $reg_fail('We could not create your account. Please try again.');
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
      $reg_fail('We could not create your account. Please try again.');
    }
    } // end profile upload check
    } // end username check
  }

  // Rete di sicurezza: se un percorso di errore non e' passato da $reg_fail
  // (che rimanda al modulo), l'utente non deve comunque restare su una pagina
  // bianca senza via d'uscita.
  if (isset($msg)) {
    $reg_fail($msg);
  }
}
?>
