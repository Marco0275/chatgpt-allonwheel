<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../repositories/PromptRepository.php';

require_once __DIR__ . '/PromptEngine.php';
require_once __DIR__ . '/OpenAIClient.php';

class FaqGenerator
{
    private PromptEngine $promptEngine;

    private OpenAIClient $client;

    public function __construct(PDO $db)
    {
        $this->promptEngine = new PromptEngine(
            new PromptRepository($db)
        );

        $this->client = new OpenAIClient($db);
    }

    public function generate(
        Article $article
    ): Article {

        $prompt = $this->promptEngine->render(

            'article_faq',

            [

                'title' =>
                    $article->getTitle(),

                'excerpt' =>
                    $article->getExcerpt(),

                'body' =>
                    $article->getBody(),

                'language' =>
                    $article->getLanguage(),

                'category' =>
                    $article->getCategory()

            ]

        );

        $response = $this->client->chat(

            $prompt->getPrompt(),

            [

                'model' =>
                    $prompt->getModel(),

                'temperature' =>
                    $prompt->getTemperature(),

                'max_tokens' =>
                    $prompt->getMaxTokens()

            ]

        );

        $json = json_decode(
            $response,
            true
        );

        if (!is_array($json)) {

            throw new RuntimeException(
                'Invalid FAQ response.'
            );

        }

        $faq = [];

        foreach (

            $json['faq'] ?? []

            as $item

        ) {

            if (

                empty($item['question']) ||

                empty($item['answer'])

            ) {

                continue;

            }

            $faq[] = [

                'question' => trim(

                    (string)$item['question']

                ),

                'answer' => trim(

                    (string)$item['answer']

                )

            ];

        }

        $article->setFaq(
            $faq
        );

        return $article;

    }
}