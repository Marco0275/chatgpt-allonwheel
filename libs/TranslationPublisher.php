<?php
declare(strict_types=1);

require_once __DIR__ . '/OpenAIClient.php';
require_once __DIR__ . '/BlogPublisher.php';

class TranslationPublisher
{
    private PDO $db;

    private OpenAIClient $ai;

    private BlogPublisher $publisher;

    public function __construct(PDO $db)
    {
        $this->db = $db;

        $this->ai = new OpenAIClient($db);

        $this->publisher = new BlogPublisher($db);
    }

    public function publish(array $article): array
    {
        $languages = $this->enabledLanguages();

        $published = [];

        foreach ($languages as $language) {

            if ($language === ($article['language'] ?? 'en')) {

                continue;

            }

            $translated = $this->translate(

                $article,

                $language

            );

            $blogId = $this->publisher
                ->publish($translated);

            $published[] = [

                'language' => $language,

                'blog_id' => $blogId

            ];

            $this->storeRelation(

                $article,

                $blogId,

                $language

            );

        }

        return $published;
    }

    private function translate(
        array $article,
        string $language
    ): array
    {
        $prompt =

        "Translate the following JSON.

        Preserve HTML.

        Preserve links.

        Preserve Schema.org.

        Preserve FAQ.

        Target language: {$language}

        Return ONLY valid JSON.

        JSON:

        "

        .

        json_encode(

            $article,

            JSON_UNESCAPED_UNICODE

        );

        $json = $this->ai->chat(

            $prompt,

            [

                'temperature'=>0.2

            ]

        );

        $translated = json_decode(

            $json,

            true

        );

        if(!is_array($translated)){

            throw new RuntimeException(

                'Translation failed.'

            );

        }

        $translated['language']=$language;

        return $translated;
    }

    private function enabledLanguages(): array
    {
        $stmt=$this->db->query(

            "SELECT language_code

             FROM ai_languages

             WHERE enabled=1

             ORDER BY sort_order"

        );

        return $stmt->fetchAll(

            PDO::FETCH_COLUMN

        );
    }

    private function storeRelation(
        array $original,
        int $blogId,
        string $language
    ): void
    {
        if(empty($original['blog_id'])){

            return;

        }

        $stmt=$this->db->prepare(

            "INSERT INTO ai_translations
            (
                source_blog_id,
                translated_blog_id,
                language_code,
                created_at
            )
            VALUES
            (
                :source,
                :translated,
                :language,
                NOW()
            )"

        );

        $stmt->execute([

            ':source'=>$original['blog_id'],

            ':translated'=>$blogId,

            ':language'=>$language

        ]);
    }

    public function languages(): array
    {
        return $this->enabledLanguages();
    }
}