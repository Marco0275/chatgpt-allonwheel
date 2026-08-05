<?php
// libs/rent.class.php -- Motore NOLEGGIO (07_rent): annunci, richieste, matching
// per tier (gold > premium > free) e notifica. Rispecchia WantedAds + RFQ.
require_once __DIR__ . '/mailer.class.php';
require_once __DIR__ . '/user_tier.class.php';

class RentAds
{
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    // -------- ANNUNCI DI NOLEGGIO (solo veicoli speciali) --------
    public function createListing(array $d): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO `07_rent_ads`
                (id_user, status, author, email, phone, title, subtitle, list_price,
                 type, conditions, image_original, image_thumbnail, description,
                 expires_at, item_kind, macro_category, vehicle_type, product_macro)
             VALUES
                (:u, :status, :author, :email, :phone, :title, :subtitle, :price,
                 :type, :cond, :img_o, :img_t, :descr,
                 :expires, :kind, :macro_cat, :vtype, :pmacro)'
        );
        $st->execute([
            ':u' => (int)$d['id_user'],
            ':status' => $d['status'] ?? 'approved',
            ':author' => (string)($d['author'] ?? ''),
            ':email' => (string)($d['email'] ?? ''),
            ':phone' => (string)($d['phone'] ?? ''),
            ':title' => (string)($d['title'] ?? 'Title'),
            ':subtitle' => ($d['subtitle'] ?? null),
            ':price' => (float)($d['list_price'] ?? 0),
            ':type' => 'For rent',
            ':cond' => (string)($d['conditions'] ?? 'As good as new'),
            ':img_o' => (string)($d['image_original'] ?? 'no_image.jpg'),
            ':img_t' => (string)($d['image_thumbnail'] ?? 'no_image.jpg'),
            ':descr' => (string)($d['description'] ?? ''),
            ':expires' => (string)$d['expires_at'],
            ':kind' => (string)($d['item_kind'] ?? 'vehicle'),
            ':macro_cat' => 'special',
            ':vtype' => ($d['vehicle_type'] ?? null),
            ':pmacro' => ($d['product_macro'] ?? null),
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function getListing(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM `07_rent_ads` WHERE id_ads = :id LIMIT 1');
        $st->execute([':id' => $id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function listActive(?string $vtype = null, int $limit = 60): array
    {
        $sql = "SELECT * FROM `07_rent_ads` WHERE status = 'approved'";
        $p = [];
        if ($vtype !== null && $vtype !== '') { $sql .= ' AND vehicle_type = :v'; $p[':v'] = $vtype; }
        $sql .= ' ORDER BY created_at DESC LIMIT ' . (int)$limit;
        $st = $this->pdo->prepare($sql);
        $st->execute($p);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listByUser(int $idUser): array
    {
        $st = $this->pdo->prepare('SELECT * FROM `07_rent_ads` WHERE id_user = :u ORDER BY created_at DESC');
        $st->execute([':u' => $idUser]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // -------- RICHIESTE DI NOLEGGIO --------
    public function createRequest(int $idUser, array $vtypes, ?float $budget, ?string $country,
                                  string $desc, ?string $from = null, ?string $to = null, string $title = 'Rental request'): int
    {
        $clean = array_values(array_unique(array_filter(array_map('strval', $vtypes), fn($s) => $s !== '')));
        $st = $this->pdo->prepare(
            'INSERT INTO `07_rent_requests`
                (id_user, title, vehicle_types, budget, country_code, rent_from, rent_to, description, status)
             VALUES (:u, :t, :vt, :b, :c, :f, :to, :d, \'new\')'
        );
        $st->execute([
            ':u' => $idUser, ':t' => $title, ':vt' => implode(',', $clean),
            ':b' => $budget, ':c' => ($country ?: null),
            ':f' => ($from ?: null), ':to' => ($to ?: null), ':d' => $desc,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function getRequest(int $id): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT r.*, u.username, u.email AS requester_email
               FROM `07_rent_requests` r JOIN `users` u ON u.id_user = r.id_user
              WHERE r.id = :id LIMIT 1'
        );
        $st->execute([':id' => $id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    // Destinatari: (a) chi ha annunci di noleggio APPROVATI sui tipi richiesti,
    // (b) le aziende con offers_rental=1 che hanno DICHIARATO in registrazione uno
    // dei tipi richiesti (anche SENZA annunci pubblicati). Ordinati per tier.
    public function matchCompanies(array $vtypes, int $excludeUser = 0): array
    {
        $clean = array_values(array_unique(array_filter(array_map('strval', $vtypes), fn($s) => $s !== '')));
        if (empty($clean)) { return []; }
        $mk = function (string $prefix) use ($clean) {
            $ph = []; $args = [];
            foreach ($clean as $i => $slug) { $k = ':' . $prefix . $i; $ph[] = $k; $args[$k] = $slug; }
            return [implode(',', $ph), $args];
        };
        [$ain, $aArgs] = $mk('a');
        [$bin, $bArgs] = $mk('b');
        [$cin, $cArgs] = $mk('c');
        $args = $aArgs + $bArgs + $cArgs;
        $args[':ex'] = $excludeUser;
        $sql =
            'SELECT u.id_user, u.username, u.email, u.user_tier AS tier,
                    MAX(oc.id) AS company_id, COUNT(*) AS matches
               FROM (
                    SELECT id_user FROM `07_rent_ads`
                      WHERE status = \'approved\' AND vehicle_type IN (' . $ain . ')
                    UNION ALL
                    SELECT c.user_id AS id_user
                      FROM `06_company` c
                      JOIN `06_company_products` cp ON cp.company_id = c.id
                     WHERE c.offers_rental = 1 AND cp.product_key IN (' . $bin . ')
                    UNION ALL
                    SELECT c.user_id AS id_user
                      FROM `06_company` c
                      JOIN `06_company_products_special` cps ON cps.company_id = c.id
                     WHERE c.offers_rental = 1 AND cps.product_key IN (' . $cin . ')
               ) m
               JOIN `users` u ON u.id_user = m.id_user
          LEFT JOIN `06_company` oc ON oc.user_id = u.id_user
              WHERE u.email <> \'\' AND u.id_user <> :ex
           GROUP BY u.id_user, u.username, u.email, u.user_tier
           ORDER BY CASE u.user_tier WHEN \'gold\' THEN 0 WHEN \'premium\' THEN 1 WHEN \'free\' THEN 2 ELSE 3 END,
                    matches DESC, u.id_user ASC';
        $st = $this->pdo->prepare($sql);
        $st->execute($args);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // Notifica i destinatari: TUTTI vengono registrati (visibili in area lead);
    // l'EMAIL parte per gold/premium sempre e per i free entro il tetto (anti-spam).
    public function notifyCompanies(array $request, array $recipients): int
    {
        $emailCap = defined('AOW_RFQ_MAX_RECIPIENTS') ? (int)AOW_RFQ_MAX_RECIPIENTS : 3;
        $reqId = (int)($request['id'] ?? 0);
        $link  = (defined('BASE_URL') ? BASE_URL : '') . '/07_rent/07_40_rent_leads.php';
        $ins = $this->pdo->prepare(
            'INSERT INTO `07_rent_request_recipients`
                (request_id, id_user, company_id, tier, rank_pos, emailed_at)
             VALUES (:rid, :uid, :cid, :tier, :rank, :emailed)
             ON DUPLICATE KEY UPDATE rank_pos = VALUES(rank_pos)'
        );
        $sent = 0; $pos = 0;
        foreach ($recipients as $r) {
            $pos++;
            $tier = (string)($r['tier'] ?? 'free');
            $doEmail = in_array($tier, ['gold', 'premium'], true) || $emailCap <= 0 || $pos <= $emailCap;
            $emailedAt = null;
            if ($doEmail) {
                $body = '<p>Dear ' . htmlspecialchars((string)$r['username']) . ',</p>'
                      . '<p>A user is requesting to <strong>rent</strong> special vehicles that match your published rental listings:</p>'
                      . '<p><strong>' . htmlspecialchars((string)($request['title'] ?? 'Rental request')) . '</strong></p>'
                      . '<p>See the request and respond from your rental leads:<br>'
                      . '<a href="' . $link . '">' . $link . '</a></p>'
                      . '<p>All on Wheel Ltd</p>';
                if (Mailer::send((string)$r['email'], 'A user is looking to rent your type of vehicle', $body, '', (string)$r['username'])) {
                    $sent++; $emailedAt = date('Y-m-d H:i:s');
                }
            }
            $ins->execute([
                ':rid' => $reqId, ':uid' => (int)$r['id_user'],
                ':cid' => ($r['company_id'] ?? null), ':tier' => $tier,
                ':rank' => $pos, ':emailed' => $emailedAt,
            ]);
        }
        return $sent;
    }

    public function markDistributed(int $id): void
    {
        $st = $this->pdo->prepare('UPDATE `07_rent_requests` SET status = \'distributed\' WHERE id = :id AND status = \'new\'');
        $st->execute([':id' => $id]);
    }

    // -------- AREA LEAD (destinatario) --------
    public function leadsForUser(int $idUser): array
    {
        $st = $this->pdo->prepare(
            'SELECT rr.id AS recipient_id, rr.emailed_at, rr.claimed_at, rr.rank_pos, rr.tier,
                    q.id AS request_id, q.title, q.vehicle_types, q.budget, q.country_code,
                    q.rent_from, q.rent_to, q.description, q.created_at,
                    u.username AS requester, u.email AS requester_email
               FROM `07_rent_request_recipients` rr
               JOIN `07_rent_requests` q ON q.id = rr.request_id
               JOIN `users` u ON u.id_user = q.id_user
              WHERE rr.id_user = :u
           ORDER BY q.created_at DESC'
        );
        $st->execute([':u' => $idUser]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function claimLead(int $requestId, int $idUser): bool
    {
        $st = $this->pdo->prepare(
            'UPDATE `07_rent_request_recipients` SET claimed_at = NOW()
              WHERE request_id = :r AND id_user = :u AND claimed_at IS NULL'
        );
        $st->execute([':r' => $requestId, ':u' => $idUser]);
        return $st->rowCount() > 0;
    }
}
