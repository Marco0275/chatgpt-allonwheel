<?php
declare(strict_types=1);

require_once __DIR__.'/../config/bootstrap.php';

require_once __DIR__.'/../libs/AIManager.php';
require_once __DIR__.'/../libs/PromptBuilder.php';
require_once __DIR__.'/../libs/AIResponseParser.php';
require_once __DIR__.'/../libs/EditorialQueue.php';
require_once __DIR__.'/../libs/AILogger.php';
require_once __DIR__.'/../libs/ArticleGenerator.php';

$queue = new EditorialQueue($db);

$item = $queue->getNext();

if($item===null)
{
    die('Queue empty');
}

$generator = new ArticleGenerator($db);

$result = $generator->generate($item);

echo '<pre>';

print_r($result);

echo '</pre>';