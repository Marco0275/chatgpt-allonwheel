<?php
// ============================================================
// config/app_settings.php  Flag di prodotto (comportamenti configurabili)
//
// 20 lug 2026. Raccoglie in UN posto le costanti che governano comportamenti
// di prodotto, finora sparse o solo implicite. Ognuna puo' essere
// sovrascritta da .env (getenv) senza toccare il codice; qui c'e' il DEFAULT
// e la spiegazione di cosa fa.
//
// Incluso da config/bootstrap.php. Se una costante e' gia' definita altrove
// (es. .env caricato prima), NON viene sovrascritta (define e' idempotente).
// ============================================================

// -------------------------------------------------------------------
// MODERAZIONE DEGLI ANNUNCI
// -------------------------------------------------------------------
// false (default storico): un annuncio nuovo nasce 'approved' -> PUBBLICO
//        ALL'ISTANTE. Veloce, ma nessun filtro: chiunque pubblica subito.
// true : un annuncio nuovo nasce 'pending' -> resta invisibile finche' un
//        admin non lo approva da _admin/moderate_ads.php. Piu' controllo,
//        piu' lavoro per l'admin. Quando l'admin approva, i buyer con una
//        wanted compatibile vengono notificati (gia' gestito in moderate_ads).
//
// Nota: con la moderazione ATTIVA, la notifica ai buyer alla pubblicazione
// (nel wizard) NON parte, perche' l'annuncio non e' ancora pubblico; parte
// all'approvazione. Il wizard se ne accorge da solo leggendo questo flag.
if (!defined('AOW_MODERATION_REQUIRED')) {
    // Pubblicazione IMMEDIATA: ogni annuncio nasce 'approved'.
    // L'admin puo' solo rimuovere/modificare su richiesta dell'utente.
    define('AOW_MODERATION_REQUIRED', false);
}

// -------------------------------------------------------------------
// RFQ: destinatari mirati
// -------------------------------------------------------------------
// Tetto ai fornitori destinatari di una richiesta di preventivo. I fornitori
// sono ordinati per pertinenza (chiavi prodotto in comune): il tetto tiene i
// piu' pertinenti. 0 = nessun tetto. Anti-spam sui fornitori.
if (!defined('AOW_RFQ_MAX_RECIPIENTS')) {
    $v = getenv('AOW_RFQ_MAX_RECIPIENTS');
    define('AOW_RFQ_MAX_RECIPIENTS', $v !== false ? max(0, (int)$v) : 3);
}

// Torna al vecchio broadcast (RFQ a TUTTE le aziende attive). Sconsigliato:
// esiste solo come via di fuga se serve ripristinare il comportamento storico.
if (!defined('AOW_RFQ_BROADCAST')) {
    $v = getenv('AOW_RFQ_BROADCAST');
    define('AOW_RFQ_BROADCAST',
        $v !== false ? in_array(strtolower((string)$v), ['1', 'true', 'yes', 'on'], true) : false
    );
}

// -------------------------------------------------------------------
// WANTED: notifiche
// -------------------------------------------------------------------
// Salvaguardia latenza: quando esce un annuncio, i buyer con una wanted
// compatibile vengono avvisati. Mailer e' sincrono; questo cap evita di
// bloccare la pubblicazione con centinaia di invii. I buyer sono ordinati
// per pertinenza. 0 = nessun cap. Diverso dal tetto RFQ: qui e' anti-blocco,
// non anti-spam (il buyer HA chiesto di essere avvisato).
if (!defined('AOW_WANTED_NOTIFY_MAX')) {
    $v = getenv('AOW_WANTED_NOTIFY_MAX');
    define('AOW_WANTED_NOTIFY_MAX', $v !== false ? max(0, (int)$v) : 50);
}

// -------------------------------------------------------------------
// RFQ: escalation dei lead fermi (cron)
// -------------------------------------------------------------------
// Ore oltre le quali un lead ancora 'new'/'distributed' viene segnalato
// all'admin da scripts/rfq_escalation.php.
if (!defined('AOW_RFQ_ESCALATION_HOURS')) {
    $v = getenv('AOW_RFQ_ESCALATION_HOURS');
    define('AOW_RFQ_ESCALATION_HOURS', $v !== false ? max(1, (int)$v) : 24);
}

// -------------------------------------------------------------------
// RFQ: claim dei lead (cron rfq_claim_reassign.php)
// -------------------------------------------------------------------
// Dopo quante ore un lead non preso in carico da nessun fornitore fa scattare
// il SOLLECITO ai fornitori destinatari.
if (!defined('AOW_CLAIM_REMIND_HOURS')) {
    $v = getenv('AOW_CLAIM_REMIND_HOURS');
    define('AOW_CLAIM_REMIND_HOURS', $v !== false ? max(1, (int)$v) : 24);
}
// Dopo quante ore un lead ANCORA non preso (dopo il sollecito) viene segnalato
// all'admin per un intervento manuale.
if (!defined('AOW_CLAIM_ESCALATE_HOURS')) {
    $v = getenv('AOW_CLAIM_ESCALATE_HOURS');
    define('AOW_CLAIM_ESCALATE_HOURS', $v !== false ? max(1, (int)$v) : 48);
}
// Casella admin per l'escalation dei lead.
if (!defined('AOW_RFQ_INBOX')) {
    $v = getenv('AOW_RFQ_INBOX');
    define('AOW_RFQ_INBOX', ($v !== false && $v !== '') ? (string)$v : 'rfq@allonwheel.com');
}
