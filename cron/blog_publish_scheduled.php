<?php
// ============================================================
// cron/blog_publish_scheduled.php
// Pubblica gli articoli in stato 'scheduled' il cui published_at e' scaduto.
// Da chiamare periodicamente (es. ogni 10 min) da cron-job.org o dal cron
// del server. Protetto dallo stesso CRON_TOKEN degli altri job (env .env).
//
// Uso HTTP:  https://www.allonwheel.com/cron/blog_publish_scheduled.php?token=XXX
// Uso CLI:   php cron/blog_publish_scheduled.php  (in CLI il token non serve)
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/blog.class.php';

$is_cli = (PHP_SAPI === 'cli');

if (!$is_cli) {
    header('Content-Type: text/plain; charset=utf-8');
    $expected = (string)getenv('CRON_TOKEN');
    $token    = (string)($_GET['token'] ?? '');
    if ($expected === '' || !hash_equals($expected, $token)) {
        http_response_code(403);
        echo "Forbidden";
        exit;
    }
}

try {
    $blog = new BlogManager($pdo);
    $n = $blog->publishDueScheduled();
    $msg = date('c') . " — published_due_scheduled: {$n} article(s) published.";
    error_log('[Allonwheel] ' . $msg);
    echo $msg . "\n";
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[Allonwheel] blog_publish_scheduled error: ' . $e->getMessage());
    echo "Error\n";
}
