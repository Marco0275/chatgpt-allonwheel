<?php
declare(strict_types=1);

class AIResponseParser
{

    public static function parse(string $response): array
    {

        $json = self::extractJson($response);

        $data = json_decode($json, true);

        if (!is_array($data))
        {
            throw new Exception(
                'Invalid JSON returned by AI.'
            );
        }

        self::validate($data);

        $data['title'] = trim((string)$data['title']);

        $data['slug'] = trim((string)$data['slug']);

        $data['excerpt'] = trim((string)$data['excerpt']);

        $data['meta_title'] = trim((string)$data['meta_title']);

        $data['meta_description'] = trim((string)$data['meta_description']);

        $data['content'] = trim((string)$data['content']);

        if (!isset($data['faq']) || !is_array($data['faq']))
        {
            $data['faq'] = [];
        }

        return $data;

    }

    private static function extractJson(string $text): string
    {

        $text = trim($text);

        /*
        |--------------------------------------------------------------------------
        | UTF8 BOM
        |--------------------------------------------------------------------------
        */

        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);

        /*
        |--------------------------------------------------------------------------
        | ```json
        |--------------------------------------------------------------------------
        */

        $text = preg_replace('/^```json/i', '', $text);

        $text = preg_replace('/^```/i', '', $text);

        $text = preg_replace('/```$/', '', $text);

        $text = trim($text);

        /*
        |--------------------------------------------------------------------------
        | Estrae il primo JSON valido
        |--------------------------------------------------------------------------
        */

        $start = strpos($text, '{');

        $end = strrpos($text, '}');

        if ($start === false || $end === false)
        {
            throw new Exception(
                'JSON not found.'
            );
        }

        return substr(
            $text,
            $start,
            ($end - $start) + 1
        );

    }

    private static function validate(array $data): void
    {

        $required = [

            'title',

            'slug',

            'excerpt',

            'meta_title',

            'meta_description',

            'content',

            'faq'

        ];

        foreach ($required as $field)
        {

            if (!array_key_exists($field, $data))
            {
                throw new Exception(
                    "Missing field: {$field}"
                );
            }

        }

        if (!is_array($data['faq']))
        {
            throw new Exception(
                'FAQ must be an array.'
            );
        }

        foreach ($data['faq'] as $faq)
        {

            if (!isset($faq['question']))
            {
                throw new Exception(
                    'FAQ question missing.'
                );
            }

            if (!isset($faq['answer']))
            {
                throw new Exception(
                    'FAQ answer missing.'
                );
            }

        }

    }

}