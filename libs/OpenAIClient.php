<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/AIProviderInterface.php';

class OpenAIClient implements AIProviderInterface
{
    private PDO $db;

    private Logger $logger;

    private string $apiKey;

    private string $model;

    private string $endpoint =
        'https://api.openai.com/v1/chat/completions';

    private int $timeout = OPENAI_TIMEOUT;

    private int $maxRetries = OPENAI_MAX_RETRIES;

    public function __construct(PDO $db)
    {
        $this->db = $db;

        $this->logger = new Logger();

        $this->apiKey = OPENAI_API_KEY;

        $this->model = OPENAI_MODEL;
    }

    public function getName(): string
    {
        return 'OpenAI';
    }

    public function chat(
        string $prompt,
        array $options = []
    ): string {

        $payload = [

            'model' => $options['model'] ?? $this->model,

            'messages' => [

                [

                    'role' => 'system',

                    'content' =>
                        $options['system']
                        ?? 'You are AllOnWheel AI.'

                ],

                [

                    'role' => 'user',

                    'content' => $prompt

                ]

            ],

            'temperature' =>
                $options['temperature']
                ?? OPENAI_TEMPERATURE,

            'max_completion_tokens' =>
                $options['max_tokens']
                ?? OPENAI_MAX_COMPLETION_TOKENS,

            'response_format' => [

                'type' => 'json_object'

            ]

        ];

        return $this->request($payload);
    }

    public function embeddings(
        string $text,
        array $options = []
    ): array
    {
        return [];
    }

    public function image(
        string $prompt,
        array $options = []
    ): string
    {
        return '';
    }

    public function models(): array
    {
        return [

            'gpt-5.5',

            'gpt-5.5-mini',

            'gpt-5.5-nano'

        ];
    }

    public function health(): bool
    {
        try {

            $this->chat(

                '{"ping":"pong"}',

                [

                    'temperature' => 0,

                    'max_tokens' => 10

                ]

            );

            return true;

        } catch (Throwable) {

            return false;

        }
    }

    private function request(
        array $payload
    ): string {

        $attempt = 0;

        while ($attempt < $this->maxRetries) {

            $attempt++;

            $ch = curl_init($this->endpoint);

            curl_setopt_array($ch, [

                CURLOPT_RETURNTRANSFER => true,

                CURLOPT_POST => true,

                CURLOPT_HTTPHEADER => [

                    'Content-Type: application/json',

                    'Authorization: Bearer ' .
                    $this->apiKey

                ],

                CURLOPT_POSTFIELDS =>
                    json_encode(
                        $payload,
                        JSON_UNESCAPED_UNICODE
                    ),

                CURLOPT_TIMEOUT =>
                    $this->timeout

            ]);

            $response = curl_exec($ch);

            $http = curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
            );

            $error = curl_error($ch);

            curl_close($ch);

            if ($response === false) {

                $this->logger->error($error);

                continue;

            }

            if ($http >= 500) {

                sleep($attempt);

                continue;

            }

            if ($http >= 400) {

                throw new RuntimeException($response);

            }

            return $this->parse($response);

        }

        throw new RuntimeException(
            'OpenAI service unavailable.'
        );
    }

    private function parse(
        string $response
    ): string {

        $json = json_decode(
            $response,
            true
        );

        if (!is_array($json)) {

            throw new RuntimeException(
                'Invalid OpenAI response.'
            );

        }

        if (
            empty(
                $json['choices'][0]['message']['content']
            )
        ) {

            throw new RuntimeException(
                'Empty completion.'
            );

        }

        if (isset($json['usage'])) {

            $this->storeUsage(
                $json['usage']
            );

        }

        return trim(
            $json['choices'][0]['message']['content']
        );
    }

    private function storeUsage(
        array $usage
    ): void {

        $stmt = $this->db->prepare(

            "INSERT INTO ai_tokens
            (
                prompt_tokens,
                completion_tokens,
                total_tokens,
                created_at
            )
            VALUES
            (
                :prompt,
                :completion,
                :total,
                NOW()
            )"

        );

        $stmt->execute([

            ':prompt' =>
                $usage['prompt_tokens'] ?? 0,

            ':completion' =>
                $usage['completion_tokens'] ?? 0,

            ':total' =>
                $usage['total_tokens'] ?? 0

        ]);
    }
}