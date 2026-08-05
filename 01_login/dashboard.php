<?php
session_start();

if (isset($_SESSION['session_id'])) {
  header('Location: /index.php');
} else {
  header('Location: /01_login/login_error.php');
}
exit;
?>
