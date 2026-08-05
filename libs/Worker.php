<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Library: Worker
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/Database.php';

require_once __DIR__ . '/../repositories/ArticleRepository.php';
require_once __DIR__ . '/../repositories/PromptRepository.php';
require_once __DIR__ . '/../repositories/WorkflowRepository.php';

require_once __DIR__ . '/QueueManager.php';
require_once __DIR__ . '/OpenAIClient.php';
require_once __DIR__ . '/PromptEngine.php';
require_once __DIR__ . '/ContentGenerator.php';
require_once __DIR__ . '/WorkflowEngine.php';

class Worker
{
    private QueueManager $queue;

    private WorkflowEngine $workflowEngine;

    private bool $running = true;

    private int $sleep = 3;

    public function __construct(
        QueueManager $queue,
        WorkflowEngine $workflowEngine
    ) {
        $this->queue = $queue;
        $this->workflowEngine = $workflowEngine;
    }

    /**
     * Avvia il worker.
     */
    public function run(): void
    {
        $this->queue->resetRunning();

        while ($this->running) {

            $job = $this->queue->pop();

            if ($job === null) {

                sleep($this->sleep);

                continue;
            }

            try {

                $payload = $job['payload'];

                $workflow = $payload['workflow'] ?? '';

                $context = $payload['context'] ?? [];

                $result = $this->workflowEngine->run(
                    $workflow,
                    $context
                );

                $this->queue->complete(
                    $job['id'],
                    json_encode(
                        $result,
                        JSON_UNESCAPED_UNICODE
                    )
                );

            } catch (Throwable $e) {

                $this->queue->fail(
                    $job['id'],
                    $e->getMessage()
                );
            }
        }
    }

    /**
     * Arresta il worker.
     */
    public function stop(): void
    {
        $this->running = false;
    }

    /**
     * Imposta il tempo di polling.
     */
    public function setSleep(int $seconds): void
    {
        if ($seconds > 0) {
            $this->sleep = $seconds;
        }
    }
}