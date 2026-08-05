<?php
// ============================================================
// scripts/rfq_claim_reassign.php  Sollecito/escalation dei lead non presi
//
// 20 lug 2026. Completa il "claim": ora che i fornitori possono prendere in
// carico un lead da 06_40_my_leads.php (claimed_at), questo cron gestisce i
// lead che NESSUNO ha preso entro N ore.
//
// COME FUNZIONA (in due tempi, non un unico taglio a 24h)
//  Fase 1 - SOLLECITO (dopo AOW_CLAIM_REMIND_HOURS, default 24h):
//    ai fornitori che hanno ricevuto il lead ma non l'hanno ancora preso
//    (claimed_at IS NULL) parte UN promemoria: "un lead ti aspetta, prendilo".
//    Un solo sollecito per destinatario (reminded_at evita i doppioni).
//  Fase 2 - ESCALATION ADMIN (dopo AOW_CLAIM_ESCALATE_HOURS, default 48h):
//    se dopo il sollecito il lead e' ANCORA senza nessun claim, si segnala
//    all'admin (rfq@) perche' intervenga a mano (telefonata al buyer, ecc.).
//
// PERCHE' NON "RIASSEGNO AL SUCCESSIVO IN GRADUATORIA"
// I destinatari pertinenti sono GIA' stati scelti e notificati tutti insieme
// al momento della RFQ (fan-out con tetto per punteggio). Non c'e' una "coda"
// di riserva da promuovere: il lead e' gia' su tutti i fornitori giusti. Se
// nessuno di loro lo prende, il problema non e' "chiamare il prossimo" (non
// c'e' un prossimo piu' pertinente), ma sollecitarli e poi far intervenire
// una persona. Questo e' il claim vero, corretto per come sono i dati reali.
//
// USO (cron giornaliero, o ogni poche ore):
//   0 */6 * * * php /home/<user>/htdocs/scripts/rfq_claim_reassign.php >> /var/log/aow_claim.log 2>&1
// Manuale:
//   php scripts/rfq_claim_reassign.php
//   php scripts/rfq_claim_reassign.php --dry-run
// ============================================================

$webroot = dirname(__DIR__);
require_once $webroot . '/config/bootstrap.php';
require_once $webroot . '/config/database.php';
require_once $webroot . '/libs/mailer.class.php';

$dry = in_array('--dry-run', $argv ?? [], true);

$remindH   = defined('AOW_CLAIM_REMIND_HOURS')   ? (int)AOW_CLAIM_REMIND_HOURS   : 24;
$escalateH = defined('AOW_CLAIM_ESCALATE_HOURS') ? (int)AOW_CLAIM_ESCALATE_HOURS : 48;
if ($remindH   < 1) { $remindH = 24; }
if ($escalateH < $remindH) { $escalateH = $remindH * 2; }
$inbox = defined('AOW_RFQ_INBOX') ? (string)AOW_RFQ_INBOX : 'rfq@allonwheel.com';
$base  = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

// La colonna reminded_at potrebbe non esistere ancora (patch): la creo se serve
// non e' compito del cron. Verifico e, se manca, lavoro senza il doppio-invio-guard
// (peggio: un fornitore riceve piu' di un sollecito, non e' grave).
$hasReminded = false;
try {
    $chk = $pdo->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'quote_request_recipients'
            AND COLUMN_NAME = 'reminded_at'"
    );
    $hasReminded = ((int)$chk->fetchColumn() > 0);
} catch (Throwable $e) { $hasReminded = false; }

echo '[' . date('c') . "] rfq_claim_reassign: remind>{$remindH}h escalate>{$escalateH}h "
   . '(reminded_at ' . ($hasReminded ? 'presente' : 'assente') . ")\r\n";

// ---------- FASE 1: SOLLECITO AI FORNITORI ----------
// Destinatari con lead non preso, piu' vecchi di remindH, non gia' sollecitati.
$remindSql =
    "SELECT r.id AS rid, r.request_id, r.company_id, c.email AS company_email,
            c.ragione_sociale, q.buyer_name, q.macro
       FROM `quote_request_recipients` r
       JOIN `quote_requests` q ON q.id = r.request_id
       JOIN `06_company` c ON c.id = r.company_id
      WHERE r.claimed_at IS NULL
        AND q.status IN ('new', 'distributed')
        AND q.created_at < (NOW() - INTERVAL {$remindH} HOUR)
        AND c.email <> ''";
