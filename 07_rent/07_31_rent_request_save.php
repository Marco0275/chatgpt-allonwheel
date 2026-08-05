<?php
// 07_rent/07_31_rent_request_save.php -- Salva la richiesta, trova i destinatari
// (aziende con annunci di noleggio corrispondenti, per tier) e invia le notifiche.
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/antispam.php';
require_once __DIR__ . '/../includes/form_consent.php';
require_once __DIR__ . '/../libs/rent.class.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';

$id_user = require_user_logged_in();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /07_rent/07_30_rent_request.php'); exit; }
csrf_verify();
if (aow_is_spam(trim($_POST['description'] ?? ''))) { $_SESSION['error_message'] = 'Spam detected.'; header('Location: /07_rent/07_30_rent_request.php'); exit; }
if (!aow_privacy_consent_ok()) { $_SESSION['error_message'] = 'Please accept the privacy policy.'; header('Location: /07_rent/07_30_rent_request.php'); exit; }
aow_log_form_consent($pdo, 'rent_request');

$valid_types = array_column(VehicleTaxonomy::typesForCategory('special', $pdo), 'slug');
$sel = array_values(array_intersect((array)($_POST['vt'] ?? []), $valid_types));
$descr = trim($_POST['description'] ?? '');

if (empty($sel) || $descr === '') {
    $_SESSION['error_message'] = 'Select at least one vehicle type and add a description.';
    header('Location: /07_rent/07_30_rent_request.php'); exit;
}

$budget = ($_POST['budget'] ?? '') !== '' ? (float)str_replace(',', '.', (string)$_POST['budget']) : null;
$country = strtoupper(trim($_POST['country_code'] ?? '')) ?: null;
$from = trim($_POST['rent_from'] ?? '') ?: null;
$to   = trim($_POST['rent_to'] ?? '') ?: null;

$rent = new RentAds($pdo);
$req_id = $rent->createRequest($id_user, $sel, $budget, $country, $descr, $from, $to);

// Matching + notifica (gold/premium sempre via email, free entro il tetto).
$recipients = $rent->matchCompanies($sel, $id_user);
$sent = $rent->notifyCompanies(['id' => $req_id, 'title' => 'Rental request'], $recipients);
if (!empty($recipients)) { $rent->markDistributed($req_id); }

$_SESSION['success_message'] = 'Your rental request has been sent'
    . (count($recipients) > 0 ? ' to ' . count($recipients) . ' matching provider(s).' : '. No matching provider yet, we saved your request.');
header('Location: /07_rent/07_20_rent_list.php');
exit;
