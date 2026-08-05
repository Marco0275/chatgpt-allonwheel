<?php
// ============================================================
// 03_ads/03_01_upload_advertising_modified.php (Phase 4)
// Handler POST per il salvataggio della modifica annuncio premium.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';

$id_user = require_user_logged_in();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . BASE_URL . '/01_login/my_posts.php');
  exit;
}

csrf_verify_persistent();

$id_ads = isset($_POST['id_ads']) ? (int)$_POST['id_ads'] : 0;
if ($id_ads <= 0) {
  header('Location: ' . BASE_URL . '/01_login/my_posts.php');
  exit;
}

// OWNERSHIP CHECK
$stmt = $pdo->prepare(
  'SELECT id_ads FROM `03_ads`
  WHERE id_ads = :id_ads AND id_user = :id_user
  LIMIT 1'
);
$stmt->execute([':id_ads' => $id_ads, ':id_user' => $id_user]);
if (!$stmt->fetch()) {
  $_SESSION['error_message'] = 'Ad not found or access denied.';
  header('Location: ' . BASE_URL . '/01_login/my_posts.php');
  exit;
}

$title   = trim($_POST['title']   ?? '');
$subtitle  = trim($_POST['subtitle']  ?? '');
$list_price  = filter_var($_POST['list_price'] ?? 0, FILTER_VALIDATE_FLOAT);
$type    = trim($_POST['type']    ?? '');
$conditions  = trim($_POST['conditions']  ?? '');
// Rev. 7 lug: modifica integrale — vehicle type + famiglia (whitelist dal DB)
// Classificazione: RISPECCHIA 03_01_upload_advertising.php (22 lug 2026).
// item_kind/macro_category dai campi hidden (valori dell'annuncio); il tipo
// deve appartenere alla macro (isValidType). La famiglia e' DERIVATA piu'
// sotto con ProductMacro::forAd(), non presa dal POST.
require_once __DIR__ . '/../libs/product_macro.class.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';
require_once __DIR__ . '/../libs/ad_section_fields.class.php';

$item_kind = ($_POST['item_kind'] ?? '') === VehicleTaxonomy::KIND_SHELTER
  ? VehicleTaxonomy::KIND_SHELTER : VehicleTaxonomy::KIND_VEHICLE;
$macro_category = ($_POST['macro_category'] ?? '') === VehicleTaxonomy::MACRO_ROAD
  ? VehicleTaxonomy::MACRO_ROAD : VehicleTaxonomy::MACRO_SPECIAL;
$vehicle_type = trim((string)($_POST['vehicle_type'] ?? ''));

if ($item_kind === VehicleTaxonomy::KIND_SHELTER) {
  $macro_category = VehicleTaxonomy::MACRO_SPECIAL;
  $vehicle_type   = VehicleTaxonomy::SHELTER_SLUG;
}
if ($vehicle_type === '' || ($item_kind !== VehicleTaxonomy::KIND_SHELTER
    && !VehicleTaxonomy::isValidType($vehicle_type, $macro_category, $pdo))) {
  $_SESSION['error_message'] = 'Please choose a valid vehicle type and family.';
  header('Location: 03_modify_insert_ad.php?id_ads=' . $id_ads);
  exit;
}

$description = trim($_POST['description'] ?? '');

if ($title === '' || $description === '' || $list_price === false) {
  $_SESSION['error_message'] = 'Please fill in all required fields with valid data.';
  header('Location: 03_modify_insert_ad.php?id_ads=' . $id_ads);
  exit;
}

$allowed_types  = ['New on sell', 'Used on sell', 'For rent', 'Project'];
$allowed_conditions = ['New', 'As good as new', 'Used', 'Poor', 'Project'];
if (!in_array($type, $allowed_types, true))     $type = 'New on sell';
if (!in_array($conditions, $allowed_conditions, true)) $conditions = 'New';

$checkbox_fields = ['racing', 'promotion', 'horse', 'hospitality', 'medical',
        'military', 'motorhome', 'technology', 'street_food'];
$checkboxValues = [];
foreach ($checkbox_fields as $chk) {
  $checkboxValues[$chk] = isset($_POST[$chk]) ? 1 : 0;
}

// Misure in METRI (23 lug 2026). La vecchia $aow_dim era scritta per i
// CENTIMETRI INTERI: ctype_digit() e' falso su "12.5"/"2.45"/"0.9", quindi
// ogni misura con decimali finiva a NULL IN SILENZIO (nessun errore a video,
// campo vuoto nel DB). Ora si accettano i decimali, coerenti con la colonna
// decimal(6,2), e anche la virgola italiana ("12,5").
$aow_mt = static function (string $key) {
  $raw = str_replace(',', '.', trim((string)($_POST[$key] ?? '')));
  if ($raw === '' || !is_numeric($raw)) { return null; }
  $n = round((float)$raw, 2);
  return ($n > 0 && $n <= 9999.99) ? number_format($n, 2, '.', '') : null;
};
// axles_n resta un CONTEGGIO intero: qui ctype_digit e' corretto.
$aow_dim = static function (string $key, int $max) {
  $raw = trim((string)($_POST[$key] ?? ''));
  if ($raw === '' || !ctype_digit($raw)) { return null; }
  $n = (int)$raw;
  return ($n >= 0 && $n <= $max) ? $n : null;
};
$length_mt = $aow_mt('length_mt');
$width_mt  = $aow_mt('width_mt');
$height_mt = $aow_mt('height_mt');
$axles_n   = $aow_dim('axles_n',   20);

