<?php
// ============================================================
// shared/empty_state.php — la ricerca senza risultati come punto di raccolta
// della domanda.
//
// 27 lug 2026. Prima qui c'era un vicolo cieco: "There are no ads published
// yet". Con l'inventario a zero (0 annunci pubblicati alla data) questa e' la
// pagina che vede la quasi totalita' dei visitatori: rimandarli indietro senza
// chiedere nulla significa buttare via il 100% della domanda in arrivo.
//
// Cosa fa adesso, in ordine di valore:
//  1. Alert email sulla ricerca corrente, aperto anche a chi non ha un account
//     (doppio opt-in via email: e' un consenso, va provato).
//  2. Richiesta di preventivo precompilata con la categoria cercata: l'utente
//     ha un bisogno, i costruttori sono gia' a catalogo anche senza annunci.
//  3. Costruttori della categoria (offerta reale gia' presente sul sito).
//  4. Pubblicazione annuncio, per chi e' arrivato qui da venditore.
//
// Variabili attese (facoltative): $aow_empty_ctx = ['q','macro','vtype','label']
// ============================================================

if (!isset($aow_empty_ctx) || !is_array($aow_empty_ctx)) { $aow_empty_ctx = []; }
$es_q     = trim((string)($aow_empty_ctx['q']     ?? ''));
$es_macro = trim((string)($aow_empty_ctx['macro'] ?? ''));
$es_vtype = trim((string)($aow_empty_ctx['vtype'] ?? ''));
$es_label = trim((string)($aow_empty_ctx['label'] ?? ''));
$es_base  = isset($base_url) && $base_url !== '' ? $base_url : '';

$es_filtered = ($es_q !== '' || $es_macro !== '' || $es_vtype !== '');
$es_what     = $es_label !== '' ? $es_label : ($es_q !== '' ? $es_q : '');

