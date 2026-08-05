<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/WorkflowJob.php';
require_once __DIR__ . '/../repositories/WorkflowJobRepository.php';

class WorkflowQueue
{
    private WorkflowJobRepository $repository;

    public function __construct(PDO $db)
    {
        $this->repository = new WorkflowJobRepository(
            $db
        );
    }

    public function push(
        string $type,
        int $articleId,
        array $payload = [],
        int $priority = 100
    ): int {

        $job = new WorkflowJob();

        $job->setArticleId(
            $articleId
        );

        $job->setType(
            $type
        );

        $job->setPriority(
            $priority
        );

        $job->setStatus(
            'queued'
        );

        $job->setPayload(
            $payload
        );

        return $this->repository->insert(
            $job
        );

    }

    public function next(): ?WorkflowJob
    {
        return $this->repository->next();
    }

    public function running(
        WorkflowJob $job
    ): void {

        $job->setStatus(
            'running'
        );

        $job->setStartedAt(
            new DateTimeImmutable()
        );

        $this->repository->update(
            $job
        );

    }

    public function completed(
        WorkflowJob $job
    ): void {

        $job->setStatus(
            'completed'
        );

        $job->setFinishedAt(
            new DateTimeImmutable()
        );

        $this->repository->update(
            $job
        );

    }

    public function failed(
        WorkflowJob $job,
        Throwable $exception
    ): void {

        $job->setStatus(
            'failed'
        );

        $job->setFinishedAt(
            new DateTimeImmutable()
        );

        $job->setError(
            $exception->getMessage()
        );

        $this->repository->update(
            $job
        );

    }

    public function retry(
        WorkflowJob $job
    ): void {

        $job->setAttempts(
            $job->getAttempts() + 1
        );

        $job->setStatus(
            'queued'
        );

        $job->setStartedAt(
            null
        );

        $job->setFinishedAt(
            null
        );

        $job->setError(
            null
        );

        $this->repository->update(
            $job
        );

    }

    public function cancel(
        WorkflowJob $job
    ): void {

        $job->setStatus(
            'cancelled'
        );

        $this->repository->update(
            $job
        );

    }

    public function size(): int
    {
        return $this->repository->countQueued();
    }

    public function clearCompleted(): void
    {
        $this->repository->deleteCompleted();
    }

    public function resetRunning(): void
    {
        $this->repository->resetRunning();
    }

    public function queuedJobs(): array
    {
        return $this->repository->findByStatus(
            'queued'
        );
    }

    public function runningJobs(): array
    {
        return $this->repository->findByStatus(
            'running'
        );
    }

    public function failedJobs(): array
    {
        return $this->repository->findByStatus(
            'failed'
        );
    }

    public function completedJobs(): array
    {
        return $this->repository->findByStatus(
            'completed'
        );
    }
}