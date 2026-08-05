<?php
/**
 * AllOnWheel AI
 * Google SEO Optimizer
 */

class GoogleOptimizer
{
    /**
     * Analisi ottimizzazione Google
     *
     * @param array $article
     * @return array
     */
    public function optimize(array $article): array
    {
        $score = 0;
        $checks = [];


        // Title
        if (!empty($article['title'])) {
            $checks['title'] = true;
            $score += 10;
        } else {
            $checks['title'] = false;
        }


        // Content length
        if (!empty($article['content']) && strlen($article['content']) > 1000) {
            $checks['content_length'] = true;
            $score += 10;
        } else {
            $checks['content_length'] = false;
        }


        // Headings
        if (!empty($article['headings'])) {
            $checks['headings'] = true;
            $score += 10;
        } else {
            $checks['headings'] = false;
        }


        // Structured data readiness
        if (!empty($article['schema'])) {
            $checks['schema_ready'] = true;
            $score += 10;
        } else {
            $checks['schema_ready'] = false;
        }


        // Entity presence
        if (!empty($article['entities'])) {
            $checks['entities'] = true;
            $score += 10;
        } else {
            $checks['entities'] = false;
        }


        // Internal links
        if (!empty($article['internal_links'])) {
            $checks['internal_links'] = true;
            $score += 10;
        } else {
            $checks['internal_links'] = false;
        }


        // FAQ
        if (!empty($article['faq'])) {
            $checks['faq'] = true;
            $score += 10;
        } else {
            $checks['faq'] = false;
        }


        // Metadata
        if (!empty($article['metadata'])) {
            $checks['metadata'] = true;
            $score += 10;
        } else {
            $checks['metadata'] = false;
        }


        // Search intent
        if (!empty($article['search_intent'])) {
            $checks['search_intent'] = true;
            $score += 10;
        } else {
            $checks['search_intent'] = false;
        }


        // Readability
        if (!empty($article['readability'])) {
            $checks['readability'] = true;
            $score += 10;
        } else {
            $checks['readability'] = false;
        }


        return [
            'optimizer' => 'Google',
            'score' => $score,
            'checks' => $checks
        ];
    }
}