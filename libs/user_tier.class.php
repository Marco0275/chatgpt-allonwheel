<?php
// ============================================================
// libs/user_tier.class.php
// Business logic dei tier utente (free / premium / admin)
//
// Costanti chiave:
// FREE_AD_LIMIT   = 15  annunci free max per utente
// PREMIUM_AD_LIMIT  = 5  annunci premium max per utente
//
// REGOLE:
// - Utente 'free'  : può creare fino a 15 free ads. 0 premium ads.
// - Utente 'premium' : può creare fino a 15 free ads + 5 premium ads.
// - Utente 'admin' : non ha limiti (può comunque essere bloccato per sanity).
//
// USO TIPICO:
// require_once __DIR__ . '/../libs/user_tier.class.php';
// $check = UserTier::canInsertFreeAd($pdo, $user_id);
// if (!$check['allowed']) {
//   $_SESSION['error_message'] = $check['reason'];
//   header('Location: ...'); exit;
// }
// ============================================================

class UserTier
{
  const TIER_FREE  = 'free';
  const TIER_PREMIUM = 'premium';
  const TIER_ADMIN = 'admin';
  const TIER_GOLD  = 'gold'; // piano Gold Domination (landing)

  // Piani di pubblicazione (landing 7 lug 2026): limiti sul TOTALE annunci.
  // I due valori qui sotto sono i DEFAULT di fabbrica. Il numero effettivo di
  // annunci consentiti si cambia da admin (_admin/admin_ad_limits.php) e vive
  // in site_settings: vedi limitFor(). Le costanti restano come rete di
  // sicurezza se la tabella non fosse ancora popolata.
  const BASIC_TOTAL_LIMIT  = 2;   // tier 'free'  = piano Basic
  const Premium_TOTAL_LIMIT = 10;  // tier 'premium' = piano Premium (10 annunci totali)
  // tier 'gold' = illimitati. Deprecati (mantenuti per compatibilita'):
  const FREE_AD_LIMIT  = 15;
  const PREMIUM_AD_LIMIT = 5;

  // ---------------------------------------------------------
  // Utenti con quota ILLIMITATA (free + premium), per email.
  // Bypassano sia il limite numerico sia la restrizione
  // "free non puo' postare premium", SENZA privilegi admin.
  // Confronto case-insensitive.
  // ---------------------------------------------------------
  const UNLIMITED_EMAILS = [
    'marco.candian@yahoo.it',
  ];

  // Chiavi in site_settings che tengono i limiti modificabili da admin.
  const SETTING_LIMIT_FREE    = 'ad_limit_free';
  const SETTING_LIMIT_PREMIUM = 'ad_limit_premium';

  /**
   * Numero massimo di annunci (TOTALE free+premium) per il tier indicato.
   * Legge da site_settings; se manca la riga o la tabella, torna al default
   * di fabbrica. 0 = illimitato.
   */
  public static function limitFor(PDO $pdo, string $tier): int
  {
    require_once __DIR__ . '/site_settings.class.php';
    if ($tier === self::TIER_PREMIUM) {
      $key = self::SETTING_LIMIT_PREMIUM;
      $def = self::Premium_TOTAL_LIMIT;
    } else {
      $key = self::SETTING_LIMIT_FREE;
      $def = self::BASIC_TOTAL_LIMIT;
    }
    $raw = SiteSettings::get($pdo, $key, (string)$def);
    // Solo interi >= 0; qualunque valore sporco ricade sul default.
    return (is_numeric($raw) && (int)$raw >= 0) ? (int)$raw : $def;
  }

  /**
   * True se l'utente (per email) e' nella whitelist illimitata.
   */
  public static function isUnlimitedUser(PDO $pdo, int $user_id): bool
  {
    $stmt = $pdo->prepare('SELECT email FROM users WHERE id_user = :id LIMIT 1');
    $stmt->execute([':id' => $user_id]);
    $email = $stmt->fetchColumn();
    if (!$email) {
    return false;
    }
    $email = strtolower(trim((string)$email));
    foreach (self::UNLIMITED_EMAILS as $allowed) {
    if (strtolower(trim($allowed)) === $email) {
      return true;
    }
    }
    return false;
  }

