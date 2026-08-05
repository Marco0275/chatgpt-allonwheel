<?php
/**
 * AllOnWheel AI
 * Claude SEO Optimizer
 */

class ClaudeOptimizer
{
    /**
     * Analisi ottimizzazione Claude
     *
     * @param array $article
     * @return array
     */
    public function optimize(array $article): array
    {
        $score = 0;
        $checks = [];


        // Content depth
        $checks['content_depth'] = !empty($article['content']);

        if ($checks['content_depth']) {
            $score += 10;
        }


        // Logical structure
        $checks['structure'] = !empty($article['headings']);

        if ($checks['structure']) {
            $score += 10;
        }


        // Context understanding
        $checks['context'] = !empty($article['topic_context']);

        if ($checks['context']) {
            $score += 10;
        }


        // Detailed explanations
        $checks['explanations'] = !empty($article['sections']);

        if ($checks['explanations']) {
            $score += 10;
        }


        // Technical accuracy
        $checks['technical_accuracy'] = !empty($article['references']);

        if ($checks['technical_accuracy']) {
            $score += 10;
        }


        // Entities
        $checks['entities'] = !empty($article['entities']);

        if ($checks['entities']) {
            $score += 10;
        }


        // Examples
        $checks['examples'] = !empty($article['examples']);

        if ($checks['examples']) {
            $score += 10;
        }


        // Balanced information
        $checks['pros_cons'] = !empty($article['pros_cons']);

        if ($checks['pros_cons']) {
            $score += 10;
        }


        // Summary
        $checks['summary'] = !empty($article['summary']);

        if ($checks['summary']) {
            $score += 10;
        }


        // Readability
        $checks['readability'] = !empty($article['readability']);

        if ($checks['readability']) {
            $score += 10;
        }


        if ($score > 100) {
            $score = 100;
        }


        return [
            'optimizer' => 'Claude',
            'score' => $score,
            'checks' => $checks
        ];
    }
}