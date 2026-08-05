<?php
declare(strict_types=1);

interface AIProviderInterface
{
    public function getName(): string;

    public function chat(
        string $prompt,
        array $options = []
    ): string;

    public function embeddings(
        string $text,
        array $options = []
    ): array;

    public function image(
        string $prompt,
        array $options = []
    ): string;

    public function models(): array;

    public function health(): bool;
}