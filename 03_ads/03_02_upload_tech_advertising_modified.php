<?php
// ============================================================
// 03_ads/03_03_upload_tech_advertising_modified.php
// Aggiornamento dettagli tecnici annuncio premium (UPDATE).
//
// FIX rispetto alla versione precedente:
//  - Rimosso session_start() manuale e header Content-Type
//    (gestiti da bootstrap.php).
//  - Migrato a session_helper: require_user_logged_in() al
//    posto del controllo manuale su $_SESSION['session_id'].
//  - Ownership check ora usa $id_user da session_helper (non
//    più $_SESSION['session_id_user'] che poteva non essere
//    impostato, rendendo il check di fatto opzionale).
//  - Aggiunto controllo REQUEST_METHOD e id_ads dalla sessione.
// ============================================================

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';

$id_user = require_user_logged_in();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: 03_error_insert_tech.php');
    exit;
}

csrf_verify_persistent();

$id_ads = isset($_SESSION['id_ads']) ? (int)$_SESSION['id_ads'] : 0;
if ($id_ads <= 0) {
    $_SESSION['error_message'] = 'Session expired. Please start over.';
    header('Location: ' . BASE_URL . '/01_login/my_posts.php');
    exit;
}

// Ownership check: l'annuncio deve appartenere all'utente loggato
$ownStmt = $pdo->prepare(
    'SELECT id_ads FROM `03_ads`
      WHERE id_ads = :id_ads AND id_user = :id_user
      LIMIT 1'
);
$ownStmt->execute([':id_ads' => $id_ads, ':id_user' => $id_user]);
if (!$ownStmt->fetch()) {
    error_log('[Allonwheel] modify_tech: ownership check failed id_ads=' . $id_ads . ' id_user=' . $id_user);
    $_SESSION['error_message'] = 'Ad not found or access denied.';
    header('Location: ' . BASE_URL . '/01_login/my_posts.php');
    exit;
}

// Helper per checkbox (ritorna 1 o 0)
function getCheckboxValue(string $key): int
{
    return isset($_POST[$key]) && $_POST[$key] === '1' ? 1 : 0;
}

// Helper per input testuale
function getPostValue(string $key): string
{
    return isset($_POST[$key]) ? trim($_POST[$key]) : '';
}

try {
    $stmt = $pdo->prepare("
        UPDATE `03_ads_tech_details` SET
            cars = :cars,
            Awning = :Awning,
            Workshop = :Workshop,
            Belly = :Belly,
            Kitchen = :Kitchen,
            Beds = :Beds,
            Genset = :Genset,
            Bathroom = :Bathroom,
            SAT = :SAT,
            Lift_manufactorer = :Lift_manufactorer,
            Lift_length = :Lift_length,
            Lift_width = :Lift_width,
            Lift_capacity = :Lift_capacity,
            rails = :rails,
            LED = :LED,
            independent_entrance_cargo = :independent_entrance_cargo,
            Fixing = :Fixing,
            Cabinets = :Cabinets,
            Adjustable = :Adjustable,
            Workbenches = :Workbenches,
            HVAC = :HVAC,
            Telemetry = :Telemetry,
            independent_entrance_office = :independent_entrance_office,
            Electrical = :Electrical,
            office_other = :office_other,
            Windows = :Windows,
            TV = :TV,
            Main_panel = :Main_panel,
            batteries = :batteries,
            Charger = :Charger,
            Connection = :Connection,
            Switchgear = :Switchgear,
            electrical_other = :electrical_other,
            Sockets = :Sockets,
            Rema = :Rema,
            Plywood = :Plywood,
            painted = :painted,
            Sandwich = :Sandwich,
            Stickers = :Stickers,
            Special = :Special,
            Stepdeck = :Stepdeck,
            axles = :axles,
            Straightline = :Straightline,
            MGW = :MGW,
            chassis_special = :chassis_special,
            Saddle = :Saddle,
            ext_length = :ext_length,
            ext_width = :ext_width,
            ext_height = :ext_height
        WHERE id_ads = :id_ads
    ");

    $stmt->execute([
        ':id_ads'                      => $id_ads,
        ':cars'                        => getPostValue('cars'),
        ':Awning'                      => getCheckboxValue('Awning'),
        ':Workshop'                    => getCheckboxValue('Workshop'),
        ':Belly'                       => getCheckboxValue('Belly'),
        ':Kitchen'                     => getCheckboxValue('Kitchen'),
        ':Beds'                        => getCheckboxValue('Beds'),
        ':Genset'                      => getCheckboxValue('Genset'),
        ':Bathroom'                    => getCheckboxValue('Bathroom'),
        ':SAT'                         => getCheckboxValue('SAT'),
        ':Lift_manufactorer'           => getPostValue('Lift_manufactorer'),
        ':Lift_length'                 => getPostValue('Lift_length'),
        ':Lift_width'                  => getPostValue('Lift_width'),
        ':Lift_capacity'               => getPostValue('Lift_capacity'),
        ':rails'                       => getCheckboxValue('rails'),
        ':LED'                         => getCheckboxValue('LED'),
        ':independent_entrance_cargo'  => getCheckboxValue('independent_entrance_cargo'),
        ':Fixing'                      => getCheckboxValue('Fixing'),
        ':Cabinets'                    => getCheckboxValue('Cabinets'),
        ':Adjustable'                  => getCheckboxValue('Adjustable'),
        ':Workbenches'                 => getCheckboxValue('Workbenches'),
        ':HVAC'                        => getCheckboxValue('HVAC'),
        ':Telemetry'                   => getCheckboxValue('Telemetry'),
        ':independent_entrance_office' => getCheckboxValue('independent_entrance_office'),
        ':Electrical'                  => getCheckboxValue('Electrical'),
        ':office_other'                => getCheckboxValue('office_other'),
        ':Windows'                     => getCheckboxValue('Windows'),
        ':TV'                          => getCheckboxValue('TV'),
        ':Main_panel'                  => getCheckboxValue('Main_panel'),
        ':batteries'                   => getCheckboxValue('batteries'),
        ':Charger'                     => getCheckboxValue('Charger'),
        ':Connection'                  => getCheckboxValue('Connection'),
        ':Switchgear'                  => getCheckboxValue('Switchgear'),
        ':electrical_other'            => getCheckboxValue('electrical_other'),
        ':Sockets'                     => getCheckboxValue('Sockets'),
        ':Rema'                        => getCheckboxValue('Rema'),
        ':Plywood'                     => getCheckboxValue('Plywood'),
        ':painted'                     => getPostValue('painted'),
        ':Sandwich'                    => getCheckboxValue('Sandwich'),
        ':Stickers'                    => getPostValue('Stickers'),
        ':Special'                     => getCheckboxValue('Special'),
        ':Stepdeck'                    => getCheckboxValue('Stepdeck'),
        ':axles'                       => getPostValue('axles'),
        ':Straightline'                => getCheckboxValue('Straightline'),
        ':MGW'                         => getPostValue('MGW'),
        ':chassis_special'             => getCheckboxValue('chassis_special'),
        ':Saddle'                      => getPostValue('Saddle'),
        ':ext_length'                  => getPostValue('ext_length'),
        ':ext_width'                   => getPostValue('ext_width'),
        ':ext_height'                  => getPostValue('ext_height'),
    ]);

    header('Location: 03_preview_ad.php?id_ads=' . $id_ads);
    exit;

} catch (PDOException $e) {
    error_log('[Allonwheel] modify_tech UPDATE error: ' . $e->getMessage());
    header('Location: 03_error_insert_tech.php');
    exit;
}
