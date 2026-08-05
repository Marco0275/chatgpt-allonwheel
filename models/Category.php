<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Model: Category
 * ------------------------------------------------------------
 */

class Category
{
    public ?int $id = null;

    public string $name = '';

    public string $slug = '';

    public ?string $description = null;

    public ?int $parent_id = null;

    public int $sort_order = 0;

    public bool $enabled = true;

    public ?string $created_at = null;

    public ?string $updated_at = null;

    /**
     * Costruttore
     */
    public function __construct(array $data = [])
    {
        $this->fill($data);
    }

    /**
     * Popola il model
     */
    public function fill(array $data): void
    {
        foreach ($data as $key => $value) {

            if (property_exists($this, $key)) {
                $this->$key = $value;
            }

        }
    }

    /**
     * Array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Valida il model
     */
    public function validate(): array
    {
        $errors = [];

        if (trim($this->name) === '') {
            $errors[] = 'Category name is required.';
        }

        if (trim($this->slug) === '') {
            $errors[] = 'Category slug is required.';
        }

        return $errors;
    }

    /**
     * Model valido
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Categoria principale
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Categoria figlia
     */
    public function hasParent(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Abilitata
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Disabilitata
     */
    public function isDisabled(): bool
    {
        return !$this->enabled;
    }
}