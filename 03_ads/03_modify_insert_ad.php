<?php
// ============================================================
// 03_ads/03_modify_insert_ad.php
// Smistatore: manda l'annuncio alla pagina di modifica della SUA sezione.
//
// 23 lug 2026. La modifica e' divisa per sezione (03_modify_road.php,
// 03_modify_special.php, 03_modify_shelter.php) perche' ogni sezione ha
// variabili diverse. I link gia' in giro per il sito (es. 01_login/my_posts.php)
// puntano pero' a questo nome storico: invece di lasciarli rotti, qui si legge
// la classificazione dell'annuncio e si reindirizza al file giusto.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/ad_section_fields.class.php';

$user_id = require_user_logged_in();
$id_ads  = isset($_GET['id_ads']) ? (int)$_GET['id_ads'] : 0;

if ($id_ads <= 0) {
    $_SESSION['error_message'] = 'Missing ad id.';
    header('Location: ../01_login/my_posts.php');
    exit;
}

// Solo un proprio annuncio (dir. 12): la classificazione si legge dal DB,
// non da parametri in URL.
try {
    $st = $pdo->prepare('SELECT item_kind, macro_category FROM `03_ads` WHERE id_ads = :id AND id_user = :u LIMIT 1');
    $st->execute([':id' => $id_ads, ':u' => $user_id]);
    $ad = $st->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[Allonwheel] 03_modify dispatcher: ' . $e->getMessage());
    $ad = null;
}

if (!$ad) {
    $_SESSION['error_message'] = 'Ad not found, or it is not yours.';
    header('Location: ../01_login/my_posts.php');
    exit;
}

$section = AdSectionFields::sectionOf($ad);
header('Location: 03_modify_' . $section . '.php?id_ads=' . $id_ads);
exit;