if (!function_exists('current_user_id')) { @require_once __DIR__ . '/../config/session_helper.php'; }
$es_uid   = function_exists('current_user_id') ? current_user_id() : null;
$es_email = ($es_uid !== null && function_exists('current_user_email')) ? trim((string)current_user_email()) : '';
require_once __DIR__ . '/../config/csrf.php';
?>
<div class="post_box empty_state">
  <h2>
    <?php if ($es_filtered && $es_what !== ''): ?>
      <?php te('empty.no_match_h', 'Nothing matches'); ?> &ldquo;<?php echo htmlspecialchars($es_what, ENT_QUOTES, 'UTF-8'); ?>&rdquo; <?php te('empty.yet', 'yet'); ?>
    <?php else: ?>
      <?php te('empty.no_listing_h', 'No listings published yet'); ?>
    <?php endif; ?>
  </h2>
  <p>
    <?php te('empty.lead', 'The marketplace is in its launch phase: listings are being onboarded family by family. Tell us what you are looking for and you will be the first to know — or ask the manufacturers directly, they are already on the platform.'); ?>
  </p>

  <?php if (!empty($_SESSION['ss_flash'])): ?>
    <p class="done"><?php echo htmlspecialchars($_SESSION['ss_flash'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['ss_flash']); ?></p>
  <?php endif; ?>

  <div class="es_actions">

    <?php // 1. Alert email: il modo piu' economico di trattenere la domanda. ?>
    <div class="es_card">
      <h3><?php te('empty.alert_h', 'Get an email when it arrives'); ?></h3>
      <p><?php te('empty.alert_p', 'We will notify you as soon as a matching listing is published. One email, no newsletter, unsubscribe with one click.'); ?></p>
      <form method="post" action="<?php echo $es_base; ?>saved_search_save.php" class="es_form">
        <?php echo csrf_generate(); ?>
        <input type="hidden" name="macro" value="<?php echo htmlspecialchars($es_macro, ENT_QUOTES, 'UTF-8'); ?>" />
        <input type="hidden" name="q"     value="<?php echo htmlspecialchars($es_q, ENT_QUOTES, 'UTF-8'); ?>" />
        <input type="hidden" name="vtype" value="<?php echo htmlspecialchars($es_vtype, ENT_QUOTES, 'UTF-8'); ?>" />
        <?php if ($es_uid === null): ?>
          <?php // Campo trappola per i bot: invisibile, mai compilato da un umano. ?>
          <div class="hp_field" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off" /></label></div>
          <label for="es_email"><?php te('empty.your_email', 'Your email'); ?></label>
          <input type="email" id="es_email" name="email" required="required" autocomplete="email"
                 placeholder="name@company.com" class="input_field" />
        <?php else: ?>
          <p class="post_meta"><?php te('empty.alert_to', 'Alerts will be sent to'); ?>
             <strong><?php echo htmlspecialchars($es_email, ENT_QUOTES, 'UTF-8'); ?></strong></p>
        <?php endif; ?>
        <select name="freq" aria-label="<?php te('empty.freq', 'Frequency'); ?>">
          <option value="daily"><?php te('ss.freq_daily', 'As they arrive (daily check)'); ?></option>
          <option value="weekly"><?php te('ss.freq_weekly', 'Weekly digest'); ?></option>
        </select>
        <input type="submit" class="more btn_accent" value="<?php te('empty.alert_btn', 'Alert me'); ?>" />
        <?php if ($es_uid === null): ?>
        <p class="post_meta"><small><?php te('empty.alert_privacy', 'We only use your address for this alert. You will receive a confirmation email first (double opt-in).'); ?></small></p>
        <?php endif; ?>
      </form>
    </div>

    <?php // 2. RFQ: l'utente ha un bisogno adesso, non fra sei mesi. ?>
    <div class="es_card">
      <h3><?php te('empty.rfq_h', 'Ask the manufacturers'); ?></h3>
      <p><?php te('empty.rfq_p', 'Describe what you need: your request is sent to the specialist bodybuilders that actually build it, and they reply with a quotation.'); ?></p>
      <?php
        $es_rfq = $es_base . '04_request_offer/04_request_offer.php';
        $es_rfq_qs = [];
        if ($es_macro !== '') { $es_rfq_qs[] = 'macro=' . rawurlencode($es_macro); }
        if ($es_vtype !== '') { $es_rfq_qs[] = 'vtype=' . rawurlencode($es_vtype); }
        if ($es_q !== '')     { $es_rfq_qs[] = 'subject=' . rawurlencode($es_q); }
        if ($es_rfq_qs) { $es_rfq .= '?' . implode('&amp;', $es_rfq_qs); }
      ?>
      <p><a class="more btn_accent" href="<?php echo $es_rfq; ?>"><?php te('empty.rfq_btn', 'Request a quotation'); ?></a></p>
    </div>

    <?php // 3. Offerta gia' presente: la directory fornitori. ?>
    <div class="es_card">
      <h3><?php te('empty.sup_h', 'Browse the manufacturers'); ?></h3>
      <p><?php te('empty.sup_p', 'Specialist bodybuilders, with their product ranges and certifications.'); ?></p>
      <p><a href="<?php echo $es_base; ?>06_company/06_30_company_directory.php"><?php te('empty.sup_btn', 'Supplier directory'); ?></a>
         &middot; <a href="<?php echo $es_base; ?>portfolio.php"><?php te('nav.portfolio', 'Portfolio'); ?></a></p>
    </div>

    <?php // 4. Chi e' arrivato qui da venditore. ?>
    <div class="es_card">
      <h3><?php te('empty.sell_h', 'Do you have one to sell?'); ?></h3>
      <p><?php te('empty.sell_p', 'Listing is free and takes a few minutes. Early listings get all the visibility of a launching marketplace.'); ?></p>
      <?php if ($es_uid !== null): ?>
        <p><a class="more" href="<?php echo $es_base; ?>02_free_ads/02_insert_ad.php"><?php te('empty.sell_btn', 'Post a listing'); ?></a></p>
      <?php else: ?>
        <p><a class="more" href="<?php echo $es_base; ?>01_login/newregister.php?intent=sell"><?php te('empty.sell_btn', 'Post a listing'); ?></a></p>
      <?php endif; ?>
    </div>

  </div>

  <?php if ($es_filtered): ?>
  <p class="cleaner"><a href="<?php echo $es_base; ?>browse.php"><?php te('empty.view_all', 'View the whole marketplace'); ?></a></p>
  <?php endif; ?>
</div>
