<?php
declare(strict_types=1);

use Libs\AIManager;

class Translator
{

    private AIManager $ai;

    public function __construct(DB $db)
    {
        $this->ai = new AIManager();
    }

    public function translate(
        array $article,
        string $language
    ): array
    {

        $prompt = PromptBuilder::buildTranslation(
            $article,
            $language
        );

        $response = $this->ai->generate($prompt);

        $text = $this->ai->getResponseText($response);

        return AIResponseParser::parse($text);

    }

}