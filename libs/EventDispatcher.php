<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Library: EventDispatcher
 * ------------------------------------------------------------
 */

class EventDispatcher
{
    /**
     * @var array<string,array<int,callable>>
     */
    private array $listeners = [];

    /**
     * Registra un listener.
     */
    public function listen(
        string $event,
        callable $listener
    ): void {

        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = $listener;
    }

    /**
     * Alias.
     */
    public function on(
        string $event,
        callable $listener
    ): void {
        $this->listen($event, $listener);
    }

    /**
     * Emette un evento.
     */
    public function dispatch(
        string $event,
        array $payload = []
    ): void {

        if (!isset($this->listeners[$event])) {
            return;
        }

        foreach ($this->listeners[$event] as $listener) {
            $listener($payload);
        }
    }

    /**
     * Restituisce i listener registrati.
     */
    public function listeners(
        ?string $event = null
    ): array {

        if ($event === null) {
            return $this->listeners;
        }

        return $this->listeners[$event] ?? [];
    }

    /**
     * Verifica la presenza di listener.
     */
    public function has(
        string $event
    ): bool {

        return !empty(
            $this->listeners[$event]
        );
    }

    /**
     * Elimina tutti i listener di un evento.
     */
    public function clear(
        ?string $event = null
    ): void {

        if ($event === null) {
            $this->listeners = [];
            return;
        }

        unset($this->listeners[$event]);
    }
}