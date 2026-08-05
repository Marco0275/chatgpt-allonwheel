# Allonwheel — Blog dinamico + pubblicazione articoli da utenti registrati
*Rev. 2 giu 2026 — `blog.php`/`blog_post.php` resi dinamici; flusso di pubblicazione (login richiesto) + moderazione admin.*

## Diagnosi (/debug)

`blog.php` e `blog_post.php` erano **100% statici**: post hardcoded, "Continue" puntava sempre allo stesso `blog_post.php`, commenti finti, form commenti su `#`, nessuna tabella DB. Non esisteva alcun modo per gli utenti di pubblicare.

## Struttura implementata

- **`sql/blog.sql`** — tabella `blog` (id, id_user autore, title, excerpt, body, image, status enum pending/published/rejected, timestamp). **Da eseguire prima dell'uso.**
- **`libs/blog.class.php`** — `BlogManager` (PDO): list/get/insert/moderation/delete. Le letture pubbliche degradano a vuoto se la tabella non esiste ancora.
- **`blog.php`** — elenco articoli `published` con **paginazione reale**, bottone *Write an article* per i loggati, messaggi flash. Markup/classi invariati (`post_box`, `post_meta`, `templatemo_paging`).
- **`blog_post.php`** — articolo singolo per `?id`. Pubblico se `published`; l'autore vede i propri non pubblicati, l'admin qualsiasi stato. Rimossi i commenti finti hardcoded.
- **`blog_write.php`** — form di pubblicazione (**login obbligatorio**, `require_user_logged_in()`), CSRF, upload cover opzionale.
- **`blog_save.php`** — handler: login + CSRF + validazione + upload immagine (UploadHelper → `/upload_image/blog/`) + insert. Stato di default `published` (immediato); per moderazione preventiva basta cambiare una riga (`$status = 'pending'`).
- **`_admin/moderate_blog.php`** — moderazione: Publish / Reject / Delete (con cleanup immagini e protezione path-traversal). Audit via `UserTier::logAdminAction` (colonne corrette).
- **`header.php`** — voce *Write an article* nel menu utente loggato.
- **`_admin/dashboard.php`** — link *Blog* nella nav admin.

## Note

- **Pubblicazione immediata vs moderata**: ora gli articoli vanno online subito (`published`). Se preferisci la revisione preventiva, imposta `$status = 'pending'` in `blog_save.php`: gli articoli compariranno solo dopo *Publish* dal pannello.
- **Commenti**: fuori scope; la sezione mostra un placeholder. Posso renderli dinamici (tabella `blog_comments`) se vuoi.
- **Immagini**: cover salvata in `/upload_image/blog/{original,thumbnail}/`; `upload_image`/`images` non vengono mai toccate altrove (dir. 15).

---

## `sql/blog.sql` *(NUOVO)*

```sql
-- ============================================================
-- blog.sql — Tabella articoli del blog pubblicati dagli utenti registrati.
-- Base: la struttura statica di blog.php / blog_post.php resa dinamica.
--   status: 'published' visibile a tutti, 'pending' in attesa di revisione,
--           'rejected' nascosto. La moderazione admin e' in
--           _admin/moderate_blog.php.
-- ============================================================
CREATE TABLE IF NOT EXISTS `blog` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_user` int(10) UNSIGNED NOT NULL COMMENT 'Autore (users.id_user)',
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sommario breve (riga in corsivo)',
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Corpo articolo; paragrafi separati da newline',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cover in /upload_image/blog/',
  `status` enum('pending','published','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_blog_user` (`id_user`),
  KEY `idx_blog_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Articoli del blog pubblicati dagli utenti registrati';
```

## `libs/blog.class.php` *(NUOVO)*

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
}
```

## `blog.php`

```php
<?php
// ============================================================
// blog.php — Elenco articoli del blog (DINAMICO da tabella `blog`).
// Mostra gli articoli 'published' con paginazione. Gli utenti registrati
// vedono il bottone "Write an article". Markup/classi invariati (post_box,
// post_meta, templatemo_paging) rispetto alla versione statica.
// ============================================================
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/libs/blog.class.php';

$blog = new BlogManager($pdo);

$per_page = 5;
$page     = max(1, (int)($_GET['page'] ?? 1));
$total    = $blog->countPublished();
$pages    = max(1, (int)ceil($total / $per_page));
if ($page > $pages) { $page = $pages; }
$offset   = ($page - 1) * $per_page;
$articles = $blog->listPublished($per_page, $offset);

$flash_ok  = $_SESSION['blog_success'] ?? '';
$flash_err = $_SESSION['blog_error'] ?? '';
unset($_SESSION['blog_success'], $_SESSION['blog_error']);

// Estrae il primo paragrafo dal corpo per l'anteprima.
$first_paragraph = static function (string $body): string {
    $parts = preg_split('/\R{1,}/', trim($body));
    $p = $parts[0] ?? '';
    if (mb_strlen($p) > 300) { $p = mb_substr($p, 0, 300) . '…'; }
    return $p;
};
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Blog</title>
<meta name="description" content="All on Wheel - Blog post" />
<meta name="keywords" content="All on Wheel - Blog post" />
<meta name="robots" content="index, follow" />
<meta name="revisit-after" content="3" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<meta name="reply-to" content="" />

