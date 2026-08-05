<?php
session_start();
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/libs/antispam.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/form_consent.php';

// Sempre UTF-8
header('Content-Type: text/html; charset=UTF-8');

// Pagine consentite (protezione Open Redirect)
$allowed_pages = [
    'retry'   => '../contact-retry.php',
    'success' => '../contact-success.php',
];

// Accetta solo richieste POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['submit'])) {
    header('Location: ../contact.php');
    exit;
}

// Verifica token CSRF (anti Cross-Site Request Forgery)
csrf_verify();

// Tempo caricamento form
$momento_del_caricamento = (int)($_POST['aow_ts'] ?? $_POST['momento_del_caricamento'] ?? 0);
$tempoDiCompletamento = time() - $momento_del_caricamento;

// Email destinatario
$miamail = 'info@allonwheel.com';

// Recupero dati form
$username = trim($_POST['author'] ?? '');
$email    = trim($_POST['email'] ?? '');
$object   = trim($_POST['object'] ?? '');
$msg_raw  = trim($_POST['msg'] ?? '');

// Sanitizzazione output HTML
$username_safe = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
$email_safe    = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$object_safe   = htmlspecialchars($object, ENT_QUOTES, 'UTF-8');
$msg_safe      = nl2br(htmlspecialchars($msg_raw, ENT_QUOTES, 'UTF-8'));

// Validazione email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $allowed_pages['retry']);
    exit;
}

// Calcolo probabilità spam
// Antispam centralizzato (honeypot + time-trap firmata + filtro link/parole).
if (aow_is_spam($msg_raw)) {
    header('Location: ' . $allowed_pages['retry']);
    exit;
}
// P0.3: consenso privacy obbligatorio + registro prova.
if (!aow_privacy_consent_ok()) {
    header('Location: ' . $allowed_pages['retry']);
    exit;
}
aow_log_form_consent($pdo, 'contact');

// Corpo email HTML
$messaggio = '
<html>
<head>
</head>
<body>
<table width="100%" border="0">
  <tbody>
    <tr>
      <td><img src="https://www.allonwheel.com/images/aow.png"
         alt="All on Wheel Ltd"
         style="max-width:600px; width:10%;" loading="lazy" decoding="async"></td>
      <td>E-mail from All on Wheel contact form</td>
    </tr>
  </tbody>
</table>
    <br><br>

    <p><strong>Author:</strong> ' . $username_safe . '</p>
    <p><strong>Email:</strong> ' . $email_safe . '</p>
    <p><strong>Oggetto:</strong> ' . $object_safe . '</p>
    <p><strong>Messaggio:</strong><br>' . $msg_safe . '</p>
    <p><strong>Tempo di completamento:</strong> ' . (int)$tempoDiCompletamento . ' secondi</p>
</body>
</html>';

// Headers email
$headers  = "From: {$miamail}\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

// Invio email
$mail_sent = mail(
    $miamail,
    'Contact us: ' . $object_safe,
    $messaggio,
    $headers
);

// Controllo invio
if (!$mail_sent) {
    header('Location: ' . $allowed_pages['retry']);
    exit;
}

// Redirect finale
header('Location: ' . $allowed_pages['success']);
exit;
?>