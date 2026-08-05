<?php
declare(strict_types=1);

namespace Libs;

require_once __DIR__ . '/PromptBuilder.php';
require_once __DIR__.'/AIResponseParser.php';

class AIManager
{
    private string $apiKey;
    private string $endpoint;
    private string $model;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/ai.php';

        $this->apiKey   = (string)$config['api_key'];
        $this->endpoint = (string)$config['endpoint'];
        $this->model    = (string)$config['model'];
    }

    public function prompt(string $message, string $model = ''): array
    {
        if ($model === '') {
            $model = $this->model;
        }

        $url = $this->endpoint .
            '/models/' .
            $model .
            ':generateContent?key=' .
            urlencode($this->apiKey);

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $message
                        ]
                    ]
                ]
            ]
        ];

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new \Exception(curl_error($ch));
        }

        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $json = json_decode((string)$response, true);

        if ($http !== 200) {
            throw new \Exception(
                $json['error']['message'] ?? 'Errore Gemini'
            );
        }

        return $json ?? [];
    }

    public function getResponseText(array $response): string
    {
        return $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    public function generateSeoArticle(array $data): array
    {
        $prompt = PromptBuilder::generateSeoArticle($data);

        return $this->prompt($prompt);
    }

    public function translate(
        string $content,
        string $source,
        string $target
    ): array {

        $prompt = PromptBuilder::translateArticle(
            $content,
            $source,
            $target
        );

        return $this->prompt($prompt);
    }
	public function generateArticle(array $data): array
{
    $response = $this->generateSeoArticle($data);

    $text = $this->getResponseText($response);

    return AIResponseParser::parse($text);
}
}