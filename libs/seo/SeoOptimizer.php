<?php
/**
 * AllOnWheel AI
 * SEO Optimization Orchestrator
 */

class SeoOptimizer
{
    private array $optimizers = [];

    public function __construct()
    {
        $this->loadOptimizers();
    }

    /**
     * Carica i moduli SEO disponibili
     */
    private function loadOptimizers(): void
    {
        $basePath = __DIR__ . '/';

        $modules = [
            'GoogleOptimizer',
            'GeminiOptimizer',
            'ChatGPTOptimizer',
            'PerplexityOptimizer',
            'ClaudeOptimizer',
            'BingOptimizer',
            'SchemaOptimizer',
            'EEATOptimizer',
            'ReadabilityOptimizer',
            'InternalLinkOptimizer',
            'EntityOptimizer',
            'KeywordClusterOptimizer',
            'MetadataOptimizer'
        ];

        foreach ($modules as $module) {

            $file = $basePath . $module . '.php';

            if (file_exists($file)) {
                require_once $file;

                if (class_exists($module)) {
                    $this->optimizers[$module] = new $module();
                }
            }
        }
    }


    /**
     * Esegue ottimizzazione completa
     *
     * @param array $article
     * @return array
     */
    public function optimize(array $article): array
    {
        $results = [];

        foreach ($this->optimizers as $name => $optimizer) {

            if (method_exists($optimizer, 'optimize')) {

                $results[$name] = $optimizer->optimize($article);

            }
        }

        return [
            'article' => $article,
            'analysis' => $results,
            'visibility' => $this->calculateVisibility($results)
        ];
    }


    /**
     * Calcolo Visibility Score
     *
     * @param array $results
     * @return array
     */
    private function calculateVisibility(array $results): array
    {
        $scores = [];

        foreach ($results as $module => $data) {

            if (isset($data['score'])) {
                $scores[$module] = $data['score'];
            }

        }

        $overall = 0;

        if (!empty($scores)) {
            $overall = round(
                array_sum($scores) / count($scores)
            );
        }

        return [
            'modules' => $scores,
            'overall' => $overall
        ];
    }


    /**
     * Restituisce moduli caricati
     *
     * @return array
     */
    public function getLoadedOptimizers(): array
    {
        return array_keys($this->optimizers);
    }
}