<?php
// ============================================================
// shared/ad_card.php  CARD ANNUNCIO: formato UNICO per tutte le pagine
//
// 17 lug 2026. Prima ogni pagina disegnava la card a modo suo: browse.php
// (riferimento) aveva badge + gallery.m0 + banda footer; shelter_container
// non aveva ne' badge ne' gallery.m0 (quindi nemmeno la banda); road/special
// avevano la tabella ma non i badge; le pagine famiglia un altro formato
// ancora. Cinque rendering diversi -> ora UNO SOLO, incluso da tutte:
// la formattazione e' identica per costruzione e non puo' piu' divergere.
//
// CONTRATTO (la pagina chiamante imposta $aow_ad prima dell'include):
//   $aow_ad['id_ads','title','subtitle','list_price','type','conditions',
//           'image_original','image_thumbnail','description','author',
//           'created_at','id_user','detail_url','upload_path']
//   $aow_ad['is_premium']  bool   (facolt.: badge Premium)
//   $aow_cert_users        array  (facolt.: [id_user => x] fornitori ISO)
//   $aow_type_label        callable (facolt.: etichetta leggibile di `type`)
//   $base_url              string
// Tutte le chiavi sono lette in modo difensivo: una pagina che non ha
// l'autore o l'immagine non va in errore, semplicemente non li mostra.
//
// Nessuno stile nuovo (dir. 8): usa solo classi gia' nel foglio di stile.
// ============================================================

if (!isset($aow_ad) || !is_array($aow_ad)) { return; }

$c_base   = isset($base_url) ? $base_url : '';
$c_id     = (int)($aow_ad['id_ads'] ?? 0);
$c_title  = (string)($aow_ad['title'] ?? '');
$c_sub    = trim((string)($aow_ad['subtitle'] ?? ''));
$c_prem   = !empty($aow_ad['is_premium']);
$c_owner_tier = (string)($aow_ad['owner_tier'] ?? '');
$c_upl    = (string)($aow_ad['upload_path'] ?? '');
$c_thumb  = trim((string)($aow_ad['image_thumbnail'] ?? ''));
$c_orig   = trim((string)($aow_ad['image_original'] ?? ''));
$c_price  = (float)($aow_ad['list_price'] ?? 0);
$c_cond   = trim((string)($aow_ad['conditions'] ?? ''));
$c_author = trim((string)($aow_ad['author'] ?? ''));
$c_uid    = (int)($aow_ad['id_user'] ?? 0);

// Immagini: fallback a no_image, mai percorsi inventati (dir. 15)
$c_noimg  = $c_base . 'images/no_image.jpg';
$c_thumb_url = ($c_thumb !== '' && $c_thumb !== 'no_image.jpg') ? $c_upl . 'thumbnail/' . $c_thumb : $c_noimg;
$c_orig_url  = ($c_orig  !== '' && $c_orig  !== 'no_image.jpg') ? $c_upl . 'original/'  . $c_orig  : $c_thumb_url;

// Descrizione accorciata. NB: nel vecchio browse.php veniva stampata DUE
// volte quando il prezzo era "on request" (una nel ramo else e una dopo):
// si vedeva il testo raddoppiato nella card. Qui e' stampata una volta sola.
$c_desc  = (string)($aow_ad['description'] ?? '');
$c_short = mb_strlen($c_desc) > 220 ? mb_substr($c_desc, 0, 220) . '...' : $c_desc;

$c_ts   = strtotime((string)($aow_ad['created_at'] ?? ''));
$c_date = $c_ts ? date('d M Y', $c_ts) : '';

// URL dettaglio: la pagina passa il percorso, qui si aggiunge l'id una volta sola.
$c_durl = (string)($aow_ad['detail_url'] ?? '');
if ($c_durl !== '' && strpos($c_durl, 'id_ads=') === false) {
    $c_durl .= (strpos($c_durl, '?') === false ? '?' : '&') . 'id_ads=' . $c_id;
}
if ($c_durl !== '' && strpos($c_durl, '://') === false && strpos($c_durl, '/') !== 0) {
    $c_durl = $c_base . $c_durl;
}

