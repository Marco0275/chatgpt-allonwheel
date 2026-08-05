<?php
session_start();
require_once __DIR__ . '/../config/database.php'; // include connessione $pdo

// id annuncio da sessione o GET (da adattare)
$id_ads = $_SESSION['id_ads'] ?? 0;

// Recupera l'id annuncio da GET o SESSION
$id_ads = isset($_GET['id_ads']) && is_numeric($_GET['id_ads']) ? (int)$_GET['id_ads'] : ($_SESSION['id_ads'] ?? null);
if (!$id_ads) {
 header('Location: 03_view_ads.php'); exit;
}

// Query per leggere tutti i dettagli tecnici
$stmt = $pdo->prepare("SELECT * FROM `03_ads_tech_details` WHERE id_ads = ?");
$stmt->execute([$id_ads]);
$tech = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tech) {
  echo "No technical detail about this ad.";
  exit;
}

// Funzione helper per checkbox checked
function checked($val) {
  return $val == 1 ? 'checked="checked"' : '';
}

// Funzione helper per select option selected
function selected($field, $value) {
  return ($field == $value) ? 'selected="selected"' : '';
}

?>
<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Preview tech details</title>
<meta name="keywords" content="All on Wheel Ltd - Preview tech details" />
<meta name="description" content="All on Wheel Ltd - Preview tech details" />
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
  <div id="page_title">Tech details</div>
  <div id="search_box">
    <form action="<?php echo $base_url; ?>browse.php" method="get">
    <input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
    <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
    </form>
  </div>
  <div class="cleaner"></div>
