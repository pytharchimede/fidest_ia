<?php
declare(strict_types=1);
namespace FidestIA\Contracts;
interface DocumentExtractorInterface
{
    public function supports(string $documentType):bool;
    /** @return array<string,array{value:mixed,confidence:float,source:string}|null> */
    public function extract(string $text):array;
}
