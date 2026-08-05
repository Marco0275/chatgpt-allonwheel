<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../repositories/PromptRepository.php';

require_once __DIR__ . '/PromptEngine.php';
require_once __DIR__ . '/OpenAIClient.php';

class SchemaGenerator
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

            'article_schema',

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
                    $article->getCategory(),

                'faq' =>
                    json_encode(
                        $article->getFaq(),
                        JSON_UNESCAPED_UNICODE
                    )

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
                'Invalid Schema.org response.'
            );

        }

        if (
            empty($json['schema'])
        ) {

            throw new RuntimeException(
                'Schema.org not generated.'
            );

        }

        $article->setSchema(

            json_encode(

                $json['schema'],

                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE

            )

        );

        return $article;

    }
}