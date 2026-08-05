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
// Le voci da spuntare arrivano dalle TABELLE DI RIFERIMENTO della categoria
// scelta, non piu' da elenchi scritti nel codice:
//   road    -> vehicle_types  (lista del codice della strada)
//   special -> special_types  (lista curata dall'admin)
//   shelter -> special_types  (stessa lista: stesso allestimento su container)
// Cosi' dopo la scelta in pagina non resta NULLA delle altre due sezioni.
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';
$aow_rfq_labels = ['road'=>'Road vehicles','special'=>'Special vehicles','shelter'=>'Shelter & Container'];
$aow_rfq_label  = ($aow_section !== '') ? ($aow_rfq_labels[$aow_section] ?? '') : '';
// Finche' non si sceglie, nessun elenco: prima la categoria, poi le sue voci.
$aow_rfq_types  = ($aow_section !== '') ? VehicleTaxonomy::typesForCategory($aow_section, $pdo) : [];
// Road -> canale product[] (prodotti regolari del fornitore);
// special/shelter -> product_special[]: stessa divisione con cui i fornitori
// hanno dichiarato i propri prodotti.
$aow_rfq_field = ($aow_section === 'road') ? 'product' : 'product_special';
// Menu a tendina "uno per famiglia" (dir. Marco): elenchi dal DB.
$aow_fam_types = [
  'road'    => VehicleTaxonomy::typesForCategory('road', $pdo),
  'special' => VehicleTaxonomy::typesForCategory('special', $pdo),
  'shelter' => VehicleTaxonomy::typesForCategory('shelter', $pdo),
];
$aow_fam_field = ['road' => 'product', 'special' => 'product_special', 'shelter' => 'product_special'];
// Tier dell'utente: premium -> configuratore tecnico (cartella 03); free -> base (cartella 02)
$rfq_uid = (int)($_SESSION['user_id'] ?? 0);
$is_premium = false;
if ($rfq_uid > 0 && isset($pdo)) {
    $rfq_tier = UserTier::getTier($pdo, $rfq_uid);
    $is_premium = ($rfq_tier === 'premium' || $rfq_tier === 'admin');
}
// Prefill "Object" dalla CTA di landing/blog (?intent=), whitelistato: la RFQ
// eredita l'intento del visitatore (studio di fattibilita' / preventivo).
$aow_intent_labels = [
  'feasibility_study' => 'Feasibility study request',
  'custom_quote'      => 'Custom quote request',
  'question'          => 'General question',
];
$aow_intent         = trim((string)($_GET['intent'] ?? ''));
$aow_object_prefill = $aow_intent_labels[$aow_intent] ?? '';
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
  <?php
  // ---------------------------------------------------------------
  // SCELTA GUIDATA (24 lug 2026): stessa gerarchia del wizard annunci.
  // Prima si sceglie la CATEGORIA, e la scelta determina quali voci
  // compaiono sotto: in pagina non restano mai categorie di un'altra
  // famiglia.
  //
  // ROAD    Veicoli stradali di uso comune (ambulanze, cassoni, frigoriferi,
  //         minibus, scuolabus, carri attrezzi...).
  // SPECIAL Allestimenti speciali, definiti dall'amministratore
  //         (race trailer, hospitality, paddock, uffici e laboratori mobili).
  // SHELTER Le stesse funzioni degli Special, ma costruite SU CONTAINER:
  //         strutture, non mezzi su ruote.
  // ---------------------------------------------------------------
  $aow_sec_defs = [
    'road'    => ['Road vehicles',       'Vehicles you meet on the road every day.'],
    'special' => ['Special vehicles',    'Special builds for racing market or mobile clinic, etc.'],
    'shelter' => ['Shelter & Container', 'The same as a special vehicle, but built on a container.'],
  ];
  ?>
  <h3><?php te('rfq.choose_cat', '1. What are you looking for?'); ?></h3>
  <p><?php te('rfq.choose_cat_help', 'Pick a category: the list below shows only what belongs to it.'); ?></p>
  <div class="rfq_families">
    <?php foreach ($aow_sec_defs as $sk => $sv): ?>
    <div class="rfq_family form_row" style="margin-bottom:14px">
      <p><strong><?php echo htmlspecialchars($sv[0], ENT_QUOTES, 'UTF-8'); ?></strong> &mdash; <?php echo htmlspecialchars($sv[1], ENT_QUOTES, 'UTF-8'); ?></p>
      <?php $aow_ft = $aow_fam_types[$sk] ?? []; $aow_ff = $aow_fam_field[$sk] ?? 'product_special'; ?>
      <?php if (empty($aow_ft)): ?>
        <p><em><?php te('rfq.no_types','No types available yet.'); ?></em></p>
      <?php else: ?>
        <select name="<?php echo $aow_ff; ?>[]" class="input_field">
          <option value="">-- <?php echo htmlspecialchars($sv[0], ENT_QUOTES, 'UTF-8'); ?>: <?php te('rfq.choose_type','choose a type'); ?> --</option>
          <?php foreach ($aow_ft as $aow_t): ?>
          <option value="<?php echo htmlspecialchars($aow_t['slug'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(tcat($aow_t['slug'], $aow_t['name'])); ?></option>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="cleaner"></div>

  <?php if ($aow_section !== '') { echo '<input type="hidden" name="section" value="' . htmlspecialchars($aow_section, ENT_QUOTES, 'UTF-8') . '" />'; } ?>
  <?php
  // M3: prefill famiglia dalla CTA della scheda annuncio (?macro=), whitelistato.
  $aow_pref_macro = trim($_GET['macro'] ?? '');
  if ($aow_pref_macro !== '' && class_exists('ProductMacro') && ProductMacro::exists($aow_pref_macro)) {
      echo '<input type="hidden" name="macro" value="' . htmlspecialchars($aow_pref_macro, ENT_QUOTES, 'UTF-8') . '" />';
  }
  ?>
  <?php if ($aow_object_prefill !== '') { echo '<input type="hidden" name="intent" value="' . htmlspecialchars($aow_intent, ENT_QUOTES, 'UTF-8') . '" />'; } ?>
 <?php echo csrf_generate(); ?>
 <?php require_once __DIR__ . '/../libs/antispam.php'; echo aow_spam_fields(); ?>
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
      <td width="90%"><input type="text" id="object" name="object" value="<?php echo htmlspecialchars($aow_object_prefill, ENT_QUOTES, 'UTF-8'); ?>" class="required input_field" required /></td>
    </tr>
  </tbody>
