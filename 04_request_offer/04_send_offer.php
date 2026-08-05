<?php
// ============================================================
// 04_send_offer.php — Handler della richiesta di offerta.
//
// Riceve il modulo di 04_request_offer.php, individua TUTTE le aziende
// attive che producono almeno una delle categorie selezionate
// (06_company_products + 06_company_products_special) e invia a CIASCUNA
// il modulo completo via e-mail. Modellato su contact_submit.php
// (CSRF, honeypot, timing, spam scoring, redirect success/retry).
// ============================================================
session_start();
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/06_company.class.php';
require_once __DIR__ . '/../libs/product_macro.class.php';
require_once __DIR__ . '/../libs/mailer.class.php';
require_once __DIR__ . '/../libs/antispam.php';
require_once __DIR__ . '/../includes/form_consent.php';

header('Content-Type: text/html; charset=UTF-8');

// Pagine consentite (protezione Open Redirect) — relative a 04_request_offer/
$allowed_pages = [
    'retry'   => '04_contact-retry.php',
    'success' => '04_contact-success.php',
];

// Solo POST con submit
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit'])) {
    header('Location: 04_request_offer.php');
    exit;
}

// CSRF
csrf_verify();

// Honeypot anti-bot
if (!empty($_POST['test'])) {
    header('Location: ' . $allowed_pages['retry']);
    exit;
}

// Timing
$momento_del_caricamento = (int)($_POST['momento_del_caricamento'] ?? 0);
$tempoDiCompletamento = time() - $momento_del_caricamento;

// M3 (rev. utente 6 lug): rate-limit anti-abuso — max 1 richiesta/SETTIMANA per IP
// (stesso hash GDPR del consenso; nessun IP in chiaro).
try {
    $aow_rl_hash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|aow-salt');
    $aow_rl = $pdo->prepare("SELECT COUNT(*) FROM `quote_requests`
        WHERE consent_ip_hash = :h AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $aow_rl->execute([':h' => $aow_rl_hash]);
    if ((int)$aow_rl->fetchColumn() >= 1) {
        error_log('[Allonwheel] RFQ rate-limit hit for ip hash ' . substr($aow_rl_hash, 0, 12));
        header('Location: ' . $allowed_pages['retry']);
        exit;
    }
} catch (Throwable $e) { /* in dubbio non bloccare (fail-open) */ }

// Dati del modulo
$username = trim($_POST['author'] ?? '');
$email    = trim($_POST['email'] ?? '');
$object   = trim($_POST['object'] ?? '');
$msg_raw  = trim($_POST['msg'] ?? '');

// Validazione e-mail mittente
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $allowed_pages['retry']);
    exit;
}

// Consenso esplicito alla condivisione del contatto con i fornitori (GDPR).
// Obbligatorio: senza consenso non possiamo inoltrare la richiesta.
if (empty($_POST['consent_share'])) {
    header('Location: ' . $allowed_pages['retry']);
    exit;
}

// Categorie selezionate, validate contro i cataloghi (no input arbitrario)
// Le chiavi spuntate si validano contro le TABELLE DI RIFERIMENTO
// (vehicle_types per i road, special_types per special e shelter), non piu'
// contro elenchi scritti nel codice: sono le stesse liste che il form ha
// mostrato, quindi non puo' passare una voce di un'altra sezione.
require_once __DIR__ . '/../libs/vehicle_taxonomy.class.php';

$aow_valid_slugs = static function (string $table) use ($pdo): array {
    try {
        $st = $pdo->query("SELECT slug FROM `{$table}`");
        return $st ? array_column($st->fetchAll(PDO::FETCH_ASSOC), 'slug') : [];
    } catch (Throwable $e) {
        error_log('[Allonwheel] send_offer slugs: ' . $e->getMessage());
        return [];
    }
};
$aow_road_slugs    = $aow_valid_slugs('vehicle_types');
$aow_special_slugs = $aow_valid_slugs('special_types');

