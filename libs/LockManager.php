<?php
declare(strict_types=1);

class LockManager
{

    private DB $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    public function acquire(string $name): bool
    {

        $name = $this->db->escapeString($name);

        $sql = "SELECT GET_LOCK('{$name}',0) AS l";

        $res = $this->db->executeQuery($sql);

        $row = $this->db->fetchAssoc($res);

        return ((int)$row['l']) === 1;

    }

    public function release(string $name): void
    {

        $name = $this->db->escapeString($name);

        $sql = "SELECT RELEASE_LOCK('{$name}')";

        $this->db->executeQuery($sql);

    }

}