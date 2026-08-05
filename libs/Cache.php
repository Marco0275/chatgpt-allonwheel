<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Library: Cache
 * ------------------------------------------------------------
 */

class Cache
{
    private string $path;

    private string $extension = '.cache';

    public function __construct(string $path)
    {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR);

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    /**
     * Salva un elemento.
     */
    public function put(
        string $key,
        mixed $value,
        int $ttl = 3600
    ): bool {

        $file = $this->filename($key);

        $payload = [

            'expires' => time() + $ttl,

            'data' => serialize($value)

        ];

        return file_put_contents(
            $file,
            serialize($payload),
            LOCK_EX
        ) !== false;
    }

    /**
     * Recupera un elemento.
     */
    public function get(
        string $key,
        mixed $default = null
    ): mixed {

        $file = $this->filename($key);

        if (!file_exists($file)) {
            return $default;
        }

        $payload = @unserialize(
            file_get_contents($file)
        );

        if (!is_array($payload)) {

            @unlink($file);

            return $default;
        }

        if ($payload['expires'] < time()) {

            @unlink($file);

            return $default;
        }

        return unserialize(
            $payload['data']
        );
    }

    /**
     * Verifica l'esistenza.
     */
    public function has(
        string $key
    ): bool {

        return $this->get(
            $key,
            '__missing__'
        ) !== '__missing__';
    }

    /**
     * Elimina una chiave.
     */
    public function forget(
        string $key
    ): bool {

        $file = $this->filename($key);

        if (!file_exists($file)) {
            return true;
        }

        return unlink($file);
    }

    /**
     * Svuota la cache.
     */
    public function clear(): void
    {
        foreach (
            glob($this->path . '/*' . $this->extension)
            as $file
        ) {
            unlink($file);
        }
    }

    /**
     * Ripulisce gli elementi scaduti.
     */
    public function cleanup(): void
    {
        foreach (
            glob($this->path . '/*' . $this->extension)
            as $file
        ) {

            $payload = @unserialize(
                file_get_contents($file)
            );

            if (
                is_array($payload) &&
                isset($payload['expires']) &&
                $payload['expires'] < time()
            ) {
                unlink($file);
            }
        }
    }

    /**
     * Restituisce il filename.
     */
    private function filename(
        string $key
    ): string {

        return $this->path .
            DIRECTORY_SEPARATOR .
            md5($key) .
            $this->extension;
    }
}