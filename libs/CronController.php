<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/WorkflowExecutor.php';
require_once __DIR__ . '/PublishScheduler.php';
require_once __DIR__ . '/WorkflowRepository.php';
require_once __DIR__ . '/Logger.php';

class CronController
{
    private PDO $db;

    private WorkflowExecutor $executor;

    private PublishScheduler $scheduler;

    private WorkflowRepository $repository;

    private Logger $logger;

    public function __construct(PDO $db)
    {
        $this->db = $db;

        $this->executor = new WorkflowExecutor($db);

        $this->scheduler = new PublishScheduler($db);

        $this->repository = new WorkflowRepository($db);

        $this->logger = new Logger();
    }

    public function run(): void
    {
        $this->logger->info('Cron started');

        $this->recover();

        $this->enqueueCalendar();

        $this->executor->execute();

        $this->logger->info('Cron completed');
    }

    private function recover(): void
    {
        $reset = $this->scheduler
            ->resetStalled(60);

        if ($reset > 0) {

            $this->logger->warning(

                'Recovered stalled jobs',

                [

                    'count' => $reset

                ]

            );

        }
    }

    private function enqueueCalendar(): void
    {
        while (true) {

            $article = $this->scheduler
                ->getNextArticle();

            if (!$article) {

                break;

            }

            $workflowId = $this->repository
                ->create([

                    'calendar_id' => $article['id'],

                    'title'       => $article['title'],

                    'language'    => $article['language'],

                    'status'      => 'queued'

                ]);

            $this->scheduler
                ->reserve(
                    (int)$article['id']
                );

            $this->logger->info(

                'Calendar article queued',

                [

                    'calendar' => $article['id'],

                    'workflow' => $workflowId

                ]

            );
        }
    }

    public function runOnce(): void
    {
        $this->run();
    }

    public function health(): array
    {
        return $this->executor
            ->health();
    }
}