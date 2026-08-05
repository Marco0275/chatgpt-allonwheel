<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

require_once __DIR__ . '/../libs/WorkflowEngine.php';

$engine = new WorkflowEngine($db);

$engine->run();