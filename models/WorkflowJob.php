<?php
declare(strict_types=1);

class WorkflowJob
{
    private int $id = 0;

    private int $articleId = 0;

    private string $type = '';

    private string $status = 'queued';

    private int $priority = 100;

    private int $attempts = 0;

    private int $maxAttempts = 3;

    private array $payload = [];

    private ?string $error = null;

    private ?DateTimeImmutable $createdAt = null;

    private ?DateTimeImmutable $startedAt = null;

    private ?DateTimeImmutable $finishedAt = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(
        int $id
    ): self {

        $this->id = $id;

        return $this;

    }

    public function getArticleId(): int
    {
        return $this->articleId;
    }

    public function setArticleId(
        int $articleId
    ): self {

        $this->articleId = $articleId;

        return $this;

    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(
        string $type
    ): self {

        $this->type = $type;

        return $this;

    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(
        string $status
    ): self {

        $this->status = $status;

        return $this;

    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(
        int $priority
    ): self {

        $this->priority = $priority;

        return $this;

    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function setAttempts(
        int $attempts
    ): self {

        $this->attempts = $attempts;

        return $this;

    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function setMaxAttempts(
        int $maxAttempts
    ): self {

        $this->maxAttempts = $maxAttempts;

        return $this;

    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(
        array $payload
    ): self {

        $this->payload = $payload;

        return $this;

    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(
        ?string $error
    ): self {

        $this->error = $error;

        return $this;

    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(
        ?DateTimeImmutable $createdAt
    ): self {

        $this->createdAt = $createdAt;

        return $this;

    }

    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(
        ?DateTimeImmutable $startedAt
    ): self {

        $this->startedAt = $startedAt;

        return $this;

    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(
        ?DateTimeImmutable $finishedAt
    ): self {

        $this->finishedAt = $finishedAt;

        return $this;

    }

    public function isQueued(): bool
    {
        return $this->status === 'queued';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canRetry(): bool
    {
        return
            $this->attempts < $this->maxAttempts;
    }

    public function increaseAttempts(): self
    {
        $this->attempts++;

        return $this;
    }

    public function toArray(): array
    {
        return [

            'id' => $this->id,

            'article_id' => $this->articleId,

            'type' => $this->type,

            'status' => $this->status,

            'priority' => $this->priority,

            'attempts' => $this->attempts,

            'max_attempts' => $this->maxAttempts,

            'payload' => $this->payload,

            'error' => $this->error,

            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),

            'started_at' => $this->startedAt?->format('Y-m-d H:i:s'),

            'finished_at' => $this->finishedAt?->format('Y-m-d H:i:s')

        ];
    }
}