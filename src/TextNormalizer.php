<?php

namespace FidestIA;

use Wamania\Snowball\Stemmer\French;

class TextNormalizer
{
    protected array $stop = ['de', 'du', 'la', 'le', 'les', 'des', 'et', 'a', 'au', 'aux', 'pour', 'par', 'sur', 'avec', 'un', 'une', 'dans', 'à', 'a', 'au'];
    protected ?array $synonyms = null;

    public function __construct()
    {
        $path = __DIR__ . '/../data/synonyms.json';
        if (is_file($path)) {
            $this->synonyms = json_decode(file_get_contents($path), true) ?: null;
        }
    }

    public function normalizeTokens(string $text): array
    {
        $text = mb_strtolower($text);
        $text = $this->removeAccents($text);
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $text);
        $tokens = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '' || mb_strlen($p) < 3) continue;
            if (in_array($p, $this->stop, true)) continue;
            $tokens[] = $this->stem($p);
            // expand synonyms
            if ($this->synonyms && isset($this->synonyms[$p])) {
                foreach ($this->synonyms[$p] as $s) $tokens[] = $this->stem($s);
            }
        }
        return array_values(array_unique($tokens));
    }

    protected function stem(string $word): string
    {
        try {
            $stemmer = new French();
            return $stemmer->stem($word);
        } catch (\Throwable $e) {
            return $word;
        }
    }

    protected function removeAccents(string $s): string
    {
        $trans = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        return $trans ?: $s;
    }
}
