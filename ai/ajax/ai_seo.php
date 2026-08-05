<?php
declare(strict_types=1);

require_once __DIR__ . '/../ai/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../repositories/ArticleRepository.php';
require_once __DIR__ . '/../libs/ContentGenerator.php';

try {

    $request = json_decode(
        file_get_contents('php://input'),
        true
    );

    $repository = new ArticleRepository($db);

    $article = $repository->findById(
        (int)$request['id']
    );

    $article->setTitle($request['title']);

    $article->setExcerpt($request['excerpt']);

    $article->setBody($request['body']);

    $generator = new ContentGenerator($db);

    $article = $generator->seoOptimize($article);

    $repository->update($article);

    echo json_encode([

        'success' => true,

        'title' => $article->getTitle(),

        'excerpt' => $article->getExcerpt(),

        'body' => $article->getBody()

    ]);

} catch(Throwable $e) {

    http_response_code(500);

    echo json_encode([

        'success' => false,

        'error' => $e->getMessage()

    ]);

}