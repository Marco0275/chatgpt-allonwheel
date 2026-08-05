<?php
/**
 * AllOnWheel AI
 * ChatGPT SEO Optimizer
 */

class ChatGPTOptimizer
{
    /**
     * Analisi ottimizzazione ChatGPT
     *
     * @param array $article
     * @return array
     */
    public function optimize(array $article): array
    {
        $score = 0;
        $checks = [];


        // Clear answer
        $checks['clear_answer'] = !empty($article['quick_answer']);

        if ($checks['clear_answer']) {
            $score += 10;
        }


        // Natural language
        $checks['natural_language'] = !empty($article['natural_language']);

        if ($checks['natural_language']) {
            $score += 10;
        }


        // Context completeness
        $checks['context_completeness'] = !empty($article['content']);

        if ($checks['context_completeness']) {
            $score += 10;
        }


        // Entities
        $checks['entities'] = !empty($article['entities']);

        if ($checks['entities']) {
            $score += 10;
        }


        // Structured sections
        $checks['structured_sections'] = !empty($article['headings']);

        if ($checks['structured_sections']) {
            $score += 10;
        }


        // Examples
        $checks['examples'] = !empty($article['examples']);

        if ($checks['examples']) {
            $score += 10;
        }


        // FAQ
        $checks['faq'] = !empty($article['faq']);

        if ($checks['faq']) {
            $score += 10;
        }


        // References
        $checks['references'] = !empty($article['references']);

        if ($checks['references']) {
            $score += 10;
        }


        // Internal connections
        $checks['internal_links'] = !empty($article['internal_links']);

        if ($checks['internal_links']) {
            $score += 10;
        }


        // Summary
        $checks['summary'] = !empty($article['summary']);

        if ($checks['summary']) {
            $score += 10;
        }


        if ($score > 100) {
            $score = 100;
        }


        return [
            'optimizer' => 'ChatGPT',
            'score' => $score,
            'checks' => $checks
        ];
    }
}