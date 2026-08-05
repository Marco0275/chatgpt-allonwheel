<?php
// ============================================================
// saved_search_save.php — M4: salva una ricerca dal marketplace.
// POST only + CSRF. Dedupe su (utente/email, macro, q, vtype).
//
// 27 lug 2026 — apertura ai visitatori non registrati.
// Prima serviva un account: chi arrivava su una ricerca vuota (cioe',
// con l'inventario a zero, quasi tutti) doveva registrarsi PRIMA di poter
// dire cosa cercava, e se ne andava. Ora bastano l'email e un click sul
// link di conferma; il consenso resta provato (doppio opt-in, GDPR art. 7)
// e il cron invia solo alle righe confermate.
// Per gli utenti loggati il comportamento e' identico a prima: il
// salvataggio autenticato E' il consenso, nessuna email di conferma.
// ============================================================
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/libs/product_macro.class.php';
require_once __DIR__ . '/libs/mailer.class.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/browse.php'); exit; }
csrf_verify();

// Input whitelistati
$macro = trim($_POST['macro'] ?? '');
if ($macro !== '' && !ProductMacro::exists($macro)) { $macro = ''; }
$q     = mb_substr(trim($_POST['q'] ?? ''), 0, 120);
$vtype = mb_substr(trim($_POST['vtype'] ?? ''), 0, 80);
$freq  = ($_POST['freq'] ?? 'daily') === 'weekly' ? 'weekly' : 'daily';

$uid = current_user_id();

