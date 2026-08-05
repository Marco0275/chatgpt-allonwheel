<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Repository: WorkflowRepository
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/../models/Workflow.php';
require_once __DIR__ . '/BaseRepository.php';

class WorkflowRepository extends BaseRepository
{
    /**
     * Restituisce un workflow tramite ID.
     */
    public function find(int $id): ?Workflow
    {
        $row = $this->fetchOne(
            "
            SELECT *
            FROM workflows
            WHERE id = :id
            LIMIT 1
            ",
            [
                ':id' => $id
            ]
        );

        return $row ? new Workflow($row) : null;
    }

    /**
     * Restituisce un workflow tramite codice.
     */
    public function findByCode(string $code): ?Workflow
    {
        $row = $this->fetchOne(
            "
            SELECT *
            FROM workflows
            WHERE code = :code
            LIMIT 1
            ",
            [
                ':code' => $code
            ]
        );

        return $row ? new Workflow($row) : null;
    }

    /**
     * Restituisce tutti i workflow.
     */
    public function all(): array
    {
        $rows = $this->fetchAll(
            "
            SELECT *
            FROM workflows
            ORDER BY name ASC
            "
        );

        $workflows = [];

        foreach ($rows as $row) {
            $workflows[] = new Workflow($row);
        }

        return $workflows;
    }

    /**
     * Restituisce i workflow attivi.
     */
    public function enabled(): array
    {
        $rows = $this->fetchAll(
            "
            SELECT *
            FROM workflows
            WHERE enabled = 1
            ORDER BY name ASC
            "
        );

        $workflows = [];

        foreach ($rows as $row) {
            $workflows[] = new Workflow($row);
        }

        return $workflows;
    }

    /**
     * Restituisce i workflow per tipo.
     */
    public function byType(string $type): array
    {
        $rows = $this->fetchAll(
            "
            SELECT *
            FROM workflows
            WHERE type = :type
            AND enabled = 1
            ORDER BY name ASC
            ",
            [
                ':type' => $type
            ]
        );

        $workflows = [];

        foreach ($rows as $row) {
            $workflows[] = new Workflow($row);
        }

        return $workflows;
    }

    /**
     * Inserisce un workflow.
     */
    public function create(Workflow $workflow): int
    {
        $this->execute(
            "
            INSERT INTO workflows
            (
                name,
                code,
                description,
                type,
                steps,
                settings,
                enabled
            )
            VALUES
            (
                :name,
                :code,
                :description,
                :type,
                :steps,
                :settings,
                :enabled
            )
            ",
            [
                ':name'        => $workflow->name,
                ':code'        => $workflow->code,
                ':description' => $workflow->description,
                ':type'        => $workflow->type,
                ':steps'       => $workflow->steps,
                ':settings'    => $workflow->settings,
                ':enabled'     => $workflow->enabled ? 1 : 0
            ]
        );

        return $this->lastInsertId();
    }

    /**
     * Aggiorna un workflow.
     */
    public function update(Workflow $workflow): bool
    {
        return $this->execute(
            "
            UPDATE workflows SET

                name = :name,
                code = :code,
                description = :description,
                type = :type,
                steps = :steps,
                settings = :settings,
                enabled = :enabled,
                updated_at = NOW()

            WHERE id = :id
            ",
            [
                ':id'          => $workflow->id,
                ':name'        => $workflow->name,
                ':code'        => $workflow->code,
                ':description' => $workflow->description,
                ':type'        => $workflow->type,
                ':steps'       => $workflow->steps,
                ':settings'    => $workflow->settings,
                ':enabled'     => $workflow->enabled ? 1 : 0
            ]
        );
    }

    /**
     * Salva un workflow.
     */
    public function save(Workflow $workflow): int|bool
    {
        if ($workflow->id === null) {
            return $this->create($workflow);
        }

        return $this->update($workflow);
    }

    /**
     * Elimina un workflow.
     */
    public function delete(int $id): bool
    {
        return $this->execute(
            "
            DELETE FROM workflows
            WHERE id = :id
            ",
            [
                ':id' => $id
            ]
        );
    }

    /**
     * Verifica l'esistenza del codice.
     */
    public function codeExists(
        string $code,
        ?int $excludeId = null
    ): bool {

        if ($excludeId === null) {

            return $this->count(
                "
                SELECT COUNT(*)
                FROM workflows
                WHERE code = :code
                ",
                [
                    ':code' => $code
                ]
            ) > 0;
        }

        return $this->count(
            "
            SELECT COUNT(*)
            FROM workflows
            WHERE code = :code
            AND id <> :id
            ",
            [
                ':code' => $code,
                ':id'   => $excludeId
            ]
        ) > 0;
    }
}