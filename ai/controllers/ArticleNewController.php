<?php
declare(strict_types=1);

require_once __DIR__ . '/../../models/Article.php';

class ArticleNewController
{
    public function defaults(): array
    {
        return [

            'topic' => '',

            'keywords' => '',

            'category' => '',

            'language' => 'English',

            'tone' => 'Professional',

            'length' => 'Medium',

            'audience' => '',

            'country' => '',

            'purpose' => '',

            'references' => '',

            'title' => '',

            'excerpt' => '',

            'body' => ''

        ];
    }
}