<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />
<link href="css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/ddsmoothmenu.js"></script>
<script type="text/javascript" src="js/piroBox.1_2.js"></script>
<script type="text/javascript" src="js/site_init.js"></script>
</head>
<body>
<div id="templatemo_wrapper"><div id="templatemo_header">
 <?php include ('header.php'); ?>
</div>

 <div id="content_top">
  <div id="page_title">News Blog Page</div>
  <div id="search_box">
 <form action="#" method="get">
  <input type="text" value="Search" name="q" size="10" id="searchfield" title="searchfield" onfocus="clearText(this)" onblur="clearText(this)" />
  <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
 </form>
  </div>
  <div class="cleaner"></div>
 </div>

 <div id="templatemo_content">

  <?php if ($flash_ok !== ''): ?>
    <div class="post_box"><p><strong><?php echo htmlspecialchars($flash_ok, ENT_QUOTES, 'UTF-8'); ?></strong></p></div>
  <?php endif; ?>
  <?php if ($flash_err !== ''): ?>
    <div class="post_box"><p><strong><?php echo htmlspecialchars($flash_err, ENT_QUOTES, 'UTF-8'); ?></strong></p></div>
  <?php endif; ?>

  <?php if (is_user_logged_in()): ?>
    <p><a href="blog_write.php" class="more">Write an article</a></p>
    <div class="cleaner h20"></div>
  <?php endif; ?>

  <?php if (empty($articles)): ?>
    <div class="post_box">
      <h2>No articles yet</h2>
      <p>There are no published articles at the moment.<?php echo is_user_logged_in() ? ' Be the first to write one!' : ''; ?></p>
    </div>
  <?php else: ?>
    <?php foreach ($articles as $a):
      $img = trim((string)($a['image'] ?? ''));
      $img_url = $img !== '' ? '/upload_image/blog/original/' . rawurlencode($img) : 'images/templatemo_image_06.jpg';
      $author  = trim((string)($a['username'] ?? '')) !== '' ? $a['username'] : 'All on Wheel';
    ?>
    <div class="post_box">
      <h2><a href="blog_post.php?id=<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars($a['title']); ?></a></h2>
      <img src="<?php echo htmlspecialchars($img_url, ENT_QUOTES, 'UTF-8'); ?>"
           alt="<?php echo htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8'); ?>"
           onerror="this.onerror=null;this.src='images/templatemo_image_06.jpg';" />
      <?php if (trim((string)($a['excerpt'] ?? '')) !== ''): ?>
      <p><em><?php echo htmlspecialchars($a['excerpt']); ?></em></p>
      <?php endif; ?>
      <p><?php echo htmlspecialchars($first_paragraph((string)$a['body'])); ?></p>
      <div class="post_meta">
        <span class="cat">Posted by <?php echo htmlspecialchars($author); ?></span>
        | Date: <?php echo htmlspecialchars(date('j F Y', strtotime((string)$a['created_at']))); ?>
        <a href="blog_post.php?id=<?php echo (int)$a['id']; ?>" class="more float_r">Continue</a>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if ($pages > 1): ?>
    <div class="templatemo_paging">
      <ul>
        <li><a href="<?php echo $page > 1 ? '?page=' . ($page - 1) : '#'; ?>">Previous</a></li>
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <li><a href="?page=<?php echo $i; ?>"<?php echo $i === $page ? ' class="active"' : ''; ?>><?php echo $i; ?></a></li>
        <?php endfor; ?>
        <li><a href="<?php echo $page < $pages ? '?page=' . ($page + 1) : '#'; ?>">Next</a></li>
      </ul>
      <div class="cleaner"></div>
    </div>
    <?php endif; ?>
  <?php endif; ?>

</div>
<!-- end of content -->
<div id="templatemo_sidebar">
<?php include __DIR__ . '/include_sidebar.php'; ?>
</div>
<div class="cleaner"></div>
<!-- inizia qui il piè di pagina -->
<?php include "footer.php"; ?>
<!-- finisce qui il piè di pagina -->
</body>
</html>
```

## `blog_post.php`

```php
<?php
// ============================================================
// blog_post.php — Articolo singolo del blog (DINAMICO).
// Legge l'articolo per ?id dalla tabella `blog`. Visibile a tutti se
// 'published'; l'autore puo' vedere i propri non pubblicati e l'admin
// qualsiasi stato. Markup/classi invariati (post_box, ecc.).
// ============================================================
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/libs/blog.class.php';

$blog = new BlogManager($pdo);

$id        = (int)($_GET['id'] ?? 0);
$viewer_id = is_user_logged_in() ? current_user_id() : null;
$is_admin  = !empty($_SESSION['user_tier']) && $_SESSION['user_tier'] === 'admin';

$article = $id > 0 ? $blog->getById($id, $viewer_id, $is_admin) : null;

$page_title = $article ? $article['title'] : 'Blog post';
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - <?php echo htmlspecialchars($page_title); ?></title>
<meta name="keywords" content="All on Wheel - Blog post" />
<meta name="description" content="All on Wheel - Blog post" />
<meta name="robots" content="index, follow" />
<meta name="revisit-after" content="3" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<meta name="reply-to" content="" />

