<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Library: PluginManager
 * ------------------------------------------------------------
 */

class PluginManager
{
    /**
     * @var array<string,object>
     */
    private array $plugins = [];

    /**
     * Registra un plugin.
     */
    public function register(
        string $name,
        object $plugin
    ): void {

        $this->plugins[$name] = $plugin;

        if (method_exists($plugin, 'boot')) {
            $plugin->boot();
        }
    }

    /**
     * Restituisce un plugin.
     */
    public function get(
        string $name
    ): ?object {

        return $this->plugins[$name] ?? null;
    }

    /**
     * Verifica se il plugin esiste.
     */
    public function has(
        string $name
    ): bool {

        return isset($this->plugins[$name]);
    }

    /**
     * Restituisce tutti i plugin.
     */
    public function all(): array
    {
        return $this->plugins;
    }

    /**
     * Rimuove un plugin.
     */
    public function unregister(
        string $name
    ): void {

        if (
            isset($this->plugins[$name]) &&
            method_exists($this->plugins[$name], 'shutdown')
        ) {
            $this->plugins[$name]->shutdown();
        }

        unset($this->plugins[$name]);
    }

    /**
     * Carica automaticamente tutti i plugin.
     */
    public function load(
        string $directory
    ): void {

        if (!is_dir($directory)) {
            return;
        }

        foreach (glob($directory . '/*Plugin.php') as $file) {

            require_once $file;

            $class = pathinfo(
                $file,
                PATHINFO_FILENAME
            );

            if (!class_exists($class)) {
                continue;
            }

            $plugin = new $class();

            $this->register(
                $class,
                $plugin
            );
        }
    }
}