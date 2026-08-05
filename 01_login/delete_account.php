<?php
// ============================================================
// 01_login/delete_account.php — Cancellazione account (GDPR Art. 17)
//
// Flusso:
//   GET  → pagina di conferma con form (password + CSRF).
//   POST → re-autenticazione (password), poi cancellazione completa di
//          tutti i dati personali dell'utente in transazione, infine
//          rimozione dei file immagine dal disco e logout.
//
// Sicurezza:
//   - require_user_logged_in() (solo il proprietario opera sul proprio account)
//   - CSRF one-shot (csrf_verify)
//   - re-autenticazione con password_verify (azione distruttiva)
//   - eliminazione file con basename + realpath (anti path-traversal)
//   - admin_audit_log NON viene toccato (log di sicurezza, Art. 17(3)(b))
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';

$user_id = require_user_logged_in();

// Helper: cancellazione file sicura (basename + realpath dentro la base)
$deleteFile = static function (string $dir, ?string $filename): void {
    $filename = basename((string)$filename);
    if ($filename === '' || $filename === 'no_image.jpg') {
        return;
    }
    $full = realpath($dir . $filename);
    $base = realpath($dir);
    if ($full === false || $base === false) {
        return;
    }
    if (strpos($full, $base . DIRECTORY_SEPARATOR) !== 0) {
        error_log('[Allonwheel] delete_account: path traversal blocked: ' . $filename);
        return;
    }
    if (is_file($full)) {
        @unlink($full);
    }
};

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $password = (string)($_POST['password'] ?? '');

    // Re-autenticazione: rileggi hash + email + tier dal DB
    $stmt = $pdo->prepare('SELECT email, password, user_tier FROM users WHERE id_user = :id LIMIT 1');
    $stmt->execute([':id' => $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !password_verify($password, $row['password'])) {
        $error = 'Incorrect password. Account not deleted.';
    } elseif (($row['user_tier'] ?? '') === 'admin') {
        // Gli account admin sono referenziati da admin_audit_log (log di
        // sicurezza, conservabile ex Art. 17(3)(b) GDPR) e non possono essere
        // auto-cancellati senza compromettere l'integrita' dell'audit trail.
        $error = 'For audit-integrity reasons, administrator accounts cannot be self-deleted. Please contact another administrator.';
    } else {
        $email = (string)$row['email'];
        $docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');

        // 1) Raccogli i nomi file PRIMA di cancellare le righe
        $files = []; // [ [dir, filename], ... ]

        $collect = static function (PDO $pdo, string $sql, array $bind, string $dirOrig, string $dirThumb, array &$files): void {
            $st = $pdo->prepare($sql);
            $st->execute($bind);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                if (!empty($r['image_original']))  { $files[] = [$dirOrig,  $r['image_original']]; }
                if (!empty($r['image_thumbnail'])) { $files[] = [$dirThumb, $r['image_thumbnail']]; }
            }
        };

        $base02 = $docroot . '/upload_image/02_free_ads/';
        $base03 = $docroot . '/upload_image/03_ads/';
        $base06 = $docroot . '/upload_image/06_company/';

        // Free ads (principali) + gallery
        $collect($pdo, 'SELECT image_original, image_thumbnail FROM `02_free_ads` WHERE id_user = ?', [$user_id], $base02.'original/', $base02.'thumbnail/', $files);
        $collect($pdo, 'SELECT g.image_original, g.image_thumbnail FROM `02_free_ads_gallery` g JOIN `02_free_ads` a ON a.id_ads = g.id_ads WHERE a.id_user = ?', [$user_id], $base02.'original/', $base02.'thumbnail/', $files);

        // Premium ads (principali) + gallery
        $collect($pdo, 'SELECT image_original, image_thumbnail FROM `03_ads` WHERE id_user = ?', [$user_id], $base03.'original/', $base03.'thumbnail/', $files);
        $collect($pdo, 'SELECT g.image_original, g.image_thumbnail FROM `03_ads_gallery` g JOIN `03_ads` a ON a.id_ads = g.id_ads WHERE a.id_user = ?', [$user_id], $base03.'original/', $base03.'thumbnail/', $files);

        // Company: logo + gallery (immagine)
        $stc = $pdo->prepare('SELECT id, logo FROM `06_company` WHERE user_id = ?');
        $stc->execute([$user_id]);
        foreach ($stc->fetchAll(PDO::FETCH_ASSOC) as $c) {
            if (!empty($c['logo'])) {
                $files[] = [$base06.'original/',  $c['logo']];
                $files[] = [$base06.'thumbnail/', $c['logo']];
            }
        }
        $stg = $pdo->prepare('SELECT immagine FROM `06_company_gallery` WHERE user_id = ?');
        $stg->execute([$user_id]);
        foreach ($stg->fetchAll(PDO::FETCH_ASSOC) as $g) {
            if (!empty($g['immagine'])) {
                $files[] = [$base06.'original/',  $g['immagine']];
                $files[] = [$base06.'thumbnail/', $g['immagine']];
            }
        }

        // 2) Cancellazione DB in transazione (figli → padri → utente)
        try {
            $pdo->beginTransaction();

            // Free ads
            $pdo->prepare('DELETE g FROM `02_free_ads_gallery` g JOIN `02_free_ads` a ON a.id_ads = g.id_ads WHERE a.id_user = ?')->execute([$user_id]);
            $pdo->prepare('DELETE FROM `02_free_ads` WHERE id_user = ?')->execute([$user_id]);

            // Premium ads
            $pdo->prepare('DELETE g FROM `03_ads_gallery` g JOIN `03_ads` a ON a.id_ads = g.id_ads WHERE a.id_user = ?')->execute([$user_id]);
            $pdo->prepare('DELETE t FROM `03_ads_tech_details` t JOIN `03_ads` a ON a.id_ads = t.id_ads WHERE a.id_user = ?')->execute([$user_id]);
            $pdo->prepare('DELETE FROM `03_ads` WHERE id_user = ?')->execute([$user_id]);

            // Company (figli per company_id, poi azienda)
            $pdo->prepare('DELETE p FROM `06_company_products` p JOIN `06_company` c ON c.id = p.company_id WHERE c.user_id = ?')->execute([$user_id]);
            $pdo->prepare('DELETE s FROM `06_company_services` s JOIN `06_company` c ON c.id = s.company_id WHERE c.user_id = ?')->execute([$user_id]);
            $pdo->prepare('DELETE FROM `06_company_gallery` WHERE user_id = ?')->execute([$user_id]);
            $pdo->prepare('DELETE FROM `06_company` WHERE user_id = ?')->execute([$user_id]);

            // Log accessi (per email)
            $pdo->prepare('DELETE FROM login_attempts WHERE email = ?')->execute([$email]);

            // Account
            $pdo->prepare('DELETE FROM `users` WHERE id_user = ?')->execute([$user_id]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('[Allonwheel] delete_account error (user=' . $user_id . '): ' . $e->getMessage());
            $error = 'A database error occurred. Your account was NOT deleted. Please try again later.';
        }

        // 3) Se il DB è andato a buon fine, elimina i file e fai logout
        if ($error === '') {
            foreach ($files as [$dir, $name]) {
                $deleteFile($dir, $name);
            }
            logout_user();
            header('Location: ' . BASE_URL . '/index.php?account=deleted');
            exit;
        }
    }
}

