<?php
// ============================================================
// scripts/rfq_escalation.php
// Escalation dei lead RFQ fermi (versione onesta del "claim 24h")
//
// 17 lug 2026.
//
// PERCHE' NON IL "CLAIM 24H" IN SENSO STRETTO
// Il piano parlava di un claim: il fornitore rivendica il lead entro 24h,
// altrimenti passa al successivo in graduatoria. Ma quel meccanismo
// presuppone due cose che OGGI NON ESISTONO:
//   1) un'area riservata dove il fornitore VEDE i suoi lead e li rivendica
//      (i fornitori ricevono il lead solo via email, non c'e' un portale);
//   2) un segnale strutturato di "ho risposto" (oggi il fornitore risponde
//      rispondendo all'email al buyer; il sistema non lo sa).
// Senza questi due, un cron che "riassegna automaticamente" riassegnerebbe
// alla cieca: potrebbe scavalcare un fornitore che ha gia' risposto via
// email. Sarebbe un claim FINTO.
//
// COSA FA QUESTO INVECE (reale e utile subito)
// Segnala all'admin (rfq@) i lead ancora 'new' o 'distributed' dopo N ore
// (default 24). L'escalation la fa una PERSONA, che puo' telefonare al buyer
// o sollecitare i fornitori con cognizione. E' il valore del claim - nessun
// lead resta a marcire - senza l'infrastruttura che ancora non c'e'.
//
// Quando esistera' un'area lead per fornitori (con stato "risposto"
// strutturato), questo cron diventa il claim automatico vero: la logica di
// selezione dei lead fermi e' gia' qui, cambiera' solo l'azione.
//
// UTILIZZO (cron giornaliero):
//   0 9 * * * php /home/<user>/htdocs/scripts/rfq_escalation.php >> /var/log/aow_rfq_escalation.log 2>&1
// Manuale:
//   php scripts/rfq_escalation.php
//   php scripts/rfq_escalation.php --dry-run   (nessuna email, solo elenco)
// ============================================================

$webroot = dirname(__DIR__);
require_once $webroot . '/config/bootstrap.php';
require_once $webroot . '/config/database.php';
require_once $webroot . '/libs/mailer.class.php';

$dry = in_array('--dry-run', $argv ?? [], true);

// Soglia in ore: oltre questa, un lead senza avanzamento va segnalato.
$hours = defined('AOW_RFQ_ESCALATION_HOURS') ? (int)AOW_RFQ_ESCALATION_HOURS : 24;
if ($hours < 1) { $hours = 24; }

// Casella di escalation: la stessa che riceve la copia di servizio delle RFQ.
$inbox = defined('AOW_RFQ_INBOX') ? (string)AOW_RFQ_INBOX : 'rfq@allonwheel.com';

try {
    // Lead ancora da lavorare: 'new' (nessuno l'ha ancora preso in carico) o
    // 'distributed' (inviato ai fornitori ma non ancora quotato) e piu'
    // vecchi della soglia. 'quoted'/'won'/'lost' sono gia' avanzati: si saltano.
    // $hours e' un int gia' validato (>=1), nessun input utente: interpolarlo
    // e' sicuro. MySQL non accetta sempre un placeholder subito dopo INTERVAL.
    $hoursSafe = (int)$hours;
    $st = $pdo->prepare(
        "SELECT q.id, q.buyer_name, q.buyer_email, q.macro, q.status, q.created_at,
                (SELECT COUNT(*) FROM `quote_request_recipients` r WHERE r.request_id = q.id) AS n_recipients
           FROM `quote_requests` q
          WHERE q.status IN ('new', 'distributed')
            AND q.created_at < (NOW() - INTERVAL {$hoursSafe} HOUR)
          ORDER BY q.created_at ASC"
    );
    $st->execute();
    $stale = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[Allonwheel] rfq_escalation query: ' . $e->getMessage());
    fwrite(STDERR, 'rfq_escalation error: ' . $e->getMessage() . "\r\n");
    exit(1);
}

$n = count($stale);
echo '[' . date('c') . "] rfq_escalation: {$n} lead fermi da oltre {$hours}h\r\n";

if ($n === 0) {
    echo "rfq_escalation: nulla da segnalare\r\n";
    exit(0);
}

// Riepilogo per l'admin: una riga per lead, con il link alla pagina admin.
$base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$rows = '';
foreach ($stale as $l) {
    $age = '';
    $ts  = strtotime((string)$l['created_at']);
    if ($ts) { $age = round((time() - $ts) / 3600) . 'h'; }
    $rows .= '<tr>'
          . '<td>#' . (int)$l['id'] . '</td>'
          . '<td>' . htmlspecialchars((string)$l['buyer_name']) . '</td>'
          . '<td>' . htmlspecialchars((string)$l['macro']) . '</td>'
          . '<td>' . htmlspecialchars((string)$l['status']) . '</td>'
          . '<td>' . (int)$l['n_recipients'] . ' suppliers</td>'
          . '<td>' . htmlspecialchars($age) . '</td>'
          . '</tr>';
}

$body = '<p>The following buyer requests have had no progress for more than '
      . $hours . ' hours. They may need a personal follow-up (call the buyer '
      . 'or chase the suppliers):</p>'
      . '<table cellpadding="6" border="1" style="border-collapse:collapse">'
      . '<tr><th>Lead</th><th>Buyer</th><th>Category</th><th>Status</th>'
      . '<th>Sent to</th><th>Age</th></tr>'
      . $rows
      . '</table>'
      . '<p><a href="' . $base . '/_admin/leads.php">Open the leads admin</a></p>'
      . '<p>All on Wheel Ltd - automated escalation</p>';

if ($dry) {
    echo "rfq_escalation: DRY-RUN, nessuna email inviata. Lead:\r\n";
    foreach ($stale as $l) {
        echo '  #' . (int)$l['id'] . ' ' . (string)$l['buyer_name']
           . ' [' . (string)$l['status'] . '] ' . (string)$l['created_at'] . "\r\n";
    }
    exit(0);
}

$subject = 'Allonwheel: ' . $n . ' RFQ lead need follow-up';
if (Mailer::send($inbox, $subject, $body)) {
    echo "rfq_escalation: segnalazione inviata a {$inbox}\r\n";
} else {
    error_log('[Allonwheel] rfq_escalation: invio a ' . $inbox . ' fallito');
    fwrite(STDERR, "rfq_escalation: invio fallito\r\n");
    exit(1);
}

echo "rfq_escalation done\r\n";
