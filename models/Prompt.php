<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Model: Prompt
 * ------------------------------------------------------------
 */

class Prompt
{
    public ?int $id = null;

    public string $name = '';

    public string $code = '';

    public string $type = '';

    public string $model = '';

    public string $system_prompt = '';

    public string $user_prompt = '';

    public ?string $description = null;

    public float $temperature = 0.7;

    public int $max_tokens = 3000;

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
     * Popola il model.
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
     * Restituisce il model come array.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Validazione.
     */
    public function validate(): array
    {
        $errors = [];

        if (trim($this->name) === '') {
            $errors[] = 'Prompt name is required.';
        }

        if (trim($this->code) === '') {
            $errors[] = 'Prompt code is required.';
        }

        if (trim($this->system_prompt) === '') {
            $errors[] = 'System prompt is required.';
        }

        if (trim($this->user_prompt) === '') {
            $errors[] = 'User prompt is required.';
        }

        if ($this->temperature < 0 || $this->temperature > 2) {
            $errors[] = 'Temperature must be between 0 and 2.';
        }

        if ($this->max_tokens < 1) {
            $errors[] = 'Invalid max tokens.';
        }

        return $errors;
    }

    /**
     * Model valido.
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Prompt attivo.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Prompt disattivato.
     */
    public function isDisabled(): bool
    {
        return !$this->enabled;
    }
}