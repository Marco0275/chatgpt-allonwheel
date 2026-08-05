<?php
declare(strict_types=1);

require_once __DIR__ . '/../libs/Scheduler.php';

$scheduler = new Scheduler($queue);

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

$scheduler->register(

    'Daily AI News',

    'daily_news',

    'daily',

    [

        'language' => 'it'

    ],

    10

);

$scheduler->register(

    'SEO Optimizer',

    'seo_optimizer',

    'hourly',

    [],

    50

);

$scheduler->register(

    'Retry Failed Jobs',

    'retry_failed',

    'everyMinute',

    [],

    5

);