<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Repository: CategoryRepository
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/BaseRepository.php';

class CategoryRepository extends BaseRepository
{
    /**
     * Restituisce una categoria tramite ID.
     */
    public function find(int $id): ?Category
    {
        $row = $this->fetchOne(
            "
            SELECT *
            FROM categories
            WHERE id = :id
            LIMIT 1
            ",
            [
                ':id' => $id
            ]
        );

        return $row ? new Category($row) : null;
    }

    /**
     * Restituisce una categoria tramite slug.
     */
    public function findBySlug(string $slug): ?Category
    {
        $row = $this->fetchOne(
            "
            SELECT *
            FROM categories
            WHERE slug = :slug
            LIMIT 1
            ",
            [
                ':slug' => $slug
            ]
        );

        return $row ? new Category($row) : null;
    }

    /**
     * Elenco categorie ordinate.
     */
    public function all(): array
    {
        $rows = $this->fetchAll(
            "
            SELECT *
            FROM categories
            ORDER BY sort_order ASC, name ASC
            "
        );

        $categories = [];

        foreach ($rows as $row) {
            $categories[] = new Category($row);
        }

        return $categories;
    }

    /**
     * Categorie abilitate.
     */
    public function enabled(): array
    {
        $rows = $this->fetchAll(
            "
            SELECT *
            FROM categories
            WHERE enabled = 1
            ORDER BY sort_order ASC, name ASC
            "
        );

        $categories = [];

        foreach ($rows as $row) {
            $categories[] = new Category($row);
        }

        return $categories;
    }

    /**
     * Categorie principali.
     */
    public function roots(): array
    {
        $rows = $this->fetchAll(
            "
            SELECT *
            FROM categories
            WHERE parent_id IS NULL
            ORDER BY sort_order ASC, name ASC
            "
        );

        $categories = [];

        foreach ($rows as $row) {
            $categories[] = new Category($row);
        }

        return $categories;
    }

    /**
     * Categorie figlie.
     */
    public function children(int $parentId): array
    {
        $rows = $this->fetchAll(
            "
            SELECT *
            FROM categories
            WHERE parent_id = :parent_id
            ORDER BY sort_order ASC, name ASC
            ",
            [
                ':parent_id' => $parentId
            ]
        );

        $categories = [];

        foreach ($rows as $row) {
            $categories[] = new Category($row);
        }

        return $categories;
    }

    /**
     * Inserisce una categoria.
     */
    public function create(Category $category): int
    {
        $this->execute(
            "
            INSERT INTO categories
            (
                name,
                slug,
                description,
                parent_id,
                sort_order,
                enabled
            )
            VALUES
            (
                :name,
                :slug,
                :description,
                :parent_id,
                :sort_order,
                :enabled
            )
            ",
            [
                ':name'        => $category->name,
                ':slug'        => $category->slug,
                ':description' => $category->description,
                ':parent_id'   => $category->parent_id,
                ':sort_order'  => $category->sort_order,
                ':enabled'     => $category->enabled ? 1 : 0
            ]
        );

        return $this->lastInsertId();
    }

    /**
     * Aggiorna una categoria.
     */
    public function update(Category $category): bool
    {
        return $this->execute(
            "
            UPDATE categories SET

                name = :name,
                slug = :slug,
                description = :description,
                parent_id = :parent_id,
                sort_order = :sort_order,
                enabled = :enabled,
                updated_at = NOW()

            WHERE id = :id
            ",
            [
                ':id'          => $category->id,
                ':name'        => $category->name,
                ':slug'        => $category->slug,
                ':description' => $category->description,
                ':parent_id'   => $category->parent_id,
                ':sort_order'  => $category->sort_order,
                ':enabled'     => $category->enabled ? 1 : 0
            ]
        );
    }

    /**
     * Salva una categoria.
     */
    public function save(Category $category): int|bool
    {
        if ($category->id === null) {
            return $this->create($category);
        }

        return $this->update($category);
    }

    /**
     * Elimina una categoria.
     */
    public function delete(int $id): bool
    {
        return $this->execute(
            "
            DELETE FROM categories
            WHERE id = :id
            ",
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
                FROM categories
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
            FROM categories
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