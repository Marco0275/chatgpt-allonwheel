<?php
// ============================================================
// cron/rfq_deliver.php
// Consegna DIFFERITA delle RFQ generiche. I destinatari Free/Premium ricevono
// il lead con ritardo (deliver_at). Questo cron invia le email la cui
// deliver_at e' scaduta e non ancora inviate (sent_ok = 0), poi marca sent_ok = 1.
// Schedulare ogni ora (o piu' spesso).  Esempio crontab:  0 * * * * php /path/cron/rfq_deliver.php
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/mailer.class.php';

$sql = "SELECT r.id, r.request_id, r.company_id, c.email, c.ragione_sociale,
               q.buyer_name, q.buyer_email, q.message, q.categories_json
          FROM `quote_request_recipients` r
          JOIN `06_company` c ON c.id = r.company_id
          JOIN `quote_requests` q ON q.id = r.request_id
         WHERE r.sent_ok = 0 AND r.deliver_at IS NOT NULL AND r.deliver_at <= NOW()
         LIMIT 200";

try {
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[Allonwheel] rfq_deliver query error: ' . $e->getMessage());
    exit(1);
}

$upd  = $pdo->prepare('UPDATE `quote_request_recipients` SET sent_ok = 1 WHERE id = :id');
$done = 0;
foreach ($rows as $r) {
    $to = trim((string)($r['email'] ?? ''));
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $upd->execute([':id' => (int)$r['id']]); // niente email valida: chiudo comunque
        continue;
    }
    $company = htmlspecialchars((string)($r['ragione_sociale'] ?? 'Supplier'), ENT_QUOTES, 'UTF-8');
    $buyer   = htmlspecialchars((string)($r['buyer_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $bmail   = htmlspecialchars((string)($r['buyer_email'] ?? ''), ENT_QUOTES, 'UTF-8');
    $msg     = nl2br(htmlspecialchars((string)($r['message'] ?? ''), ENT_QUOTES, 'UTF-8'));
    $cats    = '';
    $cj = json_decode((string)($r['categories_json'] ?? ''), true);
    if (is_array($cj)) {
        $flat = array_merge((array)($cj['regular'] ?? []), (array)($cj['special'] ?? []));
        $cats = htmlspecialchars(implode(', ', array_map('strval', $flat)), ENT_QUOTES, 'UTF-8');
    }
    $subject = 'New quotation request via All on Wheel';
    $body = '<p>Dear ' . $company . ',</p>'
          . '<p>You have received a new quotation request.</p>'
          . ($cats !== '' ? '<p><strong>Categories:</strong> ' . $cats . '</p>' : '')
          . '<p><strong>From:</strong> ' . $buyer . ' &lt;' . $bmail . '&gt;</p>'
          . ($msg !== '' ? '<p><strong>Message:</strong><br>' . $msg . '</p>' : '')
          . '<p>You can reply directly to this e-mail to contact the buyer.</p>';
    if (Mailer::send($to, $subject, $body, (string)($r['buyer_email'] ?? ''))) {
        $upd->execute([':id' => (int)$r['id']]);
        $done++;
    }
}
echo '[rfq_deliver] delivered ' . $done . ' of ' . count($rows) . PHP_EOL;