<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />
<link href="css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/ddsmoothmenu.js"></script>
<script type="text/javascript" src="js/piroBox.1_2.js"></script>
<script type="text/javascript" src="js/site_init.js"></script>
</head>
<body>
<div id="templatemo_wrapper"><div id="templatemo_header">
 <?php include ('header.php'); ?>
</div>
 <div id="content_top">
  <div id="page_title">Full Blog Post</div>
  <div id="search_box">
 <form action="#" method="get">
  <input type="text" value="Search" name="q" size="10" id="searchfield" title="searchfield" onfocus="clearText(this)" onblur="clearText(this)" />
  <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
 </form>
  </div>
  <div class="cleaner"></div>
 </div>

 <div id="templatemo_content">
 <?php if (!$article): ?>
   <div class="post_box">
     <h2>Article not found</h2>
     <p>The article you are looking for does not exist or is not available.</p>
     <a href="blog.php" class="more">Back to the blog</a>
   </div>
 <?php else:
   $img    = trim((string)($article['image'] ?? ''));
   $author = trim((string)($article['username'] ?? '')) !== '' ? $article['username'] : 'All on Wheel';
   $paragraphs = preg_split('/\R{1,}/', trim((string)$article['body']));
 ?>
   <div class="post_box">
     <h2><?php echo htmlspecialchars($article['title']); ?></h2>
     <?php if ($article['status'] !== 'published'): ?>
       <p><em>(<?php echo htmlspecialchars($article['status']); ?> — visible only to you / the admin)</em></p>
     <?php endif; ?>
     <?php if ($img !== ''):
       $orig = '/upload_image/blog/original/' . rawurlencode($img);
     ?>
     <a class="pirobox" href="<?php echo htmlspecialchars($orig, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>">
       <img src="<?php echo htmlspecialchars($orig, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8'); ?>"
            onerror="this.onerror=null;this.src='images/templatemo_image_06.jpg';" />
     </a>
     <?php endif; ?>
     <?php if (trim((string)($article['excerpt'] ?? '')) !== ''): ?>
     <p><em><?php echo htmlspecialchars($article['excerpt']); ?></em></p>
     <?php endif; ?>
     <?php foreach ($paragraphs as $p): if (trim($p) === '') continue; ?>
     <p align="justify"><?php echo nl2br(htmlspecialchars($p)); ?></p>
     <?php endforeach; ?>
     <div class="post_meta">
       <span class="cat">Posted by <?php echo htmlspecialchars($author); ?></span>
       | Date: <?php echo htmlspecialchars(date('j F Y', strtotime((string)$article['created_at']))); ?>
       <a href="blog.php" class="more float_r">Back to the blog</a>
     </div>
   </div>

   <div class="cleaner"></div>
   <h3>Comments</h3>
   <div id="comment_section">
     <p>Comments are not available yet.</p>
   </div>
 <?php endif; ?>
</div>
<!-- end of content -->
<div id="templatemo_sidebar">
<?php include __DIR__ . '/include_sidebar.php'; ?>
</div>
<div class="cleaner"></div>
<!-- inizia qui il piè di pagina -->
<?php include "footer.php"; ?>
<!-- finisce qui il piè di pagina -->
</body>
</html>
```

## `blog_write.php` *(NUOVO)*

```php
<?php
// ============================================================
// blog_write.php — Form di pubblicazione articolo (utenti registrati).
// Login obbligatorio. Invia a blog_save.php (CSRF + upload immagine).
// ============================================================
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/session_helper.php';

require_user_logged_in();

$flash_err = $_SESSION['blog_error'] ?? '';
unset($_SESSION['blog_error']);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Write an article</title>
<meta name="robots" content="noindex, follow" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />

<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />
<link href="css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/ddsmoothmenu.js"></script>
<script type="text/javascript" src="js/piroBox.1_2.js"></script>
<script type="text/javascript" src="js/site_init.js"></script>
</head>
<body>
<div id="templatemo_wrapper"><div id="templatemo_header">
 <?php include ('header.php'); ?>
</div>
 <div id="content_top">
  <div id="page_title">Write an article</div>
  <div id="search_box">
 <form action="#" method="get">
  <input type="text" value="Search" name="q" size="10" id="searchfield" title="searchfield" onfocus="clearText(this)" onblur="clearText(this)" />
  <input type="submit" name="Search" value="" id="searchbutton" title="Search" />
 </form>
  </div>
  <div class="cleaner"></div>
 </div>

 <div id="templatemo_content">
  <?php if ($flash_err !== ''): ?>
    <div class="post_box"><p><strong><?php echo htmlspecialchars($flash_err, ENT_QUOTES, 'UTF-8'); ?></strong></p></div>
  <?php endif; ?>

  <div id="contact_form">
   <form name="blogform" method="post" action="blog_save.php" enctype="multipart/form-data">
    <?php echo csrf_generate(); ?>

    <label for="title">Title (* required)</label>
    <input type="text" id="title" name="title" class="required input_field" maxlength="200" />

    <div class="cleaner h10"></div>
    <label for="excerpt">Short summary (optional)</label>
    <input type="text" id="excerpt" name="excerpt" class="input_field" maxlength="255" />

    <div class="cleaner h10"></div>
    <label for="body">Article (* required)</label>
    <textarea id="body" name="body" rows="12" cols="0" class="required"></textarea>

    <div class="cleaner h10"></div>
    <label for="image">Cover image (optional, JPG/PNG/GIF)</label>
    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif" />

    <div class="cleaner h20"></div>
    <input type="submit" class="submit_btn float_r" name="submit" id="submit" value="Publish" />
    <a href="blog.php" class="back float_l">Cancel</a>
   </form>
  </div>
 </div>
