<?php
declare(strict_types=1);

require_once __DIR__ . '/../libs/PluginManager.php';

$plugins = new PluginManager();

$plugins->load(
    __DIR__ . '/../plugins'
);