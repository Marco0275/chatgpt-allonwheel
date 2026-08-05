<?php
/**
 * AllOnWheel AI
 * LLM Training Feedback Analyzer
 */

class LLMTrainer
{
    private array $sources = [
        'chatgpt',
        'gemini',
        'perplexity',
        'claude'
    ];


    /**
     * Analizza dati provenienti dagli LLM
     *
     * @param array $articles
     * @return array
     */
    public function analyze(array $articles): array
    {
        $results = [
            'analyzed_articles' => 0,
            'llm_visibility' => [],
            'suggestions' => []
        ];


        foreach ($articles as $article) {

            $results['analyzed_articles']++;


            $visibility = $this->analyzeVisibility($article);

            $results['llm_visibility'][] = $visibility;


            $suggestions = $this->generateSuggestions($article);

            if (!empty($suggestions)) {
                $results['suggestions'][] = [
                    'article_id' => $article['id'] ?? null,
                    'suggestions' => $suggestions
                ];
            }
        }


        return $results;
    }


    /**
     * Analizza presenza articolo negli LLM
     *
     * @param array $article
     * @return array
     */
    private function analyzeVisibility(array $article): array
    {
        $result = [];


        foreach ($this->sources as $source) {

            $result[$source] = [
                'mentioned' => !empty($article[$source]['mentioned']),
                'citations' => $article[$source]['citations'] ?? 0
            ];

        }


        return $result;
    }


    /**
     * Genera suggerimenti automatici
     *
     * @param array $article
     * @return array
     */
    private function generateSuggestions(array $article): array
    {
        $suggestions = [];


        if (empty($article['tables'])) {
            $suggestions[] = 'Add comparison or information tables';
        }


        if (empty($article['entities'])) {
            $suggestions[] = 'Improve semantic entities';
        }


        if (empty($article['references'])) {
            $suggestions[] = 'Add authoritative references';
        }


        if (empty($article['numbers'])) {
            $suggestions[] = 'Add numerical data and statistics';
        }


        if (empty($article['internal_links'])) {
            $suggestions[] = 'Create internal links to related articles';
        }


        if (empty($article['quick_answer'])) {
            $suggestions[] = 'Add a quick answer block';
        }


        return $suggestions;
    }
}