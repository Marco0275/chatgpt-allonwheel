<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../repositories/ArticleRepository.php';

class ArticlePublisher
{
    private PDO $db;

    private ArticleRepository $repository;

    public function __construct(PDO $db)
    {
        $this->db = $db;

        $this->repository = new ArticleRepository($db);
    }

    public function publish(
        Article $article
    ): int {

        $this->db->beginTransaction();

        try {

            $id = $this->insertBlogArticle(
                $article
            );

            $article->setStatus(
                'published'
            );

            $article->setPublishedAt(
                new DateTimeImmutable()
            );

            $article->setExternalId(
                $id
            );

            $this->repository->save(
                $article
            );

            $this->db->commit();

            return $id;

        } catch(Throwable $e) {

            if ($this->db->inTransaction()) {

                $this->db->rollBack();

            }

            throw $e;

        }

    }

    private function insertBlogArticle(
        Article $article
    ): int {

        $stmt = $this->db->prepare(

            "INSERT INTO blog
            (

                id_user,

                title,

                slug,

                excerpt,

                body,

                image,

                category,

                seo_title,

                seo_description,

                canonical,

                status,

                published_at,

                created_at,

                updated_at

            )

            VALUES
            (

                :id_user,

                :title,

                :slug,

                :excerpt,

                :body,

                :image,

                :category,

                :seo_title,

                :seo_description,

                :canonical,

                'published',

                NOW(),

                NOW(),

                NOW()

            )"

        );

        $stmt->execute([

            ':id_user' =>
                $article->getAuthorId(),

            ':title' =>
                $article->getTitle(),

            ':slug' =>
                $article->getSlug(),

            ':excerpt' =>
                $article->getExcerpt(),

            ':body' =>
                $article->getBody(),

            ':image' =>
                $article->getImage(),

            ':category' =>
                $article->getCategory(),

            ':seo_title' =>
                $article->getSeoTitle(),

            ':seo_description' =>
                $article->getSeoDescription(),

            ':canonical' =>
                $article->getCanonical()

        ]);

        return (int)$this->db->lastInsertId();

    }

}