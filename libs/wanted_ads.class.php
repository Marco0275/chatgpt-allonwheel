<?php
// libs/wanted_ads.class.php — Motore "Wanted" (richieste inverse) + matching (PDO).
// Tassonomia reale: macro (product_macros.slug) [+ vehicle_type opzionale].
// Matching annunci su MACRO (gli annunci 02/03 portano product_macro; free inclusi).
require_once __DIR__ . '/mailer.class.php';

class WantedAds
{
    public const STATUSES = ['active', 'matched', 'closed'];

    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function create(int $idUser, string $title, string $macro, ?string $vtype,
                           ?float $budget, ?string $country, string $desc): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO `wanted_ads`
                (id_user, title, macro, vehicle_type, budget, country_code, description, status)
             VALUES (:u, :t, :m, :v, :b, :c, :d, \'active\')'
        );
        $st->execute([
            ':u' => $idUser, ':t' => $title, ':m' => ($macro !== '' ? $macro : null),
            ':v' => ($vtype ?: null), ':b' => $budget, ':c' => ($country ?: null), ':d' => $desc,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function get(int $id): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT w.*, u.username, u.email AS buyer_email
               FROM `wanted_ads` w JOIN `users` u ON u.id_user = w.id_user
              WHERE w.id = :id LIMIT 1'
        );
        $st->execute([':id' => $id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function listActive(?string $macro = null, ?string $vtype = null, int $limit = 100): array
    {
        $sql = "SELECT w.*, u.username FROM `wanted_ads` w
                  JOIN `users` u ON u.id_user = w.id_user
                 WHERE w.status = 'active'";
        $p = [];
        if ($macro !== null && $macro !== '') { $sql .= ' AND w.macro = :m'; $p[':m'] = $macro; }
        if ($vtype !== null && $vtype !== '') { $sql .= ' AND w.vehicle_type = :v'; $p[':v'] = $vtype; }
        $sql .= ' ORDER BY w.created_at DESC LIMIT ' . (int)$limit;
        $st = $this->pdo->prepare($sql);
        $st->execute($p);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listByUser(int $idUser): array
    {
        $st = $this->pdo->prepare('SELECT * FROM `wanted_ads` WHERE id_user = :u ORDER BY created_at DESC');
        $st->execute([':u' => $idUser]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setStatus(int $id, int $idUser, string $status): bool
    {
        if (!in_array($status, self::STATUSES, true)) return false;
        $st = $this->pdo->prepare('UPDATE `wanted_ads` SET status = :s WHERE id = :id AND id_user = :u');
        $st->execute([':s' => $status, ':id' => $id, ':u' => $idUser]);
        return $st->rowCount() > 0;
    }

    public function deleteOwned(int $id, int $idUser): bool
    {
        $st = $this->pdo->prepare('DELETE FROM `wanted_ads` WHERE id = :id AND id_user = :u');
        $st->execute([':id' => $id, ':u' => $idUser]);
        return $st->rowCount() > 0;
    }

    // ---- MATCHING ----
    // Venditori con annunci approvati (02+03) compatibili per macro (free inclusi).
    public function sellersForMacro(string $macro, int $excludeUser = 0, ?string $vtype = null): array
    {
        // Punteggio di pertinenza: un venditore che ha annunci del vehicle_type
        // ESATTO richiesto viene prima di chi ha solo la macro giusta. Cosi'
        // il tetto in notifySellers taglia i meno pertinenti, non a caso.
        // Se vtype e' null, il punteggio e' 0 per tutti (solo filtro macro).
        $st = $this->pdo->prepare(
            "SELECT u.id_user, u.username, u.email,
                    MAX(x.vt_match) AS relevance
               FROM (
                    SELECT id_user, (vehicle_type = :vt1) AS vt_match
                      FROM `03_ads`      WHERE status = 'approved' AND product_macro = :m1
                    UNION ALL
                    SELECT id_user, (vehicle_type = :vt2) AS vt_match
                      FROM `02_free_ads` WHERE status = 'approved' AND product_macro = :m2
               ) x
               JOIN `users` u ON u.id_user = x.id_user
              WHERE u.email <> '' AND u.id_user <> :ex
              GROUP BY u.id_user, u.username, u.email
              ORDER BY relevance DESC, u.id_user ASC"
        );
        $st->execute([
            ':vt1' => (string)$vtype, ':vt2' => (string)$vtype,
            ':m1' => $macro, ':m2' => $macro, ':ex' => $excludeUser,
        ]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // Annunci approvati compatibili con una macro (per mostrare i match nella vista).
    public function adsForMacro(string $macro, int $limit = 50): array
    {
        $st = $this->pdo->prepare(
            "SELECT id_ads, title, '03_ads' AS ad_table FROM `03_ads`
               WHERE status = 'approved' AND product_macro = :m1
             UNION ALL
             SELECT id_ads, title, '02_free_ads' AS ad_table FROM `02_free_ads`
               WHERE status = 'approved' AND product_macro = :m2
             LIMIT " . (int)$limit
        );
        $st->execute([':m1' => $macro, ':m2' => $macro]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // Wanted attive compatibili con un annuncio (per notificare i buyer quando un annuncio e' approvato).
    public function activeWantedForMacro(string $macro, int $excludeUser = 0, ?string $vtype = null): array
    {
        // (w.vehicle_type = :vt) come booleano 0/1: le wanted che chiedono
        // esattamente quel tipo salgono in cima. Se vtype e' null, ordine
        // neutro (solo per data).
        $st = $this->pdo->prepare(
            "SELECT w.id, w.title, w.id_user, u.username, u.email,
                    (w.vehicle_type = :vt) AS relevance
               FROM `wanted_ads` w JOIN `users` u ON u.id_user = w.id_user
              WHERE w.status = 'active' AND w.macro = :m AND u.email <> '' AND u.id_user <> :ex
              ORDER BY relevance DESC, w.created_at DESC"
        );
        $st->execute([':vt' => (string)$vtype, ':m' => $macro, ':ex' => $excludeUser]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // Notifica i venditori compatibili quando una wanted viene creata (one-to-one). Ritorna n. inviate.
    public function notifySellers(array $wanted): int
    {
        $macro = (string)($wanted['macro'] ?? '');
        if ($macro === '') return 0;
        $sellers = $this->sellersForMacro($macro, (int)($wanted['id_user'] ?? 0), ($wanted['vehicle_type'] ?? null));

        // TETTO sui destinatari, coerente con le RFQ (AOW_RFQ_MAX_RECIPIENTS):
        // una wanted che scrive a TUTTI i venditori di una macro e' lo stesso
        // broadcast che rende le notifiche spam e fa scappare i fornitori.
        // sellersForMacro li restituisce gia' ordinati per pertinenza
        // (match su vehicle_type prima, poi macro). Qui si tiene la testa.
        // 0 = nessun tetto. Riusa la stessa costante delle RFQ per coerenza.
        $aow_cap = defined('AOW_RFQ_MAX_RECIPIENTS') ? (int)AOW_RFQ_MAX_RECIPIENTS : 3;
        if ($aow_cap > 0 && count($sellers) > $aow_cap) {
            $sellers = array_slice($sellers, 0, $aow_cap);
        }

        $link = BASE_URL . '/05_wanted/wanted_view.php?id=' . (int)($wanted['id'] ?? 0);
        $sent = 0;
        foreach ($sellers as $s) {
            $body = '<p>Dear ' . htmlspecialchars((string)$s['username']) . ',</p>'
                  . '<p>A buyer is looking for a vehicle in a category you offer ('
                  . htmlspecialchars($macro) . '):</p>'
                  . '<p><strong>' . htmlspecialchars((string)($wanted['title'] ?? '')) . '</strong></p>'
                  . '<p>See the request and respond:<br><a href="' . $link . '">' . $link . '</a></p>'
                  . '<p>All on Wheel Ltd</p>';
            if (Mailer::send((string)$s['email'], 'A buyer is looking for your type of vehicle', $body, '', (string)$s['username'])) {
                $sent++;
            }
        }
        return $sent;
    }

    // Notifica i buyer con wanted attive quando un annuncio compatibile viene approvato.
    public function notifyBuyers(string $macro, string $adTable, int $idAds, int $sellerId, string $adTitle = '', string $vtype = ''): int
    {
        if ($macro === '') return 0;
        // Simmetrico a notifySellers: i buyer che cercano il vehicle_type
        // ESATTO dell'annuncio vengono per primi; chi cerca solo la macro
        // resta comunque notificato (il vtype raffina l'ordine, non esclude).
        $buyers = $this->activeWantedForMacro($macro, $sellerId, $vtype);

        // Salvaguardia latenza: Mailer::send e' sincrono. Alla pubblicazione,
        // notificare centinaia di buyer bloccherebbe il wizard. Cap ALTO e
        // separato (default 50): i buyer sono ordinati per pertinenza, quindi
        // i piu' rilevanti vengono avvisati subito. Diverso dal tetto stretto
        // dei venditori (default 3): li e' anti-spam, qui e' solo anti-blocco,
        // perche' un buyer con wanted attiva HA chiesto di essere avvisato.
        // 0 = nessun cap.
        $aow_bcap = defined('AOW_WANTED_NOTIFY_MAX') ? (int)AOW_WANTED_NOTIFY_MAX : 50;
        if ($aow_bcap > 0 && count($buyers) > $aow_bcap) {
            $buyers = array_slice($buyers, 0, $aow_bcap);
        }
        $view = ($adTable === '03_ads') ? '03_ads/03_view_ad.php' : '02_free_ads/02_view_ad.php';
        $link = BASE_URL . '/' . $view . '?id_ads=' . $idAds;
        $sent = 0;
        foreach ($buyers as $b) {
            $body = '<p>Dear ' . htmlspecialchars((string)$b['username']) . ',</p>'
                  . '<p>A new listing matches your wanted request <strong>'
                  . htmlspecialchars((string)$b['title']) . '</strong>:</p>'
                  . ($adTitle !== '' ? '<p><strong>' . htmlspecialchars($adTitle) . '</strong></p>' : '')
                  . '<p><a href="' . $link . '">' . $link . '</a></p>'
                  . '<p>All on Wheel Ltd</p>';
            if (Mailer::send((string)$b['email'], 'A new listing matches your wanted request', $body, '', (string)$b['username'])) {
                $sent++;
            }
        }
        return $sent;
    }
}
