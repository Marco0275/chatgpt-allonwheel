<?php
declare(strict_types=1);

class EditorialQueue
{

    private DB $db;

    public function __construct(DB $db)
    {
        $this->db = $db;
    }

    /**
     * Inserisce un nuovo record
     */
    public function create(array $data): int
    {

        $uuid = $this->db->escapeString($data['uuid']);
        $publish = $this->db->escapeString($data['publish_at']);
        $language = $this->db->escapeString($data['language']);
        $category = $this->db->escapeString($data['category']);
        $title = $this->db->escapeString($data['title']);
        $keyword = $this->db->escapeString($data['keyword']);
        $secondary = $this->db->escapeString($data['secondary_keywords']);
        $words = (int)$data['target_words'];
        $priority = (int)$data['priority'];
        $sourceFile = $this->db->escapeString($data['source_file']);
        $sourceRow = (int)$data['source_row'];

        $sql = "

        INSERT INTO editorial_queue
        (
            uuid,
            publish_at,
            priority,
            language,
            category,
            title,
            keyword,
            secondary_keywords,
            target_words,
            source_file,
            source_row
        )

        VALUES
        (

            '{$uuid}',

            '{$publish}',

            {$priority},

            '{$language}',

            '{$category}',

            '{$title}',

            '{$keyword}',

            '{$secondary}',

            {$words},

            '{$sourceFile}',

            {$sourceRow}

        )

        ";

        $this->db->executeInsert($sql);

        return (int)$this->db->lastInsertId();

    }

    /**
     * Prossimo articolo da elaborare
     */
    public function getNext(): ?array
    {

        $now = date('Y-m-d H:i:s');

        $sql = "

        SELECT *

        FROM editorial_queue

        WHERE

            status='PENDING'

        AND

            publish_at<='{$now}'

        ORDER BY

            priority ASC,

            publish_at ASC,

            id ASC

        LIMIT 1

        ";

        $res = $this->db->executeQuery($sql);

        if($this->db->numRows($res)==0)
        {
            return null;
        }

        return $this->db->fetchAssoc($res);

    }

    /**
     * Stato RUNNING
     */
    public function markRunning(int $id): void
    {

        $id=(int)$id;

        $sql="

        UPDATE editorial_queue

        SET

            status='RUNNING'

        WHERE

            id={$id}

        ";

        $this->db->executeQuery($sql);

    }

    /**
     * Stato DONE
     */
    public function markDone(
        int $id,
        int $blogId
    ): void
    {

        $id=(int)$id;

        $blogId=(int)$blogId;

        $sql="

        UPDATE editorial_queue

        SET

            status='DONE',

            blog_id={$blogId}

        WHERE

            id={$id}

        ";

        $this->db->executeQuery($sql);

    }

    /**
     * Stato ERROR
     */
    public function markError(
        int $id,
        string $error
    ): void
    {

        $id=(int)$id;

        $error=$this->db->escapeString($error);

        $sql="

        UPDATE editorial_queue

        SET

            status='ERROR',

            retries=retries+1,

            last_error='{$error}'

        WHERE

            id={$id}

        ";

        $this->db->executeQuery($sql);

    }

    /**
     * Legge record
     */
    public function getById(int $id): ?array
    {

        $id=(int)$id;

        $sql="

        SELECT *

        FROM editorial_queue

        WHERE

            id={$id}

        LIMIT 1

        ";

        $res=$this->db->executeQuery($sql);

        if($this->db->numRows($res)==0)
        {
            return null;
        }

        return $this->db->fetchAssoc($res);

    }
	public function resetRunning(): void
{

    $sql="

    UPDATE editorial_queue

    SET

        status='PENDING'

    WHERE

        status='RUNNING'

    ";

    $this->db->executeQuery($sql);

}
	public function pendingCount(): int
{

    $sql="

        SELECT COUNT(*) c

        FROM editorial_queue

        WHERE status='PENDING'

    ";

    $res=$this->db->executeQuery($sql);

    $row=$this->db->fetchAssoc($res);

    return (int)$row['c'];

}

}