  // ---------------------------------------------------------
  // Lookup tier
  // ---------------------------------------------------------

  /**
   * Ritorna il tier dell'utente ('free' | 'premium' | 'admin').
   * Se l'utente non esiste ritorna 'free' (fail-safe).
   */
  public static function getTier(PDO $pdo, int $user_id): string
  {
    $stmt = $pdo->prepare('SELECT user_tier FROM users WHERE id_user = :id LIMIT 1');
    $stmt->execute([':id' => $user_id]);
    $tier = $stmt->fetchColumn();
    if (!$tier) {
    return self::TIER_FREE;
    }
    return (string)$tier;
  }

  /**
   * True se l'utente è admin.
   */
  public static function isAdmin(PDO $pdo, int $user_id): bool
  {
    return self::getTier($pdo, $user_id) === self::TIER_ADMIN;
  }

  /**
   * True se l'utente è premium o admin.
   */
  public static function isPremiumOrAdmin(PDO $pdo, int $user_id): bool
  {
    $tier = self::getTier($pdo, $user_id);
    return $tier === self::TIER_PREMIUM || $tier === self::TIER_ADMIN;
  }

  // ---------------------------------------------------------
  // Conteggio annunci posseduti
  // ---------------------------------------------------------

  public static function countFreeAds(PDO $pdo, int $user_id): int
  {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM `02_free_ads` WHERE id_user = :id');
    $stmt->execute([':id' => $user_id]);
    return (int)$stmt->fetchColumn();
  }

  public static function countPremiumAds(PDO $pdo, int $user_id): int
  {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM `03_ads` WHERE id_user = :id');
    $stmt->execute([':id' => $user_id]);
    return (int)$stmt->fetchColumn();
  }

  // ---------------------------------------------------------
  // Check di permesso per inserire un nuovo annuncio
  // ---------------------------------------------------------

  /**
   * Ritorna ['allowed' => bool, 'reason' => string, 'used' => int, 'limit' => int]
   * - allowed = true  → l'utente può creare un nuovo free ad
   * - allowed = false → reason contiene il motivo (in inglese, da mostrare all'utente)
   */
  public static function canInsertFreeAd(PDO $pdo, int $user_id): array
  {
    $tier = self::getTier($pdo, $user_id);
    $used = self::countFreeAds($pdo, $user_id);
    $total = $used + self::countPremiumAds($pdo, $user_id);

    // Admin / whitelist / Gold: nessun limite
    if ($tier === self::TIER_ADMIN || $tier === self::TIER_GOLD || self::isUnlimitedUser($pdo, $user_id)) {
      return ['allowed' => true, 'reason' => '', 'used' => $used, 'limit' => 0];
    }

    // Piani landing: il limite e' sul TOTALE degli annunci (free + premium)
    $cap = self::limitFor($pdo, $tier); // configurabile da admin
    $plan = ($tier === self::TIER_PREMIUM) ? 'Premium' : 'Basic';
    if ($cap > 0 && $total >= $cap) { // 0 = illimitato
      return [
        'allowed' => false,
        'reason'  => sprintf(
          'Your %s plan allows up to %d listings in total (you have %d). Upgrade your plan or delete an existing listing.',
          $plan, $cap, $total
        ),
        'used'  => $used,
        'limit' => $cap,
      ];
    }
    return ['allowed' => true, 'reason' => '', 'used' => $used, 'limit' => $cap];
  }

