<?php
// ============================================================
// libs/plan_policy.class.php
// Fonte UNICA di verita' per i limiti/permessi dei piani (Free/Premium/Gold).
// Ogni funzionalita' a piani legge da qui: annunci, foto, tech/PDF, wanted,
// blog, directory, ritardo RFQ, quota social, badge. I tier sono allineati
// a UserTier. -1 = illimitato (dove applicabile); 0 = zero/illimitato dove
// indicato nei commenti.
// ============================================================

class PlanPolicy
{
  const FREE = 'free', PREMIUM = 'premium', GOLD = 'gold', ADMIN = 'admin';

  // Foto gallery per annuncio (l'immagine principale e' sempre inclusa a parte).
  // Free = solo principale (0 gallery) | Premium = 10 | Gold/Admin = illimitato (-1).
  const PHOTOS = ['free' => 0, 'premium' => 10, 'gold' => -1, 'admin' => -1];

  // Ritardo (giorni) sulla ricezione delle RFQ generiche, dalla CREAZIONE della RFQ.
  const RFQ_DELAY_DAYS = ['free' => 5, 'premium' => 3, 'gold' => 0, 'admin' => 0];

  // Quota pubblicazioni social all'anno (le pubblica un'IA esterna; qui il contatore).
  const SOCIAL_QUOTA = ['free' => 0, 'premium' => 3, 'gold' => 12, 'admin' => 12];

  // Livello del profilo nella directory fornitori.
  const DIRECTORY = ['free' => 'base', 'premium' => 'advanced', 'gold' => 'top', 'admin' => 'top'];

  public static function norm($tier): string
  {
    $t = strtolower(trim((string)$tier));
    return in_array($t, [self::FREE, self::PREMIUM, self::GOLD, self::ADMIN], true) ? $t : self::FREE;
  }

  private static function isAtLeastPremium($tier): bool
  {
    return in_array(self::norm($tier), [self::PREMIUM, self::GOLD, self::ADMIN], true);
  }

  // ----- Media -----
  /** Foto gallery consentite: intero, oppure -1 = illimitato. */
  public static function photoLimit($tier): int { return self::PHOTOS[self::norm($tier)]; }
  public static function canGallery($tier): bool { return self::photoLimit($tier) !== 0; }
  public static function canTechDetails($tier): bool { return self::isAtLeastPremium($tier); }
  public static function canDocuments($tier): bool { return self::isAtLeastPremium($tier); } // planimetrie PDF

  // ----- Wanted / Blog -----
  public static function canWanted($tier): bool { return self::isAtLeastPremium($tier); }
  public static function canBlogPublish($tier): bool { return in_array(self::norm($tier), [self::GOLD, self::ADMIN], true); }
  public static function canBlogReply($tier): bool { return self::isAtLeastPremium($tier); }

  // ----- Directory / RFQ / Social -----
  public static function directoryLevel($tier): string { return self::DIRECTORY[self::norm($tier)]; }
  public static function isDirectoryAdvanced($tier): bool { return self::directoryLevel($tier) !== 'base'; } // logo/link/portfolio
  public static function isDirectoryTop($tier): bool { return self::directoryLevel($tier) === 'top'; }      // vetrina Gold
  public static function rfqDelayDays($tier): int { return self::RFQ_DELAY_DAYS[self::norm($tier)]; }
  public static function socialQuota($tier): int { return self::SOCIAL_QUOTA[self::norm($tier)]; }

  // ----- Badge listing: '' | 'Premium' | 'Featured' -----
  public static function badge($tier): string
  {
    $t = self::norm($tier);
    if ($t === self::GOLD)    { return 'Featured'; }
    if ($t === self::PREMIUM) { return 'Premium'; }
    return '';
  }

  // ----- Limite annunci TOTALI per tier (delega a UserTier, override admin) -----
  /** 0 = illimitato. Free=2, Premium=10 (default), Gold/Admin=illimitato. */
  public static function adLimit(PDO $pdo, $tier): int
  {
    $t = self::norm($tier);
    if ($t === self::GOLD || $t === self::ADMIN) { return 0; }
    require_once __DIR__ . '/user_tier.class.php';
    return UserTier::limitFor($pdo, $t === self::PREMIUM ? UserTier::TIER_PREMIUM : UserTier::TIER_FREE);
  }
}