<!-- end of content -->
<div id="templatemo_sidebar">
<?php include __DIR__ . '/include_sidebar.php'; ?>
</div>
<div class="cleaner"></div>
<!-- inizia qui il piè di pagina -->
<?php include "footer.php"; ?>
<!-- finisce qui il piè di pagina -->
</body>
</html>
```

## `blog_save.php` *(NUOVO)*

```php
<?php
// ============================================================
// blog_save.php — Handler pubblicazione articolo (utenti registrati).
// Login + CSRF + validazione + upload immagine opzionale. Inserisce in
// `blog`. Stato di default 'published' (immediato): per attivare la
// moderazione preventiva, impostare $status = 'pending' qui sotto.
// ============================================================
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/session_helper.php';
require_once __DIR__ . '/libs/blog.class.php';
require_once __DIR__ . '/libs/upload_helper.class.php';

$id_user = require_user_logged_in();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit'])) {
    header('Location: /blog_write.php');
    exit;
}

csrf_verify();

$title   = trim($_POST['title'] ?? '');
$excerpt = trim($_POST['excerpt'] ?? '');
$body    = trim($_POST['body'] ?? '');

if ($title === '' || $body === '') {
    $_SESSION['blog_error'] = 'Title and article text are required.';
    header('Location: /blog_write.php');
    exit;
}

// Upload immagine di copertina (opzionale)
$image_filename = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $res = UploadHelper::handleImageUpload($_FILES['image'], [
        'target_dir_original'  => '/upload_image/blog/original/',
        'target_dir_thumbnail' => '/upload_image/blog/thumbnail/',
        'thumb_width'          => 220,
        'thumb_height'         => 150,
        'thumb_crop'           => true,
        'max_size_bytes'       => 5 * 1024 * 1024,
        'filename_prefix'      => 'blog_' . $id_user,
    ]);
    if (!$res['ok']) {
        $_SESSION['blog_error'] = 'Image upload failed: ' . $res['error'];
        header('Location: /blog_write.php');
        exit;
    }
    $image_filename = (string)$res['filename'];
}

// Stato iniziale. Cambiare in 'pending' per moderazione preventiva admin.
$status = 'published';

try {
    $blog = new BlogManager($pdo);
    $new_id = $blog->insertArticle([
        'id_user' => $id_user,
        'title'   => $title,
        'excerpt' => $excerpt,
        'body'    => $body,
        'image'   => $image_filename,
        'status'  => $status,
    ]);
} catch (PDOException $e) {
    error_log('[Allonwheel] blog insert error: ' . $e->getMessage());
    // Cleanup immagine se l'insert fallisce
    if ($image_filename !== '') {
        $base = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/blog/';
        foreach (['original/', 'thumbnail/'] as $sub) {
            $f = $base . $sub . basename($image_filename);
            if (is_file($f)) { @unlink($f); }
        }
    }
    $_SESSION['blog_error'] = 'Database error while publishing the article. Please try again.';
    header('Location: /blog_write.php');
    exit;
}

if ($status === 'published' && $new_id > 0) {
    $_SESSION['blog_success'] = 'Article published successfully.';
    header('Location: /blog_post.php?id=' . $new_id);
} else {
    $_SESSION['blog_success'] = 'Article submitted and awaiting review.';
    header('Location: /blog.php');
}
exit;
```

## `_admin/moderate_blog.php` *(NUOVO)*

```php
<?php
// ============================================================
// /_admin/moderate_blog.php — Moderazione articoli del blog.
// Approve (-> published), Reject (-> rejected), Delete (rimuove anche le
// immagini). Audit via UserTier::logAdminAction (colonne corrette).
// ============================================================
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';
require_once __DIR__ . '/../libs/blog.class.php';

$admin_id = AdminAuth::requireAdminSession();
$blog = new BlogManager($pdo);

