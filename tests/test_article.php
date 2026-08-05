<?php
declare(strict_types=1);

require_once __DIR__.'/../config/bootstrap.php';
require_once __DIR__.'/../libs/AIManager.php';
require_once __DIR__.'/../libs/PromptBuilder.php';
require_once __DIR__.'/../libs/AIResponseParser.php';

use Libs\AIManager;

$ai = new AIManager();

try {

    $article = $ai->generateArticle([

        'title' => 'How to choose a race trailer',

        'category' => 'Trailers',

        'language' => 'EN',

        'keyword' => 'race trailer',

        'secondary_keywords' => 'motorsport trailer,racing trailer',

        'words' => 1200

    ]);

    echo "<pre>";

    print_r($article);

    echo "</pre>";

} catch(Throwable $e){

    echo "<h2>ERROR</h2>";

    echo $e->getMessage();

}