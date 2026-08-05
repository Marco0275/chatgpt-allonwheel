<?php
declare(strict_types=1);

require_once __DIR__ . '/AIProviderFactory.php';

class ModelManager
{
    private PDO $db;

    private AIProviderInterface $provider;

    public function __construct(PDO $db)
    {
        $this->db = $db;

        $this->provider = AIProviderFactory::create($db);
    }

    public function provider(): AIProviderInterface
    {
        return $this->provider;
    }

    public function providerName(): string
    {
        return $this->provider->getName();
    }

    public function models(): array
    {
        return $this->provider->models();
    }

    public function defaultModel(): string
    {
        return OPENAI_MODEL;
    }

    public function exists(string $model): bool
    {
        return in_array(
            $model,
            $this->models(),
            true
        );
    }

    public function health(): bool
    {
        return $this->provider->health();
    }

    public function select(
        ?string $purpose = null
    ): string {

        $purpose ??= 'default';

        return match ($purpose) {

            'seo' => OPENAI_MODEL,

            'rewrite' => OPENAI_MODEL,

            'translate' => OPENAI_MODEL,

            'faq' => OPENAI_MODEL,

            'schema' => OPENAI_MODEL,

            'tags' => OPENAI_MODEL,

            'social' => OPENAI_MODEL,

            default => OPENAI_MODEL

        };

    }

    public function options(
        ?string $purpose = null
    ): array {

        return [

            'model' => $this->select($purpose),

            'temperature' => OPENAI_TEMPERATURE,

            'max_tokens' => OPENAI_MAX_COMPLETION_TOKENS

        ];

    }
}