// ----- Azioni POST -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $blog_id = (int)($_POST['blog_id'] ?? 0);
    $action  = in_array($_POST['action'] ?? '', ['approve', 'reject', 'delete'], true)
                 ? $_POST['action'] : '';

    if ($blog_id > 0 && $action !== '') {
        if ($action === 'delete') {
            $image = $blog->deleteArticle($blog_id);
            // Cleanup file con protezione path-traversal
            if ($image) {
                $base = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/upload_image/blog/';
                $clean = static function (string $dir, string $filename): void {
                    $filename = basename($filename);
                    if ($filename === '') { return; }
                    $full = realpath($dir . $filename);
                    $root = realpath($dir);
                    if ($full === false || $root === false) { return; }
                    if (strpos($full, $root . DIRECTORY_SEPARATOR) !== 0) { return; }
                    if (is_file($full)) { @unlink($full); }
                };
                $clean($base . 'original/',  $image);
                $clean($base . 'thumbnail/', $image);
            }
            $_SESSION['admin_success'] = "Article #{$blog_id} deleted permanently.";
            UserTier::logAdminAction($pdo, $admin_id, 'delete_blog', null,
                'blog #' . $blog_id . ' deleted', $_SERVER['REMOTE_ADDR'] ?? '');
        } else {
            $new_status = $action === 'approve' ? 'published' : 'rejected';
            $blog->setStatus($blog_id, $new_status);
            $_SESSION['admin_success'] = "Article #{$blog_id} marked as {$new_status}.";
            UserTier::logAdminAction($pdo, $admin_id, 'moderate_blog', null,
                'blog #' . $blog_id . ' -> ' . $new_status, $_SERVER['REMOTE_ADDR'] ?? '');
        }
    }
    header('Location: /_admin/moderate_blog.php' . (isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : ''));
    exit;
}

// ----- Filtro -----
$allowed_filters = ['all', 'pending', 'published', 'rejected'];
$filter = in_array($_GET['filter'] ?? '', $allowed_filters, true) ? $_GET['filter'] : 'all';
$articles = $blog->listForModeration($filter);

csrf_generate();
$csrf_token = $_SESSION['csrf_token'] ?? '';
$success = $_SESSION['admin_success'] ?? '';
$error   = $_SESSION['admin_error']   ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin — Blog Moderation</title>
<meta name="robots" content="noindex, nofollow" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="../js/jquery.min.js"></script>
<script type="text/javascript" src="../js/ddsmoothmenu.js"></script>
<script type="text/javascript" src="../js/piroBox.1_2.js"></script>
<script type="text/javascript" src="../js/site_init.js"></script>
</head>
<body>
<div id="templatemo_wrapper">

  <div id="templatemo_header">
    <div id="site_title"><h1><a href="/index.php"></a></h1></div>
  </div>

  <div id="content_top">
    <div id="page_title">Blog Moderation</div>
    <div class="cleaner"></div>
  </div>

  <div id="templatemo_content" style="width:100%;">

    <?php if ($success !== ''): ?>
    <div class="post_box"><p class="done"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
    <div class="post_box"><p class="error-msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p></div>
    <?php endif; ?>

    <div class="post_box">
      <div class="post_meta">
        <a href="dashboard.php">Users</a> &nbsp;|&nbsp;
        <a href="moderate_ads.php">Ad moderation</a> &nbsp;|&nbsp;
        <a href="manage_companies.php">Companies</a> &nbsp;|&nbsp;
        <strong>Blog</strong>
        <a href="logout.php" class="more float_r">Sign out</a>
        <div class="cleaner"></div>
      </div>
    </div>

    <div class="post_box">
      <h2>Articles</h2>
      <p>
        <a href="?filter=all">All</a> &nbsp;|&nbsp;
        <a href="?filter=pending">Pending</a> &nbsp;|&nbsp;
        <a href="?filter=published">Published</a> &nbsp;|&nbsp;
        <a href="?filter=rejected">Rejected</a>
      </p>

      <?php if (empty($articles)): ?>
        <p>No articles<?php echo $filter !== 'all' ? ' with status "' . htmlspecialchars($filter) . '"' : ''; ?>.</p>
      <?php else: ?>
      <table width="100%" border="0" cellpadding="6" cellspacing="0" style="border-collapse:collapse;">
        <thead>
        <tr style="background:#1D275A;color:#fff;">
          <th align="left">#</th>
          <th align="left">Title</th>
          <th align="left">Author</th>
          <th align="left">Status</th>
          <th align="left">Date</th>
          <th align="left">Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($articles as $a): ?>
        <tr style="border-bottom:1px solid #eee;">
          <td><?php echo (int)$a['id']; ?></td>
          <td><a href="/blog_post.php?id=<?php echo (int)$a['id']; ?>" target="_blank"><?php echo htmlspecialchars($a['title']); ?></a></td>
          <td><?php echo htmlspecialchars((string)($a['username'] ?? '—')); ?></td>
          <td><strong><?php echo htmlspecialchars($a['status']); ?></strong></td>
          <td><?php echo htmlspecialchars(date('j M Y', strtotime((string)$a['created_at']))); ?></td>
          <td>
            <?php if ($a['status'] !== 'published'): ?>
            <form method="post" action="moderate_blog.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>" style="display:inline; margin:0;">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="blog_id" value="<?php echo (int)$a['id']; ?>" />
              <input type="hidden" name="action" value="approve" />
              <button type="submit" class="more">Publish</button>
            </form>
            <?php endif; ?>
            <?php if ($a['status'] !== 'rejected'): ?>
            <form method="post" action="moderate_blog.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                  style="display:inline; margin:0;" data-confirm="Reject article #<?php echo (int)$a['id']; ?>?">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="blog_id" value="<?php echo (int)$a['id']; ?>" />
              <input type="hidden" name="action" value="reject" />
              <button type="submit" class="more">Reject</button>
            </form>
            <?php endif; ?>
            <form method="post" action="moderate_blog.php<?php echo $filter !== 'all' ? '?filter=' . urlencode($filter) : ''; ?>"
                  style="display:inline; margin:0;" data-confirm="Permanently delete article #<?php echo (int)$a['id']; ?> and its image? This cannot be undone.">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="blog_id" value="<?php echo (int)$a['id']; ?>" />
              <input type="hidden" name="action" value="delete" />
              <button type="submit" class="more">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

  </div>
  <div class="cleaner"></div>
