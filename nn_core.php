<?php
class ContextNN {
    private $weights = [];
    private $noiseWords;
    private $learningRate = 0.1;
    
    public function __construct() {
        $this->noiseWords = ['очень', 'просто', 'так', 'такой', 'этот', 'эта', 'это', 'вроде', 'как бы', 'наверное', 'действительно', 'буквально', 'фактически'];
    }
    
    private function tokenize($text) {
        preg_match_all('/\b[\w\']+\b/u', mb_strtolower($text), $matches);
        return $matches[0] ?? [];
    }
    
    private function extractFeatures($tokens, $idx) {
        return [
            'word' => $tokens[$idx],
            'prev' => $idx > 0 ? $tokens[$idx-1] : null,
            'next' => $idx < count($tokens)-1 ? $tokens[$idx+1] : null
        ];
    }
    
    private function computeContextScore($features) {
        $score = 0;
        if ($features['prev'] && isset($this->weights["{$features['prev']}->{$features['word']}"])) {
            $score += $this->weights["{$features['prev']}->{$features['word']}"];
        }
        if ($features['next'] && isset($this->weights["{$features['word']}->{$features['next']}"])) {
            $score += $this->weights["{$features['word']}->{$features['next']}"];
        }
        if (in_array($features['word'], $this->noiseWords)) $score -= 0.5;
        return $score;
    }
    
    private function filterSentence($tokens) {
        $result = [];
        foreach ($tokens as $idx => $word) {
            $feat = $this->extractFeatures($tokens, $idx);
            if ($this->computeContextScore($feat) > -0.3) {
                $result[] = $word;
            }
        }
        return $result;
    }
    
    public function learn($sentence) {
        $tokens = $this->tokenize($sentence);
        for ($i = 0; $i < count($tokens)-1; $i++) {
            $pair = "{$tokens[$i]}->{$tokens[$i+1]}";
            $this->weights[$pair] = ($this->weights[$pair] ?? 0) + $this->learningRate;
        }
        foreach ($tokens as $i => $word) {
            if (in_array($word, $this->noiseWords)) {
                for ($j = -1; $j <= 1; $j++) {
                    if (isset($tokens[$i+$j])) {
                        $pair = ($j === -1) ? "{$tokens[$i+$j]}->{$word}" : "{$word}->{$tokens[$i+$j]}";
                        if (isset($this->weights[$pair])) {
                            $this->weights[$pair] -= $this->learningRate * 0.5;
                        }
                    }
                }
            }
        }
    }
    
    private function toBinary($text) {
        $binary = '';
        for ($i = 0; $i < mb_strlen($text); $i++) {
            $char = mb_substr($text, $i, 1);
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        return $binary;
    }
    
    public function process($sentence) {
        $this->learn($sentence);
        $tokens = $this->tokenize($sentence);
        $filtered = $this->filterSentence($tokens);
        $cleaned = implode(' ', $filtered);
        $binary = $this->toBinary($cleaned);
        return [
            'original' => $sentence,
            'cleaned' => $cleaned,
            'binary' => $binary,
            'contextWeights' => $this->weights
        ];
    }
}
?>
