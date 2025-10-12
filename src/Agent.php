<?php

namespace FidestIA;

class Agent
{
    protected string $name;
    protected TextNormalizer $norm;

    public function __construct(string $name = 'etalonIA')
    {
        $this->name = $name;
        $this->norm = new TextNormalizer();
    }

    // Predict decision based on simple nearest-example (token overlap) and DecisionEngine
    public function predict(string $label, string $ocr_text, \PDO $pdo): array
    {
        // load training examples
        $stmt = $pdo->prepare('SELECT * FROM training_examples_ia WHERE agent = ?');
        $stmt->execute([$this->name]);
        $examples = $stmt->fetchAll();

        // tokenise input
        // normalize tokens with stemming and synonyms
        $inputTokens = $this->norm->normalizeTokens($label);
        $docTokens = $this->norm->normalizeTokens($ocr_text);

        $best = null;
        $bestScore = 0;
        foreach ($examples as $ex) {
            $exTokens = $this->norm->normalizeTokens($ex['label']);
            $common = array_intersect($exTokens, $docTokens);
            $score = count($common) / max(1, count($exTokens));
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $ex;
            }
        }

        $res = ['agent' => $this->name, 'label' => $label, 'decision' => 'HUMAN_REVIEW', 'reasons' => [], 'example_match' => null, 'score' => $bestScore];
        if ($best) {
            $res['example_match'] = $best;
            // if example has decision, use it if score high
            if ($bestScore >= 0.6) {
                $res['decision'] = $best['decision'];
                $res['reasons'][] = ['code' => 'AGENT_EXAMPLE', 'message' => 'Matched training example'];
            }
        }

        // fallback to DecisionEngine text-only decision
        $fallback = (new DecisionEngine())->decide($label, 0, $ocr_text);
        if ($res['decision'] === 'HUMAN_REVIEW') {
            $res['decision'] = $fallback['decision'];
            $res['reasons'] = array_merge($res['reasons'], $fallback['reasons']);
        }

        return $res;
    }
}
