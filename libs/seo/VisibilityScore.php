<?php
/**
 * AllOnWheel AI
 * AI Visibility Score Calculator
 */

class VisibilityScore
{
    /**
     * Calcola punteggio complessivo visibilità AI
     *
     * @param array $scores
     * @return array
     */
    public function calculate(array $scores): array
    {
        $result = [
            'google' => 0,
            'gemini' => 0,
            'chatgpt' => 0,
            'claude' => 0,
            'perplexity' => 0,
            'bing' => 0,
            'eeat' => 0,
            'entities' => 0,
            'schema' => 0,
            'readability' => 0,
            'overall' => 0
        ];


        foreach ($scores as $module => $data) {

            if (!isset($data['score'])) {
                continue;
            }


            $score = (int) $data['score'];


            switch ($module) {

                case 'GoogleOptimizer':
                    $result['google'] = $score;
                    break;


                case 'GeminiOptimizer':
                    $result['gemini'] = $score;
                    break;


                case 'ChatGPTOptimizer':
                    $result['chatgpt'] = $score;
                    break;


                case 'ClaudeOptimizer':
                    $result['claude'] = $score;
                    break;


                case 'PerplexityOptimizer':
                    $result['perplexity'] = $score;
                    break;


                case 'BingOptimizer':
                    $result['bing'] = $score;
                    break;


                case 'EEATOptimizer':
                    $result['eeat'] = $score;
                    break;


                case 'EntityOptimizer':
                    $result['entities'] = $score;
                    break;


                case 'SchemaOptimizer':
                    $result['schema'] = $score;
                    break;


                case 'ReadabilityOptimizer':
                    $result['readability'] = $score;
                    break;
            }
        }


        $values = [
            $result['google'],
            $result['gemini'],
            $result['chatgpt'],
            $result['claude'],
            $result['perplexity'],
            $result['bing'],
            $result['eeat'],
            $result['entities'],
            $result['schema'],
            $result['readability']
        ];


        $result['overall'] = round(
            array_sum($values) / count($values)
        );


        return $result;
    }
}