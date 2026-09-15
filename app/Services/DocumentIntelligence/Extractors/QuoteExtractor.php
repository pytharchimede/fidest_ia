<?php
declare(strict_types=1);
namespace FidestIA\Services\DocumentIntelligence\Extractors;
use FidestIA\Contracts\DocumentExtractorInterface;
final class QuoteExtractor extends AbstractExtractor implements DocumentExtractorInterface
{
    public function supports(string $type):bool{return (bool)preg_match('/QUOTE|DEVIS/i',$type);}
    public function extract(string $text):array{return array_merge($this->common($text),$this->field($text,'quote_number',['/(?:DEVIS|PROFORMA)\s*(?:N[°ºo])?\s*[:#-]?\s*([A-Z0-9\/-]{3,40})/iu'],'string',.9),$this->field($text,'amount_ht',['/TOTAL\s*H\.?T\.?(?:VA)?\s*[: ]+([0-9][0-9 .,:]*)/iu'],'amount',.88),$this->field($text,'amount_ttc',['/TOTAL\s*TTC\s*[: ]+([0-9][0-9 .,:]*)/iu'],'amount',.92));}
}
