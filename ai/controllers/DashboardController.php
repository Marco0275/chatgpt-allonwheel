<?php
declare(strict_types=1);

require_once __DIR__ . '/../../repositories/WorkflowRepository.php';
require_once __DIR__ . '/../../repositories/PromptRepository.php';
require_once __DIR__ . '/../../libs/QueueManager.php';

class DashboardController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function data(): array
    {
        $queue = new QueueManager($this->db);

        $workflowRepository = new WorkflowRepository($this->db);

        $promptRepository = new PromptRepository($this->db);

        return [

            'generated_today' => $this->generatedToday(),

            'published_today' => $this->publishedToday(),

            'queue_pending' => count(
                $queue->pending()
            ),

            'queue_running' => count(
                $queue->running()
            ),

            'queue_failed' => count(
                $queue->failed()
            ),

            'workflows' => count(
                $workflowRepository->findAll()
            ),

            'prompts' => count(
                $promptRepository->findAll()
            )

        ];
    }

    private function generatedToday(): int
    {
        return (int)$this->db
            ->query("
                SELECT COUNT(*)
                FROM blog
                WHERE DATE(created_at)=CURDATE()
            ")
            ->fetchColumn();
    }

    private function publishedToday(): int
    {
        return (int)$this->db
            ->query("
                SELECT COUNT(*)
                FROM blog
                WHERE status='published'
                AND DATE(published_at)=CURDATE()
            ")
            ->fetchColumn();
    }
}