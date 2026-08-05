<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/WorkflowJob.php';

require_once __DIR__ . '/../repositories/ArticleRepository.php';

require_once __DIR__ . '/WorkflowEngine.php';

class WorkflowWorker
{
    private PDO $db;

    private WorkflowEngine $engine;

    private ArticleRepository $articles;

    public function __construct(PDO $db)
    {
        $this->db = $db;

        $this->engine = new WorkflowEngine($db);

        $this->articles = new ArticleRepository($db);
    }

    public function execute(
        WorkflowJob $job
    ): void {

        $article = $this->articles->findById(
            $job->getArticleId()
        );

        if ($article === null) {

            throw new RuntimeException(
                'Article not found.'
            );

        }

        switch ($job->getType()) {

            case 'generate':

                $this->engine->execute(
                    $article
                );

            break;

            case 'generate_publish':

                $this->engine->execute(

                    $article,

                    [

                        'auto_publish' => true

                    ]

                );

            break;

            case 'translate':

                $payload = $job->getPayload();

                $this->engine->execute(

                    $article,

                    [

                        'translate' =>

                        $payload['language']
                        ?? 'en'

                    ]

                );

            break;

            case 'translate_publish':

                $payload = $job->getPayload();

                $this->engine->execute(

                    $article,

                    [

                        'translate' =>

                        $payload['language']
                        ?? 'en',

                        'auto_publish' => true

                    ]

                );

            break;

            case 'seo':

                $this->engine->executeById(

                    $article->getId(),

                    [

                        'seo_only' => true

                    ]

                );

            break;

            case 'faq':

                $this->engine->executeById(

                    $article->getId(),

                    [

                        'faq_only' => true

                    ]

                );

            break;

            case 'schema':

                $this->engine->executeById(

                    $article->getId(),

                    [

                        'schema_only' => true

                    ]

                );

            break;

            case 'image':

                $this->engine->executeById(

                    $article->getId(),

                    [

                        'image_only' => true

                    ]

                );

            break;

            case 'publish':

                $this->engine->execute(

                    $article,

                    [

                        'auto_publish' => true

                    ]

                );

            break;

            default:

                throw new RuntimeException(
                    'Unknown workflow job: ' .
                    $job->getType()
                );

        }

    }

}