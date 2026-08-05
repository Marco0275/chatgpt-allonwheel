<?php
declare(strict_types=1);

require_once __DIR__ . '/../libs/Publisher.php';

require_once __DIR__ .
    '/../libs/publishers/WordPressPublisher.php';

$publisher = new Publisher();

$wp = new WordPressPublisher();

$publisher->register(
    'wordpress',
    [$wp, 'publish']
);