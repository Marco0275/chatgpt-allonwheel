<?php
// ============================================================
// 03_ads/03_save_draft.php  Salvataggio bozza dell'ospite (punto 2)
//
// 17 lug 2026. Dove POSTa l'OSPITE quando preme "Register to publish" nel
// form annuncio (03_insert_ad.php). NON e' l'handler di pubblicazione: qui
// non si scrive nessun annuncio, si salva solo una bozza e si manda l'utente
// a registrarsi. L'account e' obbligatorio per pubblicare (lo status nasce
// 'approved' = pubblico all'istante: la verifica email e' l'unica difesa).
//
// File NUOVO e volutamente minimale: l'handler vero (02_01_upload_advertising)
// e' il punto fragile del wizard e resta INTOCCATO da questo flusso.
//
// Sequenza completa del punto 2:
//   ospite compila 02_insert_ad -> [QUI: salva bozza] -> registrazione/login
//   -> claim al login -> torna su 02_insert_ad (form ripopolato dalla bozza)
//   -> pubblica normalmente (handler invariato) -> bozza cancellata.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';
require_once __DIR__ . '/../libs/ad_draft.class.php';

// Solo POST: un GET qui non ha senso, rimanda al wizard.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: 03_00_select_type.php');
    exit;
}

// CSRF: stessa protezione del resto del wizard (token persistente).
csrf_verify_persistent();

// Se per qualsiasi ragione qui ci arriva un utente GIA' loggato (es. ha fatto
// login in un'altra scheda nel frattempo), non ha senso salvare una bozza:
// lo si manda all'handler vero, che pubblica.
if (current_user_id() !== null) {
    // Il repost dei campi non e' banale; piu' pulito rimandarlo al form, che
    // da loggato posta direttamente all'handler.
    header('Location: 03_insert_ad.php');
    exit;
}

// --- Raccolta campi: STESSI nomi e stessa pulizia dell'handler vero, cosi'
//     la bozza contiene esattamente cio' che poi verra' pubblicato. ---
$payload = [
    'title'          => trim((string)($_POST['title']       ?? '')),
    'subtitle'       => trim((string)($_POST['subtitle']    ?? '')),
    'type'           => trim((string)($_POST['type']        ?? '')),
    'conditions'     => trim((string)($_POST['conditions']  ?? '')),
    'description'    => trim((string)($_POST['description'] ?? '')),
    'list_price'     => trim((string)($_POST['list_price']  ?? '')),
    'item_kind'      => trim((string)($_POST['item_kind']      ?? '')),
    'macro_category' => trim((string)($_POST['macro_category'] ?? '')),
    'vehicle_type'   => trim((string)($_POST['vehicle_type']   ?? '')),
    'length_mt'      => trim((string)($_POST['length_mt']      ?? '')),
    'width_mt'       => trim((string)($_POST['width_mt']       ?? '')),
    'height_mt'      => trim((string)($_POST['height_mt']      ?? '')),
    'axles_n'        => trim((string)($_POST['axles_n']        ?? '')),
];

// Validazione minima: titolo e descrizione sono gli unici campi che
// l'handler considera obbligatori. Se mancano, si torna al form senza salvare
// una bozza vuota (che sporcherebbe la tabella e il conteggio orfane).
if ($payload['title'] === '' || $payload['description'] === '') {
    $_SESSION['error_message'] = 'Please fill in at least a title and a description before continuing.';
    header('Location: 03_insert_ad.php');
    exit;
}

// listing free/premium: come nel resto del wizard unificato.
$aow_lt = ((($_SESSION['ad_wizard']['module'] ?? '02')) === '03') ? 'prem' : 'free';

// Token della bozza (crea il cookie httponly se non c'e').
$token = AdDraft::currentToken(true);
if ($token === '') {
    // random_bytes non disponibile: non si finge un salvataggio andato bene.
    $_SESSION['error_message'] = 'Could not save your draft. Please try again.';
    header('Location: 03_insert_ad.php');
    exit;
}

AdDraft::save($pdo, $token, $payload, $aow_lt, 1, '');

// Da qui in poi l'utente deve creare un account (o accedere). require_* al
// prossimo giro nel wizard salvera' il ritorno; qui lo impostiamo esplicito
// cosi' dopo la registrazione torna al FORM, che ripopola dalla bozza.
$_SESSION['redirect_after_login'] = '/03_ads/03_insert_ad.php';
$_SESSION['info_message'] = 'Your listing is saved. Create an account (or log in) to publish it.';

// Registrazione: e' il percorso naturale per un ospite nuovo. Chi ha gia' un
// account trovera' il link "Log in" nella pagina di registrazione.
header('Location: ' . BASE_URL . '/01_login/register.php');
exit;
