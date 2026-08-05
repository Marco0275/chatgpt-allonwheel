<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Model: Workflow
 * ------------------------------------------------------------
 */

class Workflow
{
    public ?int $id = null;

    public string $name = '';

    public string $code = '';

    public ?string $description = null;

    /**
     * article
     * image
     * translate
     * seo
     * publish
     * ...
     */
    public string $type = '';

    /**
     * JSON contenente gli step
     */
    public string $steps = '[]';

    /**
     * JSON con la configurazione
     */
    public string $settings = '{}';

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
     * Restituisce gli step decodificati
     */
    public function getSteps(): array
    {
        $steps = json_decode($this->steps, true);

        return is_array($steps) ? $steps : [];
    }

    /**
     * Imposta gli step
     */
    public function setSteps(array $steps): void
    {
        $this->steps = json_encode(
            $steps,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
    }

    /**
     * Restituisce le impostazioni
     */
    public function getSettings(): array
    {
        $settings = json_decode($this->settings, true);

        return is_array($settings) ? $settings : [];
    }

    /**
     * Imposta le impostazioni
     */
    public function setSettings(array $settings): void
    {
        $this->settings = json_encode(
            $settings,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
    }

    /**
     * Validazione
     */
    public function validate(): array
    {
        $errors = [];

        if (trim($this->name) === '') {
            $errors[] = 'Workflow name is required.';
        }

        if (trim($this->code) === '') {
            $errors[] = 'Workflow code is required.';
        }

        if (trim($this->type) === '') {
            $errors[] = 'Workflow type is required.';
        }

        json_decode($this->steps);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'Invalid steps JSON.';
        }

        json_decode($this->settings);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'Invalid settings JSON.';
        }

        return $errors;
    }

    /**
     * Workflow valido
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }

    /**
     * Workflow attivo
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Workflow disattivato
     */
    public function isDisabled(): bool
    {
        return !$this->enabled;
    }
}