<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../libs/CronController.php';

set_time_limit(0);

ignore_user_abort(true);

$controller = new CronController($pdo);

$controller->run();