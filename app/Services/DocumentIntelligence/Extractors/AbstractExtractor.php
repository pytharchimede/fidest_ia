<?php
declare(strict_types=1);
namespace FidestIA\Services\DocumentIntelligence\Extractors;
use FidestIA\Services\DocumentIntelligence\TextNormalizer;
abstract class AbstractExtractor
{
    public function __construct(protected readonly TextNormalizer $normalizer=new TextNormalizer()){}
    protected function field(string $text,string $name,array $patterns,string $type='string',float $confidence=.85):array
    {
        foreach($patterns as $pattern)if(preg_match($pattern,$text,$m)){ $raw=trim($m[1]);$value=match($type){'amount'=>$this->normalizer->amount($raw),'date'=>$this->normalizer->date($raw),default=>$raw};if($value!==null)return [$name=>['value'=>$value,'confidence'=>$confidence,'source'=>mb_substr(trim($m[0]),0,180)]];}return [$name=>null];
    }
    protected function common(string $text):array
    {
        return array_merge($this->field($text,'date',['/\b(\d{2}[\/.-]\d{2}[\/.-]\d{4})\b/u'],'date',.78),$this->field($text,'email',['/\b([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})\b/iu'],'string',.95),$this->field($text,'phone',['/\b((?:\+225\s*)?(?:0?\d[ .-]?){10})\b/u'],'string',.75),$this->field($text,'ncc',['/(?:NCC|N°\s*CC)\s*[:#-]?\s*([A-Z0-9-]{5,30})/iu'],'string',.9));
    }
}
