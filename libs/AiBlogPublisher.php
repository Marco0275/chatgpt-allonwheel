<?php
declare(strict_types=1);

require_once __DIR__ . '/blog.class.php';
require_once __DIR__ . '/AiBlogClient.php';

/**
 * Autopublisher AI: 1 articolo/giorno dal piano editoriale, pubblicato in IT
 * e tradotto/archiviato in EN/FR/DE. Usa BlogManager (stessa via di blog_save)
 * per l'inserimento. Idempotente sul giorno. PDO ovunque.
 */
class AiBlogPublisher
{
    private PDO $pdo;
    private BlogManager $blog;
    private AiBlogClientInterface $ai;
    private int $authorId;
    private string $source;
    /** @var string[] */
    private array $targets;
    private int $humanJobsPerRun;

    public function __construct(
        PDO $pdo,
        BlogManager $blog,
        AiBlogClientInterface $ai,
        int $authorId,
        string $sourceLang = 'it',
        array $targetLangs = ['en', 'fr', 'de'],
        int $humanJobsPerRun = 3
    ) {
        $this->pdo = $pdo;
        $this->blog = $blog;
        $this->ai = $ai;
        $this->authorId = $authorId;
        $this->source = strtolower(substr($sourceLang, 0, 2));
        $this->targets = array_values(array_filter(array_map(
            static fn($l) => strtolower(substr((string)$l, 0, 2)),
            $targetLangs
        ), fn($l) => $l !== '' && $l !== $this->source));
        $this->humanJobsPerRun = max(0, $humanJobsPerRun);
    }

    /** Esecuzione giornaliera. Ritorna un riepilogo. */
    public function runDaily(): array
    {
        $out = ['ai_article' => null, 'translations' => 0, 'human_jobs' => 0, 'skipped' => null];

        if ($this->authorId <= 0) {
            $out['skipped'] = 'author non risolto (email autore inesistente): nessuna pubblicazione';
            return $out;
        }

        if (!$this->reserveToday()) {
            $out['skipped'] = 'gia\' eseguito oggi';
            // anche se saltiamo l'articolo, proviamo comunque a smaltire i job umani
            $out['human_jobs'] = $this->processHumanJobs();
            return $out;
        }

        $plan = $this->takeNextPlan();
        if ($plan !== null) {
            try {
                $res = $this->produceFromPlan($plan);
                $out['ai_article'] = $res['blog_id'];
                $out['translations'] = $res['translations'];
                $this->markPlanDone((int)$plan['id'], (int)$res['blog_id']);
                $this->pdo->prepare("UPDATE ai_daily_log SET blog_id=:b WHERE run_date=CURDATE()")
                          ->execute([':b' => (int)$res['blog_id']]);
            } catch (Throwable $e) {
                $this->markPlanError((int)$plan['id']);
                // libera lo slot del giorno cosi' il prossimo cron puo' riprovare
                $this->pdo->query("DELETE FROM ai_daily_log WHERE run_date=CURDATE() AND blog_id IS NULL");
                $out['skipped'] = 'errore generazione: ' . $e->getMessage();
            }
        } else {
            $out['skipped'] = 'nessuna riga di piano PENDING';
            $this->pdo->query("DELETE FROM ai_daily_log WHERE run_date=CURDATE() AND blog_id IS NULL");
        }

        $out['human_jobs'] = $this->processHumanJobs();
        return $out;
    }

    /** Genera l'articolo IT dal piano, lo pubblica e lo traduce. */
    private function produceFromPlan(array $plan): array
    {
        $group = $this->uuid();
        $article = $this->ai->generateArticle($plan, $this->source);
        if (($article['title'] ?? '') === '' || ($article['body'] ?? '') === '') {
            throw new RuntimeException('AI: articolo IT vuoto');
        }
        if (($article['category'] ?? '') === '') { $article['category'] = (string)($plan['category'] ?? ''); }

        $blogId = $this->publishOne($article, $this->source, $group);
        $tx = $this->publishTranslations($article, $group);
        return ['blog_id' => $blogId, 'translations' => $tx];
    }

    /** Traduce e pubblica nelle lingue target. Ritorna quante ne ha salvate. */
    private function publishTranslations(array $article, string $group): int
    {
        $n = 0;
        foreach ($this->targets as $to) {
            try {
                $tr = $this->ai->translate($article, $this->source, $to);
                if (($tr['title'] ?? '') === '' || ($tr['body'] ?? '') === '') { continue; }
                if (($tr['category'] ?? '') === '') { $tr['category'] = $article['category'] ?? ''; }
                $this->publishOne($tr, $to, $group);
                $n++;
            } catch (Throwable $e) {
                error_log('[AiBlogPublisher] traduzione ' . $to . ' fallita: ' . $e->getMessage());
            }
        }
        return $n;
    }

