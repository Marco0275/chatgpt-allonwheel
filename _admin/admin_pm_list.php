<?php
// _admin/admin_pm_list.php — Invio dell'elenco Project manager + Consulenti
// alle aziende che hanno spuntato "ricevi elenco PM/consulenti" (wants_pm_list=1).
// Email one-to-one (1 destinatario per messaggio). Solo admin.
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/mailer.class.php';

$admin_id = AdminAuth::requireAdminSession();

// Persone per ruolo (via PDO)
$rolePeople = static function (PDO $pdo, string $role): array {
    $st = $pdo->prepare(
        'SELECT u.username, u.email, u.phone
           FROM `user_roles` r JOIN `users` u ON u.id_user = r.user_id
          WHERE r.role = :r ORDER BY u.username'
    );
    $st->execute([':r' => $role]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
};
$e = static function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

$pms  = $rolePeople($pdo, 'project_manager');
$cons = $rolePeople($pdo, 'consultant');

// Aziende che vogliono l'elenco
$companies = $pdo->query(
    "SELECT ragione_sociale, email FROM `06_company`
      WHERE attiva = 1 AND wants_pm_list = 1 AND email <> '' ORDER BY ragione_sociale"
)->fetchAll(PDO::FETCH_ASSOC);

// Corpo email con l'elenco
$rowLi = static function (array $p) use ($e): string {
    $ph = trim((string)($p['phone'] ?? ''));
    return '<li>' . $e($p['username']) . ' &mdash; ' . $e($p['email'])
         . ($ph !== '' ? ' &mdash; ' . $e($ph) : '') . '</li>';
};
$listHtml = '<h3>Project managers</h3>'
    . ($pms ? '<ul>' . implode('', array_map($rowLi, $pms)) . '</ul>' : '<p>None.</p>')
    . '<h3>Consultants</h3>'
    . ($cons ? '<ul>' . implode('', array_map($rowLi, $cons)) . '</ul>' : '<p>None.</p>');

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
    csrf_verify();
    $sent = 0; $fail = 0;
    foreach ($companies as $c) {
        $body = '<p>Dear ' . $e($c['ragione_sociale']) . ',</p>'
              . '<p>Here is the current list of project managers and consultants on All on Wheel:</p>'
              . $listHtml
              . '<p>All on Wheel Ltd</p>';
        if (Mailer::send((string)$c['email'], 'Project managers & consultants list', $body, '', (string)$c['ragione_sociale'])) {
            $sent++;
        } else {
            $fail++;
        }
    }
    $msg = "List sent to {$sent} company(ies)" . ($fail ? ", {$fail} failed." : '.');
}

csrf_generate();
$csrf = $_SESSION['csrf_token'] ?? '';
$admin_title  = 'PM/Consultant list';
$admin_active = 'pmlist';
include __DIR__ . '/admin_header.php';
?>
<div class="post_box">
  <h2>Project managers &amp; consultants list</h2>
  <?php if ($msg !== ''): ?><p><em><?php echo $e($msg); ?></em></p><?php endif; ?>
  <p>Send the current list to the <strong><?php echo count($companies); ?></strong> company(ies)
     that opted in to receive it.</p>

  <form method="post" action="admin_pm_list.php">
    <input type="hidden" name="csrf_token" value="<?php echo $e($csrf); ?>" />
    <input type="hidden" name="action" value="send" />
    <div class="post_meta"><input type="submit" class="more float_r" value="Send list to companies"<?php echo $companies ? '' : ' disabled'; ?> /></div>
    <div class="cleaner"></div>
  </form>

  <h3>Project managers (<?php echo count($pms); ?>)</h3>
  <?php if ($pms): ?><ul>
    <?php foreach ($pms as $p): ?><li><?php echo $e($p['username']); ?> &mdash; <?php echo $e($p['email']); ?><?php if (trim((string)$p['phone']) !== ''): ?> &mdash; <?php echo $e($p['phone']); ?><?php endif; ?></li><?php endforeach; ?>
  </ul><?php else: ?><p><em>No project managers yet.</em></p><?php endif; ?>

  <h3>Consultants (<?php echo count($cons); ?>)</h3>
  <?php if ($cons): ?><ul>
    <?php foreach ($cons as $p): ?><li><?php echo $e($p['username']); ?> &mdash; <?php echo $e($p['email']); ?><?php if (trim((string)$p['phone']) !== ''): ?> &mdash; <?php echo $e($p['phone']); ?><?php endif; ?></li><?php endforeach; ?>
  </ul><?php else: ?><p><em>No consultants yet.</em></p><?php endif; ?>

  <h3>Companies receiving the list (<?php echo count($companies); ?>)</h3>
  <?php if ($companies): ?><ul>
    <?php foreach ($companies as $c): ?><li><?php echo $e($c['ragione_sociale']); ?> &mdash; <?php echo $e($c['email']); ?></li><?php endforeach; ?>
  </ul><?php else: ?><p><em>No company opted in yet.</em></p><?php endif; ?>
</div>
<?php include __DIR__ . '/admin_footer.php'; ?>
