<?php
/**
 * AllOnWheel AI
 * E-E-A-T SEO Optimizer
 */

class EEATOptimizer
{
    /**
     * Analisi Experience, Expertise, Authoritativeness, Trust
     *
     * @param array $article
     * @return array
     */
    public function optimize(array $article): array
    {
        $score = 0;
        $checks = [];


        // Experience
        $checks['experience'] = !empty($article['experience']);

        if ($checks['experience']) {
            $score += 15;
        }


        // Expertise
        $checks['expertise'] = !empty($article['author']) ||
                               !empty($article['expertise']);

        if ($checks['expertise']) {
            $score += 15;
        }


        // Authoritativeness
        $checks['authority'] = !empty($article['references']) ||
                               !empty($article['sources']);

        if ($checks['authority']) {
            $score += 15;
        }


        // Trust
        $checks['trust'] = !empty($article['publisher']) ||
                           !empty($article['source_trust']);

        if ($checks['trust']) {
            $score += 15;
        }


        // Author profile
        $checks['author_profile'] = !empty($article['author']);

        if ($checks['author_profile']) {
            $score += 10;
        }


        // Citations
        $checks['citations'] = !empty($article['references']);

        if ($checks['citations']) {
            $score += 10;
        }


        // Technical accuracy
        $checks['accuracy'] = !empty($article['technical_data']);

        if ($checks['accuracy']) {
            $score += 10;
        }


        // Updated content
        $checks['freshness'] = !empty($article['updated_at']);

        if ($checks['freshness']) {
            $score += 10;
        }


        // Transparency
        $checks['transparency'] = !empty($article['disclaimer']);

        if ($checks['transparency']) {
            $score += 5;
        }


        if ($score > 100) {
            $score = 100;
        }


        return [
            'optimizer' => 'EEAT',
            'score' => $score,
            'checks' => $checks
        ];
    }
}