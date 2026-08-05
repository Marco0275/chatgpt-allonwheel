<?php
// ============================================================
// 04_request_offer.php — Richiesta di offerta (lato acquirente).
//
// Nodo "Request quotation" del flowchart (sotto Marketplace).
// L'utente seleziona una o piu' categorie (regolari da
// CompanyManager::$products + speciali da $products_special) e compila
// un modulo in stile contact.php. All'invio (04_send_offer.php) il modulo
// completo viene inviato via e-mail a OGNI azienda che produce almeno una
// delle categorie selezionate (06_company_products / _special).
//
// Solo i cataloghi statici della classe servono qui (nessuna query):
// la pagina si renderizza anche senza DB. Path corretti per subfolder.
// ============================================================
require_once __DIR__ . '/../config/csrf.php';
if (!function_exists('t')) require_once __DIR__ . '/../config/i18n.php';
require_once __DIR__ . '/../libs/06_company.class.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/product_macro.class.php'; // M3: prefill famiglia da ?macro=

// Sezione RFQ (23 lug 2026): road | special | shelter. Ogni sezione mostra
// SOLO le sue categorie, invece di un unico elenco con tutto mescolato.
// Senza ?section (o valore ignoto) si vede tutto, come prima: i link
// generici alla RFQ (header, footer, home) restano validi.
$aow_section = trim((string)($_GET['section'] ?? ''));
if ($aow_section !== '' && !isset(CompanyManager::$rfqSections[$aow_section])) {
    $aow_section = '';
}
$aow_rfq_cats  = CompanyManager::rfqCategoriesFor($aow_section !== '' ? $aow_section : null);
$aow_rfq_label = (string)$aow_rfq_cats['label'];
// Tier dell'utente: premium -> configuratore tecnico (cartella 03); free -> base (cartella 02)
$rfq_uid = (int)($_SESSION['user_id'] ?? 0);
$is_premium = false;
if ($rfq_uid > 0 && isset($pdo)) {
    $rfq_tier = UserTier::getTier($pdo, $rfq_uid);
    $is_premium = ($rfq_tier === 'premium' || $rfq_tier === 'admin');
}
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - <?php te('rfq.title','Request an offer'); ?></title>
<meta name="keywords" content="All on Wheel - Request an offer" />
<meta name="description" content="Request an offer from our suppliers" />
<meta name="robots" content="index, follow" />
<meta name="revisit-after" content="3" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<meta name="reply-to" content="" />

<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="../js/jquery.min.js" defer></script>
<script type="text/javascript" src="../js/ddsmoothmenu.js" defer></script>
<script type="text/javascript" src="../js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="../js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper"><div id="templatemo_header">
 <?php include ('../header.php'); ?>
</div>

<div id="content_top">
<div id="page_title"><?php te('rfq.title','Request an offer'); if ($aow_rfq_label !== '') { echo ' &ndash; ' . htmlspecialchars($aow_rfq_label, ENT_QUOTES, 'UTF-8'); } ?></div>
<div id="search_box">
<form action="<?php echo $base_url; ?>browse.php" method="get">
<input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
<input type="submit" name="Search" value="" id="searchbutton" title="Search" />
</form>
</div>
<div class="cleaner"></div>
</div>
<div id="main"></div><div id="templatemo_content">
<p><?php te('rfq.intro',''); ?></p>
<div id="contact_form">
  <form name="formmail" method="post" action="04_send_offer.php">
  <?php if ($aow_section !== '') { echo '<input type="hidden" name="section" value="' . htmlspecialchars($aow_section, ENT_QUOTES, 'UTF-8') . '" />'; } ?>
  <?php
  // M3: prefill famiglia dalla CTA della scheda annuncio (?macro=), whitelistato.
  $aow_pref_macro = trim($_GET['macro'] ?? '');
  if ($aow_pref_macro !== '' && class_exists('ProductMacro') && ProductMacro::exists($aow_pref_macro)) {
      echo '<input type="hidden" name="macro" value="' . htmlspecialchars($aow_pref_macro, ENT_QUOTES, 'UTF-8') . '" />';
  }
  ?>
 <?php echo csrf_generate(); ?>
 <!-- start antispam -->
 <input name="test" type="text" style="display:none;" />
 <input type="hidden" name="momento_del_caricamento" value="<?php echo time(); ?>" />
 <input type="text" name="test" style="display:none" autocomplete="off">
 <!-- end antispam -->
 <div>