if ($uid !== null) {
    // Email: sessione, con fallback dal DB (fonte di verita')
    $email = trim(current_user_email());
    if ($email === '') {
        $st = $pdo->prepare('SELECT email FROM users WHERE id_user = :id LIMIT 1');
        $st->execute([':id' => $uid]);
        $email = trim((string)$st->fetchColumn());
    }
} else {
    // Honeypot: campo nascosto compilato = bot. Si finge il successo per non
    // dare all'automatismo un segnale su cosa cambiare.
    if (trim((string)($_POST['website'] ?? '')) !== '') {
        $_SESSION['ss_flash'] = 'Check your inbox to confirm the alert.';
        header('Location: ' . BASE_URL . '/browse.php'); exit;
    }
    $email = trim((string)($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['ss_flash'] = 'Please enter a valid email address.';
        header('Location: ' . BASE_URL . '/browse.php'); exit;
    }
    $email = mb_substr($email, 0, 255);
}
if ($email === '') { header('Location: ' . BASE_URL . '/browse.php'); exit; }

if ($macro === '' && $q === '' && $vtype === '') {
    // niente da salvare: una ricerca "tutto il marketplace" quotidiana sarebbe rumore
    $_SESSION['ss_flash'] = 'Choose a family or type a search term before saving.';
    header('Location: ' . BASE_URL . '/browse.php'); exit;
}

// Le colonne vtype/confirm_token/confirmed_at arrivano con la migrazione
// sql/migrations/2026_07_27_guest_alerts.sql. Il codice non deve dipendere
// dal momento in cui viene applicata: se manca, il salvataggio resta quello
// di prima (utenti loggati) e il guest viene mandato alla registrazione
// invece di ricevere un errore.
$ss_cols = [];
try {
    $ss_cols = $pdo->query('SHOW COLUMNS FROM `saved_searches`')->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) { $ss_cols = []; }
$has_vtype   = in_array('vtype', $ss_cols, true);
$has_confirm = in_array('confirm_token', $ss_cols, true) && in_array('confirmed_at', $ss_cols, true);
if (!$has_vtype) { $vtype = ''; }

if ($uid === null && !$has_confirm) {
    $_SESSION['ss_flash'] = 'Email alerts for guests are not enabled yet: please create an account to save this search.';
    header('Location: ' . BASE_URL . '/01_login/newregister.php'); exit;
}

try {
    // Dedupe: stessa ricerca -> riattiva e aggiorna la frequenza
    $dedupe_sql = 'SELECT id' . ($has_confirm ? ', confirmed_at' : '') . ' FROM saved_searches
                    WHERE ' . ($uid !== null ? 'id_user = :key' : '(id_user = 0 AND email = :key)') . '
                      AND IFNULL(macro,"") = :m AND IFNULL(q,"") = :q'
                 . ($has_vtype ? ' AND IFNULL(vtype,"") = :v' : '') . ' LIMIT 1';
    $params = [':key' => $uid !== null ? $uid : $email, ':m' => $macro, ':q' => $q];
    if ($has_vtype) { $params[':v'] = $vtype; }
    $st = $pdo->prepare($dedupe_sql);
    $st->execute($params);
    $exist = $st->fetch(PDO::FETCH_ASSOC);

    $confirm_token = null;

    if ($exist) {
        $pdo->prepare('UPDATE saved_searches SET active = 1, freq = :f, email = :e WHERE id = :id')
            ->execute([':f' => $freq, ':e' => $email, ':id' => (int)$exist['id']]);
        $needs_confirm = ($uid === null && empty($exist['confirmed_at']));
        if ($needs_confirm) {
            $confirm_token = bin2hex(random_bytes(16));
            $pdo->prepare('UPDATE saved_searches SET confirm_token = :t WHERE id = :id')
                ->execute([':t' => $confirm_token, ':id' => (int)$exist['id']]);
        }
    } else {
        // Guest: riga in attesa di conferma. Utente loggato: attiva subito.
        $confirm_token = ($uid === null) ? bin2hex(random_bytes(16)) : null;
        $cols = 'id_user, email, macro, q, freq, token';
        $vals = ':u, :e, :m, :q, :f, :t';
        $ins  = [
            ':u'  => $uid !== null ? $uid : 0,
            ':e'  => $email,
            ':m'  => $macro !== '' ? $macro : null,
            ':q'  => $q !== '' ? $q : null,
            ':f'  => $freq,
            ':t'  => bin2hex(random_bytes(16)),
        ];
        if ($has_confirm) {
            $cols .= ', confirm_token, confirmed_at';
            $vals .= ', :ct, :ca';
            $ins[':ct'] = $confirm_token;
            $ins[':ca'] = $uid !== null ? date('Y-m-d H:i:s') : null;
        }
        if ($has_vtype) { $cols .= ', vtype'; $vals .= ', :v'; $ins[':v'] = $vtype !== '' ? $vtype : null; }
        $pdo->prepare("INSERT INTO saved_searches ($cols) VALUES ($vals)")->execute($ins);
    }

    if ($confirm_token !== null) {
        $link = rtrim(BASE_URL, '/') . '/saved_search_confirm.php?token=' . $confirm_token;
        $what = $macro !== '' ? $macro : ($vtype !== '' ? $vtype : $q);
        $body = '<p>Hello,</p>'
              . '<p>You asked All on Wheel to alert you when a listing matching <strong>'
              . htmlspecialchars($what, ENT_QUOTES, 'UTF-8') . '</strong> is published.</p>'
              . '<p>Please confirm your address by clicking the link below. Until you do, we will not send you anything.</p>'
              . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">Confirm my alert</a></p>'
              . '<p>If you did not request this, ignore this message: the request expires on its own.</p>';
        Mailer::send($email, 'Confirm your All on Wheel alert', $body);
        $_SESSION['ss_flash'] = 'Almost done: check your inbox and click the confirmation link.';
    } else {
        $_SESSION['ss_flash'] = 'Saved! We will email you when new matching listings are published.';
    }
} catch (Throwable $e) {
    error_log('[Allonwheel] saved_search_save error: ' . $e->getMessage());
    $_SESSION['ss_flash'] = 'Could not save the search, please try again.';
}

// Ritorno alla ricerca di provenienza
$back = BASE_URL . '/browse.php';
$qs = [];
if ($macro !== '') { $qs[] = 'macro=' . rawurlencode($macro); }
if ($vtype !== '') { $qs[] = 'vtype=' . rawurlencode($vtype); }
if ($q !== '')     { $qs[] = 'q=' . rawurlencode($q); }
if ($qs) { $back .= '?' . implode('&', $qs); }
header('Location: ' . $back);
exit;
