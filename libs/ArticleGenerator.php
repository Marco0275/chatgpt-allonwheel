<?php
declare(strict_types=1);

use Libs\AIManager;

class ArticleGenerator
{

    private AIManager $ai;

    private EditorialQueue $queue;

    private AILogger $logger;

    public function __construct(DB $db)
    {
        $this->ai = new AIManager();

        $this->queue = new EditorialQueue($db);

        $this->logger = new AILogger($db);
    }

    public function generate(array $job): array
    {

        $start = microtime(true);

        try
        {

            $prompt = PromptBuilder::buildArticle($job);

            $response = $this->ai->generate($prompt);

            $text = $this->ai->getResponseText($response);

            $article = AIResponseParser::parse($text);

            $elapsed = (int)((microtime(true)-$start)*1000);

            $this->logger->success(

                (int)$job['id'],

                'GENERATE',

                'Generated in '.$elapsed.' ms'

            );

            return $article;

        }
        catch(Throwable $e)
        {

            $this->queue->markError(

                (int)$job['id'],

                $e->getMessage()

            );

            $this->logger->error(

                (int)$job['id'],

                'GENERATE',

                $e->getMessage()

            );

            throw $e;

        }

    }

}