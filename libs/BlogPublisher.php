<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/blog.class.php';

class BlogPublisher
{
    private PDO $db;

    private BlogManager $blog;

    public function __construct(PDO $db)
    {
        $this->db = $db;

        $this->blog = new BlogManager($db);
    }

    public function publish(
        array $article
    ): int {

        $data = [

            'id_user' => $article['id_user'] ?? 0,

            'title' => $article['title'],

            'slug' => $article['slug'] ?? null,

            'excerpt' => $article['excerpt'] ?? '',

            'question' => $article['question'] ?? '',

            'body' => $article['body'],

            'category' => $article['category'] ?? 'general',

            'image' => $article['image'] ?? '',

            'meta_title' =>
                $article['meta_title'] ?? '',

            'meta_description' =>
                $article['meta_description'] ?? '',

            'meta_keywords' =>
                $article['meta_keywords'] ?? '',

            'schema_json' =>
                $article['schema_json'] ?? '',

            'faq_json' =>
                $article['faq_json'] ?? '',

            'seo_score' =>
                $article['seo_score'] ?? 0,

            'readability_score' =>
                $article['readability_score'] ?? 0,

            'language' =>
                $article['language'] ?? 'en',

            'status' => 'published'

        ];

        $blogId = $this->blog
            ->insertArticle($data);

        $this->afterPublish(
            $blogId,
            $article
        );

        return $blogId;
    }

    private function afterPublish(
        int $blogId,
        array $article
    ): void {

        $stmt = $this->db->prepare(

            "UPDATE blog

             SET

                published_at=NOW(),

                updated_at=NOW()

             WHERE id=:id"

        );

        $stmt->execute([

            ':id'=>$blogId

        ]);

        if(!empty($article['internal_links'])){

            $this->saveLinks(

                $blogId,

                $article['internal_links']

            );

        }

        if(!empty($article['related'])){

            $this->saveRelated(

                $blogId,

                $article['related']

            );

        }

    }

    private function saveLinks(
        int $blogId,
        array $links
    ): void {

        $stmt = $this->db->prepare(

            "INSERT INTO ai_internal_links
            (
                blog_id,
                target_id,
                anchor
            )
            VALUES
            (
                :blog,
                :target,
                :anchor
            )"

        );

        foreach($links as $link){

            $stmt->execute([

                ':blog'=>$blogId,

                ':target'=>$link['id'],

                ':anchor'=>$link['anchor']

            ]);

        }

    }

    private function saveRelated(
        int $blogId,
        array $related
    ): void {

        $stmt = $this->db->prepare(

            "INSERT INTO ai_related_articles
            (
                blog_id,
                related_blog_id
            )
            VALUES
            (
                :blog,
                :related
            )"

        );

        foreach($related as $id){

            $stmt->execute([

                ':blog'=>$blogId,

                ':related'=>$id

            ]);

        }

    }

    public function unpublish(
        int $blogId
    ): void {

        $stmt=$this->db->prepare(

            "UPDATE blog

             SET status='draft'

             WHERE id=:id"

        );

        $stmt->execute([

            ':id'=>$blogId

        ]);

    }

    public function delete(
        int $blogId
    ): void {

        $stmt=$this->db->prepare(

            "DELETE FROM blog

             WHERE id=:id"

        );

        $stmt->execute([

            ':id'=>$blogId

        ]);

    }

    public function existsSlug(
        string $slug
    ): bool {

        $stmt=$this->db->prepare(

            "SELECT COUNT(*)

             FROM blog

             WHERE slug=:slug"

        );

        $stmt->execute([

            ':slug'=>$slug

        ]);

        return (bool)$stmt->fetchColumn();

    }

    public function generateUniqueSlug(
        string $slug
    ): string {

        $base=$slug;

        $i=1;

        while(
            $this->existsSlug($slug)
        ){

            $slug=$base.'-'.$i;

            $i++;

        }

        return $slug;

    }

}