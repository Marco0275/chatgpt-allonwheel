<?php
session_start();

// 1. Svuota tutte le variabili di sessione
session_unset();

// 2. Elimina il cookie di sessione dal browser dell'utente
if (ini_get('session.use_cookies')) {
  $params = session_get_cookie_params();
  setcookie(
    session_name(),
    '',
    time() - 3600,
    $params['path'],
    $params['domain'],
    $params['secure'],
    $params['httponly']
  );
}

// 3. Distruggi la sessione lato server
session_destroy();

// Redirect alla home
header('Location: ../index.php');
exit;
?>
