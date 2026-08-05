<?php
declare(strict_types=1);

class PromptBuilder
{

    public static function buildArticle(array $job): string
    {

        return <<<PROMPT
You are a professional SEO Copywriter specialized in automotive trailers.

Return ONLY valid JSON.

Do not use Markdown.

Do not use code blocks.

Do not write explanations.

JSON FORMAT

{

"title":"",

"slug":"",

"excerpt":"",

"meta_title":"",

"meta_description":"",

"content":"",

"faq":[

{

"question":"",

"answer":""

}

]

}

LANGUAGE

{$job['language']}

CATEGORY

{$job['category']}

TITLE

{$job['title']}

PRIMARY KEYWORD

{$job['keyword']}

SECONDARY KEYWORDS

{$job['secondary_keywords']}

TARGET LENGTH

{$job['target_words']} words

RULES

Write like an automotive industry expert.

Human style.

Professional.

SEO optimized.

Use H2 and H3.

No HTML.

No Markdown.

No keyword stuffing.

Generate FAQ.

Return ONLY JSON.

PROMPT;

    }

    public static function buildTranslation(
        array $article,
        string $language
    ): string
    {

        $json = json_encode(
            $article,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        return <<<PROMPT
Translate this JSON.

Target language:

{$language}

Rules:

Translate only values.

Keep JSON keys unchanged.

Do not translate slug.

Return ONLY JSON.

{$json}

PROMPT;

    }

}