<?php
// ============================================================
// sidebar_user_box.php — Box utente login-aware, condiviso da TUTTE le
// sidebar di sezione (dir. 17 rev.4 — richiesta utente 9 giu 2026).
//
// Modello:
//   - L'header e' SOLO navigazione pubblica (identico per ospite e loggato).
//   - I link personali NON stanno piu' nell'header: ogni sidebar li mostra qui.
//   - Loggato     -> box "My account" con tutti i link personali + logout.
//   - Non loggato -> solo il link di Login.
//
// Produce SOLO box .sb_box. Nessuno stile nuovo (dir. 8).
// ============================================================
require_once __DIR__ . '/config/session_helper.php';
if (!function_exists('te')) { require_once __DIR__ . '/config/i18n.php'; }

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----- Base path automatico (se non gia' calcolato dalla pagina/sidebar) -----
if (!isset($base_url)) {
    $base_url = '';
    $_ub_script = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
    foreach (['00_first', '01_login', '02_free_ads', '03_ads', '04_request_offer', '05_wanted', '06_company', '07_rent', 'shared', '_admin'] as $f) {
        if (strpos($_ub_script, '/' . $f . '/') !== false) { $base_url = '../'; break; }
    }
    unset($_ub_script);
}

$is_logged_in = is_user_logged_in();
$is_admin     = $is_logged_in && isset($_SESSION['user_tier']) && $_SESSION['user_tier'] === 'admin';
?>
<?php if ($is_logged_in): ?>
<!-- ===== My account (loggato) ===== -->
<div class="sb_box">
  <h3><?php te('sb.my_account','My account'); ?></h3>
  <ul class="sb_list">
    <li><a href="<?php echo $base_url; ?>01_login/my_posts.php"><?php te('sb.my_posts','My posts'); ?></a></li>
    <li><a href="<?php echo $base_url; ?>01_login/seller_dashboard.php"><?php te('sb.seller_dashboard','Seller dashboard'); ?></a></li>
    <li><a href="<?php echo $base_url; ?>01_login/all_about_me.php"><?php te('sb.my_profile','My profile'); ?></a></li>
    <li><a href="<?php echo $base_url; ?>01_login/modify_user_details.php"><?php te('sb.account_settings','Account settings'); ?></a></li>
    <li><a href="<?php echo $base_url; ?>01_login/account_roles.php"><?php te('sb.my_role','My role: Expert / PM / Consultant'); ?></a></li>
    <li><a href="<?php echo $base_url; ?>01_login/request_premium.php"><?php te('sb.upgrade_premium','Upgrade to premium'); ?></a></li>
    <li><a href="<?php echo $base_url; ?>02_free_ads/02_00_select_type.php"><?php te('sb.post_free_ad','Post a free ad'); ?></a></li>
    <li><a href="<?php echo $base_url; ?>03_ads/03_00_select_type.php"><?php te('sb.post_premium_ad','Post a premium ad'); ?></a></li>
    <li><a href="<?php echo $base_url; ?>05_wanted/wanted_post.php"><?php te('sb.post_wanted','Post a wanted request'); ?></a></li>
    <li><a href="<?php echo $base_url; ?>05_wanted/wanted_manage.php"><?php te('sb.my_wanted','My wanted requests'); ?></a></li>
    <li><a href="<?php echo $base_url; ?>07_rent/07_10_rent_post.php"><?php te('sb.post_rental','Post a rental listing'); ?></a></li>
    <li><a href="<?php echo $base_url; ?>07_rent/07_30_rent_request.php"><?php te('sb.request_rental','Request a rental'); ?></a></li>
    <li><a href="<?php echo $base_url; ?>06_company/06_10_register_company.php"><?php te('sb.register_company','Register company'); ?></a></li>
    <li><a href="<?php echo $base_url; ?>06_company/06_40_my_leads.php"><?php te('sb.my_leads','My leads'); ?></a></li>
    <li><a href="<?php echo $base_url; ?>07_rent/07_40_rent_leads.php"><?php te('sb.rental_leads','Rental leads'); ?></a></li>
    <li><a href="<?php echo $base_url; ?>blog_write.php"><?php te('sb.ask_question','Ask a question'); ?></a></li>
    <?php if ($is_admin): ?>
      <li><a href="<?php echo $base_url; ?>_admin/dashboard.php"><?php te('sb.admin_panel','Admin panel'); ?></a></li>
    <?php endif; ?>
    <li><a href="<?php echo $base_url; ?>01_login/logout.php"><?php te('sb.logout','Logout'); ?></a></li>
  </ul>
</div>
<?php else: ?>
<!-- ===== Account (visitatore): solo link di login ===== -->
<div class="sb_box">
  <h3><?php te('sb.account','Account'); ?></h3>
  <ul class="sb_list">
    <li><a href="<?php echo $base_url; ?>01_login/newlogin.php"><?php te('sb.login','Login'); ?></a></li>
  </ul>
</div>
<?php endif; ?>
