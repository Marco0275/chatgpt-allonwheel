<?php
declare(strict_types=1);

require_once __DIR__ . '/WorkflowRepository.php';
require_once __DIR__ . '/Publisher.php';
require_once __DIR__ . '/PublishScheduler.php';
require_once __DIR__ . '/Logger.php';

class WorkflowExecutor
{
    private PDO $db;

    private WorkflowRepository $repository;

    private PublishScheduler $scheduler;

    private Publisher $publisher;

    private Logger $logger;

    public function __construct(PDO $db)
    {
        $this->db = $db;

        $this->repository = new WorkflowRepository($db);

        $this->scheduler = new PublishScheduler($db);

        $this->publisher = new Publisher($db);

        $this->logger = new Logger();
    }

    public function execute(): void
    {
        $this->scheduler->resetStalled();

        while (true) {

            $job = $this->repository->nextJob();

            if (!$job) {
                break;
            }

            $this->runJob($job);
        }
    }

    private function runJob(array $job): void
    {
        $id = (int)$job['id'];

        try {

            $this->repository->start($id);

            $this->logger->info(

                'Workflow started',

                [

                    'workflow' => $id

                ]

            );

            $blogId = $this->publisher->publish($id);

            $this->repository->complete(

                $id,

                $blogId

            );

            $this->logger->info(

                'Workflow completed',

                [

                    'workflow' => $id,

                    'blog' => $blogId

                ]

            );

        }

        catch (Throwable $e) {

            $this->repository->fail(

                $id,

                $e->getMessage()

            );

            $this->logger->error(

                $e->getMessage(),

                [

                    'workflow' => $id

                ]

            );

        }
    }

    public function executeOne(): bool
    {
        $job = $this->repository->nextJob();

        if (!$job) {

            return false;

        }

        $this->runJob($job);

        return true;
    }

    public function queueSize(): int
    {
        return $this->repository->queued();
    }

    public function running(): int
    {
        return $this->repository->running();
    }

    public function failed(): int
    {
        return $this->repository->failed();
    }

    public function completed(): int
    {
        return $this->repository->completed();
    }

    public function health(): array
    {
        return [

            'queued' => $this->queueSize(),

            'running' => $this->running(),

            'completed' => $this->completed(),

            'failed' => $this->failed(),

            'calendar_pending' => $this->scheduler->pending(),

            'calendar_processing' => $this->scheduler->processing()

        ];
    }
}