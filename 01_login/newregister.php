<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel Ltd - Register your account</title>
<meta name="keywords" content="All on Wheel Ltd - Register account" />
<meta name="description" content="All on Wheel Ltd - Register your account" />
<meta name="robots" content="index, follow" />
<meta name="revisit-after" content="3" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<link href="../allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="../images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="../ddsmoothmenu.css" />
<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<link href="../css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
 
<script type="text/javascript" src="../js/jquery.min.js" defer></script>
<script type="text/javascript" src="../js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="../js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper"><div id="templatemo_header">
 <?php include ('../header.php'); ?>
</div>
<div id="content_top">
<div id="page_title"><?php te('reg.page_title','Register account'); ?></div>
<div id="search_box">
<form action="<?php echo $base_url; ?>browse.php" method="get">
<input type="text" name="q" size="10" id="searchfield" title="<?php te('search.listings','Search listings'); ?>" placeholder="<?php te('search.placeholder','Search…'); ?>" />
<input type="submit" name="Search" value="" id="searchbutton" title="Search" />
</form>
</div>
<div class="cleaner"></div>
</div>
<div id="main"></div><div id="templatemo_content">
<?php
// ============================================================
// Form di registrazione — rifatto il 27 lug 2026.
//
// Cosa non andava:
//  - impaginazione a <table>: su smartphone i campi finivano fuori schermo
//    (il traffico B2B da mobile e' comunque la meta');
//  - etichette non associate ai campi (niente <label for>): inutilizzabile
//    con uno screen reader, e il tap sull'etichetta non attivava il campo;
//  - nessun consenso a condizioni e privacy: raccolta di dati personali senza
//    base documentata (GDPR art. 6 e 7) su un marketplace B2B soggetto anche
//    al reg. UE 2019/1150;
//  - password con massimo 20 caratteri e nessuna conferma;
//  - pulsante "Reset", che serve solo a cancellare per sbaglio il modulo;
//  - a ogni errore il server rispondeva con una riga di testo nuda e tutti i
//    dati inseriti andavano persi.
//
// Ora: layout lineare, etichette collegate, intento dichiarato (serve a
// portare l'utente al passo giusto dopo la conferma), consensi espliciti e
// separati, campi ripopolati dopo un errore.
// ============================================================
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../libs/antispam.php';

$reg_err = (string)($_SESSION['reg_error'] ?? '');
$reg_old = (array)($_SESSION['reg_old'] ?? []);
unset($_SESSION['reg_error'], $_SESSION['reg_old']);
$old = static function (string $k, string $d = '') use ($reg_old): string {
    return htmlspecialchars((string)($reg_old[$k] ?? $d), ENT_QUOTES, 'UTF-8');
};
$was = static function (string $k) use ($reg_old): bool { return !empty($reg_old[$k]); };

// Intento: puo' arrivare dai richiami sparsi per il sito (?intent=sell).
$intents = ['buy'   => t('reg.intent_buy', 'I am looking for vehicles or equipment'),
            'sell'  => t('reg.intent_sell', 'I want to sell a vehicle or a unit'),
            'build' => t('reg.intent_build', 'I build or convert vehicles (bodybuilder / manufacturer)'),
            'rent'  => t('reg.intent_rent', 'I rent out vehicles or equipment')];
$intent_sel = (string)($reg_old['intent'] ?? ($_GET['intent'] ?? ''));
if (!isset($intents[$intent_sel])) { $intent_sel = ''; }
?>
<h2><?php te('reg.h1','Create your account'); ?></h2>
<p><?php te('reg.sub','Free, and it takes a minute. You will get an email to confirm your address.'); ?></p>

<?php if ($reg_err !== ''): ?>
<div class="post_box form_error" role="alert">
  <p><strong><?php echo htmlspecialchars($reg_err, ENT_QUOTES, 'UTF-8'); ?></strong></p>
</div>
<?php endif; ?>

