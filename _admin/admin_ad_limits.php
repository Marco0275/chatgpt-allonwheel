<?php
// ============================================================
// _admin/admin_ad_limits.php
// Quanti annunci puo' pubblicare un utente: limiti modificabili da admin.
//
// 23 lug 2026. I due numeri erano costanti nel codice (UserTier), quindi per
// cambiarli serviva mettere le mani in un file. Ora vivono in site_settings e
// si cambiano da qui.
//
// COSA CONTANO ESATTAMENTE
// Il limite e' sul TOTALE degli annunci dell'utente (free + premium), come
// gia' faceva UserTier: non sono due contatori separati. Il valore "premium"
// e' quello dei piani Premium, il valore "free" quello dei piani Basic.
// 0 = illimitato.
//
// Restano fuori dal limite, come prima: admin, tier Gold e la whitelist
// UNLIMITED_EMAILS.
// ============================================================
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/admin_auth.class.php';
require_once __DIR__ . '/../libs/site_settings.class.php';
require_once __DIR__ . '/../libs/user_tier.class.php';

$admin_id = AdminAuth::requireAdminSession();

$msg = '';
$msg_ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Solo interi >= 0. Vuoto o non numerico = valore rifiutato, non azzerato:
    // azzerare per errore renderebbe tutti gli utenti illimitati.
    $raw_free = trim((string)($_POST['ad_limit_free'] ?? ''));
    $raw_prem = trim((string)($_POST['ad_limit_premium'] ?? ''));

    if ($raw_free === '' || !ctype_digit($raw_free) || $raw_prem === '' || !ctype_digit($raw_prem)) {
        $msg = 'Please enter whole numbers (0 or more). Nothing was changed.';
    } else {
        $n_free = (int)$raw_free;
        $n_prem = (int)$raw_prem;
        // Un tetto di sanita': oltre e' quasi certo un errore di battitura.
        if ($n_free > 9999 || $n_prem > 9999) {
            $msg = 'Values above 9999 are not accepted. Nothing was changed.';
        } else {
            $ok1 = SiteSettings::set($pdo, UserTier::SETTING_LIMIT_FREE, (string)$n_free, (int)$admin_id);
            $ok2 = SiteSettings::set($pdo, UserTier::SETTING_LIMIT_PREMIUM, (string)$n_prem, (int)$admin_id);
            if ($ok1 && $ok2) {
                $msg = 'Listing limits updated.';
                $msg_ok = true;
            } else {
                $msg = 'Could not save the limits. Check that the site_settings table exists.';
            }
        }
    }
}

// Valori correnti (dopo l'eventuale salvataggio)
$cur_free = UserTier::limitFor($pdo, UserTier::TIER_FREE);
$cur_prem = UserTier::limitFor($pdo, UserTier::TIER_PREMIUM);

// Quanti utenti ci sono per tier, e quanti sono gia' oltre il limite:
// serve a capire l'effetto di un abbassamento PRIMA di applicarlo.
$stats = ['free' => ['users' => 0, 'over' => 0], 'premium' => ['users' => 0, 'over' => 0]];
try {
    $sql = "SELECT u.user_tier, COUNT(*) AS n_users,
                   SUM(CASE WHEN t.n_ads > :cap THEN 1 ELSE 0 END) AS n_over
              FROM users u
              LEFT JOIN (
                    SELECT id_user, COUNT(*) AS n_ads FROM `02_free_ads` GROUP BY id_user
                    UNION ALL
                    SELECT id_user, COUNT(*) AS n_ads FROM `03_ads` GROUP BY id_user
              ) t ON t.id_user = u.id_user
             WHERE u.user_tier = :tier
             GROUP BY u.user_tier";
    foreach ([UserTier::TIER_FREE => $cur_free, UserTier::TIER_PREMIUM => $cur_prem] as $tier => $cap) {
        $st = $pdo->prepare($sql);
        $st->execute([':tier' => $tier, ':cap' => $cap > 0 ? $cap : 999999]);
        if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $stats[$tier]['users'] = (int)$row['n_users'];
            $stats[$tier]['over']  = (int)$row['n_over'];
        }
    }
} catch (Throwable $e) {
    error_log('[Allonwheel] admin_ad_limits stats: ' . $e->getMessage());
}

csrf_generate();
$csrf = $_SESSION['csrf_token'] ?? '';
require __DIR__ . '/admin_header.php';
?>
<h2>Listing limits</h2>

<?php if ($msg !== ''): ?>
<p class="<?php echo $msg_ok ? 'admin_ok' : 'admin_bad'; ?>"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
<?php endif; ?>

<p class="admin_muted">
  How many listings a user may publish. The limit counts the
  <strong>total</strong> of free and premium ads of that user, which is how the
  check already worked. Use <strong>0</strong> for unlimited.
  Admins, Gold users and the unlimited whitelist are never limited.
</p>

<form method="post" action="admin_ad_limits.php" class="admin_form">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>" />
  <table class="admin_table" border="1" cellpadding="6" cellspacing="0">
    <thead>
      <tr class="admin_thead_row">
        <th style="text-align: center">Plan</th><th style="text-align: center">Tier</th><th style="text-align: center">Max listings</th><th style="text-align: center">Users</th><th style="text-align: center">Already over the limit</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td style="text-align: center"> Basic</td>
        <td style="text-align: center">free</td>
        <td style="text-align: center"><input type="number" min="0" max="9999" step="1" name="ad_limit_free" class="input_field"
                   value="<?php echo (int)$cur_free; ?>" /></td>
        <td style="text-align: center"><?php echo (int)$stats[UserTier::TIER_FREE]['users']; ?></td>
        <td style="text-align: center"><?php echo (int)$stats[UserTier::TIER_FREE]['over']; ?></td>
      </tr>
      <tr>
        <td style="text-align: center">Premium Verified</td>
        <td style="text-align: center">premium</td>
        <td style="text-align: center"><input type="number" min="0" max="9999" step="1" name="ad_limit_premium" class="input_field"
                   value="<?php echo (int)$cur_prem; ?>" /></td>
        <td style="text-align: center"><?php echo (int)$stats[UserTier::TIER_PREMIUM]['users']; ?></td>
        <td style="text-align: center"><?php echo (int)$stats[UserTier::TIER_PREMIUM]['over']; ?></td>
      </tr>
    </tbody>
  </table>
	<div class="cleaner h10"></div>
  <p><input type="submit" class="more" value="Save limits" /></p>
</form>

<p class="admin_footer_note">
  Lowering a limit does not delete anything: users already above it simply
  cannot publish new listings until they are back under the threshold.
  The &quot;already over&quot; column above tells you how many users that would affect.
</p>
<?php require __DIR__ . '/admin_footer.php'; ?>
