<?php
// footer.php — Piè di pagina globale
$footer_base = '';
$footer_script = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
foreach (['00_first', '01_login', '02_free_ads', '03_ads', '06_company', '_admin', 'shared'] as $folder) {
    if (strpos($footer_script, '/' . $folder . '/') !== false) {
        $footer_base = '../';
        break;
    }
}

if (!function_exists('t')) { require_once __DIR__ . '/config/i18n.php'; }
?>
<!-- Footer -->
<div id="templatemo_bottom"><div class="col_4 col_f">
  <h5><?php te('footer.browse','Browse'); ?></h5>
  <ul class="footer_link">
    <li><a href="<?php echo $footer_base; ?>browse.php"><?php te('nav.all_listings','All listings'); ?></a></li>
    <li><a href="<?php echo $footer_base; ?>road_vehicles.php"><?php te('b2b.road','Road vehicles'); ?></a></li>
    <li><a href="<?php echo $footer_base; ?>special_vehicles.php"><?php te('b2b.special','Special vehicles'); ?></a></li>
    <li><a href="<?php echo $footer_base; ?>shelter_container.php"><?php te('macro.shelter','Shelter & Container'); ?></a></li>
    <li><a href="<?php echo $footer_base; ?>04_request_offer/04_request_offer.php"><?php te('nav.request_quote','Request a quotation'); ?></a></li>
  </ul>
</div>
<div class="col_4">
  <h5><?php te('footer.marketplace','Marketplace'); ?></h5>
  <ul class="footer_link">
    <li><a href="<?php echo $footer_base; ?>race_trailers.php"><?php te('macro.race_trailer','Race trailers'); ?></a></li>
    <li><a href="<?php echo $footer_base; ?>hospitality.php"><?php te('macro.hospitality','Hospitality'); ?></a></li>
    <li><a href="<?php echo $footer_base; ?>06_company/06_30_company_directory.php"><?php te('nav.directory','Supplier directory'); ?></a></li>
    <li><a href="<?php echo $footer_base; ?>portfolio.php"><?php te('nav.portfolio','Portfolio'); ?></a></li>
    <li><a href="<?php echo $footer_base; ?>blog.php"><?php te('nav.blog','Blog'); ?></a></li>
  </ul>
</div>
<div class="col_4">
  <h5><?php te('footer.useful','Useful links'); ?></h5>
  <ul class="footer_link">
    <li><a href="<?php echo $footer_base; ?>about.php"><?php te('nav.about_us','About us'); ?></a></li>
    <li><a href="<?php echo $footer_base; ?>what_we_do.php"><?php te('nav.what_we_do','What we do'); ?></a></li>
    <li><a href="<?php echo $footer_base; ?>FAQ.php"><?php te('nav.faq','F.A.Q.'); ?></a></li>
    <li><a href="<?php echo $footer_base; ?>Conditions.php"><?php te('nav.conditions','Conditions & rules'); ?></a></li>
    <li><a href="<?php echo $footer_base; ?>contact.php"><?php te('nav.contact','Contact us'); ?></a></li>
  </ul>
</div>
<div class="col_4 col_l rmc">
  <h5><?php te('footer.follow','Follow us'); ?></h5>
  <ul class="footer_link">
    <li><a href="https://www.facebook.com/profile.php?id=61590545821976" class="facebook social">Facebook</a></li>
    <li><a href="https://www.instagram.com/allonwheel/" class="instagram social">Instagram</a></li>
    <li><a href="#" class="linkedin social">LinkedIn</a></li>
    <li><a href="#" class="youtube social">YouTube</a></li>
    <li><a href="#" class="vimeo social">Vimeo</a></li>
  </ul>
</div>
<div class="cleaner"></div>
</div>
<div id="templatemo_footer">
  Copyright &copy; <?php echo date('Y'); ?> | <a href="https://www.allonwheel.com">All on Wheel Ltd.</a>
  | <a href="<?php echo $footer_base; ?>legal.php"><?php te('footer.legal','Legal &amp; seller info'); ?></a>
  | <a href="<?php echo $footer_base; ?>privacy.php"><?php te('footer.privacy','Privacy policy'); ?></a>
  | <a href="<?php echo $footer_base; ?>cookie-policy.php"><?php te('footer.cookie','Cookie policy'); ?></a>
  | <a href="#" data-aow-cc-open><?php te('footer.cookie_prefs','Cookie preferences'); ?></a>
  | <?php te('footer.lang','Language'); ?>: <?php echo aow_lang_switcher(); ?>
  <div style="margin-top:6px;font-size:11px;line-height:1.5;opacity:.85">
    All on Wheel Ltd &mdash; Registered office: 4100 Highway One, Delaware (USA) &mdash; Company reg. no. 4355806 &mdash; <a href="mailto:webmaster@allonwheel.com">webmaster@allonwheel.com</a>
  </div>
</div>
</div>
<!-- End footer -->
   <!-- Histats.com  START  (aync)-->
<script type="text/javascript">var _Hasync= _Hasync|| [];
_Hasync.push(['Histats.start', '1,4703110,4,0,0,0,00010000']);
_Hasync.push(['Histats.fasi', '1']);
_Hasync.push(['Histats.track_hits', '']);
(function() {
var hs = document.createElement('script'); hs.type = 'text/javascript'; hs.async = true;
hs.src = ('//s10.histats.com/js15_as.js');
(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(hs);
})();</script>
<noscript><a href="/" target="_blank"><img  src="//sstatic1.histats.com/0.gif?4703110&101" alt="contatore accessi" border="0"></a></noscript>
<!-- Histats.com  END  -->
<?php /* Cookie banner sito-wide: incluso una sola volta dal footer */ include __DIR__ . '/cookie_banner/cookie_banner.php'; ?>