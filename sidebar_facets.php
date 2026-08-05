<?php
// ============================================================
// sidebar_facets.php  Faccette del marketplace, in SIDEBAR (dir. 21)
//
// 17 lug 2026. browse.php ha da tempo le faccette cond[] / pmin / pmax nella
// query, ma la loro UNICA interfaccia era la chip-bar nel corpo pagina, che
// la dir. 21 ha fatto rimuovere: da allora quei filtri erano raggiungibili
// solo scrivendo l'URL a mano. Qui ritrovano casa nel posto giusto: la
// sidebar.
//
// Coerente con sidebar_vtype_search.php: menu a tendina, niente checkbox
// (richiesta 5 lug 2026), stesse classi (sb_box / submit_btn / cleaner).
// Nessuno stile nuovo (dir. 8).
//
// Le CONDIZIONI non sono un elenco fisso copiato da browse.php (divergerebbe
// al primo cambiamento): si leggono dagli annunci pubblicati. Cosi' non si
// offrono filtri che non darebbero alcun risultato (dir. 14: solo dati reali).
// ============================================================

// Stessa guardia di sidebar_vtype_search.php: il box e' autonomo e non
// dipende dall'ordine di inclusione.
if (!isset($pdo) || !($pdo instanceof PDO)) { @require_once __DIR__ . '/config/database.php'; }

if (!isset($base_url)) {
    $base_url = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
}

// Valori realmente presenti fra gli annunci approvati (free + premium).
$_fx_conds = [];
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $_fx_st = $pdo->query(
            "SELECT DISTINCT `conditions` FROM `02_free_ads` WHERE status = 'approved' AND `conditions` <> ''
             UNION
             SELECT DISTINCT `conditions` FROM `03_ads`      WHERE status = 'approved' AND `conditions` <> ''
             ORDER BY 1"
        );
        $_fx_conds = $_fx_st ? $_fx_st->fetchAll(PDO::FETCH_COLUMN) : [];
    } catch (PDOException $e) {
        error_log('[Allonwheel] sidebar_facets conditions: ' . $e->getMessage());
        $_fx_conds = [];
    }
}

// Stato corrente (per ripresentare la scelta fatta). $_GET['cond'] in
// browse.php e' trattato come array: un valore singolo va benissimo,
// (array)'Used' = ['Used'].
$_fx_cond_cur = '';
$_fx_c = $_GET['cond'] ?? '';
if (is_array($_fx_c)) { $_fx_c = reset($_fx_c); }
$_fx_cond_cur = trim((string)$_fx_c);

$_fx_pmin = (isset($_GET['pmin']) && $_GET['pmin'] !== '') ? (int)$_GET['pmin'] : '';
$_fx_pmax = (isset($_GET['pmax']) && $_GET['pmax'] !== '') ? (int)$_GET['pmax'] : '';
// Lunghezza in METRI (23 lug 2026). browse.php filtra su length_mt tramite
// lmin/lmax, ma nessun form li generava: il filtro era codice irraggiungibile.
// Decimali ammessi (colonna decimal(6,2)), niente (int) che troncherebbe 12.5.
$_fx_lmin = (isset($_GET['lmin']) && $_GET['lmin'] !== '') ? htmlspecialchars((string)$_GET['lmin'], ENT_QUOTES, 'UTF-8') : '';
$_fx_lmax = (isset($_GET['lmax']) && $_GET['lmax'] !== '') ? htmlspecialchars((string)$_GET['lmax'], ENT_QUOTES, 'UTF-8') : '';

$_fx_q    = trim((string)($_GET['q'] ?? ''));
$_fx_any  = ($_fx_cond_cur !== '' || $_fx_pmin !== '' || $_fx_pmax !== ''
             || $_fx_lmin !== '' || $_fx_lmax !== '');

