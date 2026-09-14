<?php

namespace FidestIA\Services;

final class DocumentClassifierService
{
    public function classify(string $text, array $types): ?array
    {
        $haystack = $this->normalize($text);
        $head = mb_substr($haystack, 0, 2200);
        $best = null;
        $bestScore = PHP_INT_MIN;
        $bestSignals = [];

        foreach ($types as $type) {
            if (($type['code'] ?? '') === 'GENERAL') {
                continue;
            }

            $schema = json_decode((string) ($type['extraction_schema'] ?? '{}'), true) ?: [];
            $classification = is_array($schema['classification'] ?? null) ? $schema['classification'] : [];

            $keywords = $classification['keywords'] ?? ($schema['keywords'] ?? []);
            $strongKeywords = $classification['strong_keywords'] ?? [];
            $negativeKeywords = $classification['negative_keywords'] ?? [];
            $requiredAny = $classification['required_any'] ?? [];
            $minScore = (int) ($classification['min_score'] ?? 2);

            if ($requiredAny !== [] && !$this->containsAny($haystack, $requiredAny)) {
                continue;
            }

            $score = 0;
            $signals = [];

            foreach ($keywords as $keyword) {
                $needle = $this->normalize((string) $keyword);
                if ($needle === '') {
                    continue;
                }

                $count = substr_count($haystack, $needle);
                if ($count > 0) {
                    $points = min(3, $count);
                    $score += $points;
                    $signals[] = ['signal' => (string) $keyword, 'points' => $points, 'kind' => 'keyword'];

                    if (str_contains($head, $needle)) {
                        $score += 1;
                        $signals[] = ['signal' => (string) $keyword, 'points' => 1, 'kind' => 'header'];
                    }
                }
            }

            foreach ($strongKeywords as $keyword) {
                $needle = $this->normalize((string) $keyword);
                if ($needle !== '' && str_contains($haystack, $needle)) {
                    $points = str_contains($head, $needle) ? 6 : 4;
                    $score += $points;
                    $signals[] = ['signal' => (string) $keyword, 'points' => $points, 'kind' => 'strong'];
                }
            }

            foreach ($negativeKeywords as $keyword) {
                $needle = $this->normalize((string) $keyword);
                if ($needle !== '' && str_contains($haystack, $needle)) {
                    $score -= 5;
                    $signals[] = ['signal' => (string) $keyword, 'points' => -5, 'kind' => 'negative'];
                }
            }

            if ($score < $minScore) {
                continue;
            }

            if ($score > $bestScore) {
                $best = $type;
                $bestScore = $score;
                $bestSignals = $signals;
            }
        }

        if (!$best || $bestScore === PHP_INT_MIN) {
            return null;
        }

        $best['classification_score'] = $bestScore;
        $best['classification_signals'] = $bestSignals;
        return $best;
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            $needle = $this->normalize((string) $needle);
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = strtr($value, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a',
            'ç' => 'c',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'ì' => 'i', 'í' => 'i',
            'ô' => 'o', 'ö' => 'o', 'ò' => 'o', 'ó' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
            'œ' => 'oe', '’' => "'", ' ' => ' ',
        ]);

        return preg_replace('/\s+/u', ' ', trim($value)) ?: trim($value);
    }
}
