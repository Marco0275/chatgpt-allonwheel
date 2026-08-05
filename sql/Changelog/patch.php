<?php

// Simula CLI
$argv = ['script'];

if (isset($_GET['dry-run'])) {
    $argv[] = '--dry-run';
}

if (isset($_GET['verbose'])) {
    $argv[] = '--verbose';
}

require_once 'scripts/patch_site_init.php';