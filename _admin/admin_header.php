<?php
// ============================================================
// /_admin/admin_header.php
// Header/layout condiviso di TUTTE le pagine dell'area admin.
// Uniforma <head>, wrapper, titolo pagina, apertura #templatemo_content
// e la barra di navigazione admin (con voce attiva evidenziata).
//
// Uso (prima dell'include, nella pagina chiamante):
//   $admin_title  = 'Records';            // titolo pagina (h1/page_title)
//   $admin_active = 'records';            // chiave voce di menu attiva
//   require __DIR__ . '/admin_header.php';
//
// Chiavi $admin_active ammesse: users, records, ads, companies, leads, vtypes, blog.
// Solo classi del foglio di stile esistente (dir. 8), niente stile inline.
// ============================================================

if (!isset($admin_title))  { $admin_title  = 'Admin'; }
if (!isset($admin_active)) { $admin_active = ''; }

// Voci del menu admin: chiave => [etichetta, file]
$admin_nav = [
    'users'     => ['Users',         'dashboard.php'],
    'records'   => ['Advertising',   'manage_records.php'],
    'adlimits'  => ['Listing limits', 'admin_ad_limits.php'],
    'ads'       => ['Ad moderation', 'moderate_ads.php'],
    'companies' => ['Companies',     'manage_companies.php'],
    'leads'     => ['Leads',         'leads.php'],
    'rentleads' => ['Rental leads',  'rent_leads.php'],
    'kpi'       => ['KPI',           'kpi.php'],
    'vtypes'    => ['Vehicle Types', 'admin_vehicle_types.php'],
    'stypes'    => ['Special types', 'admin_special_types.php'],
    'classify'  => ['Road/Special', 'admin_classify_vehicles.php'],
    'macros'    => ['Macro heroes',  'admin_macros.php'],
    'homehero'  => ['Home hero',     'admin_hero.php'],
    'pmlist'    => ['PM/Consultant list', 'admin_pm_list.php'],
    'blog'      => ['Blog',          'moderate_blog.php'],
    'blogleads' => ['Blog leads',    'blog_leads.php'],
];
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin &mdash; <?php echo htmlspecialchars($admin_title); ?></title>
<meta name="robots" content="noindex, nofollow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="../js/jquery.min.js" defer></script>
<script type="text/javascript" src="../js/ddsmoothmenu.js" defer></script>
<script type="text/javascript" src="../js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="../js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header">
    <div id="site_title">
      <h1><a href="../index.php" aria-label="All on Wheel - home"><img src="/images/brand/logo.png" alt="" class="brand_logo" width="40" height="40" /><span class="brand_word">ALL ON <b>WHEEL</b></span></a></h1>
      <span class="brand_tag">Admin area</span>
    </div>
  </div>
  <div id="content_top">
    <div id="page_title"><?php echo htmlspecialchars($admin_title); ?></div>
    <div class="cleaner"></div>
  </div>

  <div id="templatemo_content" class="admin_full">

    <div class="post_box">
      <div class="post_meta">
        <?php $first = true; foreach ($admin_nav as $key => $item): ?>
          <?php if (!$first) { echo ' &nbsp;|&nbsp; '; } $first = false; ?>
          <?php if ($key === $admin_active): ?>
            <strong><?php echo htmlspecialchars($item[0]); ?></strong>
          <?php else: ?>
            <a href="<?php echo htmlspecialchars($item[1]); ?>"><?php echo htmlspecialchars($item[0]); ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
        &nbsp;|&nbsp;
        <a href="logout.php" class="more float_r">Logout</a>
        <div class="cleaner"></div>
      </div>
    </div>
