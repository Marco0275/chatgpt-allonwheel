<?php
// ============================================================
// api/blog.php — REST API del blog "Ask the Experts".
//
// Pensata per essere pilotata da ChatGPT (Custom GPT / Actions) o da qualsiasi
// client server-to-server. Auth a chiave (Bearer / X-Api-Key) letta da .env
// (getenv('BLOG_API_KEY')). Nessuna chiave in chiaro nel codice (dir. 11).
//
// Endpoint unico. Verbi:
//   GET    /api/blog.php                      -> lista (filtri: category,status,limit,offset)
//   GET    /api/blog.php?id=123               -> singolo per id
//   GET    /api/blog.php?slug=xxx             -> singolo per slug
//   GET    /api/blog.php?meta=1               -> categorie + stati ammessi (per il GPT)
//   POST   /api/blog.php                      -> crea (body JSON)
//   PUT    /api/blog.php?id=123               -> aggiorna (body JSON)
//   DELETE /api/blog.php?id=123               -> elimina
// In ambienti solo-POST: passare {"_method":"PUT"|"DELETE", ...} o ?_method=.
//
// Campi articolo (JSON): title*, body* (Expert Answer), question, excerpt,
//   outlines (array|string), faq ([{q,a}]), category, image, slug,
//   status (draft|pending|scheduled|published), published_at (per scheduled),
//   id_user (opzionale; default: BLOG_API_AUTHOR_ID o primo admin).
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/blog.class.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ---- risposta JSON uniforme ----
function api_out(int $code, array $payload): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ---- 1) Auth a chiave ----
$expected = (string)getenv('BLOG_API_KEY');
if ($expected === '') {
    // API disattivata finche' non si imposta la chiave: nessun accesso aperto.
    api_out(503, ['ok' => false, 'error' => 'API disabled: BLOG_API_KEY not configured']);
}
$provided = '';
$hdr = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
if (stripos($hdr, 'Bearer ') === 0) {
    $provided = trim(substr($hdr, 7));
} elseif (!empty($_SERVER['HTTP_X_API_KEY'])) {
    $provided = trim((string)$_SERVER['HTTP_X_API_KEY']);
}
if ($provided === '' || !hash_equals($expected, $provided)) {
    api_out(401, ['ok' => false, 'error' => 'Unauthorized']);
}

$blog = new BlogManager($pdo);

// ---- 2) Metodo effettivo (override in ambienti solo-POST) ----
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$rawBody = file_get_contents('php://input') ?: '';
$body = [];
if ($rawBody !== '') {
    $dec = json_decode($rawBody, true);
    if (is_array($dec)) { $body = $dec; }
}
$override = strtoupper((string)($_GET['_method'] ?? ($body['_method'] ?? '')));
if ($method === 'POST' && in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
    $method = $override;
}

// autore di default per i post creati via API
$default_author = static function (PDO $pdo): int {
    $env = (int)getenv('BLOG_API_AUTHOR_ID');
    if ($env > 0) { return $env; }
    try {
        // primo utente con ruolo admin o expert, altrimenti 0 (autore "All on Wheel")
        $id = (int)$pdo->query(
            "SELECT ur.id_user FROM `user_roles` ur
             WHERE ur.role IN ('admin','expert') ORDER BY ur.id_user LIMIT 1"
        )->fetchColumn();
        return $id;
    } catch (Throwable $e) { return 0; }
};

try {
    switch ($method) {

        // ----------------------------- READ -----------------------------
        case 'GET': {
            if (!empty($_GET['meta'])) {
                api_out(200, [
                    'ok' => true,
                    'categories' => $blog->categories(),
                    'statuses'   => ['draft', 'pending', 'scheduled', 'published', 'rejected'],
                    'fields'     => ['title','body','question','excerpt','outlines','faq','category','image','slug','status','published_at'],
                ]);
            }
            if (isset($_GET['id']) || isset($_GET['slug'])) {
                $row = isset($_GET['id'])
                    ? $blog->getById((int)$_GET['id'], null, true)      // API autenticata: vede ogni stato
                    : $blog->getPublishedBySlug((string)$_GET['slug']);
                if (!$row) { api_out(404, ['ok' => false, 'error' => 'Not found']); }
                api_out(200, ['ok' => true, 'article' => $row]);
            }
            // lista (con filtro categoria/stato)
            $limit  = min(50, max(1, (int)($_GET['limit'] ?? 10)));
            $offset = max(0, (int)($_GET['offset'] ?? 0));
            $cat    = isset($_GET['category']) ? (string)$_GET['category'] : null;
            $status = isset($_GET['status']) ? (string)$_GET['status'] : null;
            if ($status && in_array($status, ['draft','pending','scheduled','published','rejected'], true)) {
                // lista per stato (utile al GPT per rivedere le bozze)
                $sql = "SELECT b.*, u.username FROM `blog` b
                        LEFT JOIN `users` u ON u.id_user = b.id_user
                        WHERE b.status = :st" . ($cat ? " AND b.category = :cat" : "") . "
                        ORDER BY b.created_at DESC LIMIT :lim OFFSET :off";
                $st = $pdo->prepare($sql);
                $st->bindValue(':st', $status);
                if ($cat) { $st->bindValue(':cat', $cat); }
                $st->bindValue(':lim', $limit, PDO::PARAM_INT);
                $st->bindValue(':off', $offset, PDO::PARAM_INT);
                $st->execute();
                $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $rows = $blog->listPublishedFiltered($cat, $limit, $offset);
            }
            api_out(200, ['ok' => true, 'count' => count($rows), 'articles' => $rows]);
        }

        // ----------------------------- CREATE ----------------------------
        case 'POST': {
            if (!$body) { api_out(400, ['ok' => false, 'error' => 'JSON body required']); }
            if (empty($body['id_user'])) { $body['id_user'] = $default_author($pdo); }
            $res = $blog->createFromApi($body);
            $article = $blog->getById($res['id'], null, true);
            api_out(201, ['ok' => true, 'id' => $res['id'], 'slug' => $res['slug'], 'article' => $article]);
        }

        // ----------------------------- UPDATE ----------------------------
        case 'PUT':
        case 'PATCH': {
            $id = (int)($_GET['id'] ?? ($body['id'] ?? 0));
            if ($id <= 0) { api_out(400, ['ok' => false, 'error' => 'id required']); }
            if (!$blog->getById($id, null, true)) { api_out(404, ['ok' => false, 'error' => 'Not found']); }
            $blog->updateFromApi($id, $body);
            api_out(200, ['ok' => true, 'article' => $blog->getById($id, null, true)]);
        }

        // ----------------------------- DELETE ----------------------------
        case 'DELETE': {
            $id = (int)($_GET['id'] ?? ($body['id'] ?? 0));
            if ($id <= 0) { api_out(400, ['ok' => false, 'error' => 'id required']); }
            if (!$blog->getById($id, null, true)) { api_out(404, ['ok' => false, 'error' => 'Not found']); }
            $blog->deleteArticle($id);
            api_out(200, ['ok' => true, 'deleted' => $id]);
        }

        default:
            api_out(405, ['ok' => false, 'error' => 'Method not allowed']);
    }
} catch (InvalidArgumentException $e) {
    api_out(422, ['ok' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('[Allonwheel] api/blog error: ' . $e->getMessage());
    api_out(500, ['ok' => false, 'error' => 'Server error']);
}
