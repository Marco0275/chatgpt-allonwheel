<?php
/**
 * AllOnWheel AI
 * Metadata SEO Optimizer
 */

class MetadataOptimizer
{
    /**
     * Analisi metadata SEO
     *
     * @param array $article
     * @return array
     */
    public function optimize(array $article): array
    {
        $score = 0;
        $checks = [];


        // SEO Title
        $checks['title'] = !empty($article['metadata']['title']);

        if ($checks['title']) {
            $score += 20;
        }


        // Meta description
        $checks['description'] = !empty($article['metadata']['description']);

        if ($checks['description']) {
            $score += 20;
        }


        // URL slug
        $checks['slug'] = !empty($article['slug']);

        if ($checks['slug']) {
            $score += 10;
        }


        // Open Graph
        $checks['open_graph'] = !empty($article['metadata']['og']);

        if ($checks['open_graph']) {
            $score += 10;
        }


        // Social metadata
        $checks['social'] = !empty($article['metadata']['social']);

        if ($checks['social']) {
            $score += 10;
        }


        // Canonical URL
        $checks['canonical'] = !empty($article['canonical']);

        if ($checks['canonical']) {
            $score += 10;
        }


        // Robots directives
        $checks['robots'] = !empty($article['robots']);

        if ($checks['robots']) {
            $score += 10;
        }


        // Keywords metadata
        $checks['keywords'] = !empty($article['keywords']);

        if ($checks['keywords']) {
            $score += 5;
        }


        // Language
        $checks['language'] = !empty($article['language']);

        if ($checks['language']) {
            $score += 5;
        }


        if ($score > 100) {
            $score = 100;
        }


        return [
            'optimizer' => 'Metadata',
            'score' => $score,
            'checks' => $checks
        ];
    }
}