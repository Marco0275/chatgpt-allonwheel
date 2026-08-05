<?php
// 03_ads/03_documents.php — Gestione documenti tecnici dell'annuncio.
// Solo il PROPRIETARIO dell'annuncio (anti-IDOR). CSRF + upload sicuro.
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/ads_documents.class.php';

$id_user  = require_user_logged_in();

require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/plan_policy.class.php';
if (!PlanPolicy::canDocuments(UserTier::getTier($pdo, $id_user))) {
    $_SESSION['error_message'] = 'PDF documents are available on Premium and Gold plans.';
    header('Location: ' . BASE_URL . '/01_login/my_posts.php');
    exit;
}
$id_ads   = (int)($_GET['id_ads'] ?? $_POST['id_ads'] ?? 0);
$ad_table = (string)($_GET['ad_table'] ?? $_POST['ad_table'] ?? '03_ads');
if (!AdsDocuments::isTable($ad_table)) { $ad_table = '03_ads'; }
// Rev. 7 lug 2026: i documenti tecnici sono una feature dei soli annunci PREMIUM.
if ($ad_table !== '03_ads') { http_response_code(403); exit('Technical documents are available on premium ads only.'); }

$docs = new AdsDocuments($pdo);
// Ownership: l'annuncio deve appartenere all'utente loggato
if ($id_ads <= 0 || !$docs->ownsAd($id_ads, $ad_table, $id_user)) {
    header('Location: /01_login/my_posts.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string)($_POST['action'] ?? '');
    $notice = '';
    if ($action === 'upload') {
        $type = (string)($_POST['document_type'] ?? 'other');
        if (!AdsDocuments::isType($type)) { $type = 'other'; }
        $res = UploadSecurity::storeDocument($_FILES['document'] ?? [], AdsDocuments::storageDir());
        if (!empty($res['ok'])) {
            $docs->add($id_ads, $ad_table, $type, $res['stored_name'], $res['original_name'],
                       $res['mime'], $res['size'], $id_user);
            $notice = 'Document uploaded.';
        } else {
            $notice = (string)($res['error'] ?? 'Upload failed.');
        }
    } elseif ($action === 'delete') {
        $doc_id = (int)($_POST['doc_id'] ?? 0);
        $fname  = $docs->deleteOwned($doc_id, $id_user);
        if ($fname !== null) {
            $full = AdsDocuments::storageDir() . $fname;
            if (is_file($full)) { @unlink($full); } // coerente con i delete esistenti
            $notice = 'Document deleted.';
        } else {
            $notice = 'Document not found.';
        }
    }
    $_SESSION['aow_doc_notice'] = $notice; // PRG
    header('Location: /03_ads/03_documents.php?id_ads=' . $id_ads . '&ad_table=' . urlencode($ad_table));
    exit;
}

$notice = (string)($_SESSION['aow_doc_notice'] ?? '');
unset($_SESSION['aow_doc_notice']);
$list = $docs->listByAd($id_ads, $ad_table);
csrf_generate();
$csrf = $_SESSION['csrf_token'] ?? '';
$e = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
$type_labels = [
    'technical_sheet' => 'Technical sheet', 'floorplan' => 'Floor plan',
    'certificate' => 'Certificate', 'manual' => 'Manual', 'other' => 'Other',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Technical documents</title>
<meta name="robots" content="noindex, follow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../favicon.png" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header">
    <?php include('../header.php'); ?>
  </div>
  <div id="content_top">
    <div id="page_title">Technical documents</div>
    <div class="cleaner"></div>
  </div>
  <div id="main"></div><div id="no_sidebar">
    <div class="post_box">
      <h2>Technical documents</h2>
      <?php if ($notice !== ''): ?><p><em><?php echo $e($notice); ?></em></p><?php endif; ?>
      <p>Upload technical sheets, floor plans or certificates for this listing.
         Buyers download them through a tracked link. Allowed: PDF, JPG, PNG, WEBP (max 15 MB).</p>

      <form method="post" action="03_documents.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo $e($csrf); ?>" />
        <input type="hidden" name="id_ads" value="<?php echo (int)$id_ads; ?>" />
        <input type="hidden" name="ad_table" value="<?php echo $e($ad_table); ?>" />
        <input type="hidden" name="action" value="upload" />
        <p>
          <label>Type:
            <select name="document_type">
              <?php foreach ($type_labels as $val => $lab): ?>
                <option value="<?php echo $e($val); ?>"><?php echo $e($lab); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </p>
        <p><input type="file" name="document" /></p>
        <div class="post_meta"><button type="submit" value="Upload document" class="more float_r">Upload document</button></div>
        <div class="cleaner"></div>
      </form>
<div class="cleaner h20"></div>
      <h3>Uploaded documents (<?php echo count($list); ?>)</h3>
      <?php if ($list): ?>
        <ul>
          <?php foreach ($list as $d): ?>
            <li>
              <a href="../download_doc.php?id=<?php echo (int)$d['id']; ?>"><?php echo $e($d['original_name']); ?></a>
              &mdash; <?php echo $e($type_labels[$d['document_type']] ?? $d['document_type']); ?>
              <form method="post" action="03_documents.php" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?php echo $e($csrf); ?>" />
                <input type="hidden" name="id_ads" value="<?php echo (int)$id_ads; ?>" />
                <input type="hidden" name="ad_table" value="<?php echo $e($ad_table); ?>" />
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="doc_id" value="<?php echo (int)$d['id']; ?>" />
                <button type="submit" value="Delete" class="more">Delete</button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p><em>No documents yet.</em></p>
      <?php endif; ?>

      <div class="cleaner h10"></div>
      <div><a class="more float_r" href="/01_login/my_posts.php">Back to my posts</a></div>
      <div class="cleaner"></div>
    </div>
  </div><!-- end no_sidebar -->
  <?php include('../footer.php'); ?>
</div>
</body>
</html>
