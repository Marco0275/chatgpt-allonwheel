<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Library: Scheduler
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/QueueManager.php';

class Scheduler
{
    private QueueManager $queue;

    /**
     * @var array<int,array<string,mixed>>
     */
    private array $tasks = [];

    public function __construct(
        QueueManager $queue
    ) {
        $this->queue = $queue;
    }

    /**
     * Registra un task.
     */
    public function register(
        string $name,
        string $workflow,
        string $expression,
        array $context = [],
        int $priority = 100
    ): void {

        $this->tasks[] = [

            'name'       => $name,
            'workflow'   => $workflow,
            'expression' => $expression,
            'context'    => $context,
            'priority'   => $priority

        ];
    }

    /**
     * Esegue i task pianificati.
     */
    public function run(): void
    {
        foreach ($this->tasks as $task) {

            if (!$this->isDue($task['expression'])) {
                continue;
            }

            $this->queue->push(
                $task['workflow'],
                [
                    'workflow' => $task['workflow'],
                    'context'  => $task['context']
                ],
                $task['priority']
            );
        }
    }

    /**
     * Verifica se il task deve essere eseguito.
     *
     * Formati supportati:
     *
     * everyMinute
     * hourly
     * daily
     * weekly
     * monthly
     */
    private function isDue(
        string $expression
    ): bool {

        return match ($expression) {

            'everyMinute' =>
                true,

            'hourly' =>
                date('i') === '00',

            'daily' =>
                date('H:i') === '00:00',

            'weekly' =>
                date('N') == 1 &&
                date('H:i') === '00:00',

            'monthly' =>
                date('d') == 1 &&
                date('H:i') === '00:00',

            default =>
                false

        };
    }

    /**
     * Elenco task.
     */
    public function tasks(): array
    {
        return $this->tasks;
    }

    /**
     * Cancella scheduler.
     */
    public function clear(): void
    {
        $this->tasks = [];
    }
}