// Se non c'e' nulla da filtrare, il box non ha senso: non lo mostro affatto
// (una tendina vuota fa sembrare il sito rotto).
if (empty($_fx_conds)) { return; }
?>
<!-- ===== Faccette marketplace (dir. 21: i filtri stanno nelle sidebar) ===== -->
<div class="sb_box">
  <h3><?php te('facet.refine', 'Refine listings'); ?></h3>
  <form method="get" action="<?php echo $base_url; ?>browse.php">
    <?php // La ricerca testuale in corso non va persa applicando un filtro. ?>
    <?php if ($_fx_q !== ''): ?>
    <input type="hidden" name="q" value="<?php echo htmlspecialchars($_fx_q, ENT_QUOTES, 'UTF-8'); ?>" />
    <?php endif; ?>
    <?php
    // Anche la famiglia e il tipo di veicolo vanno riportati: applicando un
    // filtro di prezzo l'utente veniva sbalzato dalla categoria all'intero
    // marketplace, e il numero di risultati aumentava invece di ridursi.
    foreach (['macro', 'vtype', 'cat'] as $_fx_keep):
        $_fx_kv = trim((string)($_GET[$_fx_keep] ?? ''));
        if ($_fx_kv === '') { continue; } ?>
    <input type="hidden" name="<?php echo $_fx_keep; ?>" value="<?php echo htmlspecialchars($_fx_kv, ENT_QUOTES, 'UTF-8'); ?>" />
    <?php endforeach; ?>

    <select name="cond">
      <option value=""><?php te('facet.all_cond', 'All conditions'); ?></option>
      <?php foreach ($_fx_conds as $_fx_cv): ?>
      <option value="<?php echo htmlspecialchars($_fx_cv, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $_fx_cond_cur === $_fx_cv ? ' selected' : ''; ?>><?php echo htmlspecialchars($_fx_cv, ENT_QUOTES, 'UTF-8'); ?></option>
      <?php endforeach; ?>
    </select>

    <div class="cleaner h10"></div>
    <input type="number" name="pmin" min="0" step="1000" class="input_field"
           value="<?php echo htmlspecialchars((string)$_fx_pmin, ENT_QUOTES, 'UTF-8'); ?>"
           placeholder="<?php te('facet.price_min', 'Min price (EUR)'); ?>" />

    <div class="cleaner h10"></div>
    <input type="number" name="pmax" min="0" step="1000" class="input_field"
           value="<?php echo htmlspecialchars((string)$_fx_pmax, ENT_QUOTES, 'UTF-8'); ?>"
           placeholder="<?php te('facet.price_max', 'Max price (EUR)'); ?>" />
    <?php
    // 27 lug 2026 — qui c'era il filtro lunghezza "commentato" dentro un
    // commento HTML. Non funziona: il tag di chiusura del primo blocco PHP
    // interno chiude anche il commento, e il resto ("===== -->" e i valori dei
    // campi) finiva stampato nella pagina, visibile agli utenti su ogni
    // schermata del marketplace. Il codice morto e' stato rimosso: i parametri
    // lmin/lmax continuano a funzionare via URL, gestiti da browse.php.
    ?>
    <div class="cleaner h10"></div>
    <input type="submit" class="submit_btn" value="<?php te('facet.apply', 'Search'); ?>" />
  </form>

  <?php if ($_fx_any): ?>
  <div class="cleaner h10"></div>
  <?php
  // "Clear filters" azzera prezzo e condizione ma non deve buttare via anche
  // la ricerca e la categoria in cui l'utente si trova.
  $_fx_keep_qs = [];
  if ($_fx_q !== '') { $_fx_keep_qs['q'] = $_fx_q; }
  foreach (['macro', 'vtype', 'cat'] as $_fx_k) {
      $_fx_v = trim((string)($_GET[$_fx_k] ?? ''));
      if ($_fx_v !== '') { $_fx_keep_qs[$_fx_k] = $_fx_v; }
  }
  ?>
  <a href="<?php echo $base_url; ?>browse.php<?php echo $_fx_keep_qs ? '?' . htmlspecialchars(http_build_query($_fx_keep_qs), ENT_QUOTES, 'UTF-8') : ''; ?>"><?php te('facet.clear', 'Clear filters'); ?></a>
  <?php endif; ?>
</div>
<div class="cleaner h20"></div>
