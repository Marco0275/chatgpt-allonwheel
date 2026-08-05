<?php
/**
 * libs/antispam.php - Antispam centralizzato per tutti i form che inviano mail.
 *
 * Tre livelli di protezione:
 *   1) HONEYPOT: un campo nascosto che gli umani non vedono; se e' compilato -> bot.
 *   2) TIME-TRAP FIRMATA: si misura quanto tempo passa tra apertura e invio del form.
 *      Troppo veloce (o troppo vecchio) = spam. Il timestamp e' firmato (HMAC) cosi'
 *      un bot non puo' falsificarlo.
 *   3) FILTRO LINK/PAROLE: troppi link o parole "hard" nel messaggio = spam.
 *
 * ====== PARAMETRI REGOLABILI (modifica qui) ======
 *   AOW_SPAM_MIN_SECONDS : sotto questo tempo di compilazione l'invio e' SPAM (es. 5).
 *   AOW_SPAM_MAX_SECONDS : oltre questo tempo il form e' "scaduto" (es. 2 ore).
 *   AOW_SPAM_MAX_LINKS   : numero di link http(s) oltre il quale scatta lo spam.
 *   AOW_SPAM_HONEYPOT    : nome del campo-trappola (deve sembrare plausibile).
 *   AOW_SPAM_WORDS       : parole che, se presenti, bloccano subito.
 *   AOW_SPAM_SECRET      : chiave per firmare il timestamp (cambiala una volta).
 * Puoi ridefinire queste costanti in config/app_settings.php PRIMA di questo file.
 */

if (!defined('AOW_SPAM_MIN_SECONDS')) { define('AOW_SPAM_MIN_SECONDS', 5); }
if (!defined('AOW_SPAM_MAX_SECONDS')) { define('AOW_SPAM_MAX_SECONDS', 3600); } // 1 ora
if (!defined('AOW_SPAM_MAX_LINKS'))   { define('AOW_SPAM_MAX_LINKS', 1); }
if (!defined('AOW_SPAM_HONEYPOT'))    { define('AOW_SPAM_HONEYPOT', 'website_url'); }
if (!defined('AOW_SPAM_SECRET'))      { define('AOW_SPAM_SECRET', 'Cambia la chiave segreta una sola volta apri antispam e in cima sostituisci con questo'); }
if (!defined('AOW_SPAM_WORDS')) {
    define('AOW_SPAM_WORDS', 'viagra|cialis|casino|porn|xxx|escort|bitcoin|crypto|loan|[url|<a href');
}

/**
 * Campi nascosti da mettere dentro OGNI form protetto: honeypot + timestamp firmato.
 * Uso nel form:  <?php echo aow_spam_fields(); ?>
 */
function aow_spam_fields(): string
{
    $ts  = time();
    $sig = hash_hmac('sha256', (string)$ts, AOW_SPAM_SECRET);
    $hp  = htmlspecialchars(AOW_SPAM_HONEYPOT, ENT_QUOTES, 'UTF-8');

    return
        '<div aria-hidden="true" style="position:absolute!important;left:-9999px!important;'
      . 'top:-9999px!important;width:1px;height:1px;overflow:hidden">'
      . '<label>Leave this field empty'
      . '<input type="text" name="' . $hp . '" tabindex="-1" autocomplete="off" value="" />'
      . '</label></div>'
      . '<input type="hidden" name="aow_ts" value="' . $ts . '" />'
      . '<input type="hidden" name="aow_sig" value="' . htmlspecialchars($sig, ENT_QUOTES, 'UTF-8') . '" />';
}

/**
 * Ritorna TRUE se la submission va rifiutata come spam.
 * $text = testo libero da controllare per link/parole (messaggio/descrizione), opzionale.
 * Uso nell'handler:  if (aow_is_spam($messaggio)) { header('Location: ...retry'); exit; }
 */
function aow_is_spam(string $text = ''): bool
{
    // 1) Honeypot (nuovo campo + retro-compatibilita' col vecchio 'test')
    if (!empty($_POST[AOW_SPAM_HONEYPOT])) { return true; }
    if (!empty($_POST['test']))            { return true; }

    // 2) Time-trap: timestamp firmato (retro-compat: vecchio momento_del_caricamento)
    $ts  = (int)($_POST['aow_ts'] ?? 0);
    $sig = (string)($_POST['aow_sig'] ?? '');
    if ($ts > 0 && $sig !== '') {
        // firma valida obbligatoria
        if (!hash_equals(hash_hmac('sha256', (string)$ts, AOW_SPAM_SECRET), $sig)) {
            return true; // timestamp manomesso
        }
    } else {
        // form vecchio non ancora aggiornato: usa il campo storico (non firmato)
        $ts = (int)($_POST['momento_del_caricamento'] ?? 0);
    }
    if ($ts <= 0) { return true; } // nessun timestamp = sospetto

    $elapsed = time() - $ts;
    if ($elapsed < AOW_SPAM_MIN_SECONDS || $elapsed > AOW_SPAM_MAX_SECONDS) {
        return true; // troppo veloce o form scaduto
    }

    // 3) Link e parole nel testo
    if ($text !== '') {
        if (preg_match_all('~https?://~i', $text) >= AOW_SPAM_MAX_LINKS) { return true; }
        if (preg_match('~(' . AOW_SPAM_WORDS . ')~i', $text)) { return true; }
    }

    return false;
}
