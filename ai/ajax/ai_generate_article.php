<?php
declare(strict_types=1);

require_once __DIR__ . '/../ai/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../repositories/ArticleRepository.php';
require_once __DIR__ . '/../libs/PromptEngine.php';
require_once __DIR__ . '/../libs/ContentGenerator.php';
require_once __DIR__ . '/../libs/WorkflowEngine.php';

try {

    $topic = trim($_POST['topic'] ?? '');

    if ($topic === '') {
        throw new RuntimeException('Topic is required.');
    }

    $article = new Article();

    $article->setTitle('');

    $article->setExcerpt('');

    $article->setBody('');

    $article->setStatus('draft');

    $article->setAuthorId($id_user);

    $article->setLanguage(
        trim($_POST['language'] ?? 'English')
    );

    $article->setCategory(
        trim($_POST['category'] ?? '')
    );

    $article->setMeta([

        'topic'      => $topic,

        'keywords'   => trim($_POST['keywords'] ?? ''),

        'audience'   => trim($_POST['audience'] ?? ''),

        'country'    => trim($_POST['country'] ?? ''),

        'tone'       => trim($_POST['tone'] ?? 'Professional'),

        'length'     => trim($_POST['length'] ?? 'Medium'),

        'purpose'    => trim($_POST['purpose'] ?? ''),

        'references' => trim($_POST['references'] ?? '')

    ]);

    $repository = new ArticleRepository($db);

    $id = $repository->create($article);

    $workflow = new WorkflowEngine($db);

    $workflow->generateArticle($id);

    echo json_encode([

        'success' => true,

        'id'      => $id

    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([

        'success' => false,

        'error'   => $e->getMessage()

    ]);

}