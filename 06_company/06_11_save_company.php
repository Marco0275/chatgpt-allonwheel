<?php
/**
 * 06_11_save_company.php — Salva nuova azienda nel database
 *
 * FIX rispetto alla versione precedente:
 *  - Logo salvato in /upload_image/06_company/original/ (originale)
 *    e /upload_image/06_company/thumbnail/ (thumbnail ridimensionata).
 *    In precedenza entrambi i path puntavano alla stessa cartella,
 *    causando la sovrascrittura del file originale con la thumbnail.
 *  - Cleanup corretto: in caso di rollback, elimina i file da entrambe
 *    le sottodirectory.
 */
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/06_company.class.php';
require_once __DIR__ . '/../libs/upload_helper.class.php';

$user_id = require_user_logged_in();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /06_company/06_10_register_company.php');
    exit;
}

csrf_verify();

$cm = new CompanyManager($pdo);

// Verifica se ha già un'azienda
if ($cm->userHasCompany($user_id)) {
    $_SESSION['error_message'] = 'You already have a registered company.';
    header('Location: /01_login/my_posts.php');
    exit;
}

// Validazione campi obbligatori
$required = ['ragione_sociale', 'partita_iva', 'indirizzo', 'cap', 'citta', 'provincia', 'email'];
foreach ($required as $field) {
    if (empty(trim($_POST[$field] ?? ''))) {
        $_SESSION['error_message'] = 'Please fill in all required fields.';
        header('Location: /06_company/06_10_register_company.php');
        exit;
    }
}

// -------------------------------------------------------------------
// Upload logo (opzionale)
// Originale → /upload_image/06_company/original/
// Thumbnail → /upload_image/06_company/thumbnail/
// -------------------------------------------------------------------
$logo_filename = null;

if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $logo_result = UploadHelper::handleImageUpload($_FILES['logo'], [
        'target_dir_original'  => '/upload_image/06_company/original/',
        'target_dir_thumbnail' => '/upload_image/06_company/thumbnail/',
        'thumb_width'          => 220,
        'thumb_height'         => 150,
        'thumb_crop'           => true,
        'max_size_bytes'       => 5 * 1024 * 1024,
        'filename_prefix'      => 'logo_' . $user_id,
    ]);

    if (!$logo_result['ok']) {
        $_SESSION['error_message'] = 'Logo upload failed: ' . $logo_result['error'];
        header('Location: /06_company/06_10_register_company.php');
        exit;
    }

    $logo_filename = $logo_result['filename'];
}

// -------------------------------------------------------------------
// Inserimento azienda
// -------------------------------------------------------------------
$data = [
    'user_id'            => $user_id,
    'ragione_sociale'    => trim($_POST['ragione_sociale']),
    'partita_iva'        => trim($_POST['partita_iva']),
    'codice_fiscale'     => trim($_POST['codice_fiscale'] ?? ''),
    'indirizzo'          => trim($_POST['indirizzo']),
    'cap'                => trim($_POST['cap']),
    'citta'              => trim($_POST['citta']),
    'provincia'          => trim($_POST['provincia']),
    'nazione'            => trim($_POST['nazione'] ?? 'Italia'),
    'telefono'           => trim($_POST['telefono'] ?? ''),
    'cellulare'          => trim($_POST['cellulare'] ?? ''),
    'fax'                => trim($_POST['fax'] ?? ''),
    'email'              => trim($_POST['email']),
    'pec'                => trim($_POST['pec'] ?? ''),
    'sito_web'           => trim($_POST['sito_web'] ?? ''),
    'descrizione'        => trim($_POST['descrizione'] ?? ''),
    'logo'               => $logo_filename,
    'referente_nome'     => trim($_POST['referente_nome'] ?? ''),
    'referente_cognome'  => trim($_POST['referente_cognome'] ?? ''),
    'referente_ruolo'    => trim($_POST['referente_ruolo'] ?? ''),
    'referente_email'    => trim($_POST['referente_email'] ?? ''),
    'referente_telefono' => trim($_POST['referente_telefono'] ?? ''),
    'offers_rental'      => !empty($_POST['offers_rental']) ? 1 : 0,
    'general_note'       => trim($_POST['general_note'] ?? ''),
];

$insert_id = $cm->insertCompany($data);

if ($insert_id) {
    $cm->saveCompanyPrefs($insert_id, trim($_POST['descrizione_it'] ?? ''), !empty($_POST['wants_pm_list']));
    // Salva anche tipologie veicolo (products) e servizi accessori se selezionati
    $products_data = [];
    foreach (CompanyManager::productsRoad($pdo) as $key => $label) {
        if (isset($_POST['product'][$key])) {
            $products_data[] = [
                'product_key'             => $key,
                'note'                    => '',
                'certificazioni_prodotto' => 0,
                'campioni_gratuiti'       => 0,
                'assistenza_posa'         => 0,
                'progettazione_supporto'  => 0,
                'schede_tecniche'         => 0,
            ];
        }
    }
    $services_data = [];
    foreach (CompanyManager::$services as $key => $label) {
        if (isset($_POST['service'][$key])) {
            $services_data[] = [
                'service_key' => $key,
                'note'        => '',
            ];
        }
    }
    if (!empty($products_data)) {
        $cm->saveProducts($insert_id, $products_data);
    }
    if (!empty($services_data)) {
        $cm->saveServices($insert_id, $services_data);
    }
    // Categorie speciali selezionate -> 06_company_products_special
    $special_data = [];
    foreach (CompanyManager::productsSpecial($pdo) as $key => $label) {
        if (isset($_POST['product_special'][$key])) {
            $special_data[] = [
                'product_key' => $key,
                'note'        => '',
            ];
        }
    }
    $cm->saveProductsSpecial($insert_id, $special_data);

    $_SESSION['success_message'] = 'Company registered successfully!';
    header('Location: /06_company/06_02_view_company.php?id=' . $insert_id);
} else {
    // Rimuovi i file logo già caricati se l'insert fallisce
    if ($logo_filename) {
        $base_dir  = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/06_company/';
        foreach (['original', 'thumbnail'] as $sub) {
            $f = $base_dir . $sub . '/' . $logo_filename;
            if (is_file($f)) {
                @unlink($f);
            }
        }
    }
    $_SESSION['error_message'] = 'Error registering company. The VAT number may already be in use.';
    header('Location: /06_company/06_10_register_company.php');
}
exit;
?>
