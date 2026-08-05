<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Model: Article
 * ------------------------------------------------------------
 */

class Article
{
    public ?int $id = null;

    public string $title = '';

    public string $slug = '';

    public string $excerpt = '';

    public string $content = '';

    public string $language = 'it';

    public string $status = 'draft';

    public ?string $featured_image = null;

    public ?string $meta_title = null;

    public ?string $meta_description = null;

    public ?string $seo_keywords = null;

    public ?int $author_id = null;

    public ?string $published_at = null;

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
     * Converte in array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Verifica se è pubblicato
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Verifica se è bozza
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Verifica se è pianificato
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Validazione
     */
    public function validate(): array
    {
        $errors = [];

        if (trim($this->title) === '') {
            $errors[] = 'Title is required.';
        }

        if (trim($this->slug) === '') {
            $errors[] = 'Slug is required.';
        }

        if (trim($this->language) === '') {
            $errors[] = 'Language is required.';
        }

        if (!in_array($this->status, [
            'draft',
            'scheduled',
            'published',
            'archived'
        ], true)) {

            $errors[] = 'Invalid status.';
        }

        return $errors;
    }

    /**
     * Articolo valido
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}