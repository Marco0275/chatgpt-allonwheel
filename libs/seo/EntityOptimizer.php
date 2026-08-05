<?php
/**
 * AllOnWheel AI
 * Entity SEO Optimizer
 */

class EntityOptimizer
{
    /**
     * Analisi entità semantiche
     *
     * @param array $article
     * @return array
     */
    public function optimize(array $article): array
    {
        $score = 0;
        $checks = [];


        // Presenza entità
        $checks['entities'] = !empty($article['entities']);

        if ($checks['entities']) {
            $score += 20;
        }


        // Entità principali
        $checks['primary_entities'] = !empty($article['primary_entities']);

        if ($checks['primary_entities']) {
            $score += 15;
        }


        // Relazioni tra entità
        $checks['entity_relationships'] = !empty($article['entity_relationships']);

        if ($checks['entity_relationships']) {
            $score += 15;
        }


        // Contesto delle entità
        $checks['entity_context'] = !empty($article['entity_context']);

        if ($checks['entity_context']) {
            $score += 15;
        }


        // Dati strutturati entità
        $checks['entity_schema'] = !empty($article['schema']);

        if ($checks['entity_schema']) {
            $score += 10;
        }


        // Collegamenti semantici
        $checks['semantic_links'] = !empty($article['internal_links']);

        if ($checks['semantic_links']) {
            $score += 10;
        }


        // Knowledge graph readiness
        $checks['knowledge_graph'] = $this->checkKnowledgeGraph($article);

        if ($checks['knowledge_graph']) {
            $score += 15;
        }


        if ($score > 100) {
            $score = 100;
        }


        return [
            'optimizer' => 'Entity',
            'score' => $score,
            'checks' => $checks
        ];
    }


    /**
     * Verifica compatibilità Knowledge Graph
     *
     * @param array $article
     * @return bool
     */
    private function checkKnowledgeGraph(array $article): bool
    {
        return (
            !empty($article['entities']) &&
            !empty($article['entity_relationships'])
        );
    }
}