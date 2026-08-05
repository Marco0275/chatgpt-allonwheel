<?php
// ============================================================
// shared/ad_modify_page.php
// Pagina di modifica annuncio, una sola implementazione per tutte le sezioni.
//
// 23 lug 2026. I file di sezione (02_modify_road.php, 02_modify_special.php,
// 02_modify_shelter.php e i gemelli 03_*) impostano due variabili e includono
// questo file: cosi' ogni sezione ha il suo ingresso, ma la logica -
// proprieta', validazione, layout - sta in un posto solo e non puo' divergere.
//
// Variabili attese PRIMA dell'include:
//   $aow_lt             'free' | 'prem'      (tabella 02_free_ads / 03_ads)
//   $aow_expect_section 'road' | 'special' | 'shelter'
//
// Sicurezza: si modifica SOLO un proprio annuncio (id_user = utente in
// sessione) e SOLO dalla pagina della sezione giusta: se l'annuncio non
// appartiene alla sezione, si viene rimandati a quella corretta invece di
// mostrare campi non pertinenti.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';
require_once __DIR__ . '/../libs/ad_section_fields.class.php';

$user_id  = require_user_logged_in();
$aow_lt   = (isset($aow_lt) && $aow_lt === 'prem') ? 'prem' : 'free';
$aow_tbl  = ($aow_lt === 'prem') ? '03_ads' : '02_free_ads';
$aow_dir  = ($aow_lt === 'prem') ? '03_ads' : '02_free_ads';
$aow_pfx  = ($aow_lt === 'prem') ? '03' : '02';
$aow_expect_section = isset($aow_expect_section) ? (string)$aow_expect_section : '';
$base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';

$id_ads = isset($_GET['id_ads']) ? (int)$_GET['id_ads'] : 0;
if ($id_ads <= 0) {
    $_SESSION['error_message'] = 'Missing ad id.';
    header('Location: ../01_login/my_posts.php');
    exit;
}

// Annuncio + ownership: si legge solo il proprio (dir. 12).
try {
    $st = $pdo->prepare("SELECT * FROM `{$aow_tbl}` WHERE id_ads = :id AND id_user = :u LIMIT 1");
    $st->execute([':id' => $id_ads, ':u' => $user_id]);
    $ad = $st->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[Allonwheel] ad_modify_page load: ' . $e->getMessage());
    $ad = null;
}
if (!$ad) {
    $_SESSION['error_message'] = 'Ad not found, or it is not yours.';
    header('Location: ../01_login/my_posts.php');
    exit;
}

// Sezione REALE dell'annuncio. Se non e' quella della pagina aperta, si
// reindirizza al file giusto: cosi' non si modifica un veicolo stradale da
// una pagina che mostra i campi degli shelter.
$aow_section = AdSectionFields::sectionOf($ad);
if ($aow_expect_section !== '' && $aow_section !== $aow_expect_section) {
    header('Location: ' . $aow_pfx . '_modify_' . $aow_section . '.php?id_ads=' . $id_ads);
    exit;
}

// Tipi veicolo della macro dell'annuncio (vuoto per shelter: tipo fisso).
$aow_vts = [];
if (AdSectionFields::hasVehicleTypeChoice($aow_section)) {
    try {
        // Nuova tassonomia: road -> vehicle_types, special e shelter ->
        // special_types. La sezione dell'annuncio e' gia' quella giusta.
        $aow_vts = VehicleTaxonomy::typesForCategory($aow_section, $pdo);
    } catch (Throwable $e) {
        error_log('[Allonwheel] ad_modify_page types: ' . $e->getMessage());
    }
}

// Dettagli tecnici (solo premium): valori correnti per il pre-riempimento.
$tech = [];
if ($aow_lt === 'prem') {
    try {
        $qt = $pdo->prepare('SELECT * FROM `03_ads_tech_details` WHERE id_ads = :id LIMIT 1');
        $qt->execute([':id' => $id_ads]);
        $tech = $qt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('[Allonwheel] ad_modify_page tech: ' . $e->getMessage());
        $tech = [];
    }
}

// L'handler dei dettagli tecnici (03_02_upload_tech_advertising_modified.php)
// prende l'id dell'annuncio dalla SESSIONE, come nel wizard di inserimento:
// lo impostiamo qui, altrimenti il salvataggio della scheda tecnica non
// saprebbe su quale annuncio scrivere.
$_SESSION['id_ads'] = $id_ads;

