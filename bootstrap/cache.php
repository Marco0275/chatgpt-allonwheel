<?php
declare(strict_types=1);

require_once __DIR__ . '/../libs/Cache.php';

$cache = new Cache(
    $config['cache_path']
);