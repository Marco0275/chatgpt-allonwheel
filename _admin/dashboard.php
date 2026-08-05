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
    SUM(CASE WHEN user_tier='gold' THEN 1 ELSE 0 END) AS gold_count,
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

$admin_title  = 'Premium Approvals';
$admin_active = 'users';
require __DIR__ . '/admin_header.php';
?>

     <div id="templatemo_content" class="admin_full">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?></h2>
    <p>
      <strong>Free:</strong> <?php echo (int)$stats['free_count']; ?> &nbsp;|&nbsp;
      <strong>Premium:</strong> <?php echo (int)$stats['premium_count']; ?> &nbsp;|&nbsp;
      <strong>Admin:</strong> <?php echo (int)$stats['admin_count']; ?> &nbsp;|&nbsp;
      <strong>Pending requests:</strong> <?php echo (int)$stats['pending_count']; ?>
    </p>
    <div>
      <a href="?filter=pending"<?php echo $filter === 'pending' ? : ''; ?>>
        Pending requests (<?php echo (int)$stats['pending_count']; ?>)
      </a>
      &nbsp;|
      <a href="?filter=all"<?php echo $filter === 'all' ? : ''; ?>>
        All users (<?php echo (int)($stats['free_count'] + $stats['premium_count'] + $stats['admin_count']); ?>)
      </a>
      &nbsp;
      <div class="cleaner"></div>
    </div>
    </div>
    <!-- Tabella utenti -->
    <div id="templatemo_content" class="admin_full">
    <h2><?php echo $only_pend ? 'Pending premium requests' : 'All users'; ?></h2>
    <?php if (empty($users)): ?>
      <p><em><?php echo $only_pend ? 'No pending requests at the moment.' : 'No users found.'; ?></em></p>
    <?php else: ?>
      <table width="100%" border="1" cellpadding="1" cellspacing="0" class="admin_table">
        <thead>
        <tr>
          <th nowrap="nowrap" style="text-align: center">ID</th>
          <th nowrap="nowrap" style="text-align: center">Username</th>
          <th nowrap="nowrap" style="text-align: center">Email</th>
          <th nowrap="nowrap" style="text-align: center">Current tier</th>
          <th nowrap="nowrap" style="text-align: center">Free / Premium ads</th>
          <th nowrap="nowrap" style="text-align: center">Requested at</th>
          <th nowrap="nowrap" style="text-align: center">Granted at</th>
          <th width="19%" nowrap="nowrap" style="text-align: center">Action</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u):
          $is_admin = ($u['user_tier'] === 'admin');
          $is_premium = ($u['user_tier'] === 'premium');
          $is_gold  = ($u['user_tier'] === 'gold');
          $is_free  = ($u['user_tier'] === 'free');
          $has_pend = ((int)$u['premium_requested'] === 1) && $is_free;
        ?>
        <tr<?php echo $has_pend ? ' class="admin_row_pending"' : ''; ?>>
          <td align="center" valign="middle" style="text-align: center"><?php echo (int)$u['id_user']; ?></td>
          <td align="center" valign="middle" style="text-align: center"><?php echo htmlspecialchars($u['username']); ?></td>
          <td align="center" valign="middle" style="text-align: center"><?php echo htmlspecialchars($u['email']); ?></td>
          <td align="center" valign="middle" style="text-align: center">
            <?php if ($is_admin): ?>
            <strong>admin</strong>
            <?php elseif ($is_gold): ?>
            <strong>&#9733; GOLD</strong>
            <?php elseif ($is_premium): ?>
            <strong>premium (Premium)</strong>
            <?php else: ?>
            free<?php echo $has_pend ? ' <em>(requested)</em>' : ''; ?>
            <?php endif; ?>
          </td>
          <td align="center" valign="middle" style="text-align: center">
            <?php echo (int)$u['free_ads_count']; ?> /
            <?php echo (int)$u['premium_ads_count']; ?>
          </td>
          <td align="center" valign="middle" style="text-align: center">
            <?php
            echo $u['premium_requested_at']
            ? htmlspecialchars(date('Y-m-d', strtotime($u['premium_requested_at'])))
            : '—';
            ?>
          </td>
          <td align="center" valign="middle" style="text-align: center">
            <?php
            echo $u['premium_granted_at']
            ? htmlspecialchars(date('Y-m-d', strtotime($u['premium_granted_at'])))
            : '—';
            ?>
          </td>
          <td align="center" valign="middle" style="text-align: center">
            <?php if ($is_admin): ?>
            <em>—</em>
            <?php elseif ($is_gold): ?>
            <!-- Revoca GOLD -->
            <form method="post" action="grant_premium.php">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="user_id" value="<?php echo (int)$u['id_user']; ?>" />
              <input type="hidden" name="action" value="revoke" />
              <button type="submit" class="more">Revoke Gold</button>
            </form>
            <?php elseif ($is_premium): ?>
            <!-- Upgrade a GOLD -->
            <form method="post" action="grant_premium.php">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="user_id" value="<?php echo (int)$u['id_user']; ?>" />
              <input type="hidden" name="action" value="grant" />
              <input type="hidden" name="plan" value="gold" />
              <input name="confirm" type="hidden" value="1" />
              <button type="submit" class="more">&#9733; Gold</button>
            </form>
            <!-- Form REVOKE -->
            <form method="post" action="grant_premium.php" >
              <input type="hidden" name="csrf_token"  value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="user_id"  value="<?php echo (int)$u['id_user']; ?>" />
              <input type="hidden" name="action"   value="revoke" />
	</br>
              <button type="submit" class="more">Revoke</button>
				</br>
            </form>
            <?php else: ?>
            <!-- Form GRANT -->
            <form method="post" action="grant_premium.php">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="user_id" value="<?php echo (int)$u['id_user']; ?>" />
              <input type="hidden" name="action"  value="grant" />
				</br>
				<input name="confirm" type="hidden" required value="1" checked="checked" />
              <button type="submit" class="more">Premium</button>
								</br>
            </form>
            <form method="post" action="grant_premium.php">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" />
              <input type="hidden" name="user_id" value="<?php echo (int)$u['id_user']; ?>" />
              <input type="hidden" name="action" value="grant" />
              <input type="hidden" name="plan" value="gold" />
              <input name="confirm" type="hidden" value="1" />
              <button type="submit" class="more">&#9733; Gold</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
<div class="cleaner h20"></div>
    </div>
    <!-- Note di sicurezza -->

    <div id="templatemo_content" class="admin_full">
    <h3>Notes</h3>
    <p>
      <?php // Valori REALI configurati (admin_ad_limits.php). Il limite e' sul
            // TOTALE degli annunci dell'utente, free + premium insieme: prima qui
            // si leggevano due costanti deprecate che indicavano altri numeri. ?>
      Basic (free) plan: max <strong><?php echo (int)UserTier::limitFor($pdo, UserTier::TIER_FREE); ?></strong> listings in total. &nbsp;
      Premium (premium) plan: max <strong><?php echo (int)UserTier::limitFor($pdo, UserTier::TIER_PREMIUM); ?></strong> listings in total.
      <br /><a href="admin_ad_limits.php">Change these limits</a>.
    </p>
    <p>
      Every grant or revoke is logged in <code>admin_audit_log</code> with timestamp, IP and details.
      Session expires after <?php echo AdminAuth::ADMIN_SESSION_MINUTES; ?> minutes of inactivity.
    </p>
    </div>
  
<?php require __DIR__ . '/admin_footer.php'; ?>
