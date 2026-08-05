<?php
/**
 * AllOnWheel AI
 * Internal Link SEO Optimizer
 */

class InternalLinkOptimizer
{
    /**
     * Analisi struttura link interni
     *
     * @param array $article
     * @return array
     */
    public function optimize(array $article): array
    {
        $score = 0;
        $checks = [];


        // Presenza link interni
        $checks['internal_links'] = !empty($article['internal_links']);

        if ($checks['internal_links']) {
            $score += 20;
        }


        // Link verso articoli correlati
        $checks['related_articles'] = !empty($article['related_articles']);

        if ($checks['related_articles']) {
            $score += 15;
        }


        // Anchor text descrittivi
        $checks['anchor_text'] = !empty($article['anchor_texts']);

        if ($checks['anchor_text']) {
            $score += 15;
        }


        // Collegamenti semantici
        $checks['semantic_links'] = !empty($article['entities']);

        if ($checks['semantic_links']) {
            $score += 15;
        }


        // Topic cluster
        $checks['topic_cluster'] = !empty($article['topic_cluster']);

        if ($checks['topic_cluster']) {
            $score += 15;
        }


        // Link verso contenuti autorevoli interni
        $checks['authority_pages'] = !empty($article['pillar_pages']);

        if ($checks['authority_pages']) {
            $score += 10;
        }


        // Bilanciamento link
        $checks['link_balance'] = $this->checkLinkBalance($article);

        if ($checks['link_balance']) {
            $score += 10;
        }


        if ($score > 100) {
            $score = 100;
        }


        return [
            'optimizer' => 'InternalLink',
            'score' => $score,
            'checks' => $checks
        ];
    }


    /**
     * Controllo distribuzione link
     *
     * @param array $article
     * @return bool
     */
    private function checkLinkBalance(array $article): bool
    {
        if (empty($article['internal_links'])) {
            return false;
        }

        if (is_array($article['internal_links'])) {
            return count($article['internal_links']) >= 2;
        }

        return true;
    }
}