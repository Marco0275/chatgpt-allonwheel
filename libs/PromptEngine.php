<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../repositories/PromptRepository.php';

class PromptEngine
{
    private PromptRepository $repository;

    public function __construct(PDO $db)
    {
        $this->repository = new PromptRepository($db);
    }

    public function buildArticlePrompt(
        Article $article
    ): string {

        return $this->render(
            'article_generate',
            $this->variables($article)
        );

    }

    public function buildRewritePrompt(
        Article $article
    ): string {

        return $this->render(
            'article_rewrite',
            $this->variables($article)
        );

    }

    public function buildImprovePrompt(
        Article $article
    ): string {

        return $this->render(
            'article_improve',
            $this->variables($article)
        );

    }

    public function buildSeoPrompt(
        Article $article
    ): string {

        return $this->render(
            'article_seo',
            $this->variables($article)
        );

    }

    public function buildTranslationPrompt(
        Article $article,
        string $language
    ): string {

        $vars = $this->variables($article);

        $vars['language'] = $language;

        return $this->render(
            'article_translate',
            $vars
        );

    }

    public function buildTitlePrompt(
        Article $article
    ): string {

        return $this->render(
            'article_title',
            $this->variables($article)
        );

    }

    public function buildExcerptPrompt(
        Article $article
    ): string {

        return $this->render(
            'article_excerpt',
            $this->variables($article)
        );

    }

    public function buildTagsPrompt(
        Article $article
    ): string {

        return $this->render(
            'article_tags',
            $this->variables($article)
        );

    }

    public function buildFaqPrompt(
        Article $article
    ): string {

        return $this->render(
            'article_faq',
            $this->variables($article)
        );

    }

    public function buildSchemaPrompt(
        Article $article
    ): string {

        return $this->render(
            'article_schema',
            $this->variables($article)
        );

    }

    public function buildSocialPrompt(
        Article $article,
        string $platform
    ): string {

        $vars = $this->variables($article);

        $vars['platform'] = $platform;

        return $this->render(
            'article_social',
            $vars
        );

    }

    private function render(
        string $code,
        array $variables
    ): string {

        $template = $this->repository
            ->findActiveByCode($code);

        if ($template === null) {

            throw new RuntimeException(
                'Prompt template not found: ' . $code
            );

        }

        $prompt = $template->getContent();

        foreach ($variables as $key => $value) {

            if (is_array($value)) {

                $value = implode(', ', $value);

            }

            $prompt = str_replace(

                '{{' . $key . '}}',

                (string)$value,

                $prompt

            );

        }

        return $prompt;

    }

    private function variables(
        Article $article
    ): array {

        $meta = $article->getMeta();

        return [

            'title'       => $article->getTitle(),

            'excerpt'     => $article->getExcerpt(),

            'body'        => $article->getBody(),

            'language'    => $article->getLanguage(),

            'category'    => $article->getCategory(),

            'status'      => $article->getStatus(),

            'topic'       => $meta['topic'] ?? '',

            'keywords'    => $meta['keywords'] ?? '',

            'audience'    => $meta['audience'] ?? '',

            'country'     => $meta['country'] ?? '',

            'tone'        => $meta['tone'] ?? '',

            'length'      => $meta['length'] ?? '',

            'purpose'     => $meta['purpose'] ?? '',

            'references'  => $meta['references'] ?? '',

            'company'     => $meta['company'] ?? '',

            'website'     => $meta['website'] ?? '',

            'brand'       => $meta['brand'] ?? '',

            'cta'         => $meta['cta'] ?? '',

            'author'      => $meta['author'] ?? '',

            'today'       => date('Y-m-d'),

            'year'        => date('Y'),

            'datetime'    => date('Y-m-d H:i:s')

        ];

    }
}