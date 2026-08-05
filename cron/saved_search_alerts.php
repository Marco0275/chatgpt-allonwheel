<?php
// ============================================================
// cron/saved_search_alerts.php — M4: alert ricerche salvate + digest.
// Esecuzione: CLI (crontab)  oppure  via URL con ?key=<CRON_KEY> (env).
//   crontab consigliato:  0 7 * * *  php /path/cron/saved_search_alerts.php
// Logica: per ogni ricerca attiva "in scadenza" (daily: >20h, weekly: >6g)
// cerca gli annunci APPROVED pubblicati dopo l'ultimo invio che matchano
// macro/q; invia SOLO se ce ne sono; aggiorna sempre last_sent_at.
// ============================================================
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/mailer.class.php';

// Guardia: CLI oppure chiave segreta
if (php_sapi_name() !== 'cli') {
    $k = getenv('CRON_KEY') ?: '';
    if ($k === '' || !hash_equals($k, (string)($_GET['key'] ?? ''))) {
        http_response_code(403); exit('Forbidden');
    }
}

$base = rtrim(defined('BASE_URL') ? BASE_URL : 'https://www.allonwheel.com', '/');
$sent = 0; $checked = 0; $skipped = 0;

// Dal 27 lug 2026 un alert puo' essere creato anche senza account (id_user = 0)
// indicando solo l'email: in quel caso parte SOLO dopo che il destinatario ha
// aperto il link di conferma (confirmed_at valorizzato). Le righe non
// confermate restano in tabella ma non ricevono nulla.
$ss_cols = [];
try { $ss_cols = $pdo->query('SHOW COLUMNS FROM `saved_searches`')->fetchAll(PDO::FETCH_COLUMN); } catch (Throwable $e) {}
$confirm_clause = in_array('confirmed_at', $ss_cols, true)
    ? ' AND (id_user > 0 OR confirmed_at IS NOT NULL)'
    : '';
$has_vtype = in_array('vtype', $ss_cols, true);

$due = $pdo->query("SELECT * FROM saved_searches
    WHERE active = 1" . $confirm_clause . "
      AND ( (freq = 'daily'  AND (last_sent_at IS NULL OR last_sent_at < DATE_SUB(NOW(), INTERVAL 20 HOUR)))
         OR (freq = 'weekly' AND (last_sent_at IS NULL OR last_sent_at < DATE_SUB(NOW(), INTERVAL 6 DAY))) )
    ORDER BY id ASC LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);

foreach ($due as $s) {
    $checked++;
    $since = $s['last_sent_at'] ?: $s['created_at'];
    $macro = trim((string)($s['macro'] ?? ''));
    $q     = trim((string)($s['q'] ?? ''));

    // Stessi vincoli di visibilita' di browse: solo dati reali (dir. 14).
    //
    // 27 lug 2026: i due rami della UNION riusavano gli stessi segnaposto
    // (:since, :m, :q). Funziona solo con le prepared statement emulate, che
    // qui sono disattivate: il cron falliva a ogni giro con "Invalid parameter
    // number" e nessun alert e' mai partito. Ora ogni ramo ha i propri
    // segnaposto.
    $vtype = $has_vtype ? trim((string)($s['vtype'] ?? '')) : '';
    $params = [];
    $part = function (string $table, string $detail, string $sfx)
            use ($since, $macro, $q, $vtype, &$params) {
        $params[':since' . $sfx] = $since;
        $w = '';
        if ($macro !== '') { $w .= ' AND product_macro = :m' . $sfx; $params[':m' . $sfx] = $macro; }
        // Senza emulazione anche la ripetizione dello stesso nome nella stessa
        // query non e' ammessa: titolo e descrizione hanno segnaposto distinti.
        if ($q !== '')     { $w .= ' AND (title LIKE :qt' . $sfx . ' OR description LIKE :qd' . $sfx . ')';
                             $params[':qt' . $sfx] = '%' . $q . '%';
                             $params[':qd' . $sfx] = '%' . $q . '%'; }
        if ($vtype !== '') { $w .= ' AND vehicle_type = :vt' . $sfx; $params[':vt' . $sfx] = $vtype; }
        return "SELECT id_ads, title, list_price, created_at, '$detail' AS durl
                FROM `$table`
                WHERE status = 'approved' AND created_at > :since" . $sfx . $w;
    };
    $sql = $part('02_free_ads', '02_free_ads/02_view_ad.php', '_a')
         . ' UNION ALL '
         . $part('03_ads', '03_ads/03_view_ad.php', '_b')
         . ' ORDER BY created_at DESC LIMIT 10';
    try {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('[Allonwheel] saved_search query error: ' . $e->getMessage());
        continue;
    }

    if ($rows) {
        $label = $macro !== '' ? ucwords(str_replace('-', ' ', $macro)) : 'your search';
        $items = '';
        foreach ($rows as $r) {
            $url = $base . '/' . $r['durl'] . '?id_ads=' . (int)$r['id_ads'];
            $price = ((float)$r['list_price'] > 0)
                ? '&euro; ' . number_format((float)$r['list_price'], 0, '.', ',')
                : 'Price on request';
            $items .= '<li><a href="' . $url . '">' . htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8') . '</a> &mdash; ' . $price . '</li>';
        }
        $unsub = $base . '/saved_search_unsubscribe.php?token=' . $s['token'];
        $body = '<html><body style="font-family:Arial,Helvetica,sans-serif;color:#222;">'
            . '<p>New listings matching <strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong> on All on Wheel:</p>'
            . '<ul>' . $items . '</ul>'
            . '<p><a href="' . $base . '/browse.php' . ($macro !== '' ? '?macro=' . rawurlencode($macro) : '') . '">See all listings</a></p>'
            . '<p style="color:#777;font-size:12px;">You receive this because you saved a search on allonwheel.com. '
            . '<a href="' . $unsub . '">Unsubscribe</a></p>'
            . '</body></html>';
        $subj = ($s['freq'] === 'weekly' ? 'Weekly digest: ' : 'New listings: ') . $label . ' - All on Wheel';
        if (Mailer::send($s['email'], $subj, $body, 'info@allonwheel.com')) { $sent++; }
    } else {
        $skipped++;
    }

    // aggiorna SEMPRE: la finestra riparte da ora (niente doppioni domani)
    try {
        $pdo->prepare('UPDATE saved_searches SET last_sent_at = NOW() WHERE id = :id')
            ->execute([':id' => (int)$s['id']]);
    } catch (Throwable $e) {}
}

echo "saved_search_alerts: checked=$checked sent=$sent no_news=$skipped\n";
