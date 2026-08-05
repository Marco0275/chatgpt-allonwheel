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
require_once __DIR__ . '/libs/user_roles.class.php';

$comment_blog_id = (int)($article['id'] ?? 0);
$comments        = $blog->listComments($comment_blog_id);
$can_comment     = is_user_logged_in();
$me_id           = $can_comment ? (int)current_user_id() : 0;
$me_is_admin     = !empty($_SESSION['user_tier']) && $_SESSION['user_tier'] === 'admin';

$c_ok  = $_SESSION['comment_success'] ?? '';
$c_err = $_SESSION['comment_error'] ?? '';
unset($_SESSION['comment_success'], $_SESSION['comment_error']);
?>
<link href="allonwheel_style.css" rel="stylesheet" type="text/css">

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
      $c_expert = UserRoles::hasRolePdo($pdo, (int)($c['id_user'] ?? 0), 'expert');
      // Avatar = immagine del profilo dell'autore; fallback su images/avator.jpg
      $pimg   = trim((string)($c['profile_image'] ?? ''));
      $avatar = $pimg !== '' ? '/upload_image/profile/thumbnail/' . rawurlencode($pimg) : 'images/avator.jpg';
      $box    = ($i % 2 === 0) ? 'commentbox1' : 'commentbox2';
      $can_del = $me_is_admin || ($me_id > 0 && (int)$c['id_user'] === $me_id);
    ?>
    <li>
      <div class="comment_box <?php echo $box; ?>">
        <div class="gravatar"><img src="<?php echo htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($author, ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" decoding="async" /></div>
        <div class="comment_text">
          <div class="comment_author"><?php echo htmlspecialchars($author); ?><?php if ($c_expert): ?> <span class="badge">Expert</span><?php endif; ?>
            <span class="date"><?php echo htmlspecialchars(date('j F Y', strtotime((string)$c['created_at']))); ?></span>
            <span class="time"><?php echo htmlspecialchars(date('g:i a', strtotime((string)$c['created_at']))); ?></span>
          </div>
          <p><?php echo nl2br(htmlspecialchars((string)$c['body'])); ?></p>
          <?php if ($can_del): ?>
          <div class="reply">
            <form method="post" action="blog_comment_save.php" class="inline_form" >
              <?php echo csrf_generate(); ?>
              <input type="hidden" name="action" value="delete" />
              <input type="hidden" name="comment_id" value="<?php echo (int)$c['id']; ?>" />
              <input type="hidden" name="id_blog" value="<?php echo $comment_blog_id; ?>" />
              <button type="submit" class="more float_r">Delete</button>
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

<div id="comment_form">
  <?php if ($can_comment): ?>
  <h3>Leave your comment</h3>
  <form action="blog_comment_save.php" method="post">
    <?php echo csrf_generate(); ?>
    <input type="hidden" name="action" value="add" />
    <input type="hidden" name="id_blog" value="<?php echo $comment_blog_id; ?>" />
    <div class="form_row">
      <label>Comment (* required)</label><br />
      <textarea name="body" rows="0" cols="0"></textarea>
    </div>
    <button type="submit" name="submit" value="Post" class="more float_r">Post</button>
	  </br>
  </form>
  <?php else: ?>
  <p>Please <a href="01_login/newlogin.php">log in</a> to leave a comment.</p>
  <?php endif; ?>
</div>