<div id="contact_form">
<form method="post" action="register.php" enctype="multipart/form-data" class="aow_form">
 <?php echo csrf_generate(); ?>
 <?php echo aow_spam_fields(); ?>
 <?php // Campo trappola per i bot: nascosto, un umano non lo compila mai. ?>
 <div class="hp_field" aria-hidden="true"><label><?php te('reg.hp_company','Company website'); ?><input type="text" name="website" tabindex="-1" autocomplete="off" /></label></div>

 <fieldset>
   <legend><?php te('reg.intent_legend','What brings you here?'); ?></legend>
   <p class="field_hint"><?php te('reg.intent_hint','It only decides where we take you after sign-up. You can do everything with any choice.'); ?></p>
   <?php foreach ($intents as $iv => $ilabel): ?>
   <p><label><input type="radio" name="intent" value="<?php echo $iv; ?>"<?php echo $intent_sel === $iv ? ' checked="checked"' : ''; ?> /> <?php echo htmlspecialchars($ilabel, ENT_QUOTES, 'UTF-8'); ?></label></p>
   <?php endforeach; ?>
 </fieldset>

 <fieldset>
   <legend><?php te('reg.account_legend','Your account'); ?></legend>

   <p class="field">
     <label for="username"><?php te('reg.username','Username'); ?> <span class="req" aria-hidden="true">*</span></label>
     <input type="text" id="username" name="username" maxlength="20" required="required"
            autocomplete="username" pattern="[A-Za-z0-9_]{3,20}" value="<?php echo $old('username'); ?>" />
     <span class="field_hint"><?php te('reg.username_hint','3 to 20 characters: letters, numbers and underscore.'); ?></span>
   </p>

   <p class="field">
     <label for="email"><?php te('reg.email','E-mail'); ?> <span class="req" aria-hidden="true">*</span></label>
     <input type="email" id="email" name="email" maxlength="50" required="required"
            autocomplete="email" value="<?php echo $old('email'); ?>" />
     <span class="field_hint"><?php te('reg.email_hint','We send the confirmation link here. Use your business address.'); ?></span>
   </p>

   <p class="field">
     <label for="phone"><?php te('reg.phone','Phone'); ?></label>
     <input type="tel" id="phone" name="phone" maxlength="30" autocomplete="tel" value="<?php echo $old('phone'); ?>" />
     <span class="field_hint"><?php te('reg.phone_hint','Optional. Shown publicly only if you ask for it below.'); ?></span>
   </p>

   <p class="field">
     <label for="password"><?php te('reg.password','Password'); ?> <span class="req" aria-hidden="true">*</span></label>
     <input type="password" id="password" name="password" required="required"
            minlength="8" maxlength="72" autocomplete="new-password" />
     <span class="field_hint"><?php te('reg.password_hint','At least 8 characters. A passphrase works fine.'); ?></span>
   </p>

   <p class="field">
     <label for="password2"><?php te('reg.password2','Repeat password'); ?> <span class="req" aria-hidden="true">*</span></label>
     <input type="password" id="password2" name="password2" required="required"
            minlength="8" maxlength="72" autocomplete="new-password" />
   </p>

   <p class="field">
     <label for="profile_image"><?php te('reg.image','Profile image'); ?></label>
     <input type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif" />
     <span class="field_hint"><?php te('reg.image_hint','Optional — JPG, PNG or GIF, up to 5 MB.'); ?></span>
   </p>
 </fieldset>

 <fieldset>
   <legend><?php te('reg.roles_legend','Professional roles (optional)'); ?></legend>
   <p class="field_hint"><?php te('reg.roles_hint','If you offer professional services. You can change these later in Account settings → Account roles.'); ?></p>
   <p><label><input type="checkbox" name="role[expert]" value="1"<?php echo $was('role_expert') ? ' checked="checked"' : ''; ?> /> <?php te('reg.role_expert','Expert'); ?></label></p>
   <p><label><input type="checkbox" name="role[project_manager]" value="1"<?php echo $was('role_project_manager') ? ' checked="checked"' : ''; ?> /> <?php te('reg.role_pm','Project manager'); ?></label></p>
   <p><label><input type="checkbox" name="role[consultant]" value="1"<?php echo $was('role_consultant') ? ' checked="checked"' : ''; ?> /> <?php te('reg.role_consultant','Consultant'); ?></label></p>
   <p><label><input type="checkbox" name="public_contact" value="1"<?php echo $was('public_contact') ? ' checked="checked"' : ''; ?> /> <?php te('reg.public_contact','List me in the public professionals directory, showing my email and phone.'); ?></label></p>
 </fieldset>

 <fieldset>
   <legend><?php te('reg.consent_legend','Consent'); ?></legend>
   <?php // Consensi separati: obbligatori quelli necessari al servizio,
         // libero e non preselezionato quello commerciale (GDPR art. 7 c.2 e c.4). ?>
   <p><label><input type="checkbox" name="accept_terms" value="1" required="required"<?php echo $was('accept_terms') ? ' checked="checked"' : ''; ?> />
      <?php printf(
            htmlspecialchars(t('reg.accept_terms','I accept the %s'), ENT_QUOTES, 'UTF-8'),
            '<a href="../Conditions.php" target="_blank" rel="noopener">'
            . htmlspecialchars(t('reg.terms_link','terms and conditions'), ENT_QUOTES, 'UTF-8') . '</a>'
      ); ?> <span class="req" aria-hidden="true">*</span></label></p>
   <p><label><input type="checkbox" name="accept_privacy" value="1" required="required"<?php echo $was('accept_privacy') ? ' checked="checked"' : ''; ?> />
      <?php printf(
            htmlspecialchars(t('reg.accept_privacy','I have read the %s'), ENT_QUOTES, 'UTF-8'),
            '<a href="../privacy.php" target="_blank" rel="noopener">'
            . htmlspecialchars(t('reg.privacy_link','privacy policy'), ENT_QUOTES, 'UTF-8') . '</a>'
      ); ?> <span class="req" aria-hidden="true">*</span></label></p>
   <p><label><input type="checkbox" name="accept_marketing" value="1"<?php echo $was('accept_marketing') ? ' checked="checked"' : ''; ?> />
      <?php te('reg.accept_marketing','Send me occasional updates on new listings and platform features (optional, you can unsubscribe at any time)'); ?></label></p>
 </fieldset>

 <div class="cleaner h10"></div>
 <button type="submit" name="register" id="submit" value="Register" class="more btn_accent"><?php te('reg.submit','Create my account'); ?></button>
 <p class="post_meta"><?php te('reg.already','Already registered?'); ?> <a href="newlogin.php"><?php te('reg.sign_in','Sign in'); ?></a>.</p>
</form>
</div>
<div class="cleaner"></div>
</div> <!-- end of content -->
<div id="templatemo_sidebar">
<?php include __DIR__ . '/../include_sidebar.php'; ?>
</div>
<!-- end of sidebar -->
<div class="cleaner"></div>
<!-- inizia qui il piè di pagina -->
<?php include "../footer.php"; ?>
<!-- finisce qui il piè di pagina -->
<!-- FIX: era /a> (tag di chiusura HTML malformato) -->
</body>
</html>
