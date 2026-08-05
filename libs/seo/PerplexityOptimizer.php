<?php
/**
 * AllOnWheel AI
 * Perplexity SEO Optimizer
 */

class PerplexityOptimizer
{
    /**
     * Analisi ottimizzazione Perplexity
     *
     * @param array $article
     * @return array
     */
    public function optimize(array $article): array
    {
        $score = 0;
        $checks = [];


        // Source citations
        $checks['citations'] = !empty($article['references']) || !empty($article['sources']);

        if ($checks['citations']) {
            $score += 15;
        }


        // Source trust
        $checks['source_trust'] = !empty($article['source_trust']);

        if ($checks['source_trust']) {
            $score += 10;
        }


        // Direct answers
        $checks['direct_answers'] = !empty($article['quick_answer']);

        if ($checks['direct_answers']) {
            $score += 10;
        }


        // Structured information
        $checks['structured_content'] = (
            !empty($article['headings']) &&
            !empty($article['sections'])
        );

        if ($checks['structured_content']) {
            $score += 10;
        }


        // Entities
        $checks['entities'] = !empty($article['entities']);

        if ($checks['entities']) {
            $score += 10;
        }


        // Facts and data
        $checks['data_points'] = !empty($article['statistics']) ||
                                 !empty($article['numbers']);

        if ($checks['data_points']) {
            $score += 10;
        }


        // Comparison opportunities
        $checks['comparisons'] = !empty($article['comparisons']);

        if ($checks['comparisons']) {
            $score += 10;
        }


        // FAQ
        $checks['faq'] = !empty($article['faq']);

        if ($checks['faq']) {
            $score += 10;
        }


        // Updated information
        $checks['freshness'] = !empty($article['updated_at']);

        if ($checks['freshness']) {
            $score += 5;
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
            'optimizer' => 'Perplexity',
            'score' => $score,
            'checks' => $checks
        ];
    }
}