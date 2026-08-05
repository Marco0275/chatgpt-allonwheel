<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../repositories/PromptRepository.php';
require_once __DIR__ . '/PromptEngine.php';
require_once __DIR__ . '/OpenAIClient.php';

class SeoGenerator
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

            'article_seo',

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
                'Invalid SEO response.'
            );

        }

        $article->setSeoTitle(
            trim(
                (string)($json['seo_title'] ?? '')
            )
        );

        $article->setSeoDescription(
            trim(
                (string)($json['seo_description'] ?? '')
            )
        );

        $article->setKeywords(

            (array)(
                $json['keywords']
                ?? []
            )

        );

        $article->setCanonical(

            trim(
                (string)(
                    $json['canonical']
                    ?? ''
                )
            )

        );

        return $article;
    }
}