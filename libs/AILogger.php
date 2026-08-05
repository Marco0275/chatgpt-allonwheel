<?php
declare(strict_types=1);

class AILogger
{

    private DB $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    public function write(
        int $queueId,
        string $phase,
        string $status,
        string $message = '',
        int $duration = 0
    ): void {

        $queueId = (int)$queueId;
        $phase = $this->db->escapeString($phase);
        $status = $this->db->escapeString($status);
        $message = $this->db->escapeString($message);
        $duration = (int)$duration;

        $sql = "

            INSERT INTO ai_logs

            (

                queue_id,

                phase,

                status,

                message,

                duration_ms

            )

            VALUES

            (

                {$queueId},

                '{$phase}',

                '{$status}',

                '{$message}',

                {$duration}

            )

        ";

        $this->db->executeInsert($sql);

    }
	public function info(
    int $queueId,
    string $phase,
    string $message
): void
{

    $this->write(

        $queueId,

        $phase,

        'INFO',

        $message

    );

}
	public function error(
    int $queueId,
    string $phase,
    string $message
): void
{

    $this->write(

        $queueId,

        $phase,

        'ERROR',

        $message

    );

}
	public function success(
    int $queueId,
    string $phase,
    string $message
): void
{

    $this->write(

        $queueId,

        $phase,

        'SUCCESS',

        $message

    );

}

}