// Etichetta leggibile del campo `type` (ogni pagina puo' passare la sua)
$c_type_lbl = '';
if (isset($aow_type_label) && is_callable($aow_type_label)) {
    $c_type_lbl = (string)call_user_func($aow_type_label, (string)($aow_ad['type'] ?? ''));
} else {
    $t = trim((string)($aow_ad['type'] ?? ''));
    $c_type_lbl = $t !== '' ? ucfirst(str_replace('_', ' ', $t)) : '';
}
?>
<div class="post_box">
  <h2><a href="<?php echo htmlspecialchars($c_durl, ENT_QUOTES); ?>"><?php echo htmlspecialchars($c_title); ?></a></h2>

  <?php if ($c_sub !== ''): ?>
  <p><em><?php echo htmlspecialchars($c_sub); ?></em></p>
  <?php endif; ?>

  <?php // La classe gallery.m0 non e' decorativa: e' l'aggancio del CSS che
        // disegna la banda tratteggiata del footer. Senza, la card cambia
        // aspetto (era il caso di shelter_container.php). Va sempre presente. ?>
  <ul class="gallery m0">
    <li><a class="pirobox" href="<?php echo htmlspecialchars($c_orig_url, ENT_QUOTES); ?>" title="<?php echo htmlspecialchars($c_title, ENT_QUOTES); ?>"><img loading="lazy" decoding="async" src="<?php echo htmlspecialchars($c_thumb_url, ENT_QUOTES); ?>" alt="<?php echo htmlspecialchars($c_title, ENT_QUOTES); ?>" width="220" height="150" border="0" /></a></li>
  </ul>

  <p class="badges">
    <?php // Solo Premium: nessun badge "Free" (decisione 16 lug 2026). ?>
    <?php require_once __DIR__ . '/../libs/plan_policy.class.php';
          $c_badge = $c_owner_tier !== '' ? PlanPolicy::badge($c_owner_tier) : ($c_prem ? 'Premium' : ''); ?>
    <?php if ($c_badge === 'Featured'): ?><span class="badge badge_featured">Featured</span>
    <?php elseif ($c_badge === 'Premium'): ?><span class="badge badge_premium">Premium</span><?php endif; ?>
    <?php if ($c_type_lbl !== ''): ?><span class="badge badge_type"><?php echo htmlspecialchars($c_type_lbl); ?></span><?php endif; ?>
    <?php if ($c_cond !== ''): ?><span class="badge badge_cond"><?php echo htmlspecialchars($c_cond); ?></span><?php endif; ?>
    <?php if (!empty($aow_cert_users) && $c_uid > 0 && isset($aow_cert_users[$c_uid])): ?>
    <span class="badge badge_approved" title="ISO certified supplier">&#10003; <?php te('ad.cert_supplier', 'Certified supplier'); ?></span>
    <?php endif; ?>
  </p>

  <?php if ($c_price > 0): ?>
  <p class="price">&euro;&nbsp;<?php echo number_format($c_price, 0, '.', ','); ?></p>
  <?php else: ?>
  <p><em><?php te('ad.price_request', 'Price on request'); ?></em></p>
  <?php endif; ?>

  <?php if ($c_short !== ''): ?>
  <p><?php echo nl2br(htmlspecialchars($c_short)); ?></p>
  <?php endif; ?>

  <div class="cleaner h20"></div>

  <?php // Footer: DUE figli diretti del flex (autore/data | pulsante).
        // Il CSS .post_box:has(.gallery.m0) .post_meta e' display:flex con
        // justify-content:space-between: con due figli il pulsante finisce
        // da solo a filo del bordo destro. Prima qui c'era una <table>, cioe'
        // UN solo figlio: space-between non aveva effetto e il pulsante
        // restava a meta' card. Niente float (il CSS lo annulla comunque). ?>
  <div class="post_meta">
    <span class="cat">
      <?php if ($c_author !== ''): ?>By <strong><?php echo htmlspecialchars($c_author); ?></strong><?php endif; ?>
      <?php if ($c_author !== '' && $c_date !== ''): ?> | <?php endif; ?>
      <?php if ($c_date !== ''): ?>Published: <strong><?php echo htmlspecialchars($c_date); ?></strong><?php endif; ?>
    </span>
    <a href="<?php echo htmlspecialchars($c_durl, ENT_QUOTES); ?>" class="more"><?php te('ad.details', 'View details'); ?></a>
  </div>

  <div class="cleaner"></div>
</div>
