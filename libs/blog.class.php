<?php
// ============================================================
// libs/blog.class.php — BlogManager
// Gestione articoli del blog (tabella `blog`). Usa PDO, coerente con i
// moduli recenti (02/03/01) e con il pannello admin.
// Le letture pubbliche degradano a vuoto se la tabella non esiste ancora
// (es. SQL non ancora eseguito), per non rompere blog.php.
// ============================================================
class BlogManager
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Articoli pubblicati (con nome autore), paginati. */
    public function listPublished(int $limit = 5, int $offset = 0): array
    {
        try {
            $sql = "SELECT b.*, u.username
                    FROM `blog` b
                    LEFT JOIN `users` u ON u.id_user = b.id_user
                    WHERE b.status = 'published'
                    ORDER BY b.created_at DESC
                    LIMIT :lim OFFSET :off";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':lim', max(1, $limit), PDO::PARAM_INT);
            $stmt->bindValue(':off', max(0, $offset), PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /** Conteggio articoli pubblicati (per la paginazione). */
    public function countPublished(): int
    {
        try {
            return (int)$this->pdo->query("SELECT COUNT(*) FROM `blog` WHERE status = 'published'")->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Singolo articolo. Di default solo se 'published'; passando
     * $viewer_id si consente all'autore di vedere anche i propri non pubblicati,
     * e $is_admin consente all'admin di vedere qualsiasi stato.
     */
    public function getById(int $id, ?int $viewer_id = null, bool $is_admin = false): ?array
    {
        try {
            $sql = "SELECT b.*, u.username
                    FROM `blog` b
                    LEFT JOIN `users` u ON u.id_user = b.id_user
                    WHERE b.id = :id LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }
            if ($row['status'] !== 'published'
                && !$is_admin
                && !($viewer_id !== null && (int)$row['id_user'] === $viewer_id)) {
                return null; // non visibile a chi non ne ha diritto
            }
            return $row;
        } catch (PDOException $e) {
            return null;
        }
    }

    /** Articoli di un utente (tutti gli stati). */
    public function listByUser(int $id_user): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM `blog` WHERE id_user = :u ORDER BY created_at DESC"
            );
            $stmt->execute([':u' => $id_user]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /** Inserisce un articolo. Ritorna l'id nuovo o 0 in caso di errore. */
    public function insertArticle(array $data): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO `blog` (id_user, title, excerpt, body, image, status)
             VALUES (:u, :t, :e, :b, :img, :st)"
        );
        $stmt->execute([
            ':u'   => (int)$data['id_user'],
            ':t'   => $data['title'],
            ':e'   => $data['excerpt'] !== '' ? $data['excerpt'] : null,
            ':b'   => $data['body'],
            ':img' => $data['image'] !== '' ? $data['image'] : null,
            ':st'  => $data['status'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Elenco per moderazione admin (filtro: all|pending|published|rejected). */
    public function listForModeration(string $filter = 'all'): array
    {
        try {
            $sql = "SELECT b.*, u.username
                    FROM `blog` b
                    LEFT JOIN `users` u ON u.id_user = b.id_user";
            if (in_array($filter, ['pending', 'published', 'rejected'], true)) {
                $sql .= " WHERE b.status = " . $this->pdo->quote($filter);
            }
            $sql .= " ORDER BY b.created_at DESC";
            return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /** Aggiorna lo stato (moderazione). */
    public function setStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['pending', 'published', 'rejected'], true)) {
            return false;
        }
        $stmt = $this->pdo->prepare("UPDATE `blog` SET status = :s WHERE id = :id LIMIT 1");
        return $stmt->execute([':s' => $status, ':id' => $id]);
    }

    /** Cancella un articolo. Ritorna il filename immagine (per cleanup) o null. */
    public function deleteArticle(int $id): ?string
    {
        $stmt = $this->pdo->prepare("SELECT image FROM `blog` WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $img = $stmt->fetchColumn();
        $this->pdo->prepare("DELETE FROM `blog` WHERE id = :id LIMIT 1")->execute([':id' => $id]);
        return $img !== false && $img !== null && $img !== '' ? (string)$img : null;
    }

    // =========================================================
    // COMMENTI (tabella `blog_comments`) — solo testo, niente immagini
    // =========================================================

    /** Commenti visibili di un articolo (con nome autore), piu' vecchi prima. */
    public function listComments(int $id_blog): array
    {
        try {
            $sql = "SELECT c.*, u.username, u.profile_image
                    FROM `blog_comments` c
                    LEFT JOIN `users` u ON u.id_user = c.id_user
                    WHERE c.id_blog = :b AND c.status = 'visible'
                    ORDER BY c.created_at ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':b' => $id_blog]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /** Numero di commenti visibili di un articolo. */
    public function countComments(int $id_blog): int
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `blog_comments` WHERE id_blog = :b AND status = 'visible'");
            $stmt->execute([':b' => $id_blog]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /** Inserisce un commento (solo testo). Ritorna l'id o 0. */
    /** Email dei partecipanti al thread (autore + chi ha commentato), escluso un utente. */
    public function getThreadParticipantEmails(int $id_blog, int $exclude_user_id): array
    {
        $sql = "SELECT DISTINCT u.id_user, u.username, u.email
                FROM `users` u
                WHERE u.email <> '' AND u.id_user <> :ex AND (
                    u.id_user = (SELECT id_user FROM `blog` WHERE id = :b1)
                    OR u.id_user IN (SELECT id_user FROM `blog_comments` WHERE id_blog = :b2)
                )";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':ex' => $exclude_user_id, ':b1' => $id_blog, ':b2' => $id_blog]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertComment(int $id_blog, int $id_user, string $body): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO `blog_comments` (id_blog, id_user, body) VALUES (:b, :u, :body)"
        );
        $stmt->execute([':b' => $id_blog, ':u' => $id_user, ':body' => $body]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Restituisce un commento (per verifica proprieta' prima del delete). */
    public function getComment(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM `blog_comments` WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /** Cancella un commento. */
    public function deleteComment(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `blog_comments` WHERE id = :id LIMIT 1");
        return $stmt->execute([':id' => $id]);
    }

    // =========================================================
    // ASK THE EXPERTS — categorie, slug, scheduling, API, lead
    // (aggiunte 2026-08-02 — additive, dir. 19)
    // =========================================================

    /** Categorie disponibili (DB-driven, fallback alle 4 di default). */
    public function categories(): array
    {
        try {
            $rows = $this->pdo->query(
                "SELECT slug, name FROM `blog_categories` ORDER BY sort_order, name"
            )->fetchAll(PDO::FETCH_ASSOC);
            if ($rows) { return $rows; }
        } catch (PDOException $e) { /* tabella non ancora creata */ }
        return [
            ['slug' => 'technical-design', 'name' => 'Technical / Design'],
            ['slug' => 'feasibility',      'name' => 'Feasibility'],
            ['slug' => 'costs',            'name' => 'Costs'],
            ['slug' => 'registration',     'name' => 'Registration'],
        ];
    }

    /** Nome leggibile di una categoria dal suo slug. */
    public function categoryName(?string $slug): string
    {
        $slug = (string)$slug;
        foreach ($this->categories() as $c) {
            if ($c['slug'] === $slug) { return $c['name']; }
        }
        return '';
    }

    /**
     * Articoli pubblicati, con filtro categoria opzionale e gate sullo
     * scheduling (published_at nel passato). Retro-compatibile: la firma
     * estende quella storica con un 3o parametro opzionale.
     */
    public function listPublishedFiltered(?string $category, int $limit = 5, int $offset = 0): array
    {
        try {
            $where = "b.status = 'published' AND (b.published_at IS NULL OR b.published_at <= NOW())";
            $params = [];
            if ($category !== null && $category !== '') {
                $where .= " AND b.category = :cat";
                $params[':cat'] = $category;
            }
            $sql = "SELECT b.*, u.username
                    FROM `blog` b
                    LEFT JOIN `users` u ON u.id_user = b.id_user
                    WHERE $where
                    ORDER BY COALESCE(b.published_at, b.created_at) DESC
                    LIMIT :lim OFFSET :off";
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
            $stmt->bindValue(':lim', max(1, $limit), PDO::PARAM_INT);
            $stmt->bindValue(':off', max(0, $offset), PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /** Conteggio pubblicati con filtro categoria opzionale. */
    public function countPublishedFiltered(?string $category): int
    {
        try {
            $where = "status = 'published' AND (published_at IS NULL OR published_at <= NOW())";
            $params = [];
            if ($category !== null && $category !== '') {
                $where .= " AND category = :cat";
                $params[':cat'] = $category;
            }
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `blog` WHERE $where");
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /** Articolo pubblicato per slug (SEO). Null se assente/non pubblicato. */
    public function getPublishedBySlug(string $slug): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT b.*, u.username
                 FROM `blog` b LEFT JOIN `users` u ON u.id_user = b.id_user
                 WHERE b.slug = :s AND b.status = 'published'
                   AND (b.published_at IS NULL OR b.published_at <= NOW())
                 LIMIT 1"
            );
            $stmt->execute([':s' => $slug]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /** Slug SEO univoco derivato dal titolo (con suffisso -id se serve). */
    public function slugify(string $title, int $ignoreId = 0): string
    {
        $s = strtolower(trim($title));
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        $s = trim((string)$s, '-');
        if ($s === '') { $s = 'article'; }
        $s = substr($s, 0, 180);
        // Garantisce univocita': se lo slug e' gia' usato da un altro id, appende -n.
        $base = $s; $n = 1;
        while (true) {
            $stmt = $this->pdo->prepare("SELECT id FROM `blog` WHERE slug = :s AND id <> :i LIMIT 1");
            $stmt->execute([':s' => $s, ':i' => $ignoreId]);
            if (!$stmt->fetchColumn()) { return $s; }
            $n++; $s = substr($base, 0, 176) . '-' . $n;
        }
    }

    /**
     * Crea un articolo dai dati dell'API (ChatGPT).
     * $data: title (req), body (Expert Answer, req), question, excerpt,
     *        outlines (string o array), faq (array [{q,a}] o json), category,
     *        image, status ('draft'|'pending'|'scheduled'|'published'),
     *        published_at ('Y-m-d H:i:s', per scheduled), id_user.
     * Ritorna ['id'=>int,'slug'=>string].
     */
    public function createFromApi(array $data): array
    {
        $title = trim((string)($data['title'] ?? ''));
        $body  = (string)($data['body'] ?? '');
        if ($title === '' || trim($body) === '') {
            throw new InvalidArgumentException('title and body are required');
        }
        $status = $this->normalizeStatus((string)($data['status'] ?? 'draft'));
        $published_at = $this->resolvePublishedAt($status, $data['published_at'] ?? null);
        $slug = trim((string)($data['slug'] ?? '')) !== ''
              ? $this->slugify((string)$data['slug'])
              : $this->slugify($title);

        $stmt = $this->pdo->prepare(
            "INSERT INTO `blog`
               (id_user, title, slug, category, excerpt, question, body, outlines, faq_json, image, status, published_at, source)
             VALUES
               (:u, :t, :sl, :cat, :ex, :q, :b, :out, :faq, :img, :st, :pub, 'api')"
        );
        $stmt->execute([
            ':u'   => (int)($data['id_user'] ?? 0),
            ':t'   => $title,
            ':sl'  => $slug,
            ':cat' => $this->normalizeCategory($data['category'] ?? null),
            ':ex'  => $this->nz($data['excerpt'] ?? null),
            ':q'   => $this->nz($data['question'] ?? null),
            ':b'   => $body,
            ':out' => $this->nz($this->outlinesToText($data['outlines'] ?? null)),
            ':faq' => $this->nz($this->faqToJson($data['faq'] ?? ($data['faq_json'] ?? null))),
            ':img' => $this->nz($data['image'] ?? null),
            ':st'  => $status,
            ':pub' => $published_at,
        ]);
        $id = (int)$this->pdo->lastInsertId();
        return ['id' => $id, 'slug' => $slug];
    }

    /** Aggiorna un articolo esistente dai dati dell'API (campi presenti). */
    public function updateFromApi(int $id, array $data): bool
    {
        $sets = []; $params = [':id' => $id];
        $map = [
            'title' => ':t', 'category' => ':cat', 'excerpt' => ':ex',
            'question' => ':q', 'body' => ':b', 'image' => ':img',
        ];
        foreach ($map as $k => $ph) {
            if (array_key_exists($k, $data)) {
                $col = $k;
                $val = $k === 'category' ? $this->normalizeCategory($data[$k]) : (string)$data[$k];
                $sets[] = "`$col` = $ph"; $params[$ph] = $val;
            }
        }
        if (array_key_exists('outlines', $data)) {
            $sets[] = "`outlines` = :out"; $params[':out'] = $this->outlinesToText($data['outlines']);
        }
        if (array_key_exists('faq', $data) || array_key_exists('faq_json', $data)) {
            $sets[] = "`faq_json` = :faq"; $params[':faq'] = $this->faqToJson($data['faq'] ?? $data['faq_json']);
        }
        if (array_key_exists('slug', $data) && trim((string)$data['slug']) !== '') {
            $sets[] = "`slug` = :sl"; $params[':sl'] = $this->slugify((string)$data['slug'], $id);
        }
        if (array_key_exists('status', $data)) {
            $st = $this->normalizeStatus((string)$data['status']);
            $sets[] = "`status` = :st"; $params[':st'] = $st;
            $pub = $this->resolvePublishedAt($st, $data['published_at'] ?? null);
            $sets[] = "`published_at` = :pub"; $params[':pub'] = $pub;
        } elseif (array_key_exists('published_at', $data)) {
            $sets[] = "`published_at` = :pub"; $params[':pub'] = $this->nz($data['published_at']);
        }
        if (!$sets) { return false; }
        $sql = "UPDATE `blog` SET " . implode(', ', $sets) . " WHERE id = :id LIMIT 1";
        return $this->pdo->prepare($sql)->execute($params);
    }

    /** CRON: pubblica gli articoli schedulati con published_at scaduto. Ritorna quanti. */
    public function publishDueScheduled(): int
    {
        $stmt = $this->pdo->prepare(
            "UPDATE `blog` SET status = 'published'
             WHERE status = 'scheduled' AND published_at IS NOT NULL AND published_at <= NOW()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    /** Inserisce un lead dal form a fine articolo. Ritorna l'id o 0. */
    public function insertLead(array $d): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO `blog_leads`
               (id_blog, category, name, email, company, phone, message, intent,
                consent_given, consent_version, ip_hash, user_agent)
             VALUES
               (:b, :cat, :n, :e, :co, :ph, :msg, :it, :cg, :cv, :ip, :ua)"
        );
        $stmt->execute([
            ':b'   => !empty($d['id_blog']) ? (int)$d['id_blog'] : null,
            ':cat' => $this->nz($d['category'] ?? null),
            ':n'   => (string)($d['name'] ?? ''),
            ':e'   => (string)($d['email'] ?? ''),
            ':co'  => $this->nz($d['company'] ?? null),
            ':ph'  => $this->nz($d['phone'] ?? null),
            ':msg' => $this->nz($d['message'] ?? null),
            ':it'  => $this->nz($d['intent'] ?? null),
            ':cg'  => !empty($d['consent_given']) ? 1 : 0,
            ':cv'  => $this->nz($d['consent_version'] ?? null),
            ':ip'  => $this->nz($d['ip_hash'] ?? null),
            ':ua'  => $this->nz($d['user_agent'] ?? null),
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Decodifica il faq_json in array [{q,a}] per il rendering/schema. */
    public static function faqItems(?string $faqJson): array
    {
        if (!$faqJson) { return []; }
        $arr = json_decode($faqJson, true);
        if (!is_array($arr)) { return []; }
        $out = [];
        foreach ($arr as $it) {
            $q = trim((string)($it['q'] ?? $it['question'] ?? ''));
            $a = trim((string)($it['a'] ?? $it['answer'] ?? ''));
            if ($q !== '' && $a !== '') { $out[] = ['q' => $q, 'a' => $a]; }
        }
        return $out;
    }

    /** Le voci di outline come array (una per riga). */
    public static function outlineItems(?string $outlines): array
    {
        if (!$outlines) { return []; }
        $lines = preg_split('/\R+/', trim($outlines));
        return array_values(array_filter(array_map(
            static fn($l) => trim(ltrim($l, "-*• \t")),
            $lines
        ), static fn($l) => $l !== ''));
    }

    // ---- helper privati ----
    private function nz($v): ?string
    {
        $v = is_string($v) ? trim($v) : $v;
        return ($v === null || $v === '') ? null : (string)$v;
    }
    private function normalizeStatus(string $s): string
    {
        $s = strtolower(trim($s));
        return in_array($s, ['draft','pending','scheduled','published','rejected'], true) ? $s : 'draft';
    }
    private function normalizeCategory($c): ?string
    {
        $c = $this->nz($c);
        if ($c === null) { return null; }
        foreach ($this->categories() as $cat) { if ($cat['slug'] === $c) { return $c; } }
        return $c; // categoria libera (l'admin puo' aggiungerne)
    }
    private function resolvePublishedAt(string $status, $raw): ?string
    {
        if ($status === 'published') {
            return $raw ? date('Y-m-d H:i:s', strtotime((string)$raw)) : date('Y-m-d H:i:s');
        }
        if ($status === 'scheduled') {
            return $raw ? date('Y-m-d H:i:s', strtotime((string)$raw)) : null;
        }
        return $raw ? date('Y-m-d H:i:s', strtotime((string)$raw)) : null;
    }
    private function outlinesToText($out): ?string
    {
        if ($out === null) { return null; }
        if (is_array($out)) { return $this->nz(implode("\n", array_map('strval', $out))); }
        return $this->nz((string)$out);
    }
    private function faqToJson($faq): ?string
    {
        if ($faq === null) { return null; }
        if (is_string($faq)) {
            $dec = json_decode($faq, true);
            $faq = is_array($dec) ? $dec : [];
        }
        if (!is_array($faq)) { return null; }
        $clean = [];
        foreach ($faq as $it) {
            if (!is_array($it)) { continue; }
            $q = trim((string)($it['q'] ?? $it['question'] ?? ''));
            $a = trim((string)($it['a'] ?? $it['answer'] ?? ''));
            if ($q !== '' && $a !== '') { $clean[] = ['q' => $q, 'a' => $a]; }
        }
        return $clean ? json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    }


    /* ===== Estensioni Autopublisher AI multilingua (additive) ===== */

    /** Risolve l'id_user autore a partire dall'email (per gli articoli AI). */
    public function resolveAuthorIdByEmail(string $email): ?int
    {
        $st = $this->pdo->prepare("SELECT id_user FROM `users` WHERE email = :e LIMIT 1");
        $st->execute([':e' => trim($email)]);
        $id = $st->fetchColumn();
        return $id !== false ? (int)$id : null;
    }

    /** Inserisce un articolo con lingua e gruppo-traduzione. Ritorna l'id. */
    public function insertLocalized(array $data): int
    {
        $title = trim((string)($data['title'] ?? ''));
        $body  = (string)($data['body'] ?? '');
        if ($title === '' || trim($body) === '') {
            throw new InvalidArgumentException('title and body are required');
        }
        $lang   = strtolower(substr(trim((string)($data['language'] ?? 'en')), 0, 2));
        $status = $this->normalizeStatus((string)($data['status'] ?? 'published'));
        $pub    = $this->resolvePublishedAt($status, $data['published_at'] ?? null);
        $slug   = trim((string)($data['slug'] ?? '')) !== ''
                ? $this->slugify((string)$data['slug'])
                : $this->slugify($title);
        $group  = $this->nz($data['translation_group'] ?? null);
        $source = $this->nz($data['source'] ?? null) ?? 'ai';

        $stmt = $this->pdo->prepare(
            "INSERT INTO `blog`
               (id_user, title, slug, category, excerpt, question, body, outlines, faq_json,
                image, status, published_at, language, translation_group, source)
             VALUES
               (:u, :t, :sl, :cat, :ex, :q, :b, :out, :faq,
                :img, :st, :pub, :lang, :grp, :src)"
        );
        $stmt->execute([
            ':u'   => (int)($data['id_user'] ?? 0),
            ':t'   => $title,
            ':sl'  => $slug,
            ':cat' => $this->normalizeCategory($data['category'] ?? null),
            ':ex'  => $this->nz($data['excerpt'] ?? null),
            ':q'   => $this->nz($data['question'] ?? null),
            ':b'   => $body,
            ':out' => $this->nz($this->outlinesToText($data['outlines'] ?? null)),
            ':faq' => $this->nz($this->faqToJson($data['faq'] ?? ($data['faq_json'] ?? null))),
            ':img' => $this->nz($data['image'] ?? null),
            ':st'  => $status,
            ':pub' => $pub,
            ':lang'=> $lang,
            ':grp' => $group,
            ':src' => $source,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Listing pubblicati filtrati per lingua (con fallback a 'en' se vuoto). */
    public function listPublishedByLang(?string $category, string $lang, int $limit = 5, int $offset = 0): array
    {
        $rows = $this->queryPublishedLang($category, $lang, $limit, $offset);
        if (!$rows && strtolower($lang) !== 'en') {
            $rows = $this->queryPublishedLang($category, 'en', $limit, $offset);
        }
        return $rows;
    }
    private function queryPublishedLang(?string $category, string $lang, int $limit, int $offset): array
    {
        try {
            $where = "b.status='published' AND (b.published_at IS NULL OR b.published_at<=NOW()) AND LOWER(b.language)=:lang";
            $params = [':lang' => strtolower(substr($lang,0,2))];
            if ($category !== null && $category !== '') { $where .= " AND b.category=:cat"; $params[':cat']=$category; }
            $sql = "SELECT b.*, u.username FROM `blog` b LEFT JOIN `users` u ON u.id_user=b.id_user
                    WHERE $where ORDER BY COALESCE(b.published_at,b.created_at) DESC LIMIT :lim OFFSET :off";
            $st = $this->pdo->prepare($sql);
            foreach ($params as $k=>$v) { $st->bindValue($k,$v); }
            $st->bindValue(':lim', max(1,$limit), PDO::PARAM_INT);
            $st->bindValue(':off', max(0,$offset), PDO::PARAM_INT);
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }
    public function countPublishedByLang(?string $category, string $lang): int
    {
        try {
            $where = "status='published' AND (published_at IS NULL OR published_at<=NOW()) AND LOWER(language)=:lang";
            $params = [':lang' => strtolower(substr($lang,0,2))];
            if ($category !== null && $category !== '') { $where .= " AND category=:cat"; $params[':cat']=$category; }
            $st = $this->pdo->prepare("SELECT COUNT(*) FROM `blog` WHERE $where");
            $st->execute($params);
            $n = (int)$st->fetchColumn();
            if ($n === 0 && strtolower($lang) !== 'en') { return $this->countPublishedByLang($category, 'en'); }
            return $n;
        } catch (PDOException $e) { return 0; }
    }

    /** Versioni tradotte dello stesso articolo (per hreflang). */
    public function translationsByGroup(?string $group): array
    {
        if ($group === null || $group === '') { return []; }
        try {
            $st = $this->pdo->prepare("SELECT id, slug, language FROM `blog` WHERE translation_group=:g AND status='published'");
            $st->execute([':g'=>$group]);
            return $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }
}