</div>
</body>
</html>
```

## `header.php`

```php
<?php
// ============================================================
// header.php — Header globale del sito (menu di navigazione)
//
// Revisione UX:
//  - Menu "About" separato da "Account" (erano mescolati)
//  - Nuovo item "Browse" raccoglie tutti gli annunci + categorie
//  - "Suppliers" accorpa directory + register company
//  - "Account" separato con username visibile quando loggato
//  - "Post ad" spostato sotto Account (non accessibile ai visitatori)
//  - Link "Contact" spostato sotto About (non è una voce top-level)
// ============================================================

require_once __DIR__ . '/config/session_helper.php';

// ----- Base path automatico -----
$base_url = '';
$script   = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
foreach (['00_first', '01_login', '02_free_ads', '03_ads', '04_request_offer', '06_company', '_admin', 'shared'] as $f) {
    if (strpos($script, '/' . $f . '/') !== false) {
        $base_url = '../';
        break;
    }
}

$is_logged_in     = is_user_logged_in();
$current_username = $is_logged_in ? current_username() : '';
$is_admin         = $is_logged_in && isset($_SESSION['user_tier']) && $_SESSION['user_tier'] === 'admin';
?>
<div id="site_title">
  <h1><a href="<?php echo $base_url; ?>index.php"></a></h1>
</div>

<div id="templatemo_menu" class="ddsmoothmenu">
<ul>

  <!-- Home -->
  <li><a href="<?php echo $base_url; ?>index.php">Home</a></li>

  <!-- Browse — tutto il marketplace in un posto solo -->
  <li><a href="<?php echo $base_url; ?>browse.php">Browse</a>
    <ul>
      <li><a href="<?php echo $base_url; ?>02_free_ads/02_view_ads.php">Free ads</a></li>
      <li><a href="<?php echo $base_url; ?>03_ads/03_view_ads.php">Premium ads</a></li>
      <li><a href="<?php echo $base_url; ?>00_first/racing_trailer.php">Racing trailers</a></li>
      <li><a href="<?php echo $base_url; ?>00_first/paddock_trailer.php">Paddock trailers</a></li>
      <li><a href="<?php echo $base_url; ?>00_first/hospitality.php">Hospitality units</a></li>
      <li><a href="<?php echo $base_url; ?>00_first/motorhome_mobilhome.php">Motorhomes</a></li>
      <li><a href="<?php echo $base_url; ?>00_first/roadshow.php">Roadshow vehicles</a></li>
      <li><a href="<?php echo $base_url; ?>00_first/sell_or_rent.php">Sell or rent</a></li>
    </ul>
  </li>

  <!-- Suppliers / Directory -->
  <li><a href="<?php echo $base_url; ?>06_company/06_30_company_directory.php">Suppliers</a>
	      <ul>
      <li><a href="<?php echo $base_url; ?>04_request_offer/04_request_offer.php">Quotation request</a></li>
    </ul>
  </li>

  <!-- About — solo contenuti editoriali -->
  <li><a href="<?php echo $base_url; ?>about.php">About</a>
    <ul>
      <li><a href="<?php echo $base_url; ?>about.php">Our story</a></li>
      <li><a href="<?php echo $base_url; ?>what_we_do.php">What we do</a></li>
      <li><a href="<?php echo $base_url; ?>blog.php">Blog</a></li>
      <li><a href="<?php echo $base_url; ?>FAQ.php">F.A.Q.</a></li>
      <li><a href="<?php echo $base_url; ?>Conditions.php">Conditions &amp; rules</a></li>
      <li><a href="<?php echo $base_url; ?>contact.php">Contact us</a></li>
    </ul>
  </li>

  <!-- Account — solo azioni utente -->
  <?php if ($is_logged_in): ?>
  <li><a href="<?php echo $base_url; ?>01_login/my_posts.php"><?php
    echo $current_username !== ''
      ? htmlspecialchars($current_username, ENT_QUOTES, 'UTF-8')
      : 'My area';
  ?></a>
    <ul>
      <li><a href="<?php echo $base_url; ?>01_login/my_posts.php">My posts</a></li>
      <li><a href="<?php echo $base_url; ?>01_login/all_about_me.php">My profile</a></li>
      <li><a href="<?php echo $base_url; ?>02_free_ads/02_00_select_type.php">Post free ad</a></li>
      <li><a href="<?php echo $base_url; ?>03_ads/03_00_select_type.php">Post premium ad</a></li>
		      <?php if ($is_logged_in): ?>
		        <li><a href="<?php echo $base_url; ?>06_company/06_10_register_company.php">Register company</a></li>
      <?php endif; ?>
      <li><a href="<?php echo $base_url; ?>blog_write.php">Write an article</a></li>
      <?php if ($is_admin): ?>
        <li><a href="<?php echo $base_url; ?>_admin/dashboard.php">Admin panel</a></li>
      <?php endif; ?>
      <li><a href="<?php echo $base_url; ?>01_login/logout.php">Logout</a></li>
    </ul>
  </li>
  <?php else: ?>
  <li><a href="<?php echo $base_url; ?>01_login/newlogin.php">Login</a>
    <ul>
      <li><a href="<?php echo $base_url; ?>01_login/newlogin.php">Login</a></li>
      <li><a href="<?php echo $base_url; ?>01_login/newregister.php">Create account</a></li>
      <li><a href="<?php echo $base_url; ?>01_login/forgot_password.php">Forgot password</a></li>
    </ul>
  </li>
  <?php endif; ?>