    private function publishOne(array $a, string $lang, string $group): int
    {
        return $this->blog->insertLocalized([
            'id_user'           => $this->authorId,
            'title'             => $a['title'] ?? '',
            'excerpt'           => $a['excerpt'] ?? '',
            'body'              => $a['body'] ?? '',
            'category'          => $a['category'] ?? null,
            'faq'               => $a['faq'] ?? null,
            'outlines'          => $a['outlines'] ?? null,
            'status'            => 'published',
            'language'          => $lang,
            'translation_group' => $group,
            'source'            => 'ai',
        ]);
    }

    /* ---------- piano editoriale (editorial_queue) ---------- */

    private function takeNextPlan(): ?array
    {
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->query(
                "SELECT * FROM editorial_queue
                 WHERE status='NEW' AND publish_at<=NOW()
                 ORDER BY priority ASC, publish_at ASC, id ASC
                 LIMIT 1 FOR UPDATE"
            );
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) { $this->pdo->commit(); return null; }
            $this->pdo->prepare("UPDATE editorial_queue SET status='PROCESSING' WHERE id=:id")
                      ->execute([':id' => $row['id']]);
            $this->pdo->commit();
            return $row;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            throw $e;
        }
    }

    private function markPlanDone(int $id, int $blogId): void
    {
        $this->pdo->prepare("UPDATE editorial_queue SET status='PUBLISHED', blog_id=:b WHERE id=:id")
                  ->execute([':b' => $blogId, ':id' => $id]);
    }
    private function markPlanError(int $id): void
    {
        $this->pdo->prepare("UPDATE editorial_queue SET status='ERROR' WHERE id=:id")->execute([':id' => $id]);
    }

    /* ---------- guardia 1/giorno ---------- */

    private function reserveToday(): bool
    {
        $st = $this->pdo->prepare("INSERT IGNORE INTO ai_daily_log (run_date) VALUES (CURDATE())");
        $st->execute();
        return $st->rowCount() > 0;
    }

    /* ---------- traduzioni post UMANI (async) ---------- */

    private function processHumanJobs(): int
    {
        $done = 0;
        for ($i = 0; $i < $this->humanJobsPerRun; $i++) {
            $this->pdo->beginTransaction();
            try {
                $job = $this->pdo->query(
                    "SELECT * FROM blog_translation_jobs WHERE status='pending'
                     ORDER BY id ASC LIMIT 1 FOR UPDATE"
                )->fetch(PDO::FETCH_ASSOC);
                if (!$job) { $this->pdo->commit(); break; }
                $this->pdo->prepare("UPDATE blog_translation_jobs SET status='done' WHERE id=:id")
                          ->execute([':id' => $job['id']]);
                $this->pdo->commit();
            } catch (Throwable $e) {
                if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
                break;
            }
            try {
                $this->translateHumanPost($job);
                $done++;
            } catch (Throwable $e) {
                $this->pdo->prepare("UPDATE blog_translation_jobs SET status='error', error_message=:m WHERE id=:id")
                          ->execute([':m' => substr($e->getMessage(), 0, 255), ':id' => $job['id']]);
            }
        }
        return $done;
    }

    private function translateHumanPost(array $job): void
    {
        $src = $this->pdo->prepare("SELECT * FROM `blog` WHERE id=:id LIMIT 1");
        $src->execute([':id' => $job['blog_id']]);
        $row = $src->fetch(PDO::FETCH_ASSOC);
        if (!$row) { return; }
        $from  = strtolower(substr((string)$job['from_lang'], 0, 2));
        $group = (string)$job['translation_group'];
        $article = [
            'title'    => $row['title'] ?? '',
            'excerpt'  => $row['excerpt'] ?? '',
            'body'     => $row['body'] ?? '',
            'category' => $row['category'] ?? '',
            'faq'      => [],
            'outlines' => [],
        ];
        foreach ($this->allLangsExcept($from) as $to) {
            $tr = $this->ai->translate($article, $from, $to);
            if (($tr['title'] ?? '') === '' || ($tr['body'] ?? '') === '') { continue; }
            $tr['category'] = $tr['category'] ?? $article['category'];
            $this->blog->insertLocalized([
                'id_user'           => (int)$row['id_user'],
                'title'             => $tr['title'],
                'excerpt'           => $tr['excerpt'] ?? '',
                'body'              => $tr['body'],
                'category'          => $tr['category'],
                'status'            => 'published',
                'language'          => $to,
                'translation_group' => $group,
                'source'            => 'human-translation',
            ]);
        }
    }

    private function allLangsExcept(string $from): array
    {
        $all = array_unique(array_merge([$this->source], $this->targets, ['en']));
        return array_values(array_filter($all, fn($l) => $l !== $from));
    }

    private function uuid(): string
    {
        $d = random_bytes(16);
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
    }
}
