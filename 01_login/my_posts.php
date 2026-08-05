<?php
/**
 * 01_login/my_posts.php (Phase 3)
 * Riepilogo di tutti i post dell'utente loggato + gestione tier.
 *
 * NOVITA' Phase 3:
 *  - Mostra il tier corrente dell'utente (free / premium / admin)
 *  - Mostra il consumo attuale: "X / 5 free ads" e "Y / 15 premium ads"
 *  - Pulsante "Request premium upgrade" per gli utenti free
 *  - I link "New Free Ad" / "New Premium Ad" sono mostrati solo se
 *  l'utente non ha raggiunto il limite (UserTier::canInsertXxxAd)
 *  - Se l'utente e' admin, link diretto al pannello /_admin/dashboard.php
 *
 * Conservato da Phase 1:
 *  - Bug fix $ad/$post nel template thumbnail
 *  - Pirobox URL corrette per tipo
 *  - Nessun <style> inline
 *  - Helper di sessione centralizzato
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/06_company.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';

$user_id = require_user_logged_in();

csrf_generate();
$csrf_token = $_SESSION['csrf_token'] ?? '';

// =================================================================
// TIER E QUOTE
// =================================================================
$tier     = UserTier::getTier($pdo, $user_id);
$can_free     = UserTier::canInsertFreeAd($pdo, $user_id);
$can_premium    = UserTier::canInsertPremiumAd($pdo, $user_id);
$has_pending_premium= UserTier::hasPendingPremiumRequest($pdo, $user_id);

$is_free  = ($tier === UserTier::TIER_FREE);
$is_unlimited = UserTier::isUnlimitedUser($pdo, $user_id);
$is_premium = ($tier === UserTier::TIER_PREMIUM);
$is_admin = ($tier === UserTier::TIER_ADMIN);

// =================================================================
// RACCOLTA POST DA TUTTE LE TABELLE (immutato da Phase 1)
// =================================================================
$all_posts = [];

$stmt = $pdo->prepare(
  "SELECT a.id_ads, a.title, a.subtitle, a.description, a.created_at, a.expires_at,
    a.image_original, a.image_thumbnail,
    (SELECT COUNT(*) FROM `02_free_ads_gallery` g WHERE g.id_ads = a.id_ads) AS gallery_count
   FROM `02_free_ads` a
  WHERE a.id_user = ?
  ORDER BY a.created_at DESC"
);
if ($stmt) {
  $stmt->execute([$user_id]);
  $result = $stmt;
  while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $all_posts[] = [
    'type'     => 'free_ad',
    'type_label'   => 'Free Ad',
    'id'     => (int)$row['id_ads'],
    'title'    => $row['title'],
    'subtitle'   => $row['subtitle'],
    'description'  => $row['description'],
    'image_original' => $row['image_original'],
    'image_thumb'  => $row['image_thumbnail'],
    'created_at'   => $row['created_at'],
    'expires_at'   => $row['expires_at'],
    'gallery'    => (int)$row['gallery_count'],
    'image_dir'  => '/upload_image/02_free_ads/',
    'view_url'   => '../02_free_ads/02_view_ad.php?id_ads=' . (int)$row['id_ads'],
    'edit_url'   => '../02_free_ads/02_modify_insert_ad.php?id_ads=' . (int)$row['id_ads'],
    'delete_url'   => '../02_free_ads/02_delete_ad.php',
    'delete_field' => 'ad_id',
    ];
  }
}

$stmt = $pdo->prepare(
  "SELECT a.id_ads, a.title, a.subtitle, a.description, a.created_at, a.expires_at,
    a.image_original, a.image_thumbnail,
    (SELECT COUNT(*) FROM `03_ads_gallery` g WHERE g.id_ads = a.id_ads) AS gallery_count
   FROM `03_ads` a
  WHERE a.id_user = ?
  ORDER BY a.created_at DESC"
);
if ($stmt) {
  $stmt->execute([$user_id]);
  $result = $stmt;
  while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $all_posts[] = [
    'type'     => 'premium_ad',
    'type_label'   => 'Premium Ad',
    'id'     => (int)$row['id_ads'],
    'title'    => $row['title'],
    'subtitle'   => $row['subtitle'],
    'description'  => $row['description'],
    'image_original' => $row['image_original'],
    'image_thumb'  => $row['image_thumbnail'],
    'created_at'   => $row['created_at'],
    'expires_at'   => $row['expires_at'],
    'gallery'    => (int)$row['gallery_count'],
    'image_dir'  => '/upload_image/03_ads/',
    'view_url'   => '../03_ads/03_view_ad.php?id_ads=' . (int)$row['id_ads'],
    'edit_url'   => '../03_ads/03_modify_insert_ad.php?id_ads=' . (int)$row['id_ads'],
    'delete_url'   => '../03_ads/03_delete_ad.php',
    'delete_field' => 'ad_id',
    ];
  }
}

$stmt = $pdo->prepare(
  "SELECT c.id, c.ragione_sociale, c.descrizione, c.data_inserimento, c.logo,
    c.citta, c.provincia, c.email, c.attiva,
    (SELECT COUNT(*) FROM `06_company_gallery` g WHERE g.company_id = c.id) AS gallery_count,
    (SELECT COUNT(*) FROM `06_company_products` p WHERE p.company_id = c.id) AS products_count,
    (SELECT COUNT(*) FROM `06_company_services` s WHERE s.company_id = c.id) AS services_count
   FROM `06_company` c
  WHERE c.user_id = ?"
);
if ($stmt) {
  $stmt->execute([$user_id]);
  $result = $stmt;
  while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $location_parts = [];
    if (!empty($row['citta'])) {
    $location_parts[] = $row['citta'] . (!empty($row['provincia']) ? ' (' . $row['provincia'] . ')' : '');
    }
    if (!empty($row['email'])) {
    $location_parts[] = $row['email'];
    }
    $description = !empty($location_parts)
    ? implode(' — ', $location_parts)
    : (string)($row['descrizione'] ?? '');

    $all_posts[] = [
    'type'     => 'company',
    'type_label'   => 'Company',
    'id'     => (int)$row['id'],
    'title'    => $row['ragione_sociale'],
    'subtitle'   => null,
    'description'  => $description,
    'image_original' => $row['logo'],
    'image_thumb'  => $row['logo'],
    'created_at'   => $row['data_inserimento'],
    'gallery'    => (int)$row['gallery_count'],
    'image_dir'  => '/upload_image/06_company/',
    'extra'    => [
      'products' => (int)$row['products_count'],
      'services' => (int)$row['services_count'],
      'attiva' => (int)$row['attiva'],
    ],
    'view_url'   => '../06_company/06_02_view_company.php?id=' . (int)$row['id'],
    'edit_url'   => '../06_company/06_20_modify_company.php',
    'products_url' => '../06_company/06_12_company_products.php',
    'gallery_url'  => '../06_company/06_14_company_gallery.php',
    'delete_url'   => '../06_company/06_40_delete_company.php',
    'delete_field' => 'company_id',
    ];
  }
}

// Filtro tipo
// Wanted requests (richieste inverse) — dir.3: la vista aggregata include anche questi.
$wst = $pdo->prepare(
  "SELECT id, title, description, created_at, status
     FROM `wanted_ads` WHERE id_user = :u ORDER BY created_at DESC"
);
$wst->execute([':u' => $user_id]);
foreach ($wst->fetchAll(PDO::FETCH_ASSOC) as $row) {
  $all_posts[] = [
    'type'           => 'wanted',
    'type_label'     => 'Wanted',
    'id'             => (int)$row['id'],
    'title'          => $row['title'],
    'subtitle'       => null,
    'description'    => $row['description'],
    'image_original' => '',
    'image_thumb'    => '',
    'created_at'     => $row['created_at'],
    'gallery'        => 0,
    'image_dir'      => '/upload_image/',
    'view_url'       => '../05_wanted/wanted_view.php?id=' . (int)$row['id'],
    'edit_url'       => '../05_wanted/wanted_manage.php',
    'delete_url'     => '../05_wanted/wanted_delete.php',
    'delete_field'   => 'id',
  ];
}

$types_count = [];
foreach ($all_posts as $p) {
  $types_count[$p['type']] = ($types_count[$p['type']] ?? 0) + 1;
}
$type_labels = [
  'free_ad'  => 'Free Ads',
  'premium_ad' => 'Premium Ads',
  'company'  => 'Company',
  'wanted'   => 'Wanted',
];
$filter_type = isset($_GET['type']) && in_array($_GET['type'], ['all', 'free_ad', 'premium_ad', 'company', 'wanted'], true)
  ? $_GET['type']
  : 'all';

$cm = new CompanyManager($pdo);
$existing_company_id = $cm->userHasCompany($user_id);
$has_company = ($existing_company_id !== false && $existing_company_id !== 0);
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - My Posts</title>
<meta name="keywords" content="All on Wheel - My Posts" />
<meta name="description" content="All on Wheel - My Posts" />
<meta name="robots" content="index, follow" />
<meta name="language" content="en" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<link href="../css_pirobox/white/style.css" media="screen" rel="stylesheet" type="text/css" />
<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
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
    <div id="page_title">My Posts</div>
    <div id="search_box">
    <form action="<?php echo $base_url; ?>browse.php" method="get">
      <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
      <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
    </form>
    </div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="templatemo_content">

    <?php if (isset($_SESSION['success_message'])): ?>
    <div class="post_box"><p class="done"><?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); ?></p></div>
    <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="post_box"><p class="error-msg"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); ?></p></div>
    <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- TIER BOX -->
    <div class="post_box">
    <h2>Account status</h2>
		<ul class="gallery m0">
        <li>
		</li>
		</ul>
		<br>
		<p>
      Your tier:
      <?php if ($is_admin): ?>
        <strong>Admin</strong>
      <?php elseif ($is_premium): ?>
        <strong>Premium</strong>
      <?php else: ?>
        <strong>Free</strong>
      <?php endif; ?>
    </p>
    <p>
      <strong>Free ads:</strong>
      <?php echo (int)$can_free['used']; ?>
      <?php // Limite REALE applicato dal gate (configurabile da admin). Prima si
            // mostrava la costante deprecata FREE_AD_LIMIT, che indicava un numero
            // diverso da quello davvero applicato. ?>
      <?php if (!$is_admin && !$is_unlimited && (int)$can_free['limit'] > 0): ?> / <?php echo (int)$can_free['limit']; ?><?php endif; ?>
      <?php if ($is_unlimited): ?>&nbsp;<em>(unlimited)</em><?php endif; ?>
      <?php if (!$can_free['allowed']): ?>
        &nbsp;<em>(limit reached)</em>
      <?php endif; ?>
    </p>
    <?php if ($is_premium || $is_admin || $is_unlimited): ?>
    <p>
      <strong>Premium ads:</strong>
      <?php echo (int)$can_premium['used']; ?>
      <?php if (!$is_admin && !$is_unlimited && (int)$can_premium['limit'] > 0): ?> / <?php echo (int)$can_premium['limit']; ?><?php endif; ?>
      <?php if ($is_unlimited): ?>&nbsp;<em>(unlimited)</em><?php endif; ?>
      <?php if (!$can_premium['allowed']): ?>
        &nbsp;<em>(limit reached)</em>
      <?php endif; ?>
    </p>
    <?php endif; ?>

<div class="post_meta">
        <span class="cat">
          <a href="?type=all" class="<?php echo $filter_type === 'all' ? 'active' : ''; ?>">All (<?php echo count($all_posts); ?>)</a>
          <?php foreach ($types_count as $type => $count): ?>
            &nbsp;|&nbsp;
            <a href="?type=<?php echo urlencode($type); ?>" class="<?php echo $filter_type === $type ? 'active' : ''; ?>">
              <?php echo htmlspecialchars($type_labels[$type] ?? $type); ?> (<?php echo (int)$count; ?>)
            </a>
          <?php endforeach; ?>
        </span>
        <div class="cleaner"></div>
      </div>
    </div>

    <?php if (empty($all_posts)): ?>

    <div class="post_box">
      <h2>No posts yet</h2>
				<ul class="gallery m0">
        <li>
		</li>
		</ul>
		<br>
      <p>You have not published any content. Use the links below to get started.</p>
      <div class="post_meta">
        <span class="cat">
        <?php if ($can_free['allowed']): ?>
        <a href="../02_free_ads/02_00_select_type.php">Insert Free ad</a>
        &nbsp;|
        <?php endif; ?>
        <?php if ($can_premium['allowed']): ?>
        <a href="../02_free_ads/02_00_select_type.php?listing=prem">Insert Premium ad</a>
        &nbsp;|
        <?php endif; ?>
        <?php if (!$has_company): ?>
        <a href="../06_company/06_10_register_company.php" >Register Company</a>
        <?php endif; ?>
        </span>
        <div class="cleaner"></div>
      </div>
    </div>
	  
    <?php else: ?>

    <!-- Filtro tipo -->
    <div class="post_box">
      <h2>Your publications</h2>
		<ul class="gallery m0">
        <li>
		</li>
		</ul>
		<br>
		<p>
You have <strong><?php echo count($all_posts); ?></strong> publication<?php echo count($all_posts) === 1 ? '' : 's'; ?> in total.</p>
      <div class="post_meta">
		   <span class="cat">
           <a href="?type=all" class="<?php echo $filter_type === 'all' ? 'active' : ''; ?>">All (<?php echo count($all_posts); ?>)</a>
        <?php foreach ($types_count as $type => $count): ?>
        &nbsp; <span class="cat">|&nbsp;<a href="?type=<?php echo urlencode($type); ?>"<?php echo $filter_type === $type ? ' active' : ''; ?>">
          <?php echo htmlspecialchars($type_labels[$type] ?? $type); ?> (<?php echo (int)$count; ?>)
        </a></span>																						 
        <span class="cat">
        <?php endforeach; ?>
        </span>
        <div class="cleaner"></div>
      </div>
    </div>

    <?php foreach ($all_posts as $post):
      if ($filter_type !== 'all' && $post['type'] !== $filter_type) continue;

      $thumb_filename  = trim((string)($post['image_thumb'] ?? ''));
      $original_filename = trim((string)($post['image_original'] ?? ''));
      $img_dir     = $post['image_dir'];

      if ($thumb_filename !== '' && $thumb_filename !== 'no_image.jpg') {
        $thumb_url = $img_dir . 'thumbnail/' . $thumb_filename;
      } else {
        $thumb_url = '../images/no_image.jpg';
      }
      if ($original_filename !== '' && $original_filename !== 'no_image.jpg') {
        $original_url = $img_dir . 'original/' . $original_filename;
      } else {
        $original_url = $thumb_url;
      }
    ?>
    <div class="post_box">
      <h2>
        <?php echo htmlspecialchars($post['title']); ?>
        <?php if (isset($post['extra']['attiva']) && !$post['extra']['attiva']): ?>
        <em>(inactive)</em>
        <?php endif; ?>
      </h2>

      <ul class="gallery m0">
        <li>
        <a class="pirobox" href="<?php echo htmlspecialchars($original_url); ?>" title="<?php echo htmlspecialchars($post['title']); ?>">
          <img src="<?php echo htmlspecialchars($thumb_url); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>" width="220" height="150" border="0" loading="lazy" decoding="async" />
        </a>
        </li>
      </ul>

      <p>
        <em><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></em>
        <?php if ($post['gallery'] > 0): ?>
        &nbsp;|&nbsp; <?php echo (int)$post['gallery']; ?> gallery image<?php echo $post['gallery'] === 1 ? '' : 's'; ?>
        <?php endif; ?>
        <?php if (isset($post['extra']['products'])): ?>
        &nbsp;|&nbsp; <?php echo (int)$post['extra']['products']; ?> product<?php echo $post['extra']['products'] === 1 ? '' : 's'; ?>,
        <?php echo (int)$post['extra']['services']; ?> service<?php echo $post['extra']['services'] === 1 ? '' : 's'; ?>
        <?php endif; ?>
      </p>

      <?php if (!empty($post['expires_at'])): ?>
      <?php
        $exp_ts   = strtotime($post['expires_at']);
        $days_left = (int)ceil(($exp_ts - time()) / 86400);
        if ($days_left <= 0) {
          $exp_class = 'error-msg';
          $exp_label = 'Expired — will be deleted soon';
        } elseif ($days_left <= 7) {
          $exp_class = 'error-msg';
          $exp_label = 'Expires in ' . $days_left . ' day' . ($days_left === 1 ? '' : 's')
                     . ' (' . date('d/m/Y', $exp_ts) . ')';
        } else {
          $exp_class = '';
          $exp_label = 'Expires: ' . date('d/m/Y', $exp_ts)
                     . ' (' . $days_left . ' days left)';
        }
      ?>
      <p><small class="<?php echo $exp_class; ?>"><?php echo htmlspecialchars($exp_label); ?></small></p>
      <?php endif; ?>

      <p align="justify">
        <?php
        $desc = (string)($post['description'] ?? '');
        echo nl2br(htmlspecialchars(mb_strlen($desc) > 200 ? mb_substr($desc, 0, 200) . '…' : $desc));
        ?>
      </p>

      <div class="cleaner"></div>

      <div class="post_meta">
        <span class="cat">
        <a href="<?php echo htmlspecialchars($post['view_url']); ?>">View</a>
        | <a href="<?php echo htmlspecialchars($post['edit_url']); ?>">Edit</a>
        <?php if ($post['type'] === 'company'): ?>
          | <a href="<?php echo htmlspecialchars($post['products_url']); ?>">Products</a>
          | <a href="<?php echo htmlspecialchars($post['gallery_url']); ?>">Gallery</a>
        <?php endif; ?>
        </span>
        <form method="post" action="<?php echo htmlspecialchars($post['delete_url']); ?>"
          class="float_r" >
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
        <input type="hidden" name="<?php echo htmlspecialchars($post['delete_field']); ?>" value="<?php echo (int)$post['id']; ?>" />
        <button type="submit" class="more float_r">Delete</button>
        </form>
        <div class="cleaner"></div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

  </div>

<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>

  <div class="cleaner"></div>

  <?php include __DIR__ . '/../footer.php'; ?>

</div>
</body>
</html>
