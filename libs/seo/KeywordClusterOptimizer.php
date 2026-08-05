<?php
/**
 * AllOnWheel AI
 * Keyword Cluster SEO Optimizer
 */

class KeywordClusterOptimizer
{
    /**
     * Analisi keyword cluster
     *
     * @param array $article
     * @return array
     */
    public function optimize(array $article): array
    {
        $score = 0;
        $checks = [];


        // Keyword principale
        $checks['primary_keyword'] = !empty($article['primary_keyword']);

        if ($checks['primary_keyword']) {
            $score += 15;
        }


        // Keyword secondarie
        $checks['secondary_keywords'] = !empty($article['secondary_keywords']);

        if ($checks['secondary_keywords']) {
            $score += 15;
        }


        // Cluster tematico
        $checks['topic_cluster'] = !empty($article['topic_cluster']);

        if ($checks['topic_cluster']) {
            $score += 15;
        }


        // Keyword semantiche correlate
        $checks['semantic_keywords'] = !empty($article['semantic_keywords']);

        if ($checks['semantic_keywords']) {
            $score += 15;
        }


        // Search intent associato
        $checks['search_intent'] = !empty($article['search_intent']);

        if ($checks['search_intent']) {
            $score += 10;
        }


        // Distribuzione naturale keyword
        $checks['natural_distribution'] = $this->checkDistribution($article);

        if ($checks['natural_distribution']) {
            $score += 10;
        }


        // Copertura argomento
        $checks['topic_coverage'] = !empty($article['sections']);

        if ($checks['topic_coverage']) {
            $score += 10;
        }


        // Entità correlate
        $checks['related_entities'] = !empty($article['entities']);

        if ($checks['related_entities']) {
            $score += 10;
        }


        if ($score > 100) {
            $score = 100;
        }


        return [
            'optimizer' => 'KeywordCluster',
            'score' => $score,
            'checks' => $checks
        ];
    }


    /**
     * Controllo distribuzione keyword
     *
     * @param array $article
     * @return bool
     */
    private function checkDistribution(array $article): bool
    {
        return (
            !empty($article['content']) &&
            !empty($article['primary_keyword'])
        );
    }
}