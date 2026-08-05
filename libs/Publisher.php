<?php
declare(strict_types=1);

require_once __DIR__ . '/ContentGenerator.php';
require_once __DIR__ . '/SeoOptimizer.php';
require_once __DIR__ . '/FaqGenerator.php';
require_once __DIR__ . '/SchemaGenerator.php';
require_once __DIR__ . '/InternalLinkBuilder.php';
require_once __DIR__ . '/RelatedArticles.php';
require_once __DIR__ . '/ImageGenerator.php';
require_once __DIR__ . '/TranslationPublisher.php';
require_once __DIR__ . '/BlogPublisher.php';
require_once __DIR__ . '/WorkflowRepository.php';
require_once __DIR__ . '/Logger.php';

class Publisher
{
    private PDO $db;

    private ContentGenerator $content;

    private SeoOptimizer $seo;

    private FaqGenerator $faq;

    private SchemaGenerator $schema;

    private InternalLinkBuilder $links;

    private RelatedArticles $related;

    private ImageGenerator $images;

    private TranslationPublisher $translations;

    private BlogPublisher $blog;

    private WorkflowRepository $workflow;

    private Logger $logger;

    public function __construct(PDO $db)
    {
        $this->db=$db;

        $this->content=new ContentGenerator($db);

        $this->seo=new SeoOptimizer($db);

        $this->faq=new FaqGenerator($db);

        $this->schema=new SchemaGenerator($db);

        $this->links=new InternalLinkBuilder($db);

        $this->related=new RelatedArticles($db);

        $this->images=new ImageGenerator($db);

        $this->translations=new TranslationPublisher($db);

        $this->blog=new BlogPublisher($db);

        $this->workflow=new WorkflowRepository($db);

        $this->logger=new Logger();
    }

    public function publish(
        int $workflowId
    ): int
    {
        $job=$this->workflow
            ->find($workflowId);

        if(!$job){

            throw new RuntimeException(
                'Workflow not found.'
            );

        }

        $this->workflow->start(
            $workflowId
        );

        try{

            $article=$this->content
                ->generate(
                    $job
                );

            $article=$this->seo
                ->optimize(
                    $article
                );

            $article=$this->faq
                ->generate(
                    $article
                );

            $article=$this->schema
                ->generate(
                    $article
                );

            $article=$this->links
                ->build(
                    $article
                );

            $article=$this->related
                ->attach(
                    $article
                );

            $article=$this->images
                ->generate(
                    $article
                );

            $this->translations
                ->publish(
                    $article
                );

            $blogId=$this->blog
                ->publish(
                    $article
                );

            $this->workflow
                ->complete(
                    $workflowId,
                    $blogId
                );

            $this->logger->info(

                'Workflow completed',

                [

                    'workflow'=>$workflowId,

                    'blog'=>$blogId

                ]

            );

            return $blogId;

        }catch(Throwable $e){

            $this->workflow
                ->fail(

                    $workflowId,

                    $e->getMessage()

                );

            $this->logger->error(

                $e->getMessage(),

                [

                    'workflow'=>$workflowId

                ]

            );

            throw $e;

        }
    }
}