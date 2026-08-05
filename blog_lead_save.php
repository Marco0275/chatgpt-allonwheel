<?php
// ============================================================
// blog_lead_save.php — Handler del form "Ask the Experts" a fine articolo.
// Converte il lettore in lead B2B qualificato: valida, registra il consenso
// (GDPR), salva in `blog_leads`, notifica via Mailer e torna all'articolo.
// Sicurezza: CSRF one-shot + antispam (honeypot/time-trap) + consenso.
// ============================================================
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/libs/antispam.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/form_consent.php';
require_once __DIR__ . '/libs/blog.class.php';
require_once __DIR__ . '/libs/mailer.class.php';

header('Content-Type: text/html; charset=UTF-8');

$id_blog = (int)($_POST['id_blog'] ?? 0);
$back    = 'blog_post.php' . ($id_blog > 0 ? '?id=' . $id_blog : '');

// Solo POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . $back);
    exit;
}
csrf_verify();

$flash = static function (string $key, string $to): void {
    $_SESSION[$key] = 1;
    header('Location: ' . $to);
    exit;
};

// Dati
$name    = trim((string)($_POST['name'] ?? ''));
$email   = trim((string)($_POST['email'] ?? ''));
$company = trim((string)($_POST['company'] ?? ''));
$phone   = trim((string)($_POST['phone'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));
$intent  = (string)($_POST['intent'] ?? 'question');
$intent  = in_array($intent, ['feasibility_study', 'custom_quote', 'question'], true) ? $intent : 'question';
$category = trim((string)($_POST['category'] ?? '')) ?: null;

// Antispam (honeypot + time-trap firmata)
if (aow_is_spam($message)) { $flash('blog_lead_err', $back); }

// Validazione minima
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $flash('blog_lead_err', $back);
}

// Consenso privacy obbligatorio + prova nel registro
if (!aow_privacy_consent_ok()) { $flash('blog_lead_err', $back); }
aow_log_form_consent($pdo, 'blog_lead');

// IP pseudonimizzato (coerente con form_consent)
$salt    = defined('AOW_CONSENT_SALT') ? AOW_CONSENT_SALT : 'aow-salt';
$ip_hash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . $salt);
$ua      = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

// Persistenza lead
try {
    $blog = new BlogManager($pdo);
    $lead_id = $blog->insertLead([
        'id_blog'         => $id_blog ?: null,
        'category'        => $category,
        'name'            => $name,
        'email'           => $email,
        'company'         => $company,
        'phone'           => $phone,
        'message'         => $message,
        'intent'          => $intent,
        'consent_given'   => 1,
        'consent_version' => (string)($_POST['privacy_version'] ?? ''),
        'ip_hash'         => $ip_hash,
        'user_agent'      => $ua,
    ]);
} catch (Throwable $e) {
    error_log('[Allonwheel] blog_lead insert error: ' . $e->getMessage());
    $flash('blog_lead_err', $back);
}

// Notifica interna (best-effort: se fallisce, il lead resta comunque salvato)
try {
    $intent_label = [
        'feasibility_study' => 'Feasibility study request',
        'custom_quote'      => 'Custom quote request',
        'question'          => 'Question to the experts',
    ][$intent] ?? 'Lead';
    $esc = static fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $html = '<h3>New B2B lead — ' . $esc($intent_label) . '</h3>'
          . '<p><strong>Name:</strong> ' . $esc($name) . '<br>'
          . '<strong>Email:</strong> ' . $esc($email) . '<br>'
          . '<strong>Company:</strong> ' . $esc($company) . '<br>'
          . '<strong>Phone:</strong> ' . $esc($phone) . '<br>'
          . '<strong>Category:</strong> ' . $esc($category) . '<br>'
          . '<strong>Article ID:</strong> ' . (int)$id_blog . ' (lead #' . (int)($lead_id ?? 0) . ')</p>'
          . '<p><strong>Message:</strong><br>' . nl2br($esc($message)) . '</p>';
    Mailer::send('info@allonwheel.com', 'New B2B lead: ' . $intent_label, $html, $email, 'All on Wheel');
} catch (Throwable $e) {
    error_log('[Allonwheel] blog_lead mail error: ' . $e->getMessage());
}

$flash('blog_lead_ok', $back);
