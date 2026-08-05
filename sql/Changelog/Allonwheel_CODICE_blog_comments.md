# Allonwheel — Commenti del blog (solo testo)

Modello scelto: **commento riservato agli utenti registrati**, pubblicato subito; l'autore del commento e l'admin possono cancellarlo. **Nessun upload di immagini** (corpo solo testo, neutralizzato in output con `htmlspecialchars`).

## File

**Nuovi:** `sql/blog_comments.sql`, `blog_comments.php` (sezione commenti da includere), `blog_comment_save.php` (handler add/delete).
**Aggiornato:** `libs/blog.class.php` — aggiunti i metodi `listComments`, `countComments`, `insertComment`, `getComment`, `deleteComment` (i metodi esistenti degli articoli sono invariati).

> `blog.php` e `blog_post.php` che mi hai inviato **non sono stati toccati**. Per agganciare i commenti serve **una sola modifica** in `blog_post.php` (vedi sotto).

## Unica modifica da fare in `blog_post.php`

Sostituisci queste righe (il segnaposto):

```php
   <h3>Comments</h3>
   <div id="comment_section">
     <p>Comments are not available yet.</p>
   </div>
```

con questa singola riga (il resto della pagina resta identico):

```php
   <?php include __DIR__ . '/blog_comments.php'; ?>
```

Il partial fornisce da solo l'intestazione *Comments*, l'elenco e il form, usando le classi del template (`comment_box`, `gravatar`, `comment_text`, `comment_form`, `form_row`). Niente nuovi stili.

## Passo DB

Esegui `sql/blog_comments.sql` prima dell'uso.

## Sicurezza / scelte

- Scrittura solo per loggati (`require_user_logged_in()`), CSRF su add e delete.
- Solo testo: `strip_tags` in ingresso + `htmlspecialchars`/`nl2br` in uscita → impossibile inserire immagini o HTML.
- Isolamento: un commento puo' essere cancellato solo dal suo autore o da un admin.
- La colonna `status` (visible/hidden) e' pronta per un'eventuale moderazione futura.

---

## `sql/blog_comments.sql` *(NUOVO)*

```sql
-- ============================================================
-- blog_comments.sql — Commenti degli articoli del blog.
-- Solo TESTO (nessuna immagine). Commento scritto da utenti registrati,
-- visibile da subito; autore e admin possono cancellarlo. La colonna
-- `status` consente un'eventuale moderazione futura (visible/hidden).
-- ============================================================
CREATE TABLE IF NOT EXISTS `blog_comments` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_blog` int(10) UNSIGNED NOT NULL COMMENT 'Articolo (blog.id)',
  `id_user` int(10) UNSIGNED NOT NULL COMMENT 'Autore del commento (users.id_user)',
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Testo del commento (niente HTML/immagini)',
  `status` enum('visible','hidden') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'visible',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comment_blog` (`id_blog`),
  KEY `idx_comment_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Commenti agli articoli del blog (solo testo)';
```

## `libs/blog.class.php` *(aggiornato)*

```php
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
            $sql = "SELECT c.*, u.username
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
}
```

## `blog_comments.php` *(NUOVO)*

```php
<?php
// ============================================================
// blog_comments.php — Sezione commenti di un articolo (DA INCLUDERE in
// blog_post.php al posto del segnaposto "Comments are not available yet").
//
// Requisiti dal contesto di blog_post.php: $article (con 'id'), $blog
// (BlogManager), $pdo, e i session helper gia' inclusi.
// Commenti SOLO TESTO: nessun campo immagine, nessun HTML (output via
// htmlspecialchars + nl2br). Scrittura riservata agli utenti registrati;
// autore e admin possono cancellare. Markup con le classi del template.
// ============================================================
require_once __DIR__ . '/config/csrf.php';

$comment_blog_id = (int)($article['id'] ?? 0);
$comments        = $blog->listComments($comment_blog_id);
$can_comment     = is_user_logged_in();
$me_id           = $can_comment ? (int)current_user_id() : 0;
$me_is_admin     = !empty($_SESSION['user_tier']) && $_SESSION['user_tier'] === 'admin';

$c_ok  = $_SESSION['comment_success'] ?? '';
$c_err = $_SESSION['comment_error'] ?? '';
unset($_SESSION['comment_success'], $_SESSION['comment_error']);
?>
<h3>Comments<?php echo $comments ? ' (' . count($comments) . ')' : ''; ?></h3>

