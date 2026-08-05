<?php
// shared/ad_pdf.php  Scheda dell'annuncio in PDF, per annunci FREE e PREMIUM.
// Generalizza il vecchio 03_ads/03_tech_pdf.php (che gestiva solo i premium):
// accetta ?id_ads=N&t=<tabella>. Free (02_free_ads): titolo/meta/descrizione/gallery.
// Premium (03_ads): in piu' la scheda tecnica. mPDF via PdfHelper.
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../libs/pdf_helper.class.php';

$id_ads = (int)($_GET['id_ads'] ?? 0);
if ($id_ads <= 0) { http_response_code(400); exit('Bad request'); }

// Tabella whitelisted: mai concatenare input non validato in SQL (dir. 11).
$table = (string)($_GET['t'] ?? $_GET['ad_table'] ?? '03_ads');
$ALLOWED = ['02_free_ads', '03_ads'];
if (!in_array($table, $ALLOWED, true)) { http_response_code(400); exit('Bad request'); }
$is_premium = ($table === '03_ads');

$st = $pdo->prepare(sprintf('SELECT * FROM `%s` WHERE id_ads = :id LIMIT 1', $table));
$st->execute([':id' => $id_ads]);
$ad = $st->fetch(PDO::FETCH_ASSOC);
if (!$ad) { http_response_code(404); exit('Not found'); }

// Scheda tecnica: solo premium.
$tech = [];
if ($is_premium) {
    $st2 = $pdo->prepare('SELECT * FROM `03_ads_tech_details` WHERE id_ads = :id LIMIT 1');
    $st2->execute([':id' => $id_ads]);
    $tech = $st2->fetch(PDO::FETCH_ASSOC) ?: [];
}

// Gallery: stessa struttura su entrambe (id_images/image_original/image_thumbnail).
$st3 = $pdo->prepare(sprintf(
    'SELECT image_original, image_thumbnail FROM `%s_gallery` WHERE id_ads = :id ORDER BY id_images ASC',
    $table
));
$st3->execute([':id' => $id_ads]);
$gallery = $st3->fetchAll(PDO::FETCH_ASSOC);

$e = static function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

// Percorso filesystem immagini (mPDF legge da disco) sottocartella per tabella.
$doc = $_SERVER['DOCUMENT_ROOT'] ?? '';
if ($doc === '' || !is_dir($doc)) { $doc = realpath(__DIR__ . '/..') ?: __DIR__ . '/..'; }
$imgTag = static function (string $subdir, $file, int $w) use ($doc, $table, $e): string {
    $file = trim((string)$file);
    if ($file === '' || $file === 'no_image.jpg') { return ''; }
    $fs = $doc . '/upload_image/' . $table . '/' . $subdir . '/' . $file;
    if (!is_file($fs)) { return ''; }
    return '<img src="' . $e($fs) . '" width="' . $w . '" loading="lazy" decoding="async" />';
};

// Prettify slug (vehicle_type / product_macro).
$pretty_slug = static function ($s): string {
    $s = trim((string)$s);
    return $s === '' ? '' : ucwords(str_replace(['-', '_'], ' ', $s));
};

// Categorie: flag-colonna presenti solo sui premium (non esistono su 02_free_ads).
$cats = [];
if ($is_premium) {
    $cat_map = ['racing'=>'Racing','hospitality'=>'Hospitality','medical'=>'Medical','military'=>'Military',
                'motorhome'=>'Motorhome','technology'=>'Technology','street_food'=>'Street food','horse'=>'Animal transport'];
    foreach ($cat_map as $k => $lab) { if (!empty($ad[$k])) { $cats[] = $lab; } }
}

// Gruppi/etichette tech (come 03_view_tech_details) usati solo se premium.
$groups = [
  'General options'    => ['Awning','Workshop','Belly','Kitchen','Beds','Genset','Bathroom','SAT'],
  'Lift facilities'    => ['Lift_manufactorer','Lift_length','Lift_width','Lift_capacity'],
  'Cargo facilities'   => ['rails','LED','independent_entrance_cargo','Fixing','Cabinets','Adjustable','Workbenches'],
  'Office furniture'   => ['HVAC','Telemetry','independent_entrance_office','Electrical','office_other','Windows','TV'],
  'Electrical system'  => ['Main_panel','batteries','Charger','Connection','Switchgear','electrical_other','Sockets','Rema'],
  'Outside finishing'  => ['Plywood','painted','Sandwich','Stickers','Special','Stepdeck'],
  'Chassis'            => ['axles','Straightline','MGW','chassis_special','Saddle'],
  'External dimension' => ['ext_length','ext_width','ext_height'],
];
$labels = ['Lift_manufactorer'=>'Lift manufacturer','Lift_length'=>'Lift length','Lift_width'=>'Lift width',
           'Lift_capacity'=>'Lift capacity','independent_entrance_cargo'=>'Independent cargo entrance',
           'independent_entrance_office'=>'Independent office entrance','office_other'=>'Other (office)',
           'electrical_other'=>'Other (electrical)','Main_panel'=>'Main panel','chassis_special'=>'Special chassis',
           'ext_length'=>'External length','ext_width'=>'External width','ext_height'=>'External height',
           'MGW'=>'Max gross weight'];
