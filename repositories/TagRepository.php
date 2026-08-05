<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Repository: TagRepository
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/../models/Tag.php';
require_once __DIR__ . '/BaseRepository.php';

class TagRepository extends BaseRepository
{
    /**
     * Restituisce un tag tramite ID.
     */
    public function find(int $id): ?Tag
    {
        $row = $this->fetchOne(
            "
            SELECT *
            FROM tags
            WHERE id = :id
            LIMIT 1
            ",
            [
                ':id' => $id
            ]
        );

        return $row ? new Tag($row) : null;
    }

    /**
     * Restituisce un tag tramite slug.
     */
    public function findBySlug(string $slug): ?Tag
    {
        $row = $this->fetchOne(
            "
            SELECT *
            FROM tags
            WHERE slug = :slug
            LIMIT 1
            ",
            [
                ':slug' => $slug
            ]
        );

        return $row ? new Tag($row) : null;
    }

    /**
     * Elenco completo dei tag.
     */
    public function all(): array
    {
        $rows = $this->fetchAll(
            "
            SELECT *
            FROM tags
            ORDER BY name ASC
            "
        );

        $tags = [];

        foreach ($rows as $row) {
            $tags[] = new Tag($row);
        }

        return $tags;
    }

    /**
     * Tag abilitati.
     */
    public function enabled(): array
    {
        $rows = $this->fetchAll(
            "
            SELECT *
            FROM tags
            WHERE enabled = 1
            ORDER BY name ASC
            "
        );

        $tags = [];

        foreach ($rows as $row) {
            $tags[] = new Tag($row);
        }

        return $tags;
    }

    /**
     * Inserisce un nuovo tag.
     */
    public function create(Tag $tag): int
    {
        $this->execute(
            "
            INSERT INTO tags
            (
                name,
                slug,
                description,
                enabled
            )
            VALUES
            (
                :name,
                :slug,
                :description,
                :enabled
            )
            ",
            [
                ':name'        => $tag->name,
                ':slug'        => $tag->slug,
                ':description' => $tag->description,
                ':enabled'     => $tag->enabled ? 1 : 0
            ]
        );

        return $this->lastInsertId();
    }

    /**
     * Aggiorna un tag.
     */
    public function update(Tag $tag): bool
    {
        return $this->execute(
            "
            UPDATE tags SET

                name = :name,
                slug = :slug,
                description = :description,
                enabled = :enabled,
                updated_at = NOW()

            WHERE id = :id
            ",
            [
                ':id'          => $tag->id,
                ':name'        => $tag->name,
                ':slug'        => $tag->slug,
                ':description' => $tag->description,
                ':enabled'     => $tag->enabled ? 1 : 0
            ]
        );
    }

    /**
     * Salva un tag.
     */
    public function save(Tag $tag): int|bool
    {
        if ($tag->id === null) {
            return $this->create($tag);
        }

        return $this->update($tag);
    }

    /**
     * Elimina un tag.
     */
    public function delete(int $id): bool
    {
        return $this->execute(
            "
            DELETE FROM tags
            WHERE id = :id
            ",
            [
                ':id' => $id
            ]
        );
    }

    /**
     * Verifica se uno slug esiste.
     */
    public function slugExists(
        string $slug,
        ?int $excludeId = null
    ): bool {

        if ($excludeId === null) {

            return $this->count(
                "
                SELECT COUNT(*)
                FROM tags
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
            FROM tags
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