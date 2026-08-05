<?php
declare(strict_types=1);
// ============================================================
// cron/blog_ai_daily.php — Autopublisher AI: 1 articolo/giorno dal piano
// editoriale, pubblicato in IT e tradotto/archiviato in EN/FR/DE.
// Idempotente sul giorno (tabella ai_daily_log). CLI libero; via HTTP richiede
// ?token=CRON_TOKEN. Autore risolto per email (AI_BLOG_AUTHOR_EMAIL).
// ============================================================
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/AIManager.php';
require_once __DIR__ . '/../libs/AiBlogPublisher.php';

$isCli = (PHP_SAPI === 'cli');
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    $tok = getenv('CRON_TOKEN') ?: '';
    if ($tok === '' || !hash_equals($tok, (string)($_GET['token'] ?? ''))) {
        http_response_code(403);
        echo "Forbidden\n";
        exit;
    }
}

$authorEmail = getenv('AI_BLOG_AUTHOR_EMAIL') ?: 'candian46@gmail.com';

$blog     = new BlogManager($pdo);
$authorId = (int)($blog->resolveAuthorIdByEmail($authorEmail) ?? 0);

if ($authorId <= 0) {
    echo "ERRORE: utente autore non trovato per email {$authorEmail}. Nessuna pubblicazione.\n";
    exit(1);
}

$client    = new GeminiBlogClient();
$publisher = new AiBlogPublisher($pdo, $blog, $client, $authorId, 'it', ['en', 'fr', 'de'], 3);
$result    = $publisher->runDaily();

echo 'AI daily: ' . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n";
