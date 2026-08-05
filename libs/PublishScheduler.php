<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class PublishScheduler
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getNextArticle(): ?array
    {
        $stmt = $this->db->prepare(

            "SELECT *

             FROM ai_calendar

             WHERE status='planned'

             AND publish_date<=CURDATE()

             ORDER BY publish_date ASC,id ASC

             LIMIT 1"

        );

        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function reserve(int $id): void
    {
        $stmt = $this->db->prepare(

            "UPDATE ai_calendar

             SET status='processing',

                 started_at=NOW()

             WHERE id=:id"

        );

        $stmt->execute([

            ':id'=>$id

        ]);
    }

    public function complete(
        int $id,
        int $articleId
    ): void
    {
        $stmt = $this->db->prepare(

            "UPDATE ai_calendar

             SET

                status='published',

                ai_article_id=:article,

                completed_at=NOW()

             WHERE id=:id"

        );

        $stmt->execute([

            ':id'=>$id,

            ':article'=>$articleId

        ]);
    }

    public function fail(
        int $id,
        string $error
    ): void
    {
        $stmt = $this->db->prepare(

            "UPDATE ai_calendar

             SET

                status='failed',

                error_message=:error,

                completed_at=NOW()

             WHERE id=:id"

        );

        $stmt->execute([

            ':id'=>$id,

            ':error'=>$error

        ]);
    }

    public function pending(): int
    {
        return (int)$this->db
            ->query(

                "SELECT COUNT(*)

                 FROM ai_calendar

                 WHERE status='planned'"

            )
            ->fetchColumn();
    }

    public function processing(): int
    {
        return (int)$this->db
            ->query(

                "SELECT COUNT(*)

                 FROM ai_calendar

                 WHERE status='processing'"

            )
            ->fetchColumn();
    }

    public function published(): int
    {
        return (int)$this->db
            ->query(

                "SELECT COUNT(*)

                 FROM ai_calendar

                 WHERE status='published'"

            )
            ->fetchColumn();
    }

    public function failed(): int
    {
        return (int)$this->db
            ->query(

                "SELECT COUNT(*)

                 FROM ai_calendar

                 WHERE status='failed'"

            )
            ->fetchColumn();
    }

    public function resetStalled(
        int $minutes=60
    ): int
    {
        $stmt=$this->db->prepare(

            "UPDATE ai_calendar

             SET status='planned'

             WHERE status='processing'

             AND started_at<

             DATE_SUB(
                NOW(),
                INTERVAL :m MINUTE
             )"

        );

        $stmt->bindValue(
            ':m',
            $minutes,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->rowCount();
    }

    public function upcoming(
        int $limit=20
    ): array
    {
        $stmt=$this->db->prepare(

            "SELECT *

             FROM ai_calendar

             ORDER BY publish_date ASC

             LIMIT :limit"

        );

        $stmt->bindValue(
            ':limit',
            $limit,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}