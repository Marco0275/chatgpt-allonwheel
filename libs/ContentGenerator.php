<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Article.php';

require_once __DIR__ . '/AIProviderInterface.php';
require_once __DIR__ . '/ModelManager.php';
require_once __DIR__ . '/PromptEngine.php';

class ContentGenerator
{
    private PDO $db;

    private AIProviderInterface $client;

    private PromptEngine $promptEngine;

    private ModelManager $models;

    public function __construct(PDO $db)
    {
        $this->db = $db;

        $this->models = new ModelManager($db);

        $this->client = $this->models->provider();

        $this->promptEngine = new PromptEngine($db);
    }

    public function generate(Article $article): Article
    {
        $prompt = $this->promptEngine->buildArticlePrompt($article);

        $response = $this->client->chat(
            $prompt,
            $this->models->options('generate')
        );

        return $this->hydrate($article, $response);
    }

    public function rewrite(Article $article): Article
    {
        $prompt = $this->promptEngine->buildRewritePrompt($article);

        $response = $this->client->chat(
            $prompt,
            $this->models->options('rewrite')
        );

        return $this->hydrate($article, $response);
    }

    public function improve(Article $article): Article
    {
        $prompt = $this->promptEngine->buildImprovePrompt($article);

        $response = $this->client->chat(
            $prompt,
            $this->models->options('improve')
        );

        return $this->hydrate($article, $response);
    }

    public function seoOptimize(Article $article): Article
    {
        $prompt = $this->promptEngine->buildSeoPrompt($article);

        $response = $this->client->chat(
            $prompt,
            $this->models->options('seo')
        );

        return $this->hydrate($article, $response);
    }

    public function translate(
        Article $article,
        ?string $language = null
    ): Article {

        $prompt = $this->promptEngine->buildTranslationPrompt(
            $article,
            $language ?? $article->getLanguage()
        );

        $response = $this->client->chat(
            $prompt,
            $this->models->options('translate')
        );

        return $this->hydrate($article, $response);
    }

    public function excerpt(Article $article): Article
    {
        $prompt = $this->promptEngine->buildExcerptPrompt($article);

        $response = $this->client->chat(
            $prompt,
            $this->models->options('excerpt')
        );

        $data = $this->decode($response);

        if (!empty($data['excerpt'])) {
            $article->setExcerpt(
                trim($data['excerpt'])
            );
        }

        return $article;
    }

    public function title(Article $article): Article
    {
        $prompt = $this->promptEngine->buildTitlePrompt($article);

        $response = $this->client->chat(
            $prompt,
            $this->models->options('title')
        );

        $data = $this->decode($response);

        if (!empty($data['title'])) {
            $article->setTitle(
                trim($data['title'])
            );
        }

        return $article;
    }

    public function tags(Article $article): array
    {
        $prompt = $this->promptEngine->buildTagsPrompt($article);

        $response = $this->client->chat(
            $prompt,
            $this->models->options('tags')
        );

        $data = $this->decode($response);

        return $data['tags'] ?? [];
    }

    public function faq(Article $article): array
    {
        $prompt = $this->promptEngine->buildFaqPrompt($article);

        $response = $this->client->chat(
            $prompt,
            $this->models->options('faq')
        );

        $data = $this->decode($response);

        return $data['faq'] ?? [];
    }

    public function schema(Article $article): array
    {
        $prompt = $this->promptEngine->buildSchemaPrompt($article);

        $response = $this->client->chat(
            $prompt,
            $this->models->options('schema')
        );

        $data = $this->decode($response);

        return $data['schema'] ?? [];
    }

    public function social(
        Article $article,
        string $platform
    ): string {

        $prompt = $this->promptEngine->buildSocialPrompt(
            $article,
            $platform
        );

        return $this->client->chat(
            $prompt,
            $this->models->options('social')
        );
    }

    private function hydrate(
        Article $article,
        string $response
    ): Article {

        $data = $this->decode($response);

        if (!empty($data['title'])) {
            $article->setTitle(
                trim($data['title'])
            );
        }

        if (!empty($data['excerpt'])) {
            $article->setExcerpt(
                trim($data['excerpt'])
            );
        }

        if (!empty($data['body'])) {
            $article->setBody(
                trim($data['body'])
            );
        }

        return $article;
    }

    private function decode(
        string $response
    ): array {

        $json = json_decode(
            $response,
            true
        );

        if (!is_array($json)) {
            return [];
        }

        return $json;
    }
}