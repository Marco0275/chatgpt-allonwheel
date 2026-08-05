<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Repository: ArticleRepository
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/BaseRepository.php';

class ArticleRepository extends BaseRepository
{
    /**
     * Restituisce un articolo tramite ID.
     */
    public function find(int $id): ?Article
    {
        $row = $this->fetchOne(
            "SELECT * FROM articles WHERE id = :id LIMIT 1",
            [
                ':id' => $id
            ]
        );

        return $row ? new Article($row) : null;
    }

    /**
     * Restituisce un articolo tramite slug.
     */
    public function findBySlug(string $slug): ?Article
    {
        $row = $this->fetchOne(
            "SELECT * FROM articles WHERE slug = :slug LIMIT 1",
            [
                ':slug' => $slug
            ]
        );

        return $row ? new Article($row) : null;
    }

    /**
     * Elenco articoli.
     */
    public function all(
        int $limit = 100,
        int $offset = 0
    ): array {

        $stmt = $this->connection()->prepare(
            "
            SELECT *
            FROM articles
            ORDER BY created_at DESC
            LIMIT :limit
            OFFSET :offset
            "
        );

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $articles = [];

        while ($row = $stmt->fetch()) {
            $articles[] = new Article($row);
        }

        return $articles;
    }

    /**
     * Inserimento articolo.
     */
    public function create(Article $article): int
    {
        $this->execute(
            "
            INSERT INTO articles
            (
                title,
                slug,
                excerpt,
                content,
                language,
                status,
                featured_image,
                meta_title,
                meta_description,
                seo_keywords,
                author_id,
                published_at
            )
            VALUES
            (
                :title,
                :slug,
                :excerpt,
                :content,
                :language,
                :status,
                :featured_image,
                :meta_title,
                :meta_description,
                :seo_keywords,
                :author_id,
                :published_at
            )
            ",
            [
                ':title'            => $article->title,
                ':slug'             => $article->slug,
                ':excerpt'          => $article->excerpt,
                ':content'          => $article->content,
                ':language'         => $article->language,
                ':status'           => $article->status,
                ':featured_image'   => $article->featured_image,
                ':meta_title'       => $article->meta_title,
                ':meta_description' => $article->meta_description,
                ':seo_keywords'     => $article->seo_keywords,
                ':author_id'        => $article->author_id,
                ':published_at'     => $article->published_at
            ]
        );

        return $this->lastInsertId();
    }

    /**
     * Aggiorna articolo.
     */
    public function update(Article $article): bool
    {
        return $this->execute(
            "
            UPDATE articles SET

                title = :title,
                slug = :slug,
                excerpt = :excerpt,
                content = :content,
                language = :language,
                status = :status,
                featured_image = :featured_image,
                meta_title = :meta_title,
                meta_description = :meta_description,
                seo_keywords = :seo_keywords,
                author_id = :author_id,
                published_at = :published_at,
                updated_at = NOW()

            WHERE id = :id
            ",
            [
                ':id'               => $article->id,
                ':title'            => $article->title,
                ':slug'             => $article->slug,
                ':excerpt'          => $article->excerpt,
                ':content'          => $article->content,
                ':language'         => $article->language,
                ':status'           => $article->status,
                ':featured_image'   => $article->featured_image,
                ':meta_title'       => $article->meta_title,
                ':meta_description' => $article->meta_description,
                ':seo_keywords'     => $article->seo_keywords,
                ':author_id'        => $article->author_id,
                ':published_at'     => $article->published_at
            ]
        );
    }

    /**
     * Salva articolo.
     */
    public function save(Article $article): int|bool
    {
        if ($article->id === null) {
            return $this->create($article);
        }

        return $this->update($article);
    }

    /**
     * Pubblica articolo.
     */
    public function publish(int $id): bool
    {
        return $this->execute(
            "
            UPDATE articles
            SET
                status = 'published',
                published_at = NOW()
            WHERE id = :id
            ",
            [
                ':id' => $id
            ]
        );
    }

    /**
     * Archivia articolo.
     */
    public function archive(int $id): bool
    {
        return $this->execute(
            "
            UPDATE articles
            SET status = 'archived'
            WHERE id = :id
            ",
            [
                ':id' => $id
            ]
        );
    }

    /**
     * Elimina articolo.
     */
    public function delete(int $id): bool
    {
        return $this->execute(
            "DELETE FROM articles WHERE id = :id",
            [
                ':id' => $id
            ]
        );
    }

    /**
     * Verifica esistenza slug.
     */
    public function slugExists(
        string $slug,
        ?int $excludeId = null
    ): bool {

        if ($excludeId === null) {

            return $this->count(
                "
                SELECT COUNT(*)
                FROM articles
                WHERE slug = :slug
                ",
                [
                    ':slug' => $slug
                ]
            ) > 0;
        }

        return $this->count(
            "
            SELECT COUNT(*)
            FROM articles
            WHERE slug = :slug
            AND id <> :id
            ",
            [
                ':slug' => $slug,
                ':id'   => $excludeId
            ]
        ) > 0;
    }
}