</div>
<div id="main"></div><div id="templatemo_content">
  <div id="contact_form" class="tech_view">
  <table width="100%" border="0">
   <tr class="checkbox">
   <td scope="col">Number of cars:
   <?= htmlspecialchars($tech['cars']) ?>
   </td>
   </tr>
 </table>
 <table width="100%" border="0">
  <tr>
   <td colspan="3" class="checkbox" scope="col"><strong>General options</strong></td>
  </tr>
  <tr>
   <td><span class="checkbox">
   <input type="checkbox" class="control control--checkbox" id="Awning" name="Awning" value="1" <?=checked($tech['Awning'] ?? 0)?> />
   Awning&nbsp;</span></td>
   <td><span class="checkbox">
   <input type="checkbox" class="control control--checkbox" id="Workshop" name="Workshop" value="1" <?=checked($tech['Workshop'] ?? 0)?> />
   Workshop&nbsp;</span></td>
   <td><span class="checkbox">
   <input type="checkbox" class="control control--checkbox" id="Belly" name="Belly" value="1" <?=checked($tech['Belly'] ?? 0)?> />
   Belly Lockers</span></td>
  </tr>
  <tr>
   <td><span class="checkbox">
   <input type="checkbox" class="control control--checkbox" id="Kitchen" name="Kitchen" value="1" <?=checked($tech['Kitchen'] ?? 0)?> />
   Kitchen&nbsp;</span></td>
   <td><span class="checkbox">
   <input type="checkbox" class="control control--checkbox" id="Beds" name="Beds" value="1" <?=checked($tech['Beds'] ?? 0)?> />
   Beds</span></td>
   <td><span class="checkbox">
   <input type="checkbox" class="control control--checkbox" id="Genset" name="Genset" value="1" <?=checked($tech['Genset'] ?? 0)?> />
   Genset</span></td>
  </tr>
  <tr>
   <td><span class="checkbox">
   <input type="checkbox" class="control control--checkbox" id="Bathroom" name="Bathroom" value="1" <?=checked($tech['Bathroom'] ?? 0)?> />
   Bathroom</span></td>
   <td><span class="checkbox">
   <input type="checkbox" class="control control--checkbox" id="SAT" name="SAT" value="1" <?=checked($tech['SAT'] ?? 0)?> />
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
 <?= htmlspecialchars($tech['Lift_manufactorer'] ?? '') ?>
 </span></td>
  </tr>
  </table>
  <table width="100%">
	  <div class="cleaner h10"></div>
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
 <?= htmlspecialchars($tech['Lift_length'] ?? '') ?>
 mm</span></td>
 <td><span class="checkbox">
 <?= htmlspecialchars($tech['Lift_width'] ?? '') ?>
 mm</span></td>
 <td><span class="checkbox">
	<?= htmlspecialchars($tech['Lift_capacity']) ?>
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
  width="one-third" value="1" <?=checked($tech['rails'] ?? 0)?> />
 Mounting rails on the floor</span></td>
 <td colspan="2"><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="LED" name="LED"
  width="one-third" value="1" <?=checked($tech['LED'] ?? 0)?> />
 LED lights</span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="independent_entrance_cargo" name="independent_entrance_cargo"
  width="one-third" value="1" <?=checked($tech['independent_entrance_cargo'] ?? 0)?> />
 Independent entrance from cargo</span></td>
 <td colspan="2"><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Fixing" name="Fixing"
  width="one-third" value="1" <?=checked($tech['Fixing'] ?? 0)?> />
 Fixing points to strap cars</span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Cabinets" name="Cabinets"
  width="one-third" value="1" <?=checked($tech['Cabinets'] ?? 0)?> />
 Cabinets</span></td>
 <td colspan="2"><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Adjustable" name="Adjustable"
  width="one-third" value="1" <?=checked($tech['Adjustable'] ?? 0)?> />
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
  width="one-third" value="1" <?=checked($tech['Workbenches'] ?? 0)?> />
 Workbenches &nbsp;</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="HVAC" name="HVAC"
  width="one-third" value="1" <?=checked($tech['HVAC'] ?? 0)?> />
 HVAC system</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Telemetry" name="Telemetry"
  width="one-third" value="1" <?=checked($tech['Telemetry'] ?? 0)?> />
 Telemetry socket</span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="independent_entrance_office" name="independent_entrance_office"
  width="one-third" value="1" <?=checked($tech['independent_entrance_office'] ?? 0)?> />
 Independent entrance from office</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Electrical" name="Electrical"
  width="one-third" value="1" <?=checked($tech['Electrical'] ?? 0)?> />
 Electrical system</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="office_other" name="office_other"
  width="one-third" value="1" <?=checked($tech['office_other'] ?? 0)?> />
 Other</span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Windows" name="Windows"
  width="one-third" value="1" <?=checked($tech['Windows'] ?? 0)?> />
 Windows</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="TV" name="TV"
  width="one-third" value="1" <?=checked($tech['TV'] ?? 0)?> />
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
  width="one-third" value="1" <?=checked($tech['Main_panel'] ?? 0)?> />
 Main panel&nbsp;</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="batteries" name="batteries"
  width="one-third" value="1" <?=checked($tech['batteries'] ?? 0)?> />
 2 x 180 [Ah] batteries</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Charger" name="Charger"
  width="one-third" value="1" <?=checked($tech['Charger'] ?? 0)?> /> <!-- FIX: era $tech['SAT'] -->
 Batteries Charger</span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Connection" name="Connection"
  width="one-third" value="1" <?=checked($tech['Connection'] ?? 0)?> />
 Connection - 400V 32A </span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Switchgear" name="Switchgear"
  width="one-third" value="1" <?=checked($tech['Switchgear'] ?? 0)?> />
 Switchgear</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="electrical_other" name="electrical_other"
  width="one-third" value="1" <?=checked($tech['electrical_other'] ?? 0)?> />
 Other</span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Sockets" name="Sockets"
  width="one-third" value="1" <?=checked($tech['Sockets'] ?? 0)?> />
 Sockets</span></td>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Rema" name="Rema"
  width="one-third" value="1" <?=checked($tech['Rema'] ?? 0)?> />
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
  width="one-third" value="1" <?=checked($tech['Plywood'] ?? 0)?> />
 Plywood walling</span></td>
 <td><span class="checkbox">Painted in color:</span></td>
 <td><span class="checkbox">
 <?= htmlspecialchars($tech['painted'] ?? '') ?>
 </span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Sandwich" name="Sandwich"
  width="one-third" value="1" <?=checked($tech['Sandwich'] ?? 0)?> />
 Sandwich Walling</span></td>
 <td><span class="checkbox">Stickers:</span></td>
 <td><span class="checkbox">
 <?= htmlspecialchars($tech['Stickers'] ?? '') ?>
 </span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Special" name="Special"
  width="one-third" value="1" <?=checked($tech['Special'] ?? 0)?> />
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
  width="one-third" value="1" <?=checked($tech['Stepdeck'] ?? 0)?> />
 Stepdeck Frame</span></td>
 <td><span class="checkbox">Number of axles:</span></td>
 <td><span class="checkbox">
 <?= htmlspecialchars($tech['axles'] ?? '') ?>
 </span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="Straightline" name="Straightline"
  width="one-third" value="1" <?=checked($tech['Straightline'] ?? 0)?> />
 Straightline frame</span></td>
 <td><span class="checkbox">Maximum Gross Weight:</span></td>
 <td><span class="checkbox">
 <?= htmlspecialchars($tech['MGW'] ?? '') ?>
 Kg</span></td>
  </tr>
  <tr>
 <td><span class="checkbox">
 <input type="checkbox"
 class="control control--checkbox"id="chassis_special" name="chassis_special"
  width="one-third" value="1" <?=checked($tech['chassis_special'] ?? 0)?> />
 Special</span></td>
 <td><span class="checkbox">Saddle height:</span></td>
 <td><span class="checkbox">
 <?= htmlspecialchars($tech['Saddle'] ?? '') ?>
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
 <?= htmlspecialchars($tech['ext_length'] ?? '') ?>
 mm</span></td>
  </tr>
  <tr>
 <td></td>
 <td><span class="checkbox">Width:</span></td>
 <td><span class="checkbox">
 <?= htmlspecialchars($tech['ext_width'] ?? '') ?>
 mm</span></td>
  </tr>
  <tr>
 <td></td>
 <td><span class="checkbox">Height:</span></td>
 <td><span class="checkbox">
 <?= htmlspecialchars($tech['ext_height'] ?? '') ?>
 mm</span></td>
  </tr>
  </table>
  <div class="cleaner h20"></div>
<a class="more float_r" href="03_view_ad.php?id_ads=<?php echo $id_ads; ?>">Back</a>
	<br></div>
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