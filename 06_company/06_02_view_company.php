<?php
/**
 * 06_02_view_company.php — Visualizza profilo azienda
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/06_company.class.php';

$cm = new CompanyManager($pdo);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  header('Location: /06_company/06_30_company_directory.php');
  exit;
}

$company = $cm->getCompanyById($id);
if (!$company) {
  $_SESSION['error_message'] = 'Company not found.';
  header('Location: /06_company/06_30_company_directory.php');
  exit;
}

$products = $cm->getProducts($id);
$services = $cm->getServices($id);
$gallery  = $cm->getGalleryImages($id);

// --- Ponte tassonomico: macro coperte dai prodotti azienda (per link al marketplace) ---
require_once __DIR__ . '/../libs/product_macro.class.php';
$company_macros = [];
try {
    $aow_special = $cm->getProductsSpecial($id);
    $aow_keys = array_merge(array_keys($products ?? []), array_keys($aow_special ?? []));
    $company_macros = ProductMacro::macrosForSupplierKeys($aow_keys);
} catch (Throwable $e) { $company_macros = []; }

// L'utente è il proprietario?
$user_id  = (int)($_SESSION['user_id'] ?? 0);
$is_owner = ($user_id > 0 && (int)$company['user_id'] === $user_id);

// Tier del proprietario (per pulsante RFQ premium)
$owner_is_premium = false;
$owner_tier = 'free';
require_once __DIR__ . '/../libs/plan_policy.class.php';
try {
  $oid = (int)$company['user_id'];
  $ts = $pdo->prepare("SELECT user_tier FROM `users` WHERE id_user = ? LIMIT 1");
  $ts->execute([$oid]);
  $tr = $ts->fetch(PDO::FETCH_ASSOC);
  $owner_is_premium = ($tr && ($tr['user_tier'] ?? '') === 'premium');
  $owner_tier = (string)($tr['user_tier'] ?? 'free');
} catch (Throwable $e) {}

$asset_base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$logo_file  = trim((string)($company['logo'] ?? ''));
$logo_url   = ($logo_file !== '')
  ? $asset_base . '/upload_image/06_company/original/' . $logo_file
  : '../images/no_image.jpg';
$logo_thumb = ($logo_file !== '')
  ? $asset_base . '/upload_image/06_company/thumbnail/' . $logo_file
  : '../images/no_image.jpg';
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - <?php echo htmlspecialchars($company['ragione_sociale']); ?></title>
<meta name="keywords" content="<?php echo htmlspecialchars($company['ragione_sociale']); ?>" />
<meta name="description" content="<?php echo htmlspecialchars(mb_substr(aow_i18n_field($company, 'descrizione'), 0, 160)); ?>" />
<meta name="robots" content="index, follow" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />

<script type="text/javascript" src="../js/jquery.min.js" defer></script>
<script type="text/javascript" src="../js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="../js/site_init.js" defer></script>
<?php
$seo_canonical = '06_company/06_02_view_company.php?id=' . (int)$id;
$seo_jsonld = [
    '@context' => 'https://schema.org',
    '@type' => 'LocalBusiness',
    'name' => (string)$company['ragione_sociale'],
];
include __DIR__ . '/../includes/seo_head.php';
?>
</head>
<body>
<div id="templatemo_wrapper">

  <div id="templatemo_header">
    <?php include __DIR__ . '/../header.php'; ?>
  </div>

  <div id="content_top">
    <div id="page_title"><?php echo htmlspecialchars($company['ragione_sociale']); ?></div>
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
    <div class="post_box"><p class="done"><?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success_message']); ?></p></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="post_box"><p class="error-msg"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error_message']); ?></p></div>
    <?php endif; ?>

    <?php if (!(int)$company['attiva']): ?>
    <div class="post_box"><p class="error-msg">&#9888; This company profile is currently inactive.</p></div>
    <?php endif; ?>
    <?php if (!empty($company['founding_partner'])): ?>
    <p class="badges"><span class="badge badge_premium" title="Early partner of the Allonwheel launch">&#9733; <?php te('dir.founding','Founding partner'); ?></span></p>
    <?php endif; ?>
    <div id="contact_form">
    <?php if (PlanPolicy::isDirectoryTop($owner_tier)): ?><p class="badges"><span class="badge badge_featured">Featured</span></p><?php endif; ?>

    <?php if (PlanPolicy::isDirectoryAdvanced($owner_tier)): ?>
    <div class="gallery_box">
      <ul class="gallery">
        <li>
        <a class="pirobox"
         href="<?php echo htmlspecialchars($logo_url); ?>"
         title="<?php echo htmlspecialchars($company['ragione_sociale']); ?>">
          <img src="<?php echo htmlspecialchars($logo_thumb); ?>"
           alt="<?php echo htmlspecialchars($company['ragione_sociale']); ?>"
           width="220" height="150" border="0" loading="lazy" decoding="async" />
        </a>
        </li>
      </ul>
    </div>
    <?php endif; ?>

    <div class="cleaner h10"></div>

    <div class="float_l"><strong>Address:</strong> <?php echo htmlspecialchars($company['indirizzo']); ?>, <?php echo htmlspecialchars($company['cap']); ?> <?php echo htmlspecialchars($company['citta']); ?> (<?php echo htmlspecialchars($company['provincia']); ?>) &mdash; <?php echo htmlspecialchars($company['nazione']); ?></div>
    <div class="cleaner h10"></div>

    <?php if (!empty($company['telefono'])): ?>
    <div class="float_l"><strong>Phone:</strong> <?php echo htmlspecialchars($company['telefono']); ?></div>
    <div class="cleaner h10"></div>
    <?php endif; ?>

    <?php if (!empty($company['cellulare'])): ?>
    <div class="float_l"><strong>Mobile:</strong> <?php echo htmlspecialchars($company['cellulare']); ?></div>
    <div class="cleaner h10"></div>
    <?php endif; ?>

    <?php if (!empty($company['email'])): ?>
    <div class="float_l"><strong>E-mail:</strong> <?php echo htmlspecialchars($company['email']); ?></div>
    <div class="cleaner h10"></div>
    <?php endif; ?>

    <?php if (!empty($company['sito_web']) && PlanPolicy::isDirectoryAdvanced($owner_tier)): ?>
    <div class="float_l"><strong>Website:</strong> <a href="<?php echo htmlspecialchars($company['sito_web']); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($company['sito_web']); ?></a></div>
    <div class="cleaner h10"></div>
    <?php endif; ?>

    <?php if (!empty($company['partita_iva'])): ?>
    <div class="float_l"><strong>VAT:</strong> <?php echo htmlspecialchars($company['partita_iva']); ?></div>
    <div class="cleaner h10"></div>
    <?php endif; ?>

    <div class="cleaner h20"></div>
 <?php if (aow_i18n_field($company, 'descrizione') !== ''): ?>
    <p><strong>Description:</strong></p>
    <p align="justify"><?php echo nl2br(htmlspecialchars(aow_i18n_field($company, 'descrizione'))); ?></p>		
    <div class="cleaner h20"></div>
    <?php endif; ?>

    <?php
      $certs_url = $asset_base . '/upload_image/06_company/certs/';
      $iso_lbl = ['cert_iso9001'=>'ISO 9001','cert_iso14001'=>'ISO 14001','cert_iso45001'=>'ISO 45001'];
      $cert_links = [];
      foreach ($iso_lbl as $col => $lab) {
        $cf = trim((string)($company[$col] ?? ''));
        if ($cf !== '') { $cert_links[] = '<a href="' . htmlspecialchars($certs_url . rawurlencode($cf)) . '" target="_blank" rel="noopener">' . $lab . '</a>'; }
      }
      $assoc = trim((string)($company['associazioni'] ?? ''));
      $area  = trim((string)($company['area_servita'] ?? ''));
      $refs  = trim((string)($company['referenze'] ?? ''));
      $has_auth = !empty($cert_links) || $assoc !== '' || $area !== '' || $refs !== '';
    ?>
    <?php if ($has_auth || $owner_is_premium || $is_owner): ?>
    <h3>Credentials</h3>
    <?php if (!empty($cert_links)): ?>
    <p><strong>Certifications:</strong> <?php echo implode(' &middot; ', $cert_links); ?></p>
    <?php endif; ?>
    <?php if ($assoc !== ''): ?>
    <p><strong>Memberships:</strong> <?php echo htmlspecialchars($assoc); ?></p>
    <?php endif; ?>
    <?php if ($area !== ''): ?>
    <p><strong>Area served:</strong> <?php echo htmlspecialchars($area); ?></p>
    <?php endif; ?>
    <?php if ($refs !== ''): ?>
    <p><strong>References:</strong><br /><?php echo nl2br(htmlspecialchars($refs)); ?></p>
    <?php endif; ?>
    <?php if ($owner_is_premium): ?>
    <p><a class="more" href="<?php echo $asset_base; ?>/04_request_offer/04_request_offer.php?company=<?php echo (int)$company['id']; ?>">Request a quotation</a></p>
    <?php endif; ?>
    <?php if ($is_owner): ?>
    <p><a href="06_15_authority.php">Edit credentials</a></p>
    <?php endif; ?>
    <div class="cleaner h20"></div>
    <?php endif; ?>

    <?php if (!empty($products) || !empty($services)): ?>
    <h3>Products &amp; Services</h3>

    <?php if (!empty($products)): ?>
    <p><strong>Vehicle types built / supplied:</strong></p>
    <ul class="sb_list">
    <?php foreach ($products as $key => $prod): ?>
      <li><?php echo htmlspecialchars(tcat($key, CompanyManager::productLabel($key, $pdo))); ?>
        <?php if (!empty($prod['note'])): ?> &mdash; <em><?php echo htmlspecialchars($prod['note']); ?></em><?php endif; ?>
      </li>
    <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <?php if (!empty($services)): ?>
		</br>
    <p><strong>Accessory services:</strong></p>
    <ul class="sb_list">
    <?php foreach ($services as $key => $srv): ?>
      <li><?php echo htmlspecialchars(tsvc($key, CompanyManager::$services[$key] ?? $key)); ?>
        <?php if (!empty($srv['note'])): ?> &mdash; <em><?php echo htmlspecialchars($srv['note']); ?></em><?php endif; ?>
      </li>
    <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <div class="cleaner h10"></div>
    <?php endif; ?>

    <?php if (!empty($company_macros)): ?>
    <div class="cleaner h10"></div>
    <h3><?php te('bridge.listings_title', 'Related marketplace listings'); ?></h3>
    <p class="rel_sub"><?php te('bridge.listings_sub', 'See current ads in the categories this supplier covers.'); ?></p>
    <p class="rel_macros">
      <?php
      // Dir. 21: le famiglie sono PAGINE dedicate. Questi non sono filtri della
      // scheda fornitore ma link di navigazione verso l'argomento: puntano
      // quindi alla pagina della famiglia (niente ?macro=, niente 301).
      $aow_fam_pages = [
          'race-trailer'      => 'race_trailers.php',
          'hospitality'       => 'hospitality.php',
          'mobile-clinic'     => 'mobile_clinics.php',
          'shelter-container' => 'shelter_container.php',
          'custom-projects'   => 'custom_projects.php',
      ];
      foreach ($company_macros as $aow_m):
          if (!isset($aow_fam_pages[$aow_m])) { continue; } // solo pagine reali (dir. 14)
      ?>
      <a class="more" href="<?php echo $asset_base . '/' . $aow_fam_pages[$aow_m]; ?>"><?php echo htmlspecialchars(ProductMacro::label($aow_m, $pdo)); ?></a>
      <?php endforeach; ?>
    </p>
    <?php endif; ?>

    <?php if (!empty($gallery) && PlanPolicy::isDirectoryAdvanced($owner_tier)): ?>
    <div class="cleaner h20"></div>
    <h3>Gallery</h3>
    <ul class="gallery">
    <?php foreach ($gallery as $img):
      $img_url   = $asset_base . '/upload_image/06_company/original/' . htmlspecialchars($img['immagine']);
      $img_thumb = $asset_base . '/upload_image/06_company/thumbnail/' . htmlspecialchars($img['immagine']);
    ?>
      <li>
        <a class="pirobox" href="<?php echo $img_url; ?>" title="<?php echo htmlspecialchars($img['didascalia'] ?? ''); ?>">
        <img src="<?php echo $img_thumb; ?>"
          alt="<?php echo htmlspecialchars($img['didascalia'] ?? ''); ?>"
          width="220" height="150" border="0" loading="lazy" decoding="async" />
        </a>
      </li>
    <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <?php if (!empty($company['referente_nome'])): ?>
    <div class="cleaner h20"></div>
    <h3>Contact person</h3>
    <div class="float_l"><strong>Name:</strong> <?php echo htmlspecialchars($company['referente_nome'] . ' ' . $company['referente_cognome']); ?></div>
    <div class="cleaner h10"></div>
    <?php if (!empty($company['referente_ruolo'])): ?>
    <div class="float_l"><strong>Role:</strong> <?php echo htmlspecialchars($company['referente_ruolo']); ?></div>
    <div class="cleaner h10"></div>
    <?php endif; ?>
    <?php if (!empty($company['referente_email'])): ?>
    <div class="float_l"><strong>Contact e-mail:</strong> <?php echo htmlspecialchars($company['referente_email']); ?></div>
    <div class="cleaner h10"></div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="cleaner h20"></div>

    <a class="more float_l" href="06_30_company_directory.php">Back</a>
    <?php if ($is_owner): ?>
    <a href="06_20_modify_company.php" class="more float_r">Edit</a>
    <?php endif; ?>

    <div class="cleaner"></div>
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