// Accetta sia checkbox (product[slug]=1) sia menu a tendina (product[]=slug).
$regular_keys = [];
$aow_raw_r = (array)($_POST['product'] ?? []);
foreach (array_merge(array_keys($aow_raw_r), array_values($aow_raw_r)) as $k) {
    if (in_array((string)$k, $aow_road_slugs, true)) { $regular_keys[] = (string)$k; }
}
$regular_keys = array_values(array_unique($regular_keys));
$special_keys = [];
$aow_raw_s = (array)($_POST['product_special'] ?? []);
foreach (array_merge(array_keys($aow_raw_s), array_values($aow_raw_s)) as $k) {
    if (in_array((string)$k, $aow_special_slugs, true)) { $special_keys[] = (string)$k; }
}
$special_keys = array_values(array_unique($special_keys));

// Ponte configuratore -> fornitori (Fase 2): se arriva una macro, espandila
// nelle product_key di matching (validate sulle tabelle di riferimento:
// vehicle_types per i road, special_types per special e shelter).
$macro_in = trim((string)($_POST['macro'] ?? ''));
if (ProductMacro::exists($macro_in)) {
    $mk = ProductMacro::supplierKeysFor($macro_in);
    foreach ($mk['regular'] as $k) {
        if (in_array($k, $aow_road_slugs, true) && !in_array($k, $regular_keys, true)) { $regular_keys[] = $k; }
    }
    foreach ($mk['special'] as $k) {
        if (in_array($k, $aow_special_slugs, true) && !in_array($k, $special_keys, true)) { $special_keys[] = $k; }
    }
}

// Almeno una categoria
if (empty($regular_keys) && empty($special_keys)) {
    header('Location: ' . $allowed_pages['retry']);
    exit;
}

// Spam scoring (come contact_submit.php)
// Antispam centralizzato.
if (aow_is_spam($msg_raw)) {
    header('Location: ' . $allowed_pages['retry']);
    exit;
}
// P0.3: consenso privacy obbligatorio + registro prova.
if (!aow_privacy_consent_ok()) {
    header('Location: ' . $allowed_pages['retry']);
    exit;
}
aow_log_form_consent($pdo, 'quote_request');

// ---- Aziende destinatarie: MIRATE, non broadcast (17 lug 2026) ----
// PRIMA (dir. C6): getAllCompanies() -> la RFQ andava a TUTTE le aziende
// attive, a prescindere dai prodotti richiesti. I prodotti selezionati
// servivano solo per le etichette nell'email. Conseguenza: un costruttore di
// race trailer riceveva richieste di cliniche mobili. E' il modo piu' rapido
// per far scappare i fornitori migliori, che se ne vanno per primi.
//
// ORA: la richiesta va SOLO alle aziende che dichiarano quei prodotti, con
// getCompaniesByProducts() (metodo gia' esistente e usato dalla directory:
// stessa struttura di getAllCompanies + dedup per azienda + attiva = 1).
//
// Reversibile: definendo AOW_RFQ_BROADCAST = true (in config) si torna al
// comportamento precedente senza toccare il codice.
$cm = new CompanyManager($pdo);

$aow_broadcast = defined('AOW_RFQ_BROADCAST') && AOW_RFQ_BROADCAST === true;
if ($aow_broadcast) {
    $recipients = $cm->getAllCompanies();
} else {
    // Ordinati per PERTINENZA (match_count = chiavi prodotto in comune):
    // e' il criterio che rende sensato il tetto qui sotto. Senza, tagliare
    // a 3 sceglierebbe tre fornitori a caso.
    $recipients = $cm->getCompaniesByProductsScored($regular_keys, $special_keys);
}

// getCompaniesByProducts non filtra le email vuote (getAllCompanies si'):
// senza questo, si tenterebbero invii a destinatari senza indirizzo.
$recipients = array_values(array_filter($recipients, static function ($r) {
    return trim((string)($r['email'] ?? '')) !== '';
}));

