<?php
/**
 * 06_30_company_directory.php — Elenco pubblico aziende fornitrici
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/06_company.class.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';
require_once __DIR__ . '/../libs/product_macro.class.php';

$cm = new CompanyManager($pdo);

// Ricerca + filtro tipo veicolo (vtype) opzionali
$search = trim($_GET['q'] ?? '');
if ($search === 'Search suppliers') { $search = ''; }   // placeholder
$vtype  = trim($_GET['vtype'] ?? '');
$special = trim($_GET['special'] ?? '');

$macro = trim($_GET['macro'] ?? '');

// Elenco famiglie per la chip-bar
$all_macros = [];
try { $all_macros = $pdo->query('SELECT slug, name FROM `product_macros` ORDER BY sort_order, name')->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) { $all_macros = []; }

$macro_label = '';
$vtype_name = '';
if ($macro !== '' && class_exists('ProductMacro') && ProductMacro::exists($macro)) {
  // Filtro per FAMIGLIA (macro): ponte macro -> chiavi product_key fornitore
  $k = ProductMacro::supplierKeysFor($macro);
  $companies   = $cm->getCompaniesByMacroKeys($k['regular'] ?? [], $k['special'] ?? [], $search);
  $macro_label = ProductMacro::label($macro, $pdo);
  $vtype_name  = $macro_label;
} elseif ($special !== '') {
  // Filtro per categoria SPECIALE (06_company_products_special)
  $vtype_name = tcat($special, CompanyManager::productLabel($special, $pdo));
  $companies  = $cm->getCompaniesByProductSpecial($special, $search);
} elseif ($vtype !== '') {
  $vtype_name = t('vt.'.$vtype, $cm->getVehicleTypeName($vtype) ?? $vtype);
  $companies  = $cm->getCompaniesByVehicleType($vtype, $search);
} elseif ($search !== '') {
  $companies = $cm->searchCompanies($search);
} else {
  $companies = $cm->getAllActiveCompanies();
}

$total = $cm->countActiveCompanies();

$asset_base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$logo_base  = $asset_base . '/upload_image/06_company/thumbnail/';
$logo_orig_base = $asset_base . '/upload_image/06_company/original/';
require_once __DIR__ . '/../libs/plan_policy.class.php';
$aow_tier_by_cid = [];
$aow_dids = array_values(array_filter(array_map(static function ($c) { return (int)($c['id'] ?? 0); }, $companies)));
if (!empty($aow_dids)) {
    $aow_din = implode(',', array_fill(0, count($aow_dids), '?'));
    $aow_dts = $pdo->prepare("SELECT c.id, COALESCE(u.user_tier,'free') AS tier FROM `06_company` c LEFT JOIN `users` u ON u.id_user = c.user_id WHERE c.id IN ($aow_din)");
    $aow_dts->execute($aow_dids);
    foreach ($aow_dts->fetchAll(PDO::FETCH_ASSOC) as $r) { $aow_tier_by_cid[(int)$r['id']] = (string)$r['tier']; }
}
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Supplier Directory</title>
<meta name="keywords" content="supplier directory, automotive suppliers, vehicle suppliers" />
<meta name="description" content="All on Wheel Supplier Directory - Find automotive suppliers and service providers" />
<meta name="robots" content="index, follow" />
<meta name="language" content="en" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
	
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<link rel="icon" href="../images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />

<script type="text/javascript" src="../js/jquery.min.js" defer></script>
<script type="text/javascript" src="../js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="../js/site_init.js" defer></script>
<?php
$seo_canonical = '06_company/06_30_company_directory.php' . (!empty($active_macro) ? '?macro=' . rawurlencode($active_macro) : '');
include __DIR__ . '/../includes/seo_head.php';
?>
</head>
<body>
<div id="templatemo_wrapper">

  <div id="templatemo_header">
    <?php include __DIR__ . '/../header.php'; ?>
  </div>

  <div id="content_top">
    <div id="page_title">Supplier Directory</div>
    <div id="search_box">
    <form action="06_30_company_directory.php" method="get">
      <?php if ($vtype !== ''): ?>
      <input type="hidden" name="vtype" value="<?php echo htmlspecialchars($vtype); ?>" />
      <?php endif; ?>
      <?php if ($special !== ''): ?>
      <input type="hidden" name="special" value="<?php echo htmlspecialchars($special); ?>" />
      <?php endif; ?>
      <?php if ($macro !== ''): ?>
      <input type="hidden" name="macro" value="<?php echo htmlspecialchars($macro); ?>" />
      <?php endif; ?>
      <input type="text" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" name="q" size="10" id="searchfield" title="<?php te('search.suppliers','Search suppliers'); ?>" placeholder="<?php te('search.suppliers','Search suppliers'); ?>" />
      <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
    </form>
    </div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="templatemo_content">

    <?php if (empty($companies)): ?>
    <div class="post_box">
      <h2>No suppliers found</h2>
      <p>There are no suppliers to display<?php echo ($search !== '' || $vtype !== '' || $special !== '' || $macro !== '') ? ' matching the current filter' : ''; ?>.</p>
    </div>
    <?php else: ?>

    <?php foreach ($companies as $c):
      $logo_file = trim((string)($c['logo'] ?? ''));
      if ($logo_file !== '') {
        $logo_url  = $logo_base . $logo_file;
        $logo_full = $logo_orig_base . $logo_file;
      } else {
        $logo_url  = '../images/no_image.jpg';
        $logo_full = '../images/no_image.jpg';
      }
    ?>
      <div class="post_box supplier_box">
        <h2><?php echo htmlspecialchars($c['ragione_sociale']); ?></h2>
        <?php if (PlanPolicy::isDirectoryTop($aow_tier_by_cid[(int)$c['id']] ?? 'free')): ?><span class="badge badge_featured">Featured</span><?php endif; ?>
        <?php
        $aow_certs = [];
        if (!empty($c['cert_iso9001']))  { $aow_certs[] = 'ISO 9001'; }
        if (!empty($c['cert_iso14001'])) { $aow_certs[] = 'ISO 14001'; }
        if (!empty($c['cert_iso45001'])) { $aow_certs[] = 'ISO 45001'; }
        ?>
        <?php $aow_founding = !empty($c['founding_partner']); ?>
        <?php if ($aow_certs || $aow_founding): ?>
        <p class="badges">
          <?php if ($aow_founding): ?>
          <span class="badge badge_premium" title="Early partner of the Allonwheel launch">&#9733; <?php te('dir.founding','Founding partner'); ?></span>
          <?php endif; ?>
          <?php if ($aow_certs): ?>
          <span class="badge badge_approved" title="<?php echo htmlspecialchars(implode(' &middot; ', $aow_certs)); ?>">&#10003; <?php te('dir.certified','Certified'); ?></span>
          <?php endif; ?>
        </p>
        <?php endif; ?>

        <?php if (PlanPolicy::isDirectoryAdvanced($aow_tier_by_cid[(int)$c['id']] ?? 'free')): ?>
        <ul class="gallery m0">
        <li>
          <a class="pirobox"
           href="<?php echo htmlspecialchars($logo_full); ?>"
           title="<?php echo htmlspecialchars($c['ragione_sociale']); ?>">
            <img loading="lazy" decoding="async" src="<?php echo htmlspecialchars($logo_url); ?>"
             alt="<?php echo htmlspecialchars($c['ragione_sociale']); ?>"
             width="220" height="150" border="0" />
          </a>
        </li>
			<div class="cleaner h20"></div>
        </ul>
        <?php endif; ?>

        <p><strong>Location:</strong> <?php echo htmlspecialchars($c['citta']); ?> (<?php echo htmlspecialchars($c['provincia']); ?>) &mdash; <?php echo htmlspecialchars($c['nazione']); ?></p>

        <p align="justify">
        <?php
        $desc = aow_i18n_field($c, 'descrizione');
        $short = mb_strlen($desc) > 200 ? mb_substr($desc, 0, 200) . '…' : $desc;
        echo nl2br(htmlspecialchars($short));
        ?>
        </p>

        <div class="post_meta">
        
			<table width="100%" border="0">
  <tbody>
    <tr>
      <td class="cat" style="text-align: left">
        <?php if (!empty($c['num_products'])): ?>
        Products: <strong><?php echo (int)$c['num_products']; ?></strong> | <?php endif; ?>
          <?php if (!empty($c['num_services'])): ?>Services: <strong><?php echo (int)$c['num_services']; ?></strong> 
          <?php endif; ?>
                </strong>
        </td>
      <td style="text-align: right"><a href="06_02_view_company.php?id=<?php echo (int)$c['id']; ?>" class="more float_r">View profile</a></td>
    </tr>
  </tbody>
</table>

          
        
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