<?php if ($c_ok !== ''): ?>
  <p class="done"><?php echo htmlspecialchars($c_ok, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>
<?php if ($c_err !== ''): ?>
  <p class="error-msg"><?php echo htmlspecialchars($c_err, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<div id="comment_section">
  <?php if (empty($comments)): ?>
    <p>No comments yet.<?php echo $can_comment ? ' Be the first to comment!' : ''; ?></p>
  <?php else: ?>
  <ol class="comments first_level">
    <?php foreach ($comments as $i => $c):
      $author = trim((string)($c['username'] ?? '')) !== '' ? $c['username'] : 'User';
      $box    = ($i % 2 === 0) ? 'commentbox1' : 'commentbox2';
      $can_del = $me_is_admin || ($me_id > 0 && (int)$c['id_user'] === $me_id);
    ?>
    <li>
      <div class="comment_box <?php echo $box; ?>">
        <div class="gravatar"><img src="images/avator.jpg" alt="<?php echo htmlspecialchars($author, ENT_QUOTES, 'UTF-8'); ?>" /></div>
        <div class="comment_text">
          <div class="comment_author"><?php echo htmlspecialchars($author); ?>
            <span class="date"><?php echo htmlspecialchars(date('j F Y', strtotime((string)$c['created_at']))); ?></span>
            <span class="time"><?php echo htmlspecialchars(date('g:i a', strtotime((string)$c['created_at']))); ?></span>
          </div>
          <p><?php echo nl2br(htmlspecialchars((string)$c['body'])); ?></p>
          <?php if ($can_del): ?>
          <div class="reply">
            <form method="post" action="blog_comment_save.php" style="display:inline; margin:0;"
                  data-confirm="Delete this comment?">
              <?php echo csrf_generate(); ?>
              <input type="hidden" name="action" value="delete" />
              <input type="hidden" name="comment_id" value="<?php echo (int)$c['id']; ?>" />
              <input type="hidden" name="id_blog" value="<?php echo $comment_blog_id; ?>" />
              <button type="submit" class="more">Delete</button>
            </form>
          </div>
          <?php endif; ?>
        </div>
        <div class="cleaner"></div>
      </div>
    </li>
    <?php endforeach; ?>
  </ol>
  <?php endif; ?>
</div>

<div class="cleaner h20"></div>

<div id="comment_form">
  <?php if ($can_comment): ?>
  <h3>Leave your comment</h3>
  <form action="blog_comment_save.php" method="post">
    <?php echo csrf_generate(); ?>
    <input type="hidden" name="action" value="add" />
    <input type="hidden" name="id_blog" value="<?php echo $comment_blog_id; ?>" />
    <div class="form_row">
      <label>Comment (* required)</label><br />
      <textarea name="body" rows="6" cols=""></textarea>
    </div>
    <input type="submit" name="submit" value="Post comment" class="submit_btn" />
  </form>
  <?php else: ?>
  <p>Please <a href="01_login/newlogin.php">log in</a> to leave a comment.</p>
  <?php endif; ?>
</div>
```

## `blog_comment_save.php` *(NUOVO)*

```php
<?php
// ============================================================
// blog_comment_save.php — Handler commenti del blog.
// Azioni: 'add' (utente registrato scrive un commento di solo testo) e
// 'delete' (l'autore del commento o un admin lo cancella).
// Login + CSRF obbligatori. Nessuna immagine: il corpo e' solo testo.
// ============================================================
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/libs/blog.class.php';

$me_id = require_user_logged_in();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /blog.php');
    exit;
}

csrf_verify();

$action  = in_array($_POST['action'] ?? '', ['add', 'delete'], true) ? $_POST['action'] : '';
$id_blog = (int)($_POST['id_blog'] ?? 0);

$back = '/blog_post.php?id=' . $id_blog . '#comment_section';

$blog = new BlogManager($pdo);

if ($action === 'add') {
    // Solo testo: niente HTML/immagini. strip_tags + escape in output.
    $body = trim((string)($_POST['body'] ?? ''));
    $body = strip_tags($body);

    if ($id_blog <= 0 || $blog->getById($id_blog, $me_id, true) === null) {
        $_SESSION['comment_error'] = 'Article not found.';
        header('Location: /blog.php');
        exit;
    }
    if ($body === '') {
        $_SESSION['comment_error'] = 'The comment cannot be empty.';
        header('Location: ' . $back);
        exit;
    }
    if (mb_strlen($body) > 5000) {
        $body = mb_substr($body, 0, 5000);
    }

    try {
        $blog->insertComment($id_blog, $me_id, $body);
        $_SESSION['comment_success'] = 'Comment posted.';
    } catch (PDOException $e) {
        error_log('[Allonwheel] comment insert error: ' . $e->getMessage());
        $_SESSION['comment_error'] = 'Database error while posting the comment.';
    }
    header('Location: ' . $back);
    exit;
}

if ($action === 'delete') {
    $comment_id = (int)($_POST['comment_id'] ?? 0);
    $comment    = $comment_id > 0 ? $blog->getComment($comment_id) : null;
    $is_admin   = !empty($_SESSION['user_tier']) && $_SESSION['user_tier'] === 'admin';

    if ($comment === null) {
        $_SESSION['comment_error'] = 'Comment not found.';
        header('Location: ' . $back);
        exit;
    }
    // Isolamento: solo l'autore del commento o un admin possono cancellare.
    if (!$is_admin && (int)$comment['id_user'] !== $me_id) {
        $_SESSION['comment_error'] = 'You can only delete your own comments.';
        header('Location: /blog_post.php?id=' . (int)$comment['id_blog'] . '#comment_section');
        exit;
    }
    $blog->deleteComment($comment_id);
    $_SESSION['comment_success'] = 'Comment deleted.';
    header('Location: /blog_post.php?id=' . (int)$comment['id_blog'] . '#comment_section');
    exit;
}

header('Location: ' . $back);
exit;
```

