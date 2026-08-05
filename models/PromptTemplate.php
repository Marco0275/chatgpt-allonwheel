<?php
declare(strict_types=1);

class PromptTemplate
{
    private int $id = 0;

    private string $code = '';

    private string $category = '';

    private int $version = 1;

    private string $name = '';

    private string $description = '';

    private string $content = '';

    private array $variables = [];

    private string $provider = '';

    private string $model = '';

    private float $temperature = 0.7;

    private int $maxTokens = 4096;

    private bool $active = true;

    private string $createdAt = '';

    private string $updatedAt = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $value): self
    {
        $this->id = $value;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $value): self
    {
        $this->code = trim($value);
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $value): self
    {
        $this->category = trim($value);
        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $value): self
    {
        $this->version = $value;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $value): self
    {
        $this->name = trim($value);
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $value): self
    {
        $this->description = trim($value);
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $value): self
    {
        $this->content = trim($value);
        return $this;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function setVariables(array $value): self
    {
        $this->variables = $value;
        return $this;
    }

    public function addVariable(string $variable): self
    {
        if (!in_array($variable, $this->variables, true)) {
            $this->variables[] = $variable;
        }

        return $this;
    }

    public function hasVariable(string $variable): bool
    {
        return in_array($variable, $this->variables, true);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $value): self
    {
        $this->provider = trim($value);
        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $value): self
    {
        $this->model = trim($value);
        return $this;
    }

    public function getTemperature(): float
    {
        return $this->temperature;
    }

    public function setTemperature(float $value): self
    {
        $this->temperature = $value;
        return $this;
    }

    public function getMaxTokens(): int
    {
        return $this->maxTokens;
    }

    public function setMaxTokens(int $value): self
    {
        $this->maxTokens = $value;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $value): self
    {
        $this->active = $value;
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $value): self
    {
        $this->createdAt = $value;
        return $this;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(string $value): self
    {
        $this->updatedAt = $value;
        return $this;
    }

    public function toArray(): array
    {
        return [

            'id' => $this->id,

            'code' => $this->code,

            'category' => $this->category,

            'version' => $this->version,

            'name' => $this->name,

            'description' => $this->description,

            'content' => $this->content,

            'variables' => $this->variables,

            'provider' => $this->provider,

            'model' => $this->model,

            'temperature' => $this->temperature,

            'max_tokens' => $this->maxTokens,

            'active' => $this->active,

            'created_at' => $this->createdAt,

            'updated_at' => $this->updatedAt

        ];
    }
}