<?php
declare(strict_types=1);

/**
 * ------------------------------------------------------------
 * AllOnWheel AI v1.0
 * WordPress REST Publisher
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/../../models/Article.php';

class WordPressPublisher
{
    /**
     * Pubblica un articolo su WordPress REST API.
     */
    public function publish(
        Article $article,
        array $config
    ): array {

        $endpoint = rtrim(
            $config['url'],
            '/'
        ) . '/wp-json/wp/v2/posts';

        $payload = [

            'title'   => $article->title,

            'content' => $article->content,

            'excerpt' => $article->excerpt,

            'status'  => 'publish'

        ];

        $curl = curl_init($endpoint);

        curl_setopt_array($curl, [

            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_POST => true,

            CURLOPT_HTTPHEADER => [

                'Content-Type: application/json',

                'Authorization: Basic ' .
                base64_encode(
                    $config['username'] .
                    ':' .
                    $config['application_password']
                )

            ],

            CURLOPT_POSTFIELDS => json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
            )

        ]);

        $response = curl_exec($curl);

        if ($response === false) {

            throw new RuntimeException(
                curl_error($curl)
            );

        }

        $status = curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );

        curl_close($curl);

        $json = json_decode(
            $response,
            true
        );

        if ($status >= 400) {

            throw new RuntimeException(
                $json['message']
                ?? 'WordPress error.'
            );

        }

        return $json;
    }
}