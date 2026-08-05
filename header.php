<?php
// ============================================================
// header.php — Header globale del sito (menu di navigazione)
//
// Revisione UX (v0.0.9 - allineamento al flowchart reale, dir. 17/18):
//  - Macro-aree per intento: Marketplace, Suppliers, Portfolio, About.
//  - Marketplace: All listings / Request a quotation (le famiglie sono filtri su browse.php).
//  - Suppliers: directory + Road vehicles / Special vehicles / Shelter & Container.
//  - Rimosse le voci motorsport legacy (cartella 00_first) dal menu.
//  - Header SOLO navigazione pubblica: identico per ospite e loggato.
//  - I link personali e il login vivono nelle sidebar di sezione
//    (sidebar_user_box.php), non piu' nell'header (dir. 17 rev.4).
// ============================================================

require_once __DIR__ . '/config/session_helper.php';
if (!function_exists('t')) { require_once __DIR__ . '/config/i18n.php'; }


// ----- Base path automatico -----
$base_url = '';
$script   = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
foreach (['00_first', '01_login', '02_free_ads', '03_ads', '04_request_offer', '05_wanted', '06_company', '07_rent', '_admin', 'shared'] as $f) {
    if (strpos($script, '/' . $f . '/') !== false) {
        $base_url = '../';
        break;
    }
}

// Variabili di stato (mantenute per retro-compatibilita' delle pagine che le
// leggono dopo l'include; il menu pubblico NON le usa piu', dir. 17 rev.4).
$is_logged_in     = is_user_logged_in();
$current_username = $is_logged_in ? current_username() : '';
$is_admin         = $is_logged_in && isset($_SESSION['user_tier']) && $_SESSION['user_tier'] === 'admin';
?>
<?php // Salto al contenuto: obbligatorio per chi naviga da tastiera o con uno
      // screen reader, altrimenti va attraversato tutto il menu a ogni pagina
      // (WCAG 2.1, criterio 2.4.1 "Bypass Blocks"). ?>
<a class="skip_link" href="#main"><?php te('a11y.skip','Skip to content'); ?></a>
<div id="site_title">
  <?php $brand_tag = !empty($page_has_own_h1) ? 'div' : 'h1'; ?><<?php echo $brand_tag; ?> class="site_brand"><a href="<?php echo $base_url; ?>index.php" aria-label="All on Wheel - home"><img src="<?php echo $base_url; ?>images/brand/logo.png" alt="All on Wheel" class="brand_logo" width="40" height="40" /><span class="brand_word">ALL ON <b>WHEEL</b></span></a></<?php echo $brand_tag; ?>>
  <span class="brand_tag"><?php te('brand.tagline','Motorsport paddock &amp; special vehicles'); ?></span>
</div>

<div id="templatemo_menu" class="ddsmoothmenu">
<ul>

  <!-- Marketplace. Dir. 21 (16 lug 2026): ogni famiglia e' una PAGINA dedicata,
       non un filtro a chip su browse.php: quindi entra nel menu come voce propria. -->
  <li><a href="<?php echo $base_url; ?>browse.php"><?php te('nav.marketplace','Marketplace'); ?></a>
    <ul>
      <li><a href="<?php echo $base_url; ?>browse.php"><?php te('nav.all_listings','All listings'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>race_trailers.php"><?php te('macro.race_trailer','Race Trailers'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>hospitality.php"><?php te('macro.hospitality','Hospitality'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>mobile_clinics.php"><?php te('macro.mobile_clinic','Mobile Clinics'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>shelter_container.php"><?php te('macro.shelter','Shelter &amp; Container'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>custom_projects.php"><?php te('macro.custom_projects','Custom Projects'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>05_wanted/wanted_list.php"><?php te('nav.wanted','Wanted requests'); ?></a></li>
    </ul>
  </li>

  <!-- Suppliers (flowchart: Company / Project manager -> Vehicle types -> Road / Special) -->
  <li><a href="<?php echo $base_url; ?>06_company/06_30_company_directory.php"><?php te('nav.suppliers','Suppliers'); ?></a>
    <ul>
      <li><a href="<?php echo $base_url; ?>06_company/06_30_company_directory.php"><?php te('nav.directory','Supplier directory'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>road_vehicles.php"><?php te('b2b.road','Road vehicles'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>special_vehicles.php"><?php te('b2b.special','Special vehicles'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>professionals.php"><?php te('nav.professionals','Professionals'); ?></a></li>
    </ul>
  </li>

  <!-- Rental -->
  <li><a href="<?php echo $base_url; ?>07_rent/07_20_rent_list.php"><?php te('nav.rental','Rental'); ?></a></li>

  <!-- About — solo contenuti editoriali -->
  <li><a href="<?php echo $base_url; ?>about.php"><?php te('nav.about','About'); ?></a>
    <ul>
      <li><a href="<?php echo $base_url; ?>what_we_do.php"><?php te('nav.what_we_do','What we do'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>portfolio.php"><?php te('nav.portfolio','Portfolio'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>blog.php"><?php te('nav.blog','Blog'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>FAQ.php"><?php te('nav.faq','F.A.Q.'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>Conditions.php"><?php te('nav.conditions','Conditions & rules'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>contact.php"><?php te('nav.contact','Contact us'); ?></a></li>
    </ul>
  </li>

  <!-- Account: login-aware (creazione / login / modifica / delete) -->
  <li><a href="<?php echo $base_url; ?>01_login/<?php echo $is_logged_in ? 'seller_dashboard.php' : 'newlogin.php'; ?>"><?php te('nav.account','Account'); ?></a>
    <ul>
    <?php if ($is_logged_in): ?>
      <li><a href="<?php echo $base_url; ?>01_login/seller_dashboard.php"><?php te('nav.dashboard','Dashboard'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>01_login/my_posts.php"><?php te('nav.my_posts','My posts'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>01_login/all_about_me.php"><?php te('nav.my_profile','My profile'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>01_login/modify_user_details.php"><?php te('nav.account_settings','Account settings'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>01_login/request_premium.php"><?php te('nav.upgrade','Upgrade to premium'); ?></a></li>
      <?php if ($is_admin): ?><li><a href="<?php echo $base_url; ?>_admin/dashboard.php"><?php te('nav.admin','Admin panel'); ?></a></li><?php endif; ?>
      <li><a href="<?php echo $base_url; ?>01_login/logout.php"><?php te('nav.logout','Logout'); ?></a></li>
      <li class="nav_danger"><a href="<?php echo $base_url; ?>01_login/delete_account.php"><?php te('nav.delete_account','Delete account'); ?></a></li>
    <?php else: ?>
      <li><a href="<?php echo $base_url; ?>01_login/newlogin.php"><?php te('nav.login','Login'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>01_login/newregister.php"><?php te('nav.create_account','Create account'); ?></a></li>
      <li><a href="<?php echo $base_url; ?>01_login/forgot_password.php"><?php te('nav.forgot_password','Forgot password?'); ?></a></li>
    <?php endif; ?>
    </ul>
  </li>

  <li class="nav_cta"><a href="<?php echo $base_url; ?>04_request_offer/04_request_offer.php"><?php te('nav.request_quote','Request a quote'); ?></a></li>
</ul>
<br class="clear_left" />
</div>
