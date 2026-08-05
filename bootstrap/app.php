<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI
 * Bootstrap Application
 * ------------------------------------------------------------
 */

define('AOW_ROOT', dirname(__DIR__));

require_once AOW_ROOT . '/config/config.php';

require_once AOW_ROOT . '/libs/Database.php';
require_once AOW_ROOT . '/libs/Logger.php';

/*
|--------------------------------------------------------------------------
| Timezone
|--------------------------------------------------------------------------
*/

date_default_timezone_set(
    $config['timezone'] ?? 'Europe/Rome'
);

/*
|--------------------------------------------------------------------------
| Error Reporting
|--------------------------------------------------------------------------
*/

if (($config['debug'] ?? false) === true) {

    ini_set('display_errors', '1');
    error_reporting(E_ALL);

} else {

    ini_set('display_errors', '0');
    error_reporting(E_ALL);

}

/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/

Database::initialize($database);

$db = Database::instance()->pdo();

/*
|--------------------------------------------------------------------------
| Logger
|--------------------------------------------------------------------------
*/

$logger = new Logger(
    $config['log_path']
);

/*
|--------------------------------------------------------------------------
| Shared Services
|--------------------------------------------------------------------------
*/

$GLOBALS['db'] = $db;
$GLOBALS['logger'] = $logger;
$GLOBALS['config'] = $config;

/*
|--------------------------------------------------------------------------
| AI Services (autoload)
|--------------------------------------------------------------------------
*/

$aiBootstrap = AOW_ROOT . '/ai/bootstrap.php';

if (file_exists($aiBootstrap)) {
    require_once $aiBootstrap;
}

/*
|--------------------------------------------------------------------------
| Workflow bootstrap
|--------------------------------------------------------------------------
*/

$workflowBootstrap = AOW_ROOT . '/ai/workflow/bootstrap.php';

if (file_exists($workflowBootstrap)) {
    require_once $workflowBootstrap;
}