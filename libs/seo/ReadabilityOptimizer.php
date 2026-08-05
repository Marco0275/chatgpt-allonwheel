<?php
/**
 * AllOnWheel AI
 * Readability SEO Optimizer
 */

class ReadabilityOptimizer
{
    /**
     * Analisi leggibilità contenuto
     *
     * @param array $article
     * @return array
     */
    public function optimize(array $article): array
    {
        $score = 0;
        $checks = [];

        $content = $article['content'] ?? '';


        // Content availability
        $checks['content_available'] = !empty($content);

        if ($checks['content_available']) {
            $score += 10;
        }


        // Short paragraphs
        $checks['short_paragraphs'] = $this->checkParagraphs($content);

        if ($checks['short_paragraphs']) {
            $score += 15;
        }


        // Sentence length
        $checks['sentence_length'] = $this->checkSentenceLength($content);

        if ($checks['sentence_length']) {
            $score += 15;
        }


        // Headings
        $checks['headings'] = !empty($article['headings']);

        if ($checks['headings']) {
            $score += 15;
        }


        // Lists
        $checks['lists'] = !empty($article['lists']);

        if ($checks['lists']) {
            $score += 10;
        }


        // Tables
        $checks['tables'] = !empty($article['tables']);

        if ($checks['tables']) {
            $score += 10;
        }


        // Definitions
        $checks['definitions'] = !empty($article['definitions']);

        if ($checks['definitions']) {
            $score += 10;
        }


        // Examples
        $checks['examples'] = !empty($article['examples']);

        if ($checks['examples']) {
            $score += 10;
        }


        // Summary
        $checks['summary'] = !empty($article['summary']);

        if ($checks['summary']) {
            $score += 5;
        }


        if ($score > 100) {
            $score = 100;
        }


        return [
            'optimizer' => 'Readability',
            'score' => $score,
            'checks' => $checks
        ];
    }


    /**
     * Verifica lunghezza paragrafi
     *
     * @param string $content
     * @return bool
     */
    private function checkParagraphs(string $content): bool
    {
        if (empty($content)) {
            return false;
        }

        $paragraphs = preg_split('/\n\s*\n/', $content);

        foreach ($paragraphs as $paragraph) {

            if (str_word_count($paragraph) > 120) {
                return false;
            }

        }

        return true;
    }


    /**
     * Verifica lunghezza frasi
     *
     * @param string $content
     * @return bool
     */
    private function checkSentenceLength(string $content): bool
    {
        if (empty($content)) {
            return false;
        }

        $sentences = preg_split('/[.!?]+/', $content);

        foreach ($sentences as $sentence) {

            if (str_word_count($sentence) > 25) {
                return false;
            }

        }

        return true;
    }
}