<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * Library: Database
 * ------------------------------------------------------------
 */

class Database
{
    private static ?Database $instance = null;

    private PDO $pdo;

    /**
     * Costruttore privato
     */
    private function __construct(array $config)
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'] ?? 3306,
            $config['database']
        );

        $this->pdo = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }

    /**
     * Inizializza il singleton
     */
    public static function initialize(array $config): void
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
    }

    /**
     * Restituisce l'istanza
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            throw new RuntimeException('Database not initialized.');
        }

        return self::$instance;
    }

    /**
     * Restituisce PDO
     */
    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Inizia transazione
     */
    public function begin(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback
     */
    public function rollback(): bool
    {
        if ($this->pdo->inTransaction()) {
            return $this->pdo->rollBack();
        }

        return false;
    }

    /**
     * Stato transazione
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
}