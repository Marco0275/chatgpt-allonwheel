<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/WorkflowJob.php';

class WorkflowJobRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function insert(
        WorkflowJob $job
    ): int {

        $stmt = $this->db->prepare(

            "INSERT INTO ai_workflow_jobs
            (
                article_id,
                type,
                status,
                priority,
                attempts,
                max_attempts,
                payload,
                created_at
            )
            VALUES
            (
                :article_id,
                :type,
                :status,
                :priority,
                :attempts,
                :max_attempts,
                :payload,
                NOW()
            )"

        );

        $stmt->execute([

            ':article_id' => $job->getArticleId(),

            ':type' => $job->getType(),

            ':status' => $job->getStatus(),

            ':priority' => $job->getPriority(),

            ':attempts' => $job->getAttempts(),

            ':max_attempts' => $job->getMaxAttempts(),

            ':payload' => json_encode(
                $job->getPayload(),
                JSON_UNESCAPED_UNICODE
            )

        ]);

        return (int)$this->db->lastInsertId();

    }

    public function update(
        WorkflowJob $job
    ): void {

        $stmt = $this->db->prepare(

            "UPDATE ai_workflow_jobs
            SET

                status=:status,

                attempts=:attempts,

                error=:error,

                started_at=:started,

                finished_at=:finished,

                payload=:payload

            WHERE id=:id"

        );

        $stmt->execute([

            ':status' => $job->getStatus(),

            ':attempts' => $job->getAttempts(),

            ':error' => $job->getError(),

            ':started' => $job->getStartedAt()?->format('Y-m-d H:i:s'),

            ':finished' => $job->getFinishedAt()?->format('Y-m-d H:i:s'),

            ':payload' => json_encode(
                $job->getPayload(),
                JSON_UNESCAPED_UNICODE
            ),

            ':id' => $job->getId()

        ]);

    }

    public function next(): ?WorkflowJob
    {
        $stmt = $this->db->query(

            "SELECT *
             FROM ai_workflow_jobs
             WHERE status='queued'
             ORDER BY priority ASC,id ASC
             LIMIT 1"

        );

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {

            return null;

        }

        return $this->hydrate($row);

    }

    public function find(
        int $id
    ): ?WorkflowJob {

        $stmt = $this->db->prepare(

            "SELECT *
             FROM ai_workflow_jobs
             WHERE id=:id"

        );

        $stmt->execute([

            ':id'=>$id

        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$row){

            return null;

        }

        return $this->hydrate($row);

    }

    public function findByStatus(
        string $status
    ): array {

        $stmt = $this->db->prepare(

            "SELECT *
             FROM ai_workflow_jobs
             WHERE status=:status
             ORDER BY id DESC"

        );

        $stmt->execute([

            ':status'=>$status

        ]);

        $jobs=[];

        while($row=$stmt->fetch(PDO::FETCH_ASSOC)){

            $jobs[]=$this->hydrate($row);

        }

        return $jobs;

    }

    public function countQueued(): int
    {
        return (int)$this->db
            ->query(

                "SELECT COUNT(*)
                 FROM ai_workflow_jobs
                 WHERE status='queued'"

            )
            ->fetchColumn();
    }

    public function deleteCompleted(): void
    {
        $this->db->exec(

            "DELETE
             FROM ai_workflow_jobs
             WHERE status='completed'"

        );
    }

    public function resetRunning(): void
    {
        $this->db->exec(

            "UPDATE ai_workflow_jobs
             SET status='queued'
             WHERE status='running'"

        );
    }

    private function hydrate(
        array $row
    ): WorkflowJob {

        $job = new WorkflowJob();

        $job->setId(
            (int)$row['id']
        );

        $job->setArticleId(
            (int)$row['article_id']
        );

        $job->setType(
            $row['type']
        );

        $job->setStatus(
            $row['status']
        );

        $job->setPriority(
            (int)$row['priority']
        );

        $job->setAttempts(
            (int)$row['attempts']
        );

        $job->setMaxAttempts(
            (int)$row['max_attempts']
        );

        $job->setPayload(

            json_decode(
                $row['payload'] ?? '{}',
                true
            ) ?: []

        );

        $job->setError(
            $row['error']
        );

        if(!empty($row['created_at'])){

            $job->setCreatedAt(

                new DateTimeImmutable(
                    $row['created_at']
                )

            );

        }

        if(!empty($row['started_at'])){

            $job->setStartedAt(

                new DateTimeImmutable(
                    $row['started_at']
                )

            );

        }

        if(!empty($row['finished_at'])){

            $job->setFinishedAt(

                new DateTimeImmutable(
                    $row['finished_at']
                )

            );

        }

        return $job;

    }

}