<label for="author">
<table width="100%" border="0">
  <tbody>
    <tr>
      <td width="10%"><?php te('contact.name','Name'); ?>:</td>
      <td width="90%"> <input type="text" id="author" name="author" class="required input_field" required /></td>
    </tr>
  </tbody>
</table>
</label>
 </div>
 <div>
<label for="email">
	 <table width="100%" border="0">
  <tbody>
    <tr>
      <td width="10%"><?php te('contact.email','Email'); ?>:</td>
      <td width="90%"><input type="email" id="email" name="email" class="validate-email required input_field" required /></td>
    </tr>
  </tbody>
</table>
</label>
 </div>
 <div>
  <div class="cleaner h10"></div>
<label for="object">
	 <table width="100%" border="0">
  <tbody>
    <tr>
      <td width="10%"><?php te('contact.object','Object'); ?>:</td>
      <td width="90%"><input type="text" id="object" name="object" class="required input_field" required /></td>
    </tr>
  </tbody>
</table>
</label>
 </div>
 <div class="cleaner h20"></div>
<?php if (!empty($aow_rfq_cats['regular'])): ?>
 <h3><?php te('rfq.body_types','Vehicle body types'); ?></h3>
 <p>Select the categories you want to receive offers for:</p>
 <table width="100%" border="0" cellpadding="6" cellspacing="0" class="tbl_collapse">
   <thead>
   <tr class="thead_row">
     <th width="5%">&nbsp;</th>
     <th align="left"><p>Type</p></th>
   </tr>
   </thead>
   <tbody>
	   
   <?php foreach ($aow_rfq_cats['regular'] as $key => $label): ?>
   <tr class="row_sep">
     <td align="center"><input type="checkbox" name="product[<?php echo $key; ?>]" value="1" /></td>
     <td><?php echo htmlspecialchars(tcat($key, $label)); ?></td>
   </tr>
   <?php endforeach; ?>
   </tbody>
 </table>
<?php endif; ?>

 <div class="cleaner h20"></div>
<?php if (!empty($aow_rfq_cats['special'])): ?>
 <h3><?php te('rfq.special_cats','Special categories'); ?></h3>
 <p>Select the special categories you want to receive offers for:</p>
 <table width="100%" border="0" cellpadding="6" cellspacing="0" class="tbl_collapse">
   <thead>
   <tr class="thead_row">
     <th width="5%">&nbsp;</th>
     <th align="left">Category</th>
   </tr>
   </thead>
   <tbody>
   <?php foreach ($aow_rfq_cats['special'] as $key => $label): ?>
   <tr class="row_sep">
     <td align="center"><input type="checkbox" name="product_special[<?php echo $key; ?>]" value="1" /></td>
     <td style="text-align: left"><?php echo htmlspecialchars(tcat($key, $label)); ?></td>
   </tr>
   <?php endforeach; ?>
   </tbody>
 </table>
<?php endif; ?>

 <?php if ($is_premium): ?>
 <div class="cleaner h20"></div>
 <h3><?php te('rfq.tech_title','Technical configurator'); ?></h3>
 <p><?php te('rfq.tech_intro','Specify the technical requirements for your premium request (same fields as a premium listing):'); ?></p>
 <?php $mode = 'form'; $tech = []; include __DIR__ . '/../shared/tech_details_fields.php'; ?>
 <?php endif; ?>
 <div class="cleaner h20"></div>
 <label for="msg"><?php te('contact.message','Message'); ?>:</label>
 <textarea id="msg" name="msg" rows="1" class="required" required></textarea>
 <div class="cleaner h20"></div>
   <input type="checkbox" name="consent_share" value="1" required />
   <?php te('rfq.consent',''); ?>
 <div class="cleaner h20"></div>
	  	 <div class="cleaner h20"></div>
			 <input type="reset" class="submit_btn float_l" name="reset" id="reset" value="<?php te('contact.reset','Reset'); ?>" />
			 <button type="submit" name="submit" id="submit" value="<?php te('rfq.send','Send request'); ?>" class="more float_r"><?php te('rfq.send','Send request'); ?></button>
	  <div class="cleaner"></div>
</form>
</div>
</div> <!-- end of content -->
<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
<div class="cleaner"></div>
<!-- inizia qui il piè di pagina -->
<?php include "../footer.php"; ?>
<!-- finisce qui il piè di pagina -->
</body>
</html>
