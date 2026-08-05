<?php
// 07_rent/07_11_rent_save.php -- Salva un annuncio di noleggio.
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/rent.class.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';
require_once __DIR__ . '/../libs/upload_helper.class.php';

$id_user = require_user_logged_in();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /07_rent/07_10_rent_post.php'); exit; }
csrf_verify();

$title = trim($_POST['title'] ?? '');
$vtype = trim($_POST['vehicle_type'] ?? '');
$descr = trim($_POST['description'] ?? '');
$valid_types = array_column(VehicleTaxonomy::typesForCategory('special', $pdo), 'slug');

if ($title === '' || $descr === '' || !in_array($vtype, $valid_types, true)) {
    $_SESSION['error_message'] = 'Please fill in title, description and a valid special vehicle type.';
    header('Location: /07_rent/07_10_rent_post.php'); exit;
}

// Dati contatto dall'utente loggato
$ust = $pdo->prepare('SELECT username, email FROM users WHERE id_user = :u LIMIT 1');
$ust->execute([':u' => $id_user]);
$u = $ust->fetch(PDO::FETCH_ASSOC) ?: ['username' => '', 'email' => ''];

// Upload foto (opzionale) -> /upload_image/07_rent/original|thumbnail/
$img_o = 'no_image.jpg'; $img_t = 'no_image.jpg';
if (isset($_FILES['rent_image']) && $_FILES['rent_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $res = UploadHelper::handleImageUpload($_FILES['rent_image'], [
        'target_dir_original'  => '/upload_image/07_rent/original/',
        'target_dir_thumbnail' => '/upload_image/07_rent/thumbnail/',
        'thumb_width' => 400, 'thumb_height' => 300, 'thumb_crop' => true,
        'max_size_bytes' => 5 * 1024 * 1024, 'filename_prefix' => 'rent_' . $id_user,
    ]);
    if (!empty($res['ok'])) { $img_o = $res['filename']; $img_t = $res['filename']; }
}

$item_kind = ($_POST['item_kind'] ?? 'vehicle') === 'shelter_container' ? 'shelter_container' : 'vehicle';
$status = (defined('AOW_MODERATION_REQUIRED') && AOW_MODERATION_REQUIRED) ? 'pending' : 'approved';

$rent = new RentAds($pdo);
$new_id = $rent->createListing([
    'id_user' => $id_user, 'status' => $status,
    'author' => (string)$u['username'], 'email' => (string)$u['email'],
    'phone' => trim($_POST['phone'] ?? ''),
    'title' => $title, 'subtitle' => (trim($_POST['subtitle'] ?? '') ?: null),
    'list_price' => (float)str_replace(',', '.', (string)($_POST['list_price'] ?? 0)),
    'conditions' => (string)($_POST['conditions'] ?? 'As good as new'),
    'image_original' => $img_o, 'image_thumbnail' => $img_t,
    'description' => $descr,
    'expires_at' => date('Y-m-d H:i:s', strtotime('+90 days')),
    'item_kind' => $item_kind, 'vehicle_type' => $vtype, 'product_macro' => null,
]);

$_SESSION['success_message'] = 'Rental listing published.';
header('Location: /07_rent/07_21_rent_view.php?id=' . (int)$new_id);
exit;
