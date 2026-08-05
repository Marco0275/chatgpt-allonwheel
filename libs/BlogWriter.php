<?php
declare(strict_types=1);

class BlogWriter
{

    private DB $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    public function save(array $article, array $queue): int
    {

        $idUser = 1;

        $title = $this->db->escapeString($article['title'] ?? '');

        $slug = $this->db->escapeString($article['slug'] ?? '');

        $category = $this->db->escapeString($queue['category']);

        $excerpt = $this->db->escapeString($article['excerpt'] ?? '');

        $question = '';

        $body = $this->db->escapeString($article['content'] ?? '');

        $outlines = '';

        $faq = $this->db->escapeString(

            json_encode(

                $article['faq'] ?? [],

                JSON_UNESCAPED_UNICODE

            )

        );

        $status = 'scheduled';

        $published = $this->db->escapeString(

            $queue['publish_at']

        );
if(empty($article['slug']))
{
    $article['slug']=SeoHelper::slug(
        $article['title']
    );
}

if(empty($article['excerpt']))
{
    $article['excerpt']=SeoHelper::excerpt(
        $article['content']
    );
}

if(empty($article['meta_title']))
{
    $article['meta_title']=SeoHelper::metaTitle(
        $article['title']
    );
}

if(empty($article['meta_description']))
{
    $article['meta_description']=SeoHelper::metaDescription(
        $article['content']
    );
}
		public function slugExists(string $slug,string $language): bool
{

    $slug = $this->db->escapeString($slug);

    $language = $this->db->escapeString($language);

    $sql = "

        SELECT id

        FROM blog

        WHERE

            slug='{$slug}'

        AND

            language='{$language}'

        LIMIT 1

    ";

    $res = $this->db->executeQuery($sql);

    return $this->db->numRows($res)>0;

}
		
		private function uniqueSlug(
    string $slug,
    string $language
): string
{

    $base = $slug;

    $i = 1;

    while($this->slugExists($slug,$language))
    {

        $slug = $base.'-'.$i;

        $i++;

    }

    return $slug;

}
		$slug = $this->uniqueSlug(

    $slug,

    $queue['language']

);
        $sql = "

        INSERT INTO blog

        (

            id_user,

            title,

            slug,

            category,

            excerpt,

            question,

            body,

            outlines,

            faq_json,

            image,

            status,

            published_at,

            source

        )

        VALUES

        (

            {$idUser},

            '{$title}',

            '{$slug}',

            '{$category}',

            '{$excerpt}',

            '{$question}',

            '{$body}',

            '{$outlines}',

            '{$faq}',

            NULL,

            '{$status}',

            '{$published}',

            'api'

        )

        ";

        $this->db->executeInsert($sql);

        return (int)$this->db->lastInsertId();

    }

}
public function saveTranslation(

    int $parentId,

    int $queueId,

    array $article,

    array $queue,

    string $language

): int
{

    $title=$this->db->escapeString($article['title']);

    $slug=$this->db->escapeString($article['slug']);

    $excerpt=$this->db->escapeString($article['excerpt']);

    $body=$this->db->escapeString($article['content']);

    $faq=$this->db->escapeString(

        json_encode(

            $article['faq'],

            JSON_UNESCAPED_UNICODE

        )

    );

    $language=$this->db->escapeString($language);

    $category=$this->db->escapeString(

        $queue['category']

    );

    $published=$this->db->escapeString(

        $queue['publish_at']

    );

    $sql="

    INSERT INTO blog

    (

        id_user,

        parent_id,

        queue_id,

        language,

        title,

        slug,

        category,

        excerpt,

        body,

        faq_json,

        status,

        published_at,

        source,

        ai_provider,

        ai_model,

        ai_generated_at

    )

    VALUES

    (

        1,

        {$parentId},

        {$queueId},

        '{$language}',

        '{$title}',

        '{$slug}',

        '{$category}',

        '{$excerpt}',

        '{$body}',

        '{$faq}',

        'scheduled',

        '{$published}',

        'AI',

        'Google',

        '".GEMINI_MODEL."',

        NOW()

    )

    ";

    $this->db->executeInsert($sql);

    return (int)$this->db->lastInsertId();

}