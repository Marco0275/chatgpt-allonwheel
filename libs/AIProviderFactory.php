<?php
declare(strict_types=1);

require_once __DIR__ . '/AIProviderInterface.php';
require_once __DIR__ . '/OpenAIClient.php';

class AIProviderFactory
{
    public static function create(
        PDO $db,
        ?string $provider = null
    ): AIProviderInterface {

        $provider ??= DEFAULT_AI_PROVIDER;

        return match (strtolower($provider)) {

            'openai' => new OpenAIClient($db),

            default => throw new RuntimeException(
                'Unsupported AI Provider: ' . $provider
            )

        };

    }
}