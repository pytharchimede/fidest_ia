<?php

namespace FidestIA\Services;

final class DocumentClassifierService
{
    public function classify(string $text, array $types): ?array
    {
        $haystack = mb_strtolower($text);
        $best = null;
        $bestScore = 0;

        foreach ($types as $type) {
            if (($type['code'] ?? '') === 'GENERAL') {
                continue;
            }

            $schema = json_decode((string) ($type['extraction_schema'] ?? '{}'), true) ?: [];
            $keywords = $schema['keywords'] ?? [];
            $score = 0;

            foreach ($keywords as $keyword) {
                $keyword = mb_strtolower(trim((string) $keyword));
                if ($keyword !== '' && str_contains($haystack, $keyword)) {
                    $score += max(1, substr_count($haystack, $keyword));
                }
            }

            if ($score > $bestScore) {
                $best = $type;
                $bestScore = $score;
            }
        }

        if (!$best || $bestScore === 0) {
            return null;
        }

        $best['classification_score'] = $bestScore;
        return $best;
    }
}
