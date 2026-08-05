<?php
/**
 * AllOnWheel AI
 * Gemini SEO Optimizer
 */

class GeminiOptimizer
{
    /**
     * Analisi ottimizzazione Gemini
     *
     * @param array $article
     * @return array
     */
    public function optimize(array $article): array
    {
        $score = 0;
        $checks = [];


        // Search Intent
        $checks['search_intent'] = !empty($article['search_intent']);
        if ($checks['search_intent']) {
            $score += 5;
        }


        // Helpful Content
        $checks['helpful_content'] = !empty($article['content']);
        if ($checks['helpful_content']) {
            $score += 5;
        }


        // AI Overview compatibility
        $checks['ai_overview'] = $this->checkStructure($article);
        if ($checks['ai_overview']) {
            $score += 10;
        }


        // Conversational Search
        $checks['conversational_search'] = !empty($article['natural_language']);
        if ($checks['conversational_search']) {
            $score += 5;
        }


        // Semantic Entities
        $checks['semantic_entities'] = !empty($article['entities']);
        if ($checks['semantic_entities']) {
            $score += 10;
        }


        // Knowledge Graph
        $checks['knowledge_graph'] = !empty($article['entities']);
        if ($checks['knowledge_graph']) {
            $score += 5;
        }


        // Answer Engine Optimization
        $checks['answer_engine_optimization'] = !empty($article['quick_answer']);
        if ($checks['answer_engine_optimization']) {
            $score += 10;
        }


        // Topic Authority
        $checks['topic_authority'] = !empty($article['references']);
        if ($checks['topic_authority']) {
            $score += 5;
        }


        // Source Trust
        $checks['source_trust'] = !empty($article['sources']);
        if ($checks['source_trust']) {
            $score += 5;
        }


        // Readability
        $checks['readability'] = !empty($article['readability']);
        if ($checks['readability']) {
            $score += 10;
        }


        // Tables
        $checks['tables'] = !empty($article['tables']);
        if ($checks['tables']) {
            $score += 5;
        }


        // FAQ Quality
        $checks['faq_quality'] = !empty($article['faq']);
        if ($checks['faq_quality']) {
            $score += 5;
        }


        // Definitions
        $checks['definitions'] = !empty($article['definitions']);
        if ($checks['definitions']) {
            $score += 5;
        }


        // Examples
        $checks['examples'] = !empty($article['examples']);
        if ($checks['examples']) {
            $score += 5;
        }


        // Summary block
        $checks['summary_block'] = !empty($article['summary']);
        if ($checks['summary_block']) {
            $score += 5;
        }


        // Limite massimo
        if ($score > 100) {
            $score = 100;
        }


        return [
            'optimizer' => 'Gemini',
            'score' => $score,
            'checks' => $checks
        ];
    }


    /**
     * Verifica struttura compatibile AI Overview
     *
     * @param array $article
     * @return bool
     */
    private function checkStructure(array $article): bool
    {
        $required = [
            'title',
            'headings',
            'content'
        ];

        foreach ($required as $field) {

            if (empty($article[$field])) {
                return false;
            }

        }

        return true;
    }
}