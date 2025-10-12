<?php

namespace FidestIA;

use thiagoalessio\TesseractOCR\TesseractOCR;

class OCR
{
    public static function fromImage(string $path): string
    {
        $t = new TesseractOCR($path);
        return trim($t->run());
    }
}
