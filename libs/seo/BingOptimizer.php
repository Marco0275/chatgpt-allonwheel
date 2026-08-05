<?php
/**
 * AllOnWheel AI
 * Bing SEO Optimizer
 */

class BingOptimizer
{
    /**
     * Analisi ottimizzazione Bing
     *
     * @param array $article
     * @return array
     */
    public function optimize(array $article): array
    {
        $score = 0;
        $checks = [];


        // Title optimization
        $checks['title'] = !empty($article['title']);

        if ($checks['title']) {
            $score += 10;
        }


        // Meta description
        $checks['meta_description'] = !empty($article['metadata']['description']);

        if ($checks['meta_description']) {
            $score += 10;
        }


        // Keywords
        $checks['keywords'] = !empty($article['keywords']);

        if ($checks['keywords']) {
            $score += 10;
        }


        // Content relevance
        $checks['relevance'] = !empty($article['content']);

        if ($checks['relevance']) {
            $score += 10;
        }


        // Headings
        $checks['headings'] = !empty($article['headings']);

        if ($checks['headings']) {
            $score += 10;
        }


        // Schema markup
        $checks['schema'] = !empty($article['schema']);

        if ($checks['schema']) {
            $score += 10;
        }


        // Images optimization
        $checks['images'] = !empty($article['images']);

        if ($checks['images']) {
            $score += 10;
        }


        // Internal links
        $checks['internal_links'] = !empty($article['internal_links']);

        if ($checks['internal_links']) {
            $score += 10;
        }


        // Freshness
        $checks['freshness'] = !empty($article['updated_at']);

        if ($checks['freshness']) {
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
            'optimizer' => 'Bing',
            'score' => $score,
            'checks' => $checks
        ];
    }
}