if ($hasReminded) {
    $remindSql .= " AND r.reminded_at IS NULL";
}
$remindSql .= " ORDER BY q.created_at ASC LIMIT 200";

$reminded = 0;
try {
    foreach ($pdo->query($remindSql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $to = trim((string)$row['company_email']);
        if ($to === '') { continue; }
        $subject = 'A quotation request is waiting for you';
        $body = '<p>Hello ' . htmlspecialchars((string)$row['ragione_sociale']) . ',</p>'
              . '<p>A buyer request matched to your products is still waiting for a reply. '
              . 'Please take a look and respond, or another supplier may win the deal.</p>'
              . '<p><a href="' . $base . '/06_company/06_40_my_leads.php">Open my leads</a></p>'
              . '<p>All on Wheel Ltd</p>';
        if ($dry) {
            echo "  [remind] {$to} (lead #{$row['request_id']})\r\n";
            $reminded++;
        } else if (Mailer::send($to, $subject, $body)) {
            $reminded++;
            if ($hasReminded) {
                $u = $pdo->prepare('UPDATE `quote_request_recipients` SET reminded_at = NOW() WHERE id = :id');
                $u->execute([':id' => (int)$row['rid']]);
            }
        }
    }
} catch (Throwable $e) {
    error_log('[Allonwheel] claim reassign remind: ' . $e->getMessage());
}
echo "  solleciti inviati: {$reminded}\r\n";

// ---------- FASE 2: ESCALATION ADMIN ----------
// Lead ancora senza NESSUN claim, piu' vecchi di escalateH.
$escSql =
    "SELECT q.id, q.buyer_name, q.macro, q.created_at,
            (SELECT COUNT(*) FROM `quote_request_recipients` r2 WHERE r2.request_id = q.id) AS n_recip
       FROM `quote_requests` q
      WHERE q.status IN ('new', 'distributed')
        AND q.created_at < (NOW() - INTERVAL {$escalateH} HOUR)
        AND NOT EXISTS (
            SELECT 1 FROM `quote_request_recipients` r3
             WHERE r3.request_id = q.id AND r3.claimed_at IS NOT NULL
        )
      ORDER BY q.created_at ASC";
$stale = [];
try {
    $stale = $pdo->query($escSql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[Allonwheel] claim reassign escalate: ' . $e->getMessage());
}

$nStale = count($stale);
echo "  lead da segnalare all'admin: {$nStale}\r\n";

if ($nStale > 0 && !$dry) {
    $rows = '';
    foreach ($stale as $l) {
        $age = '';
        $ts = strtotime((string)$l['created_at']);
        if ($ts) { $age = round((time() - $ts) / 3600) . 'h'; }
        $rows .= '<tr><td>#' . (int)$l['id'] . '</td>'
              . '<td>' . htmlspecialchars((string)$l['buyer_name']) . '</td>'
              . '<td>' . htmlspecialchars((string)$l['macro']) . '</td>'
              . '<td>' . (int)$l['n_recip'] . ' suppliers</td>'
              . '<td>' . htmlspecialchars($age) . '</td></tr>';
    }
    $body = '<p>These buyer requests have had NO supplier take them, even after a reminder. '
          . 'They likely need a personal follow-up:</p>'
          . '<table cellpadding="6" border="1" style="border-collapse:collapse">'
          . '<tr><th>Lead</th><th>Buyer</th><th>Category</th><th>Sent to</th><th>Age</th></tr>'
          . $rows . '</table>'
          . '<p><a href="' . $base . '/_admin/leads.php">Open the leads admin</a></p>';
    Mailer::send($inbox, 'Allonwheel: ' . $nStale . ' leads still unclaimed', $body);
    echo "  segnalazione admin inviata a {$inbox}\r\n";
} elseif ($nStale > 0 && $dry) {
    foreach ($stale as $l) {
        echo "  [escalate] #{$l['id']} {$l['buyer_name']} ({$l['created_at']})\r\n";
    }
}

echo "rfq_claim_reassign done\r\n";
