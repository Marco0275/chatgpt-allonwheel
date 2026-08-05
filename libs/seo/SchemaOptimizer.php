<?php
/**
 * AllOnWheel AI
 * Structured Data / Schema Optimizer
 */

class SchemaOptimizer
{
    /**
     * Analisi Schema.org
     *
     * @param array $article
     * @return array
     */
    public function optimize(array $article): array
    {
        $score = 0;
        $checks = [];


        // Article schema
        $checks['article_schema'] = !empty($article['title']) &&
                                    !empty($article['content']);

        if ($checks['article_schema']) {
            $score += 15;
        }


        // FAQ schema
        $checks['faq_schema'] = !empty($article['faq']);

        if ($checks['faq_schema']) {
            $score += 15;
        }


        // Breadcrumb schema
        $checks['breadcrumb_schema'] = !empty($article['breadcrumbs']);

        if ($checks['breadcrumb_schema']) {
            $score += 10;
        }


        // Entity schema
        $checks['entity_schema'] = !empty($article['entities']);

        if ($checks['entity_schema']) {
            $score += 15;
        }


        // Organization / publisher data
        $checks['publisher_schema'] = !empty($article['publisher']);

        if ($checks['publisher_schema']) {
            $score += 10;
        }


        // Author information
        $checks['author_schema'] = !empty($article['author']);

        if ($checks['author_schema']) {
            $score += 10;
        }


        // Date information
        $checks['date_schema'] = !empty($article['published_at']) &&
                                 !empty($article['updated_at']);

        if ($checks['date_schema']) {
            $score += 10;
        }


        // Structured sections
        $checks['structured_sections'] = !empty($article['headings']);

        if ($checks['structured_sections']) {
            $score += 10;
        }


        // Rich results readiness
        $checks['rich_results'] = $this->checkRichResults($article);

        if ($checks['rich_results']) {
            $score += 5;
        }


        if ($score > 100) {
            $score = 100;
        }


        return [
            'optimizer' => 'Schema',
            'score' => $score,
            'checks' => $checks
        ];
    }


    /**
     * Verifica predisposizione Rich Results
     *
     * @param array $article
     * @return bool
     */
    private function checkRichResults(array $article): bool
    {
        return (
            !empty($article['schema']) ||
            !empty($article['faq']) ||
            !empty($article['tables'])
        );
    }
}