</table>
</label>
 </div>
 <div class="cleaner h20"></div>
<!-- I tipi si scelgono dai menu a tendina qui sopra (uno per famiglia). -->

 <?php if ($is_premium && $aow_section !== ''): ?>
 <div class="cleaner h20"></div>
 <h3><?php te('rfq.tech_title','Technical configurator'); ?></h3>
 <p><?php te('rfq.tech_intro','Specify the technical requirements for your premium request (same fields as a premium listing).'); ?></p>
 <?php
   // I campi tecnici seguono la CATEGORIA scelta: uno shelter non ha telaio
   // ne' sponda idraulica, un veicolo stradale non ha veranda ne' telemetria.
   // Il filtro lo applica shared/tech_details_fields.php leggendo
   // $aow_tech_section: in pagina non compare nulla delle altre sezioni.
   $mode = 'form'; $tech = [];
   $aow_tech_section = $aow_section;
   include __DIR__ . '/../shared/tech_details_fields.php';
 ?>
 <?php endif; ?>

 <div class="cleaner h20"></div>
 <label for="msg"><?php te('contact.message','Message'); ?>:</label>
 <textarea id="msg" name="msg" rows="1" class="required" required></textarea>
 <div class="cleaner h20"></div>
   <input type="checkbox" name="consent_share" value="1" required />
   <?php require_once __DIR__ . '/../includes/form_consent.php'; echo aow_privacy_consent_field(); ?>
 <div class="cleaner h20"></div>
	  	 <div class="cleaner h20"></div>
	  			 <input type="submit" name="submit" id="submit" value="<?php te('rfq.send','Send request'); ?>" class="more float_r"></input>
			 

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