$csrf_field = csrf_generate();
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Delete account</title>
<meta name="robots" content="noindex, nofollow" />
<meta name="language" content="en" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="../js/jquery.min.js" defer></script>
<script type="text/javascript" src="../js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="../js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper">

  <div id="templatemo_header">
    <?php include __DIR__ . '/../header.php'; ?>
  </div>

  <div id="content_top">
    <div id="page_title">Delete account</div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="templatemo_content">
    <div class="post_box">
      <h2>Delete your account permanently</h2>

      <?php if ($error !== ''): ?>
        <p class="error-msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
      <?php endif; ?>

      <p>This will permanently delete your account and <strong>all your data</strong>:
         profile, free and premium ads (including galleries and technical details),
         company profile and uploaded images. <strong>This action cannot be undone.</strong></p>
      <p>To confirm, please enter your password.</p>

      <div id="contact_form">
      <form action="delete_account.php" method="post" >
        <?php echo $csrf_field; ?>
        <div class="form_row">
          <label for="password"><strong>Password:</strong></label>
          <input type="password" name="password" id="password" class="input_field" required autocomplete="current-password" />
        </div>
        <div class="form_row">
          <p>
            <input type="submit" name="delete_account" value="Delete my account" class="submit_btn float_r" />
            <a href="all_about_me.php" class="more float_l">Cancel</a>
          </p>
        </div>
        <div class="cleaner"></div>
      </form>
      </div>
    </div>
  </div>

  <div id="templatemo_sidebar">
    <?php include __DIR__ . '/../include_sidebar.php'; ?>
  </div>

  <div class="cleaner"></div>
  <?php include __DIR__ . '/../footer.php'; ?>

</div>
</body>
</html>