  public static function canInsertPremiumAd(PDO $pdo, int $user_id): array
  {
    $tier = self::getTier($pdo, $user_id);
    $used  = self::countPremiumAds($pdo, $user_id);
    $total = $used + self::countFreeAds($pdo, $user_id);
    // Piano Premium: il limite e' sul TOTALE annunci (free + premium).

    // Admin / whitelist / Gold: nessun limite
    if ($tier === self::TIER_ADMIN || $tier === self::TIER_GOLD || self::isUnlimitedUser($pdo, $user_id)) {
      return ['allowed' => true, 'reason' => '', 'used' => $used, 'limit' => 0];
    }

    // Basic: nessun premium ad
    if ($tier === self::TIER_FREE) {
      return [
        'allowed' => false,
        'reason'  => 'Premium ads are reserved to Premium and Gold plans. You can request an upgrade from your "My posts" page.',
        'used'  => $used,
        'limit' => 0,
      ];
    }

    // Premium: limite sul TOTALE (free + premium)
    $cap = self::limitFor($pdo, self::TIER_PREMIUM); // configurabile da admin
    if ($cap > 0 && $total >= $cap) {
      return [
        'allowed' => false,
        'reason'  => sprintf(
          'Your Premium plan allows up to %d listings in total (you have %d). Upgrade to Gold for unlimited listings, or delete an existing one.',
          $cap, $total
        ),
        'used'  => $used,
        'limit' => $cap,
      ];
    }
    return ['allowed' => true, 'reason' => '', 'used' => $used, 'limit' => $cap];
  }

  public static function requestPremium(PDO $pdo, int $user_id): bool
  {
    $tier = self::getTier($pdo, $user_id);

    // Già premium/admin → niente da fare
    if ($tier !== self::TIER_FREE) {
    return false;
    }

    $stmt = $pdo->prepare(
    'UPDATE users
      SET premium_requested = 1,
        premium_requested_at = NOW()
      WHERE id_user = :id
      AND premium_requested = 0'
    );
    $stmt->execute([':id' => $user_id]);
    return true;
  }

  /**
   * True se l'utente ha già una richiesta premium pending.
   */
  public static function hasPendingPremiumRequest(PDO $pdo, int $user_id): bool
  {
    $stmt = $pdo->prepare(
    'SELECT premium_requested
     FROM users
      WHERE id_user = :id
      LIMIT 1'
    );
    $stmt->execute([':id' => $user_id]);
    return (bool)$stmt->fetchColumn();
  }

  // ---------------------------------------------------------
  // Coda admin: lista richieste pending
  // ---------------------------------------------------------

