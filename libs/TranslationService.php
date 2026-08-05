<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../repositories/PromptRepository.php';

require_once __DIR__ . '/PromptEngine.php';
require_once __DIR__ . '/OpenAIClient.php';

class TranslationService
{
    private PDO $db;

    private PromptEngine $promptEngine;

    private OpenAIClient $client;

    public function __construct(PDO $db)
    {
        $this->db = $db;

        $this->promptEngine = new PromptEngine(
            new PromptRepository($db)
        );

        $this->client = new OpenAIClient($db);
    }

    public function translate(
        Article $article,
        string $targetLanguage
    ): Article {

        if (
            strtolower($article->getLanguage()) ===
            strtolower($targetLanguage)
        ) {

            return $article;

        }

        $prompt = $this->promptEngine->render(

            'article_translation',

            [

                'source_language' =>
                    $article->getLanguage(),

                'target_language' =>
                    $targetLanguage,

                'title' =>
                    $article->getTitle(),

                'excerpt' =>
                    $article->getExcerpt(),

                'body' =>
                    $article->getBody(),

                'seo_title' =>
                    $article->getSeoTitle(),

                'seo_description' =>
                    $article->getSeoDescription(),

                'slug' =>
                    $article->getSlug(),

                'faq' =>
                    json_encode(
                        $article->getFaq(),
                        JSON_UNESCAPED_UNICODE
                    ),

                'schema' =>
                    $article->getSchema(),

                'image_alt' =>
                    $article->getImageAlt(),

                'image_title' =>
                    $article->getImageTitle(),

                'image_caption' =>
                    $article->getImageCaption()

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
                'Invalid translation response.'
            );

        }

        $translated = clone $article;

        $translated->setId(0);

        $translated->setParentId(
            $article->getId()
        );

        $translated->setLanguage(
            $targetLanguage
        );

        $translated->setTitle(

            trim(
                (string)($json['title'] ?? '')
            )

        );

        $translated->setSlug(

            trim(
                (string)($json['slug'] ?? '')
            )

        );

        $translated->setExcerpt(

            trim(
                (string)($json['excerpt'] ?? '')
            )

        );

        $translated->setBody(

            trim(
                (string)($json['body'] ?? '')
            )

        );

        $translated->setSeoTitle(

            trim(
                (string)($json['seo_title'] ?? '')
            )

        );

        $translated->setSeoDescription(

            trim(
                (string)($json['seo_description'] ?? '')
            )

        );

        $translated->setFaq(

            (array)(
                $json['faq']
                ?? []
            )

        );

        $translated->setSchema(

            (string)(
                $json['schema']
                ?? ''
            )

        );

        $translated->setImageAlt(

            trim(
                (string)(
                    $json['image_alt']
                    ?? ''
                )
            )

        );

        $translated->setImageTitle(

            trim(
                (string)(
                    $json['image_title']
                    ?? ''
                )
            )

        );

        $translated->setImageCaption(

            trim(
                (string)(
                    $json['image_caption']
                    ?? ''
                )
            )

        );

        $translated->setStatus(
            'draft'
        );

        return $translated;

    }

    public function translateMany(
        Article $article,
        array $languages
    ): array {

        $articles = [];

        foreach ($languages as $language) {

            $articles[] = $this->translate(
                $article,
                $language
            );

        }

        return $articles;

    }

}