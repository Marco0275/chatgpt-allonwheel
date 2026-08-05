<?php
// ============================================================
// 06_company/06_40_delete_company.php
// Elimina un'azienda dell'utente autenticato.
// La logica fine (cancellazione gallery, prodotti, servizi, file fisici)
// è delegata a CompanyManager::deleteCompany() che usa FK CASCADE.
// ============================================================

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf.php';
require_once __DIR__ . '/../config/session_helper.php';
require_once __DIR__ . '/../libs/06_company.class.php';

$user_id = require_user_logged_in();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /01_login/my_posts.php');
  exit;
}

// Validazione CSRF
$csrf_token = $_POST['csrf_token'] ?? '';
if (empty($csrf_token) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
  $_SESSION['error_message'] = 'Invalid security token. Please try again.';
  header('Location: /01_login/my_posts.php');
  exit;
}
unset($_SESSION['csrf_token']);

$company_id = isset($_POST['company_id']) ? (int)$_POST['company_id'] : 0;
if ($company_id <= 0) {
  $_SESSION['error_message'] = 'Invalid company ID.';
  header('Location: /01_login/my_posts.php');
  exit;
}

$cm = new CompanyManager($pdo);

// La query DELETE in deleteCompany() ha già la WHERE id = ? AND user_id = ?
// quindi se l'utente non è proprietario non viene eliminato nulla.
if ($cm->deleteCompany($company_id, $user_id)) {
  $_SESSION['success_message'] = 'Company deleted successfully.';
} else {
  $_SESSION['error_message'] = 'Error deleting company, or you do not have permission.';
}

header('Location: /01_login/my_posts.php');
exit;
?>