// Coerenza di SEZIONE (23 lug 2026): la sezione si ricava dalla
// classificazione dell'annuncio (item_kind/macro_category, gia' validati
// sopra), non da cio' che arriva nel POST. Se la sezione non prevede un
// campo, quel campo NON viene salvato: uno shelter/container e' una struttura
// statica e non ha assi, quindi axles_n resta NULL anche se qualcuno
// forzasse il parametro.
$aow_section = AdSectionFields::sectionOf([
  'item_kind'      => $item_kind,
  'macro_category' => $macro_category,
]);
if (!AdSectionFields::hasAxles($aow_section)) { $axles_n = null; }

// Famiglia DERIVATA come nell'inserimento.
$product_macro = ProductMacro::forAd([
  'item_kind'    => $item_kind,
  'vehicle_type' => $vehicle_type,
  'type'         => $type,
  'conditions'   => $conditions,
  'racing'       => $checkboxValues['racing'],
  'hospitality'  => $checkboxValues['hospitality'],
  'medical'      => $checkboxValues['medical'],
]);

$sql = 'UPDATE `03_ads` SET
  title = :title,
  subtitle = :subtitle,
  list_price = :list_price,
  type = :type,
  conditions = :conditions,
  vehicle_type = :vehicle_type,
  product_macro = :product_macro,
  description = :description,
  item_kind = :item_kind, macro_category = :macro_category,
  length_mt = :length_mt, width_mt = :width_mt, height_mt = :height_mt, axles_n = :axles_n
  WHERE id_ads = :id_ads AND id_user = :id_user';

try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':title'   => $title,
    ':subtitle'  => $subtitle,
    ':list_price'  => $list_price,
    ':type'    => $type,
    ':conditions'  => $conditions,
    ':vehicle_type'  => $vehicle_type,
    ':product_macro' => $product_macro,
    ':description' => $description,
    ':item_kind'      => $item_kind,
    ':macro_category' => $macro_category,
    ':length_mt' => $length_mt,
    ':width_mt'  => $width_mt,
    ':height_mt' => $height_mt,
    ':axles_n'   => $axles_n,
    ':id_ads'  => $id_ads,
    ':id_user'   => $id_user,
  ]);
} catch (PDOException $e) {
  error_log('[Allonwheel] Modify premium ad error: ' . $e->getMessage());
  $_SESSION['error_message'] = 'Database error while saving changes.';
  header('Location: 03_modify_insert_ad.php?id_ads=' . $id_ads);
  exit;
}


// Rev. 7 lug: sostituzione opzionale dell'immagine principale (stesso helper dell'insert)
if (!empty($_FILES['ad_image']) && ($_FILES['ad_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
  require_once __DIR__ . '/../libs/upload_helper.class.php';
  $aow_old = null;
  try {
    $aow_st = $pdo->prepare('SELECT image_original, image_thumbnail FROM `03_ads` WHERE id_ads = :a AND id_user = :u LIMIT 1');
    $aow_st->execute([':a' => $id_ads, ':u' => $id_user]);
    $aow_old = $aow_st->fetch(PDO::FETCH_ASSOC) ?: null;
  } catch (Throwable $e) {}
  $aow_res = UploadHelper::handleImageUpload($_FILES['ad_image'], [
    'target_dir_original'  => '/upload_image/03_ads/original/',
    'target_dir_thumbnail' => '/upload_image/03_ads/thumbnail/',
    'thumb_width'    => 220,
    'thumb_height'   => 150,
    'thumb_crop'     => false,
    'max_size_bytes'   => 5 * 1024 * 1024,
    'filename_prefix'  => 'ad_' . $id_ads,
  ]);
  if (!$aow_res['ok']) {
    $_SESSION['error_message'] = $aow_res['error'];
    header('Location: 03_modify_insert_ad.php?id_ads=' . $id_ads);
    exit;
  }
  // cleanup vecchi file (path-safe, stesso pattern dell'insert)
  if ($aow_old) {
    $aow_root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . '/upload_image/03_ads/';
    foreach (['original', 'thumbnail'] as $aow_sub) {
      $aow_o = $aow_old['image_' . ($aow_sub === 'original' ? 'original' : 'thumbnail')] ?? '';
      if ($aow_o && $aow_o !== 'no_image.jpg' && $aow_o !== $aow_res['filename']) {
        $aow_cand = realpath($aow_root . $aow_sub . '/' . basename($aow_o));
        $aow_base = realpath($aow_root . $aow_sub);
        if ($aow_cand && $aow_base && strpos($aow_cand, $aow_base . DIRECTORY_SEPARATOR) === 0) { @unlink($aow_cand); }
      }
    }
  }
  try {
    $pdo->prepare('UPDATE `03_ads` SET image_original = :o, image_thumbnail = :t WHERE id_ads = :a AND id_user = :u')
        ->execute([':o' => $aow_res['filename'], ':t' => $aow_res['filename'], ':a' => $id_ads, ':u' => $id_user]);
  } catch (Throwable $e) {
    error_log('[Allonwheel] modify main image update error: ' . $e->getMessage());
  }
}

csrf_rotate_persistent();

$_SESSION['success_message'] = 'Premium ad updated successfully.';
header('Location: ' . BASE_URL . '/01_login/my_posts.php');
exit;
