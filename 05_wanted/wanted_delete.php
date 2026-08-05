<?php
// 05_wanted/wanted_delete.php — Elimina una propria richiesta "Wanted" (da my_posts).
// CSRF + ownership; ritorna a my_posts.
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/wanted_ads.class.php';

$id_user = require_user_logged_in();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) { (new WantedAds($pdo))->deleteOwned($id, $id_user); }
}
header('Location: /01_login/my_posts.php');
exit;
