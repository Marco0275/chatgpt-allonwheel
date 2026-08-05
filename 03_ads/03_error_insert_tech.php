<?php
// ============================================================
// 03_ads/03_error_insert_tech.php - Ripresentazione form scheda tecnica dopo errore.
// Uniformato a session_helper (24 lug 2026): autenticazione via
// require_user_logged_in(), come gli altri step del wizard, al posto del
// vecchio gate manuale su $_SESSION['session_id'].
// ============================================================
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';

$id_user = require_user_logged_in();

// id dell'annuncio premium, impostato dallo step base (03_01_upload_advertising)
$id_ads = isset($_SESSION['id_ads']) ? (int)$_SESSION['id_ads'] : 0;
if ($id_ads <= 0) {
  $_SESSION['error_message'] = 'Session expired. Please start over.';
  header('Location: ' . BASE_URL . '/03_ads/03_insert_ad.php');
  exit;
}

// La scheda tecnica appartiene a un annuncio 03_ads dell'utente loggato.
$aow_own = $pdo->prepare('SELECT id_ads FROM `03_ads` WHERE id_ads = :a AND id_user = :u LIMIT 1');
$aow_own->execute([':a' => $id_ads, ':u' => $id_user]);
if (!$aow_own->fetch()) {
  $_SESSION['error_message'] = 'Ad not found or access denied.';
  header('Location: ' . BASE_URL . '/01_login/my_posts.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Insert tech details</title>
<meta name="keywords" content="All on Wheel Ltd - Insert tech details" />
<meta name="description" content="All on Wheel Ltd - Insert tech details" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<link rel="icon" href="../images/favicon.ico" />

<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />

<script type="text/javascript" src="../js/jquery.min.js" defer></script>
<script type="text/javascript" src="../js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="../js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper">
<div id="templatemo_header">
 <?php include ('../header.php'); ?>
</div>
<div id="content_top">
 <div id="page_title">Retry to insert tech details</div>
 <div id="search_box">
 <form action="<?php echo $base_url; ?>browse.php" method="get">
  <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
  <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
 </form>
 </div>
 <div class="cleaner"></div>
</div>
<div id="main"></div><div id="templatemo_content">
 <p>Something was wrong, please fill all details to have more chances to find  someone interested.</p>
 <div id="contact_form">
 <?php require_once __DIR__ . '/../config/csrf.php'; ?>
 <form method="post" action="03_01_upload_tech_advertising.php" id="submit_advertising">
  <?php echo csrf_generate_persistent(); ?>
 <table width="100%" border="0">
   <tr class="checkbox">
<td scope="col">Number of cars:
  <select name="cars" id="cars">
  <option value="0">0</option>
  <option value="1">1</option>
  <option value="2">2</option>
  <option value="3">3</option>
  <option value="4">4</option>
  <option value="5">5</option>
  <option value="5+">5+</option>
  </select></td>
  </tr>
</table>
  <div class="cleaner h10"></div>
  <table width="100%" border="0">
  <tr>
 <td colspan="3" class="checkbox" scope="col"><strong>General options</strong></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Awning" name="Awning"
  width="one-third" value="1" />
 Awning&nbsp;</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Workshop" name="Workshop"
  width="one-third" value="1" />
 Workshop&nbsp;</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Belly" name="Belly"
  width="one-third" value="1" />
 Belly Lockers</span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Kitchen" name="Kitchen"
  width="one-third" value="1" />
 Kitchen&nbsp;</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Beds" name="Beds"
  width="one-third" value="1" />
 Beds</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Genset" name="Genset"
  width="one-third" value="1" />
 Genset</span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Bathroom" name="Bathroom"
  width="one-third" value="1" />
 Bathroom</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="SAT" name="SAT"
  width="one-third" value="1" />
 SAT </span></td>
 <td></td>
  </tr>
  </table>
  <div class="cleaner h10"></div>
  <table width="100%" border="0">
  <tr>
 <td colspan="3" scope="col"><span class="checkbox"><strong>Lift facilities</strong></span></td>
  </tr>
  <tr>
 <td class="checkbox"><span class="checkbox">Manufacturer:</span></td>
 <td colspan="2" align="center">&nbsp;</td>
  </tr>
  <tr>
 <td colspan="3"><span class="checkbox">
 <input name="Lift_manufactorer" type="text" class="required input_field" id="Lift_manufactorer" value="Pasino" />
 </span></td>
  </tr>
  </table>
  <table width="100%">
  <tr align="center">
 <td align="left"><span class="checkbox">Lift features</span></td>
 <td></td>
 <td></td>
  </tr>
  <tr>
 <td><span class="checkbox">Length:</span></td>
 <td><span class="checkbox">Width:</span></td>
 <td><span class="checkbox">Capacity:</span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input name="Lift_length" type="text" id="textfield7" value="" maxlength="6" />
 mm</span></td>
 <td><span class="checkbox">
 <input name="Lift_width" type="text" id="textfield8" value="" maxlength="6" />
 mm</span></td>
 <td><span class="checkbox">
 <select name="Lift_capacity" id="Lift_capacity">
	<option value="0 kg">No lift</option>
  <option value="500 kg">500 kg</option>
  <option value="1000 kg">1000 kg</option>
  <option value="1500 kg">1500 kg</option>
  <option value="2000 kg">2000 kg</option>
  <option value="2500 kg">2500 kg</option>
  <option value="3000 kg">3000 kg</option>
 </select>
 </span></td>
  </tr>
  <tr>
 <td></td>
 <td></td>
 <td></td>
  </tr>
  <tr>
 <td colspan="3"><span class="checkbox"><strong>Cargo Facilities</strong></span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="rails" name="rails"
  width="one-third" value="1" />
 Mounting rails on the floor</span></td>
 <td colspan="2"><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="LED" name="LED"
  width="one-third" value="1" />
 LED lights</span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="independent_entrance_cargo" name="independent_entrance_cargo"
  width="one-third" value="1" />
 Independent entrance from cargo</span></td>
 <td colspan="2"><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Fixing" name="Fixing"
  width="one-third" value="1" />
 Fixing points to strap cars</span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Cabinets" name="Cabinets"
  width="one-third" value="1" />
 Cabinets</span></td>
 <td colspan="2"><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Adjustable" name="Adjustable"
  width="one-third" value="1" />
 Adjustable second deck</span></td>
  </tr>
  </table>
  <div class="cleaner h10"></div>
  <table width="100%" border="0">
  <tr>
 <td colspan="3" scope="col"><span class="checkbox"><strong>Office furniture</strong></span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Workbenches" name="Workbenches"
  width="one-third" value="1" />
 Workbenches &nbsp;</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="HVAC" name="HVAC"
  width="one-third" value="1" />
 HVAC system</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Telemetry" name="Telemetry"
  width="one-third" value="1" />
 Telemetry socket</span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="independent_entrance_office" name="independent_entrance_office"
  width="one-third" value="1" />
 Independent entrance from office</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Electrical" name="Electrical"
  width="one-third" value="1" />
 Electrical system</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="office_other" name="office_other"
  width="one-third" value="1" />
 Other</span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Windows" name="Windows"
  width="one-third" value="1" />
 Windows</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="TV" name="TV"
  width="one-third" value="1" />
 TV </span></td>
 <td></td>
  </tr>
  </table>
  <div class="cleaner h10"></div>
  <table width="100%" border="0">
  <tr>
 <td colspan="3" scope="col"><span class="checkbox"><strong>Electrical system</strong></span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Main_panel" name="Main_panel"
  width="one-third" value="1" />
 Main panel&nbsp;</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="batteries" name="batteries"
  width="one-third" value="1" />
 2 x 180 [Ah] batteries</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Charger" name="Charger"
  width="one-third" value="1" />
 Batteries Charger</span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Connection" name="Connection"
  width="one-third" value="1" />
 Connection - 400V 32A </span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Switchgear" name="Switchgear"
  width="one-third" value="1" />
 Switchgear</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="electrical_other" name="electrical_other"
  width="one-third" value="1" />
 Other</span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Sockets" name="Sockets"
  width="one-third" value="1" />
 Sockets</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Rema" name="Rema"
  width="one-third" value="1" />
 Rema wire connector</span></td>
 <td></td>
  </tr>
  </table>
  <div class="cleaner h10"></div>
  <table width="100%" border="0">
  <tr>
 <td colspan="3" scope="col"><span class="checkbox"><strong>Outside finishing</strong></span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Plywood" name="Plywood"
  width="one-third" value="1" />
 Plywood walling</span></td>
 <td><span class="checkbox">Painted in color:</span></td>
 <td><span class="checkbox">
 <input name="painted" type="text" id="painted" value="" maxlength="50" />
 </span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Sandwich" name="Sandwich"
  width="one-third" value="1" />
 Sandwich Walling</span></td>
 <td><span class="checkbox">Stickers:</span></td>
 <td><span class="checkbox">
 <input name="Stickers" type="text" id="Stickers" value="" maxlength="50" />
 </span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Special" name="Special"
  width="one-third" value="1" />
 Special</span></td>
 <td></td>
 <td></td>
  </tr>
  </table>
  <div class="cleaner h10"></div>
  <table width="100%" border="0">
  <tr>
 <td colspan="3" scope="col"><span class="checkbox"><strong>Chassis</strong></span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Stepdeck" name="Stepdeck"
  width="one-third" value="1" />
 Stepdeck Frame</span></td>
 <td><span class="checkbox">Number of axles:</span></td>
 <td><span class="checkbox">
 <input name="axles" type="text" id="axles" value="1" maxlength="1" />
 </span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Straightline" name="Straightline"
  width="one-third" value="1" />
 Straightline frame</span></td>
 <td><span class="checkbox">Maximum Gross Weight:</span></td>
 <td><span class="checkbox">
 <input name="MGW" type="text" id="MGW" value="12345" maxlength="5" />
 Kg</span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="chassis_special" name="chassis_special"
  width="one-third" value="1" />
 Special</span></td>
 <td><span class="checkbox">Saddle height:</span></td>
 <td><span class="checkbox">
 <input name="Saddle" type="text" id="Saddle" value="12345" maxlength="5" />
 mm</span></td>
  </tr>
  </table>
  <div class="cleaner h10"></div>
  <table width="100%" border="0">
  <tr>
 <td colspan="3" scope="col"><span class="checkbox"><strong>External dimension</strong></span></td>
  </tr>
  <tr>
 <td></td>
 <td><span class="checkbox">Length:</span></td>
 <td><span class="checkbox">
 <input name="ext_length" type="text" id="ext_length" value="" maxlength="5" />
 mm</span></td>
  </tr>
  <tr>
 <td></td>
 <td><span class="checkbox">Width:</span></td>
 <td><span class="checkbox">
 <input name="ext_width" type="text" id="ext_width" value="" maxlength="5" />
 mm</span></td>
  </tr>
  <tr>
 <td></td>
 <td><span class="checkbox">Height:</span></td>
 <td><span class="checkbox">
 <input name="ext_height" type="text" id="ext_height" value="" maxlength="5" />
 mm</span></td>
  </tr>
  </table>
  <div class="cleaner h20"></div>
  <input type="submit" class="submit_btn float_r" name="submit" id="submit" value="Insert" />
 </form>
 </div>
	 </div>
<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
<div class="cleaner"></div>
<div>
 <?php include ('../footer.php'); ?>
</div>
</body>
</html>