</ul>
<br style="clear: left" />
</div>
```

## `_admin/dashboard.php`

```php
<?php

// ============================================================

// /_admin/dashboard.php

// Pannello admin: tabella utenti con flag per concedere/revocare premium.

//

// Visibile solo dopo AdminAuth::requireAdminSession() (timeout 30 min,

// IP-bound, password re-auth obbligatoria).

// ============================================================



require_once __DIR__ . '/../config/bootstrap.php';

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../config/csrf.php';

require_once __DIR__ . '/../libs/user_tier.class.php';

require_once __DIR__ . '/../libs/admin_auth.class.php';



// Forza sessione admin valida

$admin_id = AdminAuth::requireAdminSession();



// Filtro: ?filter=pending → solo richieste pending

$filter  = isset($_GET['filter']) && $_GET['filter'] === 'pending' ? 'pending' : 'all';

$only_pend = ($filter === 'pending');

$users   = UserTier::listUsersForAdmin($pdo, $only_pend);



// Stats riassuntive

$stats = $pdo->query(

  "SELECT

    SUM(CASE WHEN user_tier='free'  THEN 1 ELSE 0 END) AS free_count,

    SUM(CASE WHEN user_tier='premium' THEN 1 ELSE 0 END) AS premium_count,

    SUM(CASE WHEN user_tier='admin' THEN 1 ELSE 0 END) AS admin_count,

    SUM(CASE WHEN premium_requested=1 AND user_tier='free' THEN 1 ELSE 0 END) AS pending_count

   FROM users"

)->fetch(PDO::FETCH_ASSOC);



// Token CSRF per i form di grant/revoke (uno per pagina, riusato in più form)

csrf_generate();

$csrf_token = $_SESSION['csrf_token'] ?? '';



// Flash messages

$success = $_SESSION['admin_success'] ?? '';

$error = $_SESSION['admin_error'] ?? '';

unset($_SESSION['admin_success'], $_SESSION['admin_error']);

?>

<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml">

<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Admin Dashboard</title>

<meta name="robots" content="noindex, nofollow" />

<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />

<link rel="icon" href="../images/favicon.ico" />

<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
<!--<link href="css_pirobox/white/style.css" media="screen" title="white" rel="stylesheet" type="text/css" />
<link href="css_pirobox/black/style.css" media="screen" title="black" rel="stylesheet" type="text/css" />-->
<!--////// END  \\\\\\\-->

<!--////// INCLUDE THE JS AND PIROBOX OPTION IN YOUR HEADER  \\\\\\\-->
<!--////// END  \\\\\\\-->
<script type="text/javascript" src="../js/jquery.min.js"></script>
<script type="text/javascript" src="../js/ddsmoothmenu.js"></script>
<script type="text/javascript" src="../js/piroBox.1_2.js"></script>
<script type="text/javascript" src="../js/site_init.js"></script>
</head>

<body>

