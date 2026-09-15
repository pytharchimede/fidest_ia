<?php
declare(strict_types=1);
namespace FidestIA\Services\DocumentIntelligence\Extractors;
use FidestIA\Contracts\DocumentExtractorInterface;
final class GenericExtractor extends AbstractExtractor implements DocumentExtractorInterface
{
    public function supports(string $type):bool{return true;}
    public function extract(string $text):array{return array_merge($this->common($text),$this->field($text,'document_number',['/(?:N[°ºo]|NUM[ÉE]RO|R[ÉE]F[ÉE]RENCE)\s*[:#-]?\s*([A-Z0-9\/-]{3,40})/iu'],'string',.65));}
}
