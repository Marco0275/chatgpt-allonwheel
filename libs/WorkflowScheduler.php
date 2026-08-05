<?php
declare(strict_types=1);

require_once __DIR__ . '/WorkflowQueue.php';
require_once __DIR__ . '/WorkflowWorker.php';
require_once __DIR__ . '/Logger.php';

class WorkflowScheduler
{
    private WorkflowQueue $queue;

    private WorkflowWorker $worker;

    private Logger $logger;

    private int $maxConcurrentJobs = 2;

    public function __construct(PDO $db)
    {
        $this->queue = new WorkflowQueue($db);

        $this->worker = new WorkflowWorker($db);

        $this->logger = new Logger();
    }

    public function run(): void
    {
        $this->queue->resetRunning();

        while (true) {

            if (
                count($this->queue->runningJobs())
                >=
                $this->maxConcurrentJobs
            ) {

                break;

            }

            $job = $this->queue->next();

            if ($job === null) {

                break;

            }

            $this->queue->running($job);

            try {

                $this->worker->execute($job);

                $this->queue->completed($job);

            } catch (Throwable $e) {

                $this->logger->error(
                    $e->getMessage()
                );

                if ($job->canRetry()) {

                    $job->increaseAttempts();

                    $this->queue->retry($job);

                } else {

                    $this->queue->failed(
                        $job,
                        $e
                    );

                }

            }

        }

    }

    public function setMaxConcurrentJobs(
        int $jobs
    ): self {

        $this->maxConcurrentJobs = max(
            1,
            $jobs
        );

        return $this;

    }

    public function getQueue(): WorkflowQueue
    {
        return $this->queue;
    }

}