<div id="templatemo_wrapper">



  <div id="templatemo_header">

    <div id="site_title">

    <h1><a href="/index.php"></a></h1>

    </div>

  </div>



  <div id="content_top">

    <div id="page_title">Premium Approvals</div>

    <div class="cleaner"></div>

  </div>



  <div id="templatemo_content" style="width:100%;">



    <?php if ($success !== ''): ?>

    <div class="post_box">

      <p class="done"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>

    </div>

    <?php endif; ?>

    <?php if ($error !== ''): ?>

    <div class="post_box">

      <p class="error-msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>

    </div>

    <?php endif; ?>



    <!-- Riepilogo + filtri -->

    <!-- Navigazione admin -->

    <div class="post_box">

      <div class="post_meta">

        <strong>Users</strong>

        &nbsp;|&nbsp;

        <a href="moderate_ads.php">Ad moderation</a>

        &nbsp;|&nbsp;

        <a href="manage_companies.php">Companies</a> &nbsp;|&nbsp;
        <a href="admin_vehicle_types.php">Vehicle Types</a> &nbsp;|&nbsp;
        <a href="moderate_blog.php">Blog</a>

        <div class="cleaner"></div>

      </div>

    </div>



    <div class="post_box">

    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?></h2>

    <p>

      <strong>Free:</strong> <?php echo (int)$stats['free_count']; ?> &nbsp;|&nbsp;

      <strong>Premium:</strong> <?php echo (int)$stats['premium_count']; ?> &nbsp;|&nbsp;

      <strong>Admin:</strong> <?php echo (int)$stats['admin_count']; ?> &nbsp;|&nbsp;

      <strong>Pending requests:</strong> <?php echo (int)$stats['pending_count']; ?>

    </p>

    <div class="post_meta">

      <a href="?filter=pending"<?php echo $filter === 'pending' ? ' active' : ''; ?>">

        Pending requests (<?php echo (int)$stats['pending_count']; ?>)

      </a>

      &nbsp;|

      <a href="?filter=all"<?php echo $filter === 'all' ? ' active' : ''; ?>">

        All users (<?php echo (int)($stats['free_count'] + $stats['premium_count'] + $stats['admin_count']); ?>)

      </a>

      &nbsp;

      <a href="logout.php" class="more float_r">Sign out</a>

      <div class="cleaner"></div>

    </div>

    </div>



    <!-- Tabella utenti -->

    <div class="post_box">

    <h2><?php echo $only_pend ? 'Pending premium requests' : 'All users'; ?></h2>



    <?php if (empty($users)): ?>

      <p><em><?php echo $only_pend ? 'No pending requests at the moment.' : 'No users found.'; ?></em></p>

    <?php else: ?>



      <table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse;">

        <thead>

        <tr style="background:#1D275A; color:#fff;">

          <th>ID</th>

          <th>Username</th>

          <th>Email</th>

          <th>Current tier</th>

          <th>Free / Premium ads</th>

          <th>Requested at</th>

          <th>Granted at</th>

          <th>Action</th>

        </tr>

        </thead>

        <tbody>

        <?php foreach ($users as $u):

          $is_admin = ($u['user_tier'] === 'admin');

          $is_premium = ($u['user_tier'] === 'premium');

          $is_free  = ($u['user_tier'] === 'free');

          $has_pend = ((int)$u['premium_requested'] === 1) && $is_free;

        ?>

        <tr<?php echo $has_pend ? ' style="background:#FFF8DC;"' : ''; ?>>

          <td><?php echo (int)$u['id_user']; ?></td>

          <td><?php echo htmlspecialchars($u['username']); ?></td>

          <td><?php echo htmlspecialchars($u['email']); ?></td>

          <td>

            <?php if ($is_admin): ?>

            <strong>admin</strong>

            <?php elseif ($is_premium): ?>

            <strong style="color:#1D275A;">premium</strong>

            <?php else: ?>

            free<?php echo $has_pend ? ' <em>(requested)</em>' : ''; ?>

            <?php endif; ?>

          </td>

          <td>

            <?php echo (int)$u['free_ads_count']; ?> /

            <?php echo (int)$u['premium_ads_count']; ?>

          </td>

          <td>

            <?php

            echo $u['premium_requested_at']

            ? htmlspecialchars(date('Y-m-d H:i', strtotime($u['premium_requested_at'])))

            : '—';

            ?>

          </td>

          <td>

            <?php

            echo $u['premium_granted_at']

            ? htmlspecialchars(date('Y-m-d H:i', strtotime($u['premium_granted_at'])))

            : '—';

            ?>

          </td>

          <td>

            <?php if ($is_admin): ?>

            <em>—</em>

            <?php elseif ($is_premium): ?>

            <!-- Form REVOKE -->

            <form method="post" action="grant_premium.php" style="margin:0;"

              onsubmit="return confirm('Revoke premium tier for <?php echo htmlspecialchars(addslashes($u['email'])); ?>?');">

              <input type="hidden" name="csrf_token"  value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />

              <input type="hidden" name="user_id"  value="<?php echo (int)$u['id_user']; ?>" />

              <input type="hidden" name="action"   value="revoke" />

              <button type="submit" class="more">Revoke</button>

            </form>

            <?php else: ?>

            <!-- Form GRANT -->

            <form method="post" action="grant_premium.php" style="margin:0;">

              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />

              <input type="hidden" name="user_id" value="<?php echo (int)$u['id_user']; ?>" />

              <input type="hidden" name="action"  value="grant" />

              <label>

                <input type="checkbox" name="confirm" value="1" required />

                Eligible

              </label>

              <button type="submit" class="more">Grant premium</button>

            </form>

            <?php endif; ?>

          </td>

        </tr>

        <?php endforeach; ?>

        </tbody>

      </table>



    <?php endif; ?>

    </div>



    <!-- Note di sicurezza -->

    <div class="post_box">

    <h3>Notes</h3>

    <p>

      Free users: max <strong><?php echo UserTier::FREE_AD_LIMIT; ?></strong> free ads. &nbsp;

      Premium users: max <strong><?php echo UserTier::PREMIUM_AD_LIMIT; ?></strong> premium ads (plus the same free ad allowance).

    </p>

    <p>

      Every grant or revoke is logged in <code>admin_audit_log</code> with timestamp, IP and details.

      Session expires after <?php echo AdminAuth::ADMIN_SESSION_MINUTES; ?> minutes of inactivity.

    </p>

    </div>



  </div>



  <div class="cleaner"></div>



  <div id="templatemo_footer">

    <p style="text-align:center; padding:10px 0;">&copy; All on Wheel Ltd. — Restricted area</p>

  </div>



</div>

</body>

</html>
```

