#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/app.php';

require_once __DIR__ . '/../libs/QueueManager.php';
require_once __DIR__ . '/../libs/Scheduler.php';

$queue = new QueueManager($db);

require_once __DIR__ . '/../bootstrap/scheduler.php';

$scheduler->run();