  /**
   * Ritorna l'elenco di TUTTI gli utenti con dati utili per il pannello admin:
   * - id_user, username, email, user_tier
   * - premium_requested, premium_requested_at, premium_granted_at
   * - count free / premium ads
   *
   * Filtro $only_pending = true → solo quelli con richiesta pending non concessa.
   */
  public static function listUsersForAdmin(PDO $pdo, bool $only_pending = false): array
  {
    $where = $only_pending
    ? "WHERE u.premium_requested = 1 AND u.user_tier <> 'premium' AND u.user_tier <> 'admin'"
    : '';

    $sql = "
    SELECT
      u.id_user,
      u.username,
      u.email,
      u.user_tier,
      u.premium_requested,
      u.premium_requested_at,
      u.premium_granted_at,
      u.created_at,
      (SELECT COUNT(*) FROM `02_free_ads` WHERE id_user = u.id_user) AS free_ads_count,
      (SELECT COUNT(*) FROM `03_ads`  WHERE id_user = u.id_user) AS premium_ads_count
      FROM users u
      $where
     ORDER BY
      CASE WHEN u.premium_requested = 1 AND u.user_tier = 'free' THEN 0 ELSE 1 END,
      u.premium_requested_at DESC,
      u.created_at DESC
    ";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }

  // ---------------------------------------------------------
  // Grant / revoke premium (solo admin)
  // ---------------------------------------------------------

  /**
   * Promuove un utente a premium. Logga l'azione in admin_audit_log.
   * Da chiamare SOLO dopo aver verificato che $admin_user_id sia tier=admin.
   */
  public static function grantPremium(PDO $pdo, int $admin_user_id, int $target_user_id, string $ip = ''): bool
  {
    try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
      "UPDATE users
        SET user_tier = 'premium',
        premium_granted_at = NOW(),
        premium_requested = 0
      WHERE id_user = :id
        AND user_tier <> 'admin'" // mai degradare un admin
    );
    $stmt->execute([':id' => $target_user_id]);
    $rows = $stmt->rowCount();

    self::logAdminAction(
      $pdo,
      $admin_user_id,
      'grant_premium',
      $target_user_id,
      'Granted premium tier to user_id=' . $target_user_id,
      $ip
    );

    $pdo->commit();
    return $rows > 0;
    } catch (PDOException $e) {
    $pdo->rollBack();
    error_log('[Allonwheel] grantPremium error: ' . $e->getMessage());
    return false;
    }
  }

  /**
   * Revoca premium (riporta a free). Logga in admin_audit_log.
   */
  public static function revokePremium(PDO $pdo, int $admin_user_id, int $target_user_id, string $ip = ''): bool
  {
    try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
      "UPDATE users
        SET user_tier = 'free',
        premium_requested = 0
      WHERE id_user = :id
        AND user_tier = 'premium'"  // solo se attualmente premium (no-op altrimenti)
    );
    $stmt->execute([':id' => $target_user_id]);
    $rows = $stmt->rowCount();

    self::logAdminAction(
      $pdo,
      $admin_user_id,
      'revoke_premium',
      $target_user_id,
      'Revoked premium tier from user_id=' . $target_user_id,
      $ip
    );

    $pdo->commit();
    return $rows > 0;
    } catch (PDOException $e) {
    $pdo->rollBack();
    error_log('[Allonwheel] revokePremium error: ' . $e->getMessage());
    return false;
    }
  }

  // ---------------------------------------------------------
  // Audit log helper
  // ---------------------------------------------------------

  public static function logAdminAction(
    PDO $pdo,
    int $admin_user_id,
    string $action,
    ?int $target_user_id,
    string $details,
    string $ip
  ): void {
    try {
    $stmt = $pdo->prepare(
      'INSERT INTO admin_audit_log
        (admin_user_id, action, target_user_id, details, ip_address)
       VALUES
        (:admin, :action, :target, :details, :ip)'
    );
    $stmt->execute([
      ':admin' => $admin_user_id,
      ':action'  => $action,
      ':target'  => $target_user_id,
      ':details' => $details,
      ':ip'  => $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
    ]);
    } catch (PDOException $e) {
    // Il fail dell'audit log NON deve bloccare l'azione admin,
    // ma deve essere loggato come errore di sistema.
    error_log('[Allonwheel] AUDIT LOG FAILURE: ' . $e->getMessage()
        . ' (action=' . $action . ', admin=' . $admin_user_id
        . ', target=' . ($target_user_id ?: 'null') . ')');
    }
  }
  // ---------------------------------------------------------
  // Piano GOLD (landing): assegnazione/revoca dal pannello admin.
  // ---------------------------------------------------------
  public static function setGold(PDO $pdo, int $admin_id, int $target_id, string $ip): bool
  {
    $st = $pdo->prepare("UPDATE users SET user_tier = 'gold', premium_granted_at = NOW()
      WHERE id_user = :t AND user_tier <> 'admin'");
    $st->execute([':t' => $target_id]);
    $ok = $st->rowCount() > 0;
    if ($ok) { error_log(sprintf('[Allonwheel] admin #%d set GOLD to user #%d (ip %s)', $admin_id, $target_id, $ip)); }
    return $ok;
  }

  public static function revokeGold(PDO $pdo, int $admin_id, int $target_id, string $ip): bool
  {
    $st = $pdo->prepare("UPDATE users SET user_tier = 'free'
      WHERE id_user = :t AND user_tier = 'gold'");
    $st->execute([':t' => $target_id]);
    $ok = $st->rowCount() > 0;
    if ($ok) { error_log(sprintf('[Allonwheel] admin #%d revoked GOLD from user #%d (ip %s)', $admin_id, $target_id, $ip)); }
    return $ok;
  }

}
