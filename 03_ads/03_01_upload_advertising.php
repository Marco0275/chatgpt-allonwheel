<?php
// ============================================================
// 03_ads/03_01_upload_advertising.php
// Inserisce un nuovo annuncio gratuito.
//

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';
require_once __DIR__ . '/../libs/product_macro.class.php';

$id_user = require_user_logged_in();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $_SESSION['error_message'] = 'Invalid request: this page must be reached by submitting the ad form.';
  header('Location: 03_error_insert_ad.php');
  exit;
}

csrf_verify_persistent();

// ============================================================
// TIER CHECK — limite 5 annunci free
// ============================================================
$aow_lt  = ((($_SESSION['ad_wizard']['module'] ?? '02')) === '03') ? 'prem' : 'free';
$aow_tbl = ($aow_lt === 'prem') ? '03_ads' : '03_ads';
$check = ($aow_lt === 'prem')
  ? UserTier::canInsertPremiumAd($pdo, $id_user)
  : UserTier::canInsertFreeAd($pdo, $id_user);
if (!$check['allowed']) {
  $_SESSION['error_message'] = $check['reason'];
  header('Location: ' . BASE_URL . '/01_login/my_posts.php');
  exit;
}

// ============================================================
// Dati identita' presi da DB (canonico) — non dal POST (no spoofing)
// ============================================================
$stmt = $pdo->prepare('SELECT username, email, phone FROM users WHERE id_user = :id LIMIT 1');
$stmt->execute([':id' => $id_user]);
$user_row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user_row) {
  $_SESSION['error_message'] = 'User session invalid. Please log in again.';
  header('Location: ' . BASE_URL . '/01_login/newlogin.php');
  exit;
}
$author = (string)$user_row['username'];
$email  = (string)$user_row['email'];
$phone  = (string)$user_row['phone'];

// ============================================================
// Dati annuncio dal form
// ============================================================
$title   = trim($_POST['title']   ?? '');
$subtitle  = trim($_POST['subtitle']  ?? '');
$type    = trim($_POST['type']    ?? '');
$conditions  = trim($_POST['conditions']  ?? '');
$description = trim($_POST['description'] ?? '');

$raw_price = trim((string)($_POST['list_price'] ?? ''));
if ($raw_price === '') {
  $list_price = 0.0;                       // prezzo non indicato => 0
} else {
  $normalized = $raw_price;
  if (strpos($normalized, ',') !== false) {
    $normalized = str_replace('.', '', $normalized); // separatore migliaia
    $normalized = str_replace(',', '.', $normalized); // virgola decimale -> punto
  }
  $list_price = filter_var($normalized, FILTER_VALIDATE_FLOAT);
}

// --- Validazione con messaggi ESPLICITI (mostrati nella pagina di errore) --
$validation_error = '';
if ($title === '') {
  $validation_error = 'Title is required: please enter a title for your ad.';
} elseif ($description === '') {
  $validation_error = 'Description is required: please describe your ad.';
} elseif ($list_price === false) {
  $validation_error = 'Invalid list price: enter digits only, e.g. 1500 or 1500.50 (or leave it empty).';
} elseif ($list_price < 0) {
  $validation_error = 'Invalid list price: the price cannot be negative.';
}

if ($validation_error !== '') {
  $_SESSION['error_message'] = $validation_error;
  header('Location: 03_error_insert_ad.php');
  exit;
}

$allowed_types  = ['New on sell', 'Used on sell', 'For rent', 'Project'];
$allowed_conditions = ['New', 'As good as new', 'Used', 'Poor', 'Project'];
if (!in_array($type, $allowed_types, true))     $type = 'New on sell';
if (!in_array($conditions, $allowed_conditions, true)) $conditions = 'New';

$image_original  = 'no_image.jpg';
$image_thumbnail = 'no_image.jpg';

$checkbox_fields = ['racing', 'promotion', 'horse', 'hospitality', 'medical',
        'military', 'motorhome', 'technology', 'street_food'];
$checkboxValues = [];
foreach ($checkbox_fields as $chk) {
  $checkboxValues[$chk] = isset($_POST[$chk]) ? 1 : 0;
}

// ============================================================
// Classificazione strutturata (wizard 00_select_type) — flowchart, dir. 18
// ============================================================
$item_kind = ($_POST['item_kind'] ?? '') === VehicleTaxonomy::KIND_SHELTER
  ? VehicleTaxonomy::KIND_SHELTER : VehicleTaxonomy::KIND_VEHICLE;
$macro_category = ($_POST['macro_category'] ?? '') === VehicleTaxonomy::MACRO_ROAD
  ? VehicleTaxonomy::MACRO_ROAD : VehicleTaxonomy::MACRO_SPECIAL;
$vehicle_type = trim((string)($_POST['vehicle_type'] ?? ''));

if ($item_kind === VehicleTaxonomy::KIND_SHELTER) {
  // Shelter/Container e' sempre Special, con tipo dedicato
  $macro_category = VehicleTaxonomy::MACRO_SPECIAL;
  $vehicle_type   = VehicleTaxonomy::SHELTER_SLUG;
} elseif (!VehicleTaxonomy::isValidType($vehicle_type, $macro_category, $pdo)) {
  // Tipo veicolo incoerente con la macro: rimanda al wizard di scelta
  $_SESSION['error_message'] = 'Please classify your ad before saving.';
  header('Location: 03_00_select_type.php');
  exit;
}