// Nessun fornitore corrispondente: NON si ripiega sul broadcast (sarebbe
// tornare al problema). Il lead resta comunque salvato in quote_requests e
// la copia a rfq@ parte lo stesso piu' sotto -> lo gestisci a mano da
// _admin/leads.php. Meglio un lead in triage che 40 email fuori bersaglio.
if (empty($recipients)) {
    error_log('[Allonwheel] RFQ senza fornitori corrispondenti: gestione manuale via _admin/leads.php');
}

// ---- TETTO sui destinatari ----------------------------------------
// Un lead che arriva a tutti non vale nulla per nessuno: nessuno si sente
// responsabile di rispondere e i fornitori percepiscono spam. Limitandolo
// ai piu' pertinenti, la richiesta torna a essere un privilegio.
// Configurabile: define('AOW_RFQ_MAX_RECIPIENTS', n) - 0 = nessun tetto.
$aow_max_rec = defined('AOW_RFQ_MAX_RECIPIENTS') ? (int)AOW_RFQ_MAX_RECIPIENTS : 3;
$aow_total_matched = count($recipients);
if (!$aow_broadcast && $aow_max_rec > 0 && $aow_total_matched > $aow_max_rec) {
    // Gia' ordinati per match_count DESC: si tengono i piu' pertinenti.
    $recipients = array_slice($recipients, 0, $aow_max_rec);
    error_log(sprintf('[Allonwheel] RFQ: %d fornitori corrispondenti, inviata ai %d piu' . "'" . 'pertinenti',
        $aow_total_matched, $aow_max_rec));
}

// Etichette leggibili delle categorie selezionate
$selected_labels = [];
// Etichette leggibili dalle tabelle di riferimento (se una voce fosse stata
// nel frattempo rinominata o rimossa, si ripiega sullo slug: mai una notifica
// con un campo vuoto).
$aow_name_of = static function (string $slug, string $table) use ($pdo): string {
    try {
        $st = $pdo->prepare("SELECT name FROM `{$table}` WHERE slug = :s LIMIT 1");
        $st->execute([':s' => $slug]);
        $n = $st->fetchColumn();
        return ($n !== false && $n !== null && $n !== '') ? (string)$n : $slug;
    } catch (Throwable $e) { return $slug; }
};
foreach ($regular_keys as $k) { $selected_labels[] = $aow_name_of($k, 'vehicle_types'); }
foreach ($special_keys as $k) { $selected_labels[] = $aow_name_of($k, 'special_types'); }

// Sanitizzazione output HTML
$username_safe = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
$email_safe    = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$object_safe   = htmlspecialchars($object, ENT_QUOTES, 'UTF-8');
$msg_safe      = nl2br(htmlspecialchars($msg_raw, ENT_QUOTES, 'UTF-8'));
$cats_safe     = htmlspecialchars(implode(', ', $selected_labels), ENT_QUOTES, 'UTF-8');

$miamail = 'rfq@allonwheel.com';

$subject = 'Request an offer: ' . $object_safe;

// Scheda tecnica: presente solo se l'acquirente premium ha compilato il configuratore
$tech_in = (isset($_POST['tech']) && is_array($_POST['tech'])) ? $_POST['tech'] : [];
$tech_html = '';
if (!empty($tech_in)) {
    ob_start();
    $mode = 'print'; $tech = $tech_in;
    include __DIR__ . '/../shared/tech_details_fields.php';
    $tech_html = '<hr><h3>Technical specification</h3>' . ob_get_clean();
}

