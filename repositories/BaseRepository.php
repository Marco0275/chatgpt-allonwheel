<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Repository: BaseRepository
 * ------------------------------------------------------------
 */

abstract class BaseRepository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::instance()->pdo();
    }

    /**
     * Esegue una query SELECT e restituisce tutte le righe.
     */
    protected function fetchAll(
        string $sql,
        array $params = []
    ): array {

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Esegue una query SELECT e restituisce una sola riga.
     */
    protected function fetchOne(
        string $sql,
        array $params = []
    ): ?array {

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Esegue INSERT / UPDATE / DELETE.
     */
    protected function execute(
        string $sql,
        array $params = []
    ): bool {

        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Restituisce l'ultimo ID inserito.
     */
    protected function lastInsertId(): int
    {
        return (int)$this->db->lastInsertId();
    }

    /**
     * Conta i record.
     */
    protected function count(
        string $sql,
        array $params = []
    ): int {

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Inizia transazione.
     */
    public function begin(): bool
    {
        return $this->db->beginTransaction();
    }

    /**
     * Commit.
     */
    public function commit(): bool
    {
        return $this->db->commit();
    }

    /**
     * Rollback.
     */
    public function rollback(): bool
    {
        if ($this->db->inTransaction()) {
            return $this->db->rollBack();
        }

        return false;
    }

    /**
     * Restituisce PDO.
     */
    public function connection(): PDO
    {
        return $this->db;
    }
}