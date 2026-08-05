<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/PromptTemplate.php';

class PromptRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?PromptTemplate
    {
        $stmt = $this->db->prepare(
            "SELECT *
             FROM prompt_templates
             WHERE id=:id
             LIMIT 1"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findActiveByCode(
        string $code
    ): ?PromptTemplate {

        $stmt = $this->db->prepare(

            "SELECT *
             FROM prompt_templates
             WHERE code=:code
             AND active=1
             ORDER BY version DESC
             LIMIT 1"

        );

        $stmt->execute([

            ':code' => $code

        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findAll(): array
    {
        $stmt = $this->db->query(

            "SELECT *
             FROM prompt_templates
             ORDER BY category,
                      code,
                      version DESC"

        );

        $list = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $list[] = $this->hydrate($row);

        }

        return $list;
    }

    public function findByCategory(
        string $category
    ): array {

        $stmt = $this->db->prepare(

            "SELECT *
             FROM prompt_templates
             WHERE category=:category
             ORDER BY code,
                      version DESC"

        );

        $stmt->execute([

            ':category' => $category

        ]);

        $items = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $items[] = $this->hydrate($row);

        }

        return $items;
    }

    public function save(
        PromptTemplate $prompt
    ): PromptTemplate {

        if ($prompt->getId() > 0) {

            $this->update($prompt);

            return $prompt;

        }

        $stmt = $this->db->prepare(

            "INSERT INTO prompt_templates
            (
                code,
                category,
                version,
                name,
                description,
                content,
                variables,
                provider,
                model,
                temperature,
                max_tokens,
                active,
                created_at,
                updated_at
            )
            VALUES
            (
                :code,
                :category,
                :version,
                :name,
                :description,
                :content,
                :variables,
                :provider,
                :model,
                :temperature,
                :max_tokens,
                :active,
                NOW(),
                NOW()
            )"

        );

        $stmt->execute([

            ':code' => $prompt->getCode(),

            ':category' => $prompt->getCategory(),

            ':version' => $prompt->getVersion(),

            ':name' => $prompt->getName(),

            ':description' => $prompt->getDescription(),

            ':content' => $prompt->getContent(),

            ':variables' => json_encode(
                $prompt->getVariables(),
                JSON_UNESCAPED_UNICODE
            ),

            ':provider' => $prompt->getProvider(),

            ':model' => $prompt->getModel(),

            ':temperature' => $prompt->getTemperature(),

            ':max_tokens' => $prompt->getMaxTokens(),

            ':active' => $prompt->isActive() ? 1 : 0

        ]);

        $prompt->setId(
            (int)$this->db->lastInsertId()
        );

        return $prompt;
    }

    public function update(
        PromptTemplate $prompt
    ): void {

        $stmt = $this->db->prepare(

            "UPDATE prompt_templates
             SET
                name=:name,
                description=:description,
                content=:content,
                variables=:variables,
                provider=:provider,
                model=:model,
                temperature=:temperature,
                max_tokens=:max_tokens,
                active=:active,
                updated_at=NOW()
             WHERE id=:id"

        );

        $stmt->execute([

            ':name' => $prompt->getName(),

            ':description' => $prompt->getDescription(),

            ':content' => $prompt->getContent(),

            ':variables' => json_encode(
                $prompt->getVariables(),
                JSON_UNESCAPED_UNICODE
            ),

            ':provider' => $prompt->getProvider(),

            ':model' => $prompt->getModel(),

            ':temperature' => $prompt->getTemperature(),

            ':max_tokens' => $prompt->getMaxTokens(),

            ':active' => $prompt->isActive() ? 1 : 0,

            ':id' => $prompt->getId()

        ]);
    }

    public function deactivate(
        int $id
    ): void {

        $stmt = $this->db->prepare(

            "UPDATE prompt_templates
             SET active=0
             WHERE id=:id"

        );

        $stmt->execute([

            ':id' => $id

        ]);
    }

    public function activate(
        int $id
    ): void {

        $prompt = $this->findById($id);

        if ($prompt === null) {
            return;
        }

        $stmt = $this->db->prepare(

            "UPDATE prompt_templates
             SET active=0
             WHERE code=:code"

        );

        $stmt->execute([

            ':code' => $prompt->getCode()

        ]);

        $stmt = $this->db->prepare(

            "UPDATE prompt_templates
             SET active=1
             WHERE id=:id"

        );

        $stmt->execute([

            ':id' => $id

        ]);
    }

    public function delete(
        int $id
    ): void {

        $stmt = $this->db->prepare(

            "DELETE
             FROM prompt_templates
             WHERE id=:id"

        );

        $stmt->execute([

            ':id' => $id

        ]);
    }

    private function hydrate(
        array $row
    ): PromptTemplate {

        $prompt = new PromptTemplate();

        $prompt->setId((int)$row['id']);

        $prompt->setCode($row['code']);

        $prompt->setCategory($row['category']);

        $prompt->setVersion((int)$row['version']);

        $prompt->setName($row['name']);

        $prompt->setDescription($row['description']);

        $prompt->setContent($row['content']);

        $prompt->setVariables(

            json_decode(
                $row['variables'] ?? '[]',
                true
            ) ?: []

        );

        $prompt->setProvider(
            $row['provider'] ?? DEFAULT_AI_PROVIDER
        );

        $prompt->setModel(
            $row['model'] ?? OPENAI_MODEL
        );

        $prompt->setTemperature(
            (float)$row['temperature']
        );

        $prompt->setMaxTokens(
            (int)$row['max_tokens']
        );

        $prompt->setActive(
            (bool)$row['active']
        );

        $prompt->setCreatedAt(
            $row['created_at']
        );

        $prompt->setUpdatedAt(
            $row['updated_at']
        );

        return $prompt;
    }
}