// Costruisce il corpo per una data azienda
$build_body = function (string $company_name) use (
    $username_safe, $email_safe, $object_safe, $msg_safe, $cats_safe, $tech_html
): string {
    $company_safe = htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8');
    return '
<html>
<head><meta charset="UTF-8"><title>Request an offer</title></head>
<body>
    <img src="https://www.allonwheel.com/images/aow150.jpg"
         alt="All on Wheel Ltd" style="max-width:150px; width:100%;" loading="lazy" decoding="async">
    <br><br>
    <p>Dear ' . $company_safe . ',</p>
    <p>A buyer has requested an offer through All on Wheel for the following categories:</p>
    <p><strong>Categories:</strong> ' . $cats_safe . '</p>
    <hr>
    <p><strong>Name:</strong> ' . $username_safe . '</p>
    <p><strong>Email:</strong> ' . $email_safe . '</p>
    <p><strong>Object:</strong> ' . $object_safe . '</p>
    <p><strong>Message:</strong><br>' . $msg_safe . '</p>' . $tech_html . '
    <hr>
    <p>You can reply directly to this e-mail to contact the buyer.</p>
</body>
</html>';
};

// Persistenza lead (Fase 3): registra la richiesta come lead per il CRM.
// Il fallimento del logging NON deve impedire l'invio all'utente.
$macro_for_lead  = (isset($macro_in) && ProductMacro::exists($macro_in)) ? $macro_in : null;

// Sezione RFQ da cui arriva la richiesta (road/special/shelter), validata
// contro le sezioni note: serve a sapere da dove nasce il lead.
$aow_sec_in = trim((string)($_POST['section'] ?? ''));
if ($aow_sec_in !== '' && !isset(CompanyManager::$rfqSections[$aow_sec_in])) { $aow_sec_in = ''; }
$categories_json = json_encode(
    ['section' => ($aow_sec_in !== '' ? $aow_sec_in : null),'macro' => $macro_for_lead, 'regular' => $regular_keys, 'special' => $special_keys],
    JSON_UNESCAPED_UNICODE
);
$consent_ip_hash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|aow-salt');
$consent_ua      = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
$consent_version = 'rfq-1';
$request_id = null;
try {
    $stmt_lead = $pdo->prepare(
        'INSERT INTO `quote_requests`
           (buyer_name, buyer_email, macro, categories_json, message, status,
            consent_given, consent_version, consent_ip_hash, consent_user_agent, consent_at)
         VALUES (:name, :email, :macro, :cats, :msg, :status,
            1, :cver, :cip, :cua, NOW())'
    );
    $stmt_lead->execute([
        ':name'   => $username,
        ':email'  => $email,
        ':macro'  => $macro_for_lead,
        ':cats'   => $categories_json,
        ':msg'    => $msg_raw,
        ':status' => 'distributed',
        ':cver'   => $consent_version,
        ':cip'    => $consent_ip_hash,
        ':cua'    => $consent_ua,
    ]);
    $request_id = (int)$pdo->lastInsertId();
} catch (PDOException $e) {
    error_log('[Allonwheel] quote_requests insert error: ' . $e->getMessage());
}

// Invio a ciascuna azienda destinataria, con RITARDO per piano:
// Gold = immediato, Premium = +3 giorni, Free = +5 giorni (dalla creazione RFQ).
// Il differito lo consegna il cron cron/rfq_deliver.php quando deliver_at scade.
require_once __DIR__ . '/../libs/plan_policy.class.php';
$aow_tier_by_cid = [];
$aow_cids = array_values(array_filter(array_map(static function ($c) { return (int)($c['id'] ?? 0); }, $recipients)));
if (!empty($aow_cids)) {
    $aow_in = implode(',', array_fill(0, count($aow_cids), '?'));
    $aow_ts = $pdo->prepare("SELECT c.id, COALESCE(u.user_tier,'free') AS tier FROM `06_company` c LEFT JOIN `users` u ON u.id_user = c.user_id WHERE c.id IN ($aow_in)");
    $aow_ts->execute($aow_cids);
    foreach ($aow_ts->fetchAll(PDO::FETCH_ASSOC) as $row) { $aow_tier_by_cid[(int)$row['id']] = (string)$row['tier']; }
}

