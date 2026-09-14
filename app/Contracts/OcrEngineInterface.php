<?php

namespace FidestIA\Contracts;

interface OcrEngineInterface
{
    /** @return array{text:string,confidence:?float,meta:array} */
    public function extract(string $absolutePath, string $mimeType): array;
}
