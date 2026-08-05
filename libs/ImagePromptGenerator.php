<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../repositories/PromptRepository.php';

require_once __DIR__ . '/PromptEngine.php';
require_once __DIR__ . '/OpenAIClient.php';

class ImagePromptGenerator
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

            'article_image',

            [

                'title' =>
                    $article->getTitle(),

                'excerpt' =>
                    $article->getExcerpt(),

                'body' =>
                    $article->getBody(),

                'category' =>
                    $article->getCategory(),

                'language' =>
                    $article->getLanguage(),

                'keywords' =>
                    implode(
                        ', ',
                        $article->getKeywords()
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
                'Invalid image prompt response.'
            );

        }

        $article->setImagePrompt(

            trim(

                (string)(
                    $json['image_prompt']
                    ?? ''
                )

            )

        );

        $article->setImageAlt(

            trim(

                (string)(
                    $json['alt']
                    ?? ''
                )

            )

        );

        $article->setImageTitle(

            trim(

                (string)(
                    $json['title']
                    ?? ''
                )

            )

        );

        $article->setImageCaption(

            trim(

                (string)(
                    $json['caption']
                    ?? ''
                )

            )

        );

        $article->setImageKeywords(

            (array)(
                $json['keywords']
                ?? []
            )

        );

        return $article;

    }

    public function regenerate(
        Article $article
    ): Article {

        return $this->generate(
            $article
        );

    }

}