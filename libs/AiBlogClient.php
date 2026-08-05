<?php
declare(strict_types=1);

use Libs\AIManager;

/**
 * Interfaccia del client AI usato dall'autopublisher.
 * generateArticle: dal record di piano -> articolo (lingua sorgente).
 * translate:       traduce i campi testuali da $from a $to.
 * Entrambi ritornano: ['title','excerpt','body','category'(opt),'faq'(opt),'outlines'(opt)]
 */
interface AiBlogClientInterface
{
    public function generateArticle(array $plan, string $lang): array;
    public function translate(array $article, string $from, string $to): array;
}

/** Estrae il primo oggetto JSON dal testo del modello (toglie i code-fence). */
function aow_ai_json_decode(string $text): array
{
    $t = trim($text);
    $t = preg_replace('/^\xEF\xBB\xBF/', '', $t);
    $t = preg_replace('/^```(json)?/i', '', $t);
    $t = preg_replace('/```$/', '', trim($t));
    $t = trim($t);
    // fallback: isola dal primo { all'ultimo }
    if ($t === '' || $t[0] !== '{') {
        $s = strpos($t, '{'); $e = strrpos($t, '}');
        if ($s !== false && $e !== false && $e > $s) { $t = substr($t, $s, $e - $s + 1); }
    }
    $data = json_decode($t, true);
    if (!is_array($data)) {
        throw new RuntimeException('AI: risposta non JSON valida');
    }
    return $data;
}

/** Implementazione reale: Google Gemini via AIManager. */
class GeminiBlogClient implements AiBlogClientInterface
{
    private AIManager $ai;
    public function __construct() { $this->ai = new AIManager(); }

    public function generateArticle(array $plan, string $lang): array
    {
        $langName = aow_lang_name($lang);
        $words    = (int)($plan['target_words'] ?? 800);
        $cat      = (string)($plan['category'] ?? '');
        $kw       = (string)($plan['keyword'] ?? '');
        $sec      = (string)($plan['secondary_keywords'] ?? '');
        $title    = (string)($plan['title'] ?? '');

        $prompt = "You are the editorial writer of All on Wheel (B2B special vehicles & motorsport paddock).\n"
            . "Write a complete blog article in {$langName}.\n"
            . "Topic/title hint: \"{$title}\". Primary keyword: \"{$kw}\". Secondary: \"{$sec}\". Category: \"{$cat}\".\n"
            . "Length about {$words} words. Professional, accurate, no fabricated specs.\n"
            . "Return ONLY a JSON object, no markdown, with keys:\n"
            . "{\"title\":\"...\",\"excerpt\":\"<=255 chars\",\"body\":\"plain text, paragraphs separated by blank lines\","
            . "\"category\":\"{$cat}\",\"faq\":[{\"q\":\"...\",\"a\":\"...\"}],\"outlines\":[\"...\"]}";

        $resp = $this->ai->prompt($prompt);
        return $this->normalize(aow_ai_json_decode($this->ai->getResponseText($resp)), $cat);
    }

    public function translate(array $article, string $from, string $to): array
    {
        $toName = aow_lang_name($to);
        $payload = json_encode([
            'title'    => $article['title'] ?? '',
            'excerpt'  => $article['excerpt'] ?? '',
            'body'     => $article['body'] ?? '',
            'faq'      => $article['faq'] ?? [],
            'outlines' => $article['outlines'] ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $prompt = "Translate the following blog article JSON into {$toName}. "
            . "Keep the SAME JSON structure and keys; translate only the human-readable values "
            . "(title, excerpt, body, faq q/a, outlines). Do not add commentary. Return ONLY the JSON.\n\n"
            . $payload;

        $resp = $this->ai->prompt($prompt);
        return $this->normalize(aow_ai_json_decode($this->ai->getResponseText($resp)), (string)($article['category'] ?? ''));
    }

    private function normalize(array $d, string $catFallback): array
    {
        return [
            'title'    => trim((string)($d['title'] ?? '')),
            'excerpt'  => trim((string)($d['excerpt'] ?? '')),
            'body'     => trim((string)($d['body'] ?? '')),
            'category' => trim((string)($d['category'] ?? $catFallback)),
            'faq'      => is_array($d['faq'] ?? null) ? $d['faq'] : [],
            'outlines' => is_array($d['outlines'] ?? null) ? $d['outlines'] : [],
        ];
    }
}

/** Nome lingua leggibile per i prompt. */
function aow_lang_name(string $code): string
{
    return ['it'=>'Italian','en'=>'English','fr'=>'French','de'=>'German'][strtolower(substr($code,0,2))] ?? 'English';
}