$pretty = static function (string $c) use ($labels): string {
    return $labels[$c] ?? ucfirst(str_replace('_', ' ', $c));
};

$veh   = $pretty_slug($ad['vehicle_type']  ?? '');
$macro = $pretty_slug($ad['product_macro'] ?? '');

ob_start(); ?>
<style>
  body { font-family: sans-serif; color: #222222; font-size: 12px; }
  h1 { font-size: 22px; margin: 0 0 2px 0; }
  h2 { font-size: 15px; border-bottom: 1px solid #999999; padding-bottom: 3px; margin: 18px 0 8px 0; }
  h3 { font-size: 13px; margin: 12px 0 4px 0; color: #444444; }
  .sub { color: #666666; font-style: italic; margin: 0 0 8px 0; }
  table.meta { width: 100%; border-collapse: collapse; }
  table.meta td { padding: 3px 6px; vertical-align: top; }
  table.meta td.k { width: 130px; color: #555555; font-weight: bold; }
  ul { margin: 2px 0 8px 0; }
  .gal td { padding: 4px; text-align: center; }
  .foot { margin-top: 22px; font-size: 10px; color: #777777; border-top: 1px solid #cccccc; padding-top: 6px; }
</style>

<h1><?php echo $e($ad['title'] ?? 'Advertisement'); ?></h1>
<?php if (trim((string)($ad['subtitle'] ?? '')) !== ''): ?><p class="sub"><?php echo $e($ad['subtitle']); ?></p><?php endif; ?>

<?php $main = $imgTag('original', $ad['image_original'] ?? '', 460); if ($main !== ''): ?>
  <p><?php echo $main; ?></p>
<?php endif; ?>

<table class="meta">
  <tr><td class="k">Author</td><td><?php echo $e($ad['author'] ?? ''); ?></td></tr>
  <tr><td class="k">Type</td><td><?php echo $e($ad['type'] ?? ''); ?></td></tr>
  <tr><td class="k">Condition</td><td><?php echo $e($ad['conditions'] ?? ''); ?></td></tr>
  <tr><td class="k">List price</td><td><?php echo number_format((float)($ad['list_price'] ?? 0), 2); ?> &euro;</td></tr>
  <?php if ($veh   !== ''): ?><tr><td class="k">Vehicle type</td><td><?php echo $e($veh);   ?></td></tr><?php endif; ?>
  <?php if ($macro !== ''): ?><tr><td class="k">Family</td><td><?php echo $e($macro); ?></td></tr><?php endif; ?>
  <?php if ($cats): ?><tr><td class="k">Categories</td><td><?php echo $e(implode(', ', $cats)); ?></td></tr><?php endif; ?>
</table>

<h2>Description</h2>
<p><?php echo nl2br($e($ad['description'] ?? '')); ?></p>

<?php if ($is_premium && $tech): ?>
<h2>Technical specifications</h2>
<?php if (trim((string)($tech['cars'] ?? '0')) !== '' && (string)($tech['cars'] ?? '0') !== '0'): ?>
  <p><strong>Number of cars:</strong> <?php echo $e($tech['cars']); ?></p>
<?php endif; ?>
<?php foreach ($groups as $gname => $fields):
    $items = [];
    foreach ($fields as $col) {
        $v = trim((string)($tech[$col] ?? ''));
        if ($v === '' || $v === '0' || $v === '0 kg') { continue; }
        $items[] = ($v === '1') ? $pretty($col) : ($pretty($col) . ': ' . $v);
    }
    if (!$items) { continue; } ?>
  <h3><?php echo $e($gname); ?></h3>
  <ul><?php foreach ($items as $it): ?><li><?php echo $e($it); ?></li><?php endforeach; ?></ul>
<?php endforeach; ?>
<?php endif; ?>

<?php
$thumbs = [];
foreach ($gallery as $g) { $tg = $imgTag('thumbnail', $g['image_thumbnail'] ?? '', 150); if ($tg !== '') { $thumbs[] = $tg; } }
if ($thumbs): ?>
<h2>Gallery</h2>
<table class="gal"><tr>
<?php foreach ($thumbs as $i => $tg): ?>
  <td><?php echo $tg; ?></td>
  <?php if (($i % 3) === 2): ?></tr><tr><?php endif; ?>
<?php endforeach; ?>
</tr></table>
<?php endif; ?>

<div class="foot">All on Wheel Ltd &mdash; allonwheel.com &mdash; Ref. #<?php echo (int)$id_ads; ?></div>
<?php
$html = ob_get_clean();

if (!PdfHelper::download($html, 'ad-' . $id_ads . '.pdf')) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'PDF generation is not available (mPDF / vendor/autoload.php missing).';
}
