<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Library: QueueManager
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/../repositories/BaseRepository.php';

class QueueManager extends BaseRepository
{
    /**
     * Inserisce un job in coda.
     */
    public function push(
        string $type,
        array $payload,
        int $priority = 100
    ): int {

        $this->execute(
            "
            INSERT INTO queue
            (
                type,
                payload,
                priority,
                status,
                attempts,
                created_at
            )
            VALUES
            (
                :type,
                :payload,
                :priority,
                'pending',
                0,
                NOW()
            )
            ",
            [
                ':type'     => $type,
                ':payload'  => json_encode(
                    $payload,
                    JSON_UNESCAPED_UNICODE
                ),
                ':priority' => $priority
            ]
        );

        return $this->lastInsertId();
    }

    /**
     * Estrae il prossimo job disponibile.
     */
    public function pop(): ?array
    {
        $this->begin();

        try {

            $job = $this->fetchOne(
                "
                SELECT *
                FROM queue
                WHERE status='pending'
                ORDER BY priority ASC,
                         id ASC
                LIMIT 1
                FOR UPDATE
                "
            );

            if (!$job) {

                $this->commit();

                return null;
            }

            $this->execute(
                "
                UPDATE queue
                SET
                    status='running',
                    started_at=NOW()
                WHERE id=:id
                ",
                [
                    ':id' => $job['id']
                ]
            );

            $this->commit();

            $job['payload'] = json_decode(
                $job['payload'],
                true
            );

            return $job;

        } catch (Throwable $e) {

            $this->rollback();

            throw $e;
        }
    }

    /**
     * Job completato.
     */
    public function complete(
        int $id,
        ?string $result = null
    ): bool {

        return $this->execute(
            "
            UPDATE queue
            SET

                status='completed',
                finished_at=NOW(),
                result=:result

            WHERE id=:id
            ",
            [
                ':id'     => $id,
                ':result' => $result
            ]
        );
    }

    /**
     * Job fallito.
     */
    public function fail(
        int $id,
        string $message
    ): bool {

        return $this->execute(
            "
            UPDATE queue
            SET

                status='failed',
                attempts=attempts+1,
                error_message=:message,
                finished_at=NOW()

            WHERE id=:id
            ",
            [
                ':id'      => $id,
                ':message' => $message
            ]
        );
    }

    /**
     * Rimette in coda un job.
     */
    public function retry(
        int $id
    ): bool {

        return $this->execute(
            "
            UPDATE queue
            SET

                status='pending',
                started_at=NULL,
                finished_at=NULL

            WHERE id=:id
            ",
            [
                ':id' => $id
            ]
        );
    }

    /**
     * Job in esecuzione.
     */
    public function running(): array
    {
        return $this->fetchAll(
            "
            SELECT *
            FROM queue
            WHERE status='running'
            ORDER BY started_at ASC
            "
        );
    }

    /**
     * Job pendenti.
     */
    public function pending(): array
    {
        return $this->fetchAll(
            "
            SELECT *
            FROM queue
            WHERE status='pending'
            ORDER BY priority ASC,id ASC
            "
        );
    }

    /**
     * Job falliti.
     */
    public function failed(): array
    {
        return $this->fetchAll(
            "
            SELECT *
            FROM queue
            WHERE status='failed'
            ORDER BY finished_at DESC
            "
        );
    }

    /**
     * Ripristina eventuali job rimasti "running"
     * dopo uno shutdown improvviso.
     */
    public function resetRunning(): bool
    {
        return $this->execute(
            "
            UPDATE queue
            SET

                status='pending',
                started_at=NULL

            WHERE status='running'
            "
        );
    }

    /**
     * Elimina i job completati.
     */
    public function purgeCompleted(
        int $days = 30
    ): bool {

        return $this->execute(
            "
            DELETE
            FROM queue
            WHERE status='completed'
            AND finished_at < DATE_SUB(NOW(),INTERVAL :days DAY)
            ",
            [
                ':days' => $days
            ]
        );
    }
}