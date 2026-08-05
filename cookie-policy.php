<!DOCTYPE html>
<html lang="<?php echo function_exists('aow_locale') ? htmlspecialchars(aow_locale(), ENT_QUOTES) : 'en'; ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>All on Wheel - Cookie policy</title>
<meta name="keywords" content="All on Wheel cookie policy" />
<meta name="description" content="Cookie policy for All on Wheel" />
<meta name="robots" content="index, follow" />
<meta name="language" content="en" />
<meta name="copyright" content="All on Wheel Ltd" />
<meta name="author" content="All on Wheel Ltd" />
<?php if (function_exists('aow_hreflang_tags')) echo aow_hreflang_tags(); ?>
<?php if (defined('BASE_URL')) echo '<link rel="canonical" href="' . htmlspecialchars(rtrim(BASE_URL, '/') . '/cookie-policy.php', ENT_QUOTES) . '" />'; ?>
<link href="allonwheel_style.css" rel="stylesheet" type="text/css" />
<link rel="icon" href="images/favicon.ico" />
<link rel="stylesheet" type="text/css" href="ddsmoothmenu.css" />
<!--////// CHOOSE ONE OF THE 3 PIROBOX STYLES  \\\\\\\-->
<link href="css_pirobox/white/style.css" media="screen" title="shadow" rel="stylesheet" type="text/css" />
 
<script type="text/javascript" src="js/jquery.min.js" defer></script>
<script type="text/javascript" src="js/piroBox.1_2.js" defer></script>
<script type="text/javascript" src="js/site_init.js" defer></script>
</head>
<body>
<div id="templatemo_wrapper">
  <div id="templatemo_header">
    <?php include 'header.php'; ?>
  </div>
  <div id="content_top">
    <div id="page_title">Cookie policy</div>
    <div class="cleaner"></div>
  </div>
  <div id="main"></div><div id="templatemo_content">
    <div class="post_box">
      <h2>Cookie policy</h2>
      <p>This website uses cookies and similar technologies to operate the service and protect user sessions.</p>

      <table border="0" cellpadding="6" cellspacing="0" class="tbl_collapse" style="width:100%;margin:10px 0">
        <thead><tr class="thead_row">
          <th align="left">Cookie</th><th align="left">Type</th><th align="left">Purpose</th><th align="left">Duration</th><th align="left">Party</th>
        </tr></thead>
        <tbody>
          <tr class="row_sep"><td>PHPSESSID</td><td>Technical (necessary)</td><td>Keeps the login session and the server-side CSRF token</td><td>Session (deleted when the browser closes)</td><td>First-party</td></tr>
          <tr class="row_sep"><td>aow_consent</td><td>Technical (preference)</td><td>Stores your cookie choices so the banner is not shown again</td><td>180 days</td><td>First-party</td></tr>
          <tr class="row_sep"><td>Histats counters</td><td>Analytics</td><td>Anonymous visit and page-view statistics</td><td>Up to 1 year (set by Histats)</td><td>Third-party (histats.com) &mdash; loaded ONLY with your &quot;Analytics&quot; consent</td></tr>
        </tbody>
      </table>

      <h3>Technical cookies</h3>
      <p>Technical cookies are used for login sessions, CSRF protection and account security. These cookies are required for authenticated areas of the website.</p>

      <h3>Analytics and third-party resources</h3>
      <p>All scripts needed to display the site (menu, image lightbox, jQuery) are served locally from this domain and set no tracking cookies.</p>
      <p>For visit statistics we use <strong>Histats</strong> (Histats.com, script loaded from <code>s10.histats.com</code>), which sets its own cookies to count visits and page views. Histats is loaded <strong>only</strong> after you give consent to the "Analytics" category in the cookie banner: if you reject it, or ignore the banner, the script is never requested and no analytics cookie is set.</p>
      <p>You can change or withdraw your choice at any time with the "Cookie preferences" button at the bottom of every page. Each choice is recorded, with its date and version, in our consent log so that it can be proved.</p>
      <p>We do not use advertising or profiling trackers.</p>

      <h3>Managing cookies</h3>
      <p>Users can manage or delete cookies through their browser settings. Blocking technical cookies may prevent login, posting ads, editing company profiles or using protected forms.</p>

      <h3>More information</h3>
      <p>See the <a href="privacy.php">privacy policy</a> for more information about personal data processing.</p>
    </div>
  </div>
<div id="templatemo_sidebar">
<?php include __DIR__ . '/include_sidebar.php'; ?>
</div>
<div class="cleaner"></div>
  <?php include 'footer.php'; ?>
</body>
</html>