// Retro-compatibilita' browse: mappa il tipo sui flag booleani storici
// (best-effort, solo corrispondenze univoche; gli altri restano 0).
$slug_to_bool = [
  'autonegozi_alimentari'    => 'street_food',
  'camper'                   => 'motorhome',
  'ambulanze'                => 'medical',
  'laboratori_medici_mobili' => 'medical',
  'forze_dell_ordine'        => 'military',
  'blindati'                 => 'military',
];
if (isset($slug_to_bool[$vehicle_type], $checkboxValues[$slug_to_bool[$vehicle_type]])) {
  $checkboxValues[$slug_to_bool[$vehicle_type]] = 1;
}

// Overlay macro motorsport (Fase 1): coerente con il backfill di product_macros.sql.
$product_macro = ProductMacro::forAd([
  'item_kind'    => $item_kind,
  'vehicle_type' => $vehicle_type,
  'type'         => $type,
  'conditions'   => $conditions,
  'racing'       => $checkboxValues['racing'],
  'hospitality'  => $checkboxValues['hospitality'],
  'medical'      => $checkboxValues['medical'],
]);

$sql = 'INSERT INTO `' . $aow_tbl . '`
  (id_user, author, email, phone, title, subtitle, list_price, type, conditions, description,
   item_kind, macro_category, vehicle_type, product_macro, image_original, image_thumbnail,
   status,
   expires_at)
  VALUES
  (:id_user, :author, :email, :phone, :title, :subtitle, :list_price, :type, :conditions, :description,
   :item_kind, :macro_category, :vehicle_type, :product_macro,
   :image_original, :image_thumbnail,
   :status,
   DATE_ADD(NOW(), INTERVAL 45 DAY))';

// Moderazione (config/app_settings.php): se attiva, l'annuncio nasce 'pending'
// e resta invisibile finche' un admin non lo approva. Altrimenti 'approved'
// (pubblico subito), comportamento storico.
$aow_status = (defined('AOW_MODERATION_REQUIRED') && AOW_MODERATION_REQUIRED) ? 'pending' : 'approved';

try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':id_user'   => $id_user,
    ':status'    => $aow_status,
    ':author'    => $author,
    ':email'     => $email,
    ':phone'     => $phone,
    ':title'     => $title,
    ':subtitle'    => $subtitle,
    ':list_price'  => $list_price,
    ':type'    => $type,
    ':conditions'  => $conditions,
    ':description'   => $description,
    ':item_kind'     => $item_kind,
    ':macro_category'  => $macro_category,
    ':vehicle_type'  => $vehicle_type,
    ':product_macro' => $product_macro,
    ':image_original'  => $image_original,
    ':image_thumbnail' => $image_thumbnail,
  ]);
} catch (PDOException $e) {
  // Log completo per il debug (lato server), messaggio generico per l'utente
  // (dir. 11: non esporre dettagli SQL/struttura DB).
  error_log('[Allonwheel] Insert free ad error: ' . $e->getMessage());
  $_SESSION['error_message'] = 'A database error prevented your ad from being saved. Please try again; if it keeps happening, contact support.';
  header('Location: 03_error_insert_ad.php');
  exit;
}

$lastId = $pdo->lastInsertId();
if ($lastId) {
  $_SESSION['id_ads'] = $lastId;
  $_SESSION['aow_listing'] = $aow_lt; // il resto del wizard (foto/gallery) usa questo

  // Se moderato, l'utente deve sapere che l'annuncio NON e' ancora pubblico:
  // non gli si fa credere che sia gia' online.
  if ($aow_status === 'pending') {
    $_SESSION['success_message'] = 'Your listing has been submitted and is awaiting approval. It will appear publicly once a moderator approves it.';
  }

  // Punto 2 (17 lug 2026): se questo annuncio nasce da una bozza compilata
  // da ospite e ripristinata nel form (02_insert_ad prefill), ora e'
  // pubblicato per davvero -> la bozza ha esaurito il suo scopo, si cancella.
  // Additivo e non bloccante: un problema qui non deve fermare il wizard,
  // l'annuncio e' gia' salvato.
  if (!empty($_SESSION['ad_wizard']['draft_id'])) {
    try {
      require_once __DIR__ . '/../libs/ad_draft.class.php';
      AdDraft::delete($pdo, (int)$_SESSION['ad_wizard']['draft_id']);
    } catch (Throwable $e) {
      error_log('[Allonwheel] upload_advertising delete bozza: ' . $e->getMessage());
    }
  }

  // Punto 5 (17 lug 2026): avvisa i buyer con una wanted attiva compatibile.
  // Il buco: notifyBuyers scattava SOLO all'approvazione admin, ma gli annunci
  // nascono status='approved' e non passano dalla moderazione -> i buyer non
  // venivano mai avvisati. Qui parte alla PUBBLICAZIONE, cioe' sempre.
  // Ordina per pertinenza (vehicle_type esatto prima) e rispetta il tetto.
  // Additivo e non bloccante: un errore qui non deve fermare il wizard,
  // l'annuncio e' gia' salvato.
  // Notifica ai buyer SOLO se l'annuncio e' gia' pubblico. Con la moderazione
  // attiva ($aow_status='pending') non lo e': la notifica partira'
  // all'approvazione admin (moderate_ads.php), non qui.
  if ($aow_status === 'approved' && $product_macro !== null && $product_macro !== '') {
    try {
      require_once __DIR__ . '/../libs/wanted_ads.class.php';
      (new WantedAds($pdo))->notifyBuyers(
        (string)$product_macro, $aow_tbl, (int)$lastId, (int)$id_user,
        (string)$title, (string)$vehicle_type
      );
    } catch (Throwable $e) {
      error_log('[Allonwheel] notifyBuyers on publish: ' . $e->getMessage());
    }
  }

  unset($_SESSION['ad_wizard']); // classificazione consumata
  header('Location: 03_insert_ad_image.php');
  exit;
}

$_SESSION['error_message'] = 'The ad could not be saved: no record was created. Please try again.';

header('Location: 03_error_insert_ad.php');
exit;
