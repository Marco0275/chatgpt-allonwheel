<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Article.php';

require_once __DIR__ . '/../repositories/ArticleRepository.php';
require_once __DIR__ . '/../repositories/PromptRepository.php';

require_once __DIR__ . '/ContentGenerator.php';
require_once __DIR__ . '/SeoGenerator.php';
require_once __DIR__ . '/FaqGenerator.php';
require_once __DIR__ . '/SchemaGenerator.php';
require_once __DIR__ . '/TranslationService.php';
require_once __DIR__ . '/ImagePromptGenerator.php';
require_once __DIR__ . '/ArticlePublisher.php';
require_once __DIR__ . '/Logger.php';

class WorkflowEngine
{
    private PDO $db;

    private Logger $logger;

    private ArticleRepository $articles;

    private PromptRepository $prompts;

    private ContentGenerator $contentGenerator;

    private SeoGenerator $seoGenerator;

    private FaqGenerator $faqGenerator;

    private SchemaGenerator $schemaGenerator;

    private TranslationService $translationService;

    private ImagePromptGenerator $imagePromptGenerator;

    private ArticlePublisher $publisher;

    public function __construct(PDO $db)
    {
        $this->db = $db;

        $this->logger = new Logger();

        $this->articles = new ArticleRepository($db);

        $this->prompts = new PromptRepository($db);

        $this->contentGenerator = new ContentGenerator($db);

        $this->seoGenerator = new SeoGenerator($db);

        $this->faqGenerator = new FaqGenerator($db);

        $this->schemaGenerator = new SchemaGenerator($db);

        $this->translationService = new TranslationService($db);

        $this->imagePromptGenerator = new ImagePromptGenerator($db);

        $this->publisher = new ArticlePublisher($db);
    }

    public function execute(
        Article $article,
        array $options = []
    ): Article {

        $this->logger->info(
            'Workflow started',
            [
                'article' => $article->getId()
            ]
        );

        $article->setStatus('processing');

        $this->articles->save($article);

        if (empty($article->getBody())) {

            $article = $this->contentGenerator->generate(
                $article
            );

        }

        if (
            empty($article->getSeoTitle()) ||
            empty($article->getSeoDescription())
        ) {

            $article = $this->seoGenerator->generate(
                $article
            );

        }

        if (
            empty($article->getFaq())
        ) {

            $article = $this->faqGenerator->generate(
                $article
            );

        }

        if (
            empty($article->getSchema())
        ) {

            $article = $this->schemaGenerator->generate(
                $article
            );

        }

        if (
            !empty($options['translate'])
        ) {

            $article = $this->translationService->translate(
                $article,
                $options['translate']
            );

        }

        if (
            empty($article->getImagePrompt())
        ) {

            $article = $this->imagePromptGenerator->generate(
                $article
            );

        }

        $article->setStatus('review');

        $this->articles->save($article);

        if (
            !empty($options['auto_publish'])
        ) {

            $this->publisher->publish(
                $article
            );

        }

        $this->logger->info(
            'Workflow completed',
            [
                'article' => $article->getId()
            ]
        );

        return $article;
    }

    public function executeById(
        int $id,
        array $options = []
    ): ?Article {

        $article = $this->articles->findById(
            $id
        );

        if ($article === null) {

            return null;

        }

        return $this->execute(
            $article,
            $options
        );
    }
}