$sent_any = false;
$recipient_log = [];
$aow_rank = 0;
foreach ($recipients as $company) {
    $to = trim((string)($company['email'] ?? ''));
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        continue; // azienda senza e-mail valida: saltata
    }
    $cid   = (int)($company['id'] ?? 0);
    $delay = PlanPolicy::rfqDelayDays($aow_tier_by_cid[$cid] ?? 'free'); // 5 free / 3 premium / 0 gold
    if ($delay <= 0) {
        // Gold: consegna immediata
        $body = $build_body((string)($company['ragione_sociale'] ?? 'Supplier'));
        $ok   = Mailer::send($to, $subject, $body, $email);
        if ($ok) { $sent_any = true; }
        $sent_ok    = $ok ? 1 : 0;
        $deliver_at = date('Y-m-d H:i:s');
    } else {
        // Free/Premium: consegna differita (la manda il cron)
        $sent_ok    = 0;
        $deliver_at = date('Y-m-d H:i:s', strtotime('+' . (int)$delay . ' days'));
    }
    $recipient_log[] = [
        'company_id'  => $cid,
        'sent_ok'     => $sent_ok,
        'match_score' => (int)($company['match_count'] ?? 0),
        'rank_pos'    => ++$aow_rank,
        'deliver_at'  => $deliver_at,
    ];
}

// Persistenza destinatari (Fase 3): chi ha ricevuto la RFQ e con quale esito.
if ($request_id && !empty($recipient_log)) {
    try {
        $stmt_rec = $pdo->prepare(
            'INSERT INTO `quote_request_recipients` (request_id, company_id, sent_ok, match_score, rank_pos, deliver_at)
             VALUES (:rid, :cid, :ok, :ms, :rp, :da)'
        );
        foreach ($recipient_log as $r) {
            $stmt_rec->execute([
                ':rid' => $request_id,
                ':cid' => $r['company_id'],
                ':ok'  => $r['sent_ok'],
                ':ms'  => $r['match_score'],
                ':rp'  => $r['rank_pos'],
                ':da'  => $r['deliver_at'],
            ]);
        }
    } catch (PDOException $e) {
        error_log('[Allonwheel] quote_request_recipients insert error: ' . $e->getMessage());
    }
}

// Copia di servizio alla piattaforma rfq@ (dir. C6, sempre inviata)
$copy_ok = Mailer::send($miamail, 'Request an offer (copy): ' . $object_safe, $build_body('All on Wheel Ltd'), $email);

// Esito: successo se almeno la copia rfq@ o un'azienda hanno ricevuto
if (!$sent_any && empty($copy_ok)) {
    header('Location: ' . $allowed_pages['retry']);
    exit;
}
// Conferma al compratore: ricevuta della richiesta (best-effort, non blocca il flusso).
// Nessun dato interno (elenco fornitori/esiti): solo ricevuta + prossimi passi.
try {
    $aow_buyer_body = '<html><body style="font-family:Arial,Helvetica,sans-serif;color:#222;">'
        . '<p>Dear ' . $username_safe . ',</p>'
        . '<p>Thank you for your request for quotation on <strong>All on Wheel</strong>.</p>'
        . '<p>We have received it and forwarded it to the relevant specialist suppliers. '
        . 'They will contact you directly at this address, normally within a few business days.</p>'
        . '<p><strong>Your request:</strong> ' . $object_safe . '</p>'
        . '<blockquote style="border-left:3px solid #e4002b;margin:8px 0;padding:4px 12px;color:#444;">' . $msg_safe . '</blockquote>'
        . '<p>If you need to add details, just reply to this e-mail.</p>'
        . '<p>All on Wheel Ltd &mdash; allonwheel.com</p>'
        . '</body></html>';
    Mailer::send($email, 'We received your quotation request - All on Wheel', $aow_buyer_body, $miamail);
} catch (Throwable $e) {
    error_log('[Allonwheel] buyer confirmation email error: ' . $e->getMessage());
}

if (!empty($tech_in)) { $_SESSION['rfq_tech'] = $tech_in; }
header('Location: ' . $allowed_pages['success']);
exit;
