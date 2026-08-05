<?php
// ============================================================
// /_admin/manage_records.php
// Hub del pannello "Records": punto unico da cui l'admin raggiunge i
// form di inserimento/modifica dei record nelle tabelle principali
// (annunci free, annunci premium, aziende, tipi veicolo, blog, utenti).
//
// Mostra il conteggio dei record per ciascuna tabella e i link agli editor.
// Pagina di sola lettura. Stile: solo classi del foglio esistente (dir. 8).
// Accesso: solo dopo AdminAuth::requireAdminSession().
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';

$admin_id = AdminAuth::requireAdminSession();

function aw_count(PDO $pdo, string $table): int
{
    try {
        return (int)$pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

$cards = [
    ['02_free_ads',   'Free Ads',      aw_count($pdo, '02_free_ads'),   'edit_ad.php?type=free',    'Insert / edit free classified ads.'],
    ['03_ads',        'Premium Ads',   aw_count($pdo, '03_ads'),        'edit_ad.php?type=premium', 'Insert / edit premium ads + technical details.'],
    ['06_company',    'Companies',     aw_count($pdo, '06_company'),    'edit_company.php',         'Insert / edit companies + products, special categories, services.'],
    ['vehicle_types', 'Vehicle Types', aw_count($pdo, 'vehicle_types'), 'admin_vehicle_types.php',  'Road / Special taxonomy used by the filters.'],
    ['blog',          'Blog posts',    aw_count($pdo, 'blog'),          'moderate_blog.php',        'Review and publish blog articles.'],
    ['users',         'Users',         aw_count($pdo, 'users'),         'dashboard.php',            'User tiers and premium approvals.'],
];

$admin_title  = 'Advertising';
$admin_active = 'records';
require __DIR__ . '/admin_header.php';
?>


    <div class="post_box">
      <h2>Insert / edit records</h2>
      <p>Pick a table to add new records or edit existing ones. Image upload, gallery and
         full deletion stay in the dedicated moderation pages and in the public flow, so
         the <code>upload</code> / <code>images</code> folders are never touched from here.</p>

      <table class="admin_table" border="1" cellpadding="6" cellspacing="0">
        <thead>
          <tr>
            <th style="text-align: center">Table</th><th style="text-align: center">Section</th><th width="10%" style="text-align: center">Records</th><th style="text-align: center">What you can do</th><th width="16%" style="text-align: center">Open</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($cards as $c): ?>
          <tr>
            <td style="text-align: center"><code><?php echo htmlspecialchars($c[0]); ?></code></td>
            <td style="text-align: center"><strong><?php echo htmlspecialchars($c[1]); ?></strong></td>
            <td align="center" style="text-align: center"><?php echo (int)$c[2]; ?></td>
            <td style="text-align: center"><?php echo htmlspecialchars($c[4]); ?></td>
            <td align="center" style="text-align: center"><a class="more" href="<?php echo htmlspecialchars($c[3]); ?>">Manage</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="post_box">
      <h3>Notes</h3>
      <p>Every create/update is recorded in <code>admin_audit_log</code> with the admin id,
         action, target user, timestamp and IP. Child tables (galleries, products, services)
         are synced from their own editors.</p>
    </div>

  
<?php require __DIR__ . '/admin_footer.php'; ?>
