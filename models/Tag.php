<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Model: Tag
 * ------------------------------------------------------------
 */

class Tag
{
    public ?int $id = null;

    public string $name = '';

    public string $slug = '';

    public ?string $description = null;

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
     * Restituisce il model come array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Validazione
     */
    public function validate(): array
    {
        $errors = [];

        if (trim($this->name) === '') {
            $errors[] = 'Tag name is required.';
        }

        if (trim($this->slug) === '') {
            $errors[] = 'Tag slug is required.';
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
     * Tag abilitato
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Tag disabilitato
     */
    public function isDisabled(): bool
    {
        return !$this->enabled;
    }
}