$aow_sec_label = AdSectionFields::label($aow_section);
$aow_ad = $ad; // nome atteso dal partial dei campi
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Edit <?php echo $aow_lt === 'prem' ? 'premium' : 'free'; ?> ad</title>
<meta name="robots" content="noindex, nofollow" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<link href="../css_pirobox/white/style.css" media="screen" rel="stylesheet" type="text/css" />
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
    <div id="page_title">Edit <?php echo $aow_lt === 'prem' ? 'premium' : 'free'; ?> ad</div>
    <div id="search_box">
      <form action="<?php echo $base_url; ?>browse.php" method="get">
        <input type="text" name="q" size="10" id="searchfield" title="Search listings" placeholder="Search" />
        <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
      </form>
    </div>
    <div class="cleaner"></div>
  </div>

  <div id="main"></div><div id="templatemo_content">

    <?php if (!empty($_SESSION['error_message'])): ?>
    <div class="post_box"><p class="error-msg"><?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error_message']); ?></p></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success_message'])): ?>
    <div class="post_box"><p class="done"><?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success_message']); ?></p></div>
    <?php endif; ?>

    <h2>Edit: <?php echo htmlspecialchars((string)$ad['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
    <p><strong><?php echo htmlspecialchars($aow_sec_label, ENT_QUOTES, 'UTF-8'); ?></strong>
       &mdash; <em>only the fields that apply to this category are shown.</em></p>

    <?php // Stesso contenitore del form di inserimento (02_insert_ad.php):
          // e' #contact_form a portare le regole di impaginazione - etichetta
          // sopra il campo e campo a piena larghezza, quindi tutto allineato a
          // sinistra. Senza questo wrapper i campi restavano senza stile e
          // finivano spinti a destra. ?>
    <div id="contact_form">
      <form action="<?php echo $aow_pfx; ?>_01_upload_advertising_modified.php" method="post">
        <?php echo csrf_generate_persistent(); ?>
        <input type="hidden" name="id_ads" value="<?php echo (int)$id_ads; ?>" />

        <?php include __DIR__ . '/ad_modify_fields.php'; ?>

        <div class="cleaner h20"></div>
        <div class="post_meta">
          <input type="submit" class="more float_r" value="Save changes" />
        </div>
        <div class="cleaner"></div>
      </form>
    </div><!-- end contact_form -->

    <?php // ---- Dettagli tecnici: solo premium ----------------------------
    // Form SEPARATO, verso l'handler dei tecnici gia' esistente
    // (03_02_upload_tech_advertising_modified.php): si rispetta la divisione
    // del sito fra dati annuncio e scheda tecnica, invece di accorparli.
    // I gruppi mostrati sono solo quelli della sezione: uno shelter non ha
    // telaio ne' sponda idraulica, un veicolo stradale non ha veranda o
    // telemetria.
    if ($aow_lt === 'prem'):
        $mode = 'form';
        $aow_tech_section = $aow_section; // filtro letto dal partial
    ?>
    <div class="post_box">
      <h3>Technical details</h3>
      <p><em>Only the technical groups that apply to a <?php echo htmlspecialchars(strtolower($aow_sec_label), ENT_QUOTES, 'UTF-8'); ?> are shown.</em></p>
      <form action="03_02_upload_tech_advertising_modified.php" method="post">
        <?php echo csrf_generate_persistent(); ?>
        <input type="hidden" name="id_ads" value="<?php echo (int)$id_ads; ?>" />
        <?php include __DIR__ . '/tech_details_fields.php'; ?>
        <div class="cleaner h20"></div>
        <div class="post_meta">
          <input type="submit" class="more float_r" value="Save technical details" />
        </div>
        <div class="cleaner"></div>
      </form>
    </div>
    <?php endif; ?>

    <div id="contact_form">
      <h3>Manage this ad</h3>
				<ul class="gallery m0">
        <li>
		</li>
		</ul>
		<br>
		<p>
      <p>
        <a href="<?php echo $aow_pfx; ?>_insert_ad_image.php?id_ads=<?php echo (int)$id_ads; ?>">Main photo</a> &middot;
        <a href="<?php echo $aow_pfx; ?>_insert_ad_gallery.php?id_ads=<?php echo (int)$id_ads; ?>">Photo gallery</a> &middot;
        <a href="../01_login/my_posts.php">Back to my posts</a>
      </p>

  </div><!-- end templatemo_content -->
</div>
  <div id="templatemo_sidebar">
    <?php include __DIR__ . '/../include_sidebar.php'; ?>
  </div>

  <div class="cleaner"></div>
  <?php include __DIR__ . '/../footer.php'; ?>

</div>
</body>
</html>
