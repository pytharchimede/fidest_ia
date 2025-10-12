<?php

namespace FidestIA;

class DecisionEngine
{
    public array $reasons = [];

    // Note: this engine focuses on text-only verification.
    // The declared_amount argument is accepted for backward compat but ignored.
    public function decide(string $label, int $declared_amount, string $ocr_text, array $opts = []): array
    {
        $this->reasons = [];
        $decision = 'REFUSE';

        $min_chars = $opts['min_ocr_chars'] ?? 20;
        $accept_ratio = $opts['accept_ratio'] ?? 0.6; // ratio de mots du libellé présents pour ACCEPTE
        $review_ratio = $opts['review_ratio'] ?? 0.2; // en dessous -> REFUSE, entre review_ratio et accept_ratio -> HUMAN_REVIEW

        // 1. texte utile
        if (mb_strlen(trim($ocr_text)) < $min_chars) {
            $this->reasons[] = ['code' => 'TEXT_TOO_SHORT', 'message' => 'Texte fourni trop court ou manquant.'];
            return $this->format($decision);
        }

        // 2. Tokenize label and document, remove short words/stopwords
        $label_tokens = $this->tokens(mb_strtolower($label));
        $doc_tokens = $this->tokens(mb_strtolower($ocr_text));

        if (count($label_tokens) === 0) {
            $this->reasons[] = ['code' => 'LABEL_EMPTY', 'message' => 'Libellé vide ou trop court.'];
            return $this->format($decision);
        }

        $found = 0;
        $found_terms = [];
        foreach ($label_tokens as $t) {
            if (in_array($t, $doc_tokens, true)) {
                $found++;
                $found_terms[] = $t;
            }
        }

        $ratio = $found / count($label_tokens);
        $this->reasons[] = ['code' => 'LABEL_TOKENS', 'message' => sprintf("%d/%d mot(s) du libellé trouvés (%s)", $found, count($label_tokens), implode(', ', $found_terms))];

        if ($ratio >= $accept_ratio) {
            $decision = 'ACCEPTE';
            $this->reasons[] = ['code' => 'TEXT_MATCH', 'message' => sprintf('Correspondance textuelle suffisante (%.0f%%).', $ratio * 100)];
        } elseif ($ratio >= $review_ratio) {
            $decision = 'HUMAN_REVIEW';
            $this->reasons[] = ['code' => 'TEXT_AMBIGUOUS', 'message' => sprintf('Correspondance partielle (%.0f%%) — revoir manuellement.', $ratio * 100)];
        } else {
            $decision = 'REFUSE';
            $this->reasons[] = ['code' => 'TEXT_MISMATCH', 'message' => sprintf('Aucune correspondance textuelle pertinente (%.0f%%).', $ratio * 100)];
        }

        return $this->format($decision);
    }

    protected function format(string $decision, ?int $invoice_price = null): array
    {
        $out = ['decision' => $decision, 'reasons' => $this->reasons];
        if ($invoice_price !== null) $out['invoice_price'] = $invoice_price;
        return $out;
    }

    protected function extract_price(string $text): ?int
    {
        // Deprecated in text-only mode
        return null;
    }

    public function tokens(string $text): array
    {
        // split on non-letters/digits, remove short tokens and common stopwords
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $text);
        $stop = ['de', 'du', 'la', 'le', 'les', 'des', 'et', 'a', 'au', 'aux', 'pour', 'par', 'sur', 'avec', 'un', 'une', 'dans', 'à', 'a'];
        $tokens = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '') continue;
            if (mb_strlen($p) < 3) continue;
            if (in_array($p, $stop, true)) continue;
            $tokens[] = $p;
        }
        // unique tokens
        return array_values(array_unique($tokens));
    }
}
