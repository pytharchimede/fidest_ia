<?php
declare(strict_types=1);
namespace FidestIA\Services\DocumentIntelligence;
final class AnomalyDetector
{
    public function __construct(private readonly float $amountTolerance=1.0){}
    public function detect(array $fields,string $type,?float $ocrConfidence,?float $classificationConfidence):array
    {
        $v=fn(string $key)=>$fields[$key]['value']??$fields[$key]??null;$out=[];$add=function(string $code,string $severity,string $message,array $extra=[])use(&$out){$out[]=array_merge(['code'=>$code,'severity'=>$severity,'message'=>$message],$extra);};
        $ht=$v('amount_ht');$tax=$v('amount_tax');$ttc=$v('amount_ttc');if(is_numeric($ht)&&is_numeric($tax)&&is_numeric($ttc)&&abs(((float)$ht+(float)$tax)-(float)$ttc)>$this->amountTolerance)$add('TOTAL_MISMATCH','warning','Le total TTC ne correspond pas à HT + TVA.',['expected'=>(float)$ht+(float)$tax,'detected'=>(float)$ttc]);
        $date=$v('date');if(is_string($date)&&strtotime($date)>strtotime('+2 days'))$add('FUTURE_DATE','warning','La date détectée est future.',['detected'=>$date]);
        if(preg_match('/INVOICE|FACTURE|FNE/i',$type)){if(!$v('invoice_number'))$add('MISSING_INVOICE_NUMBER','warning','Numéro de facture absent.');if(!is_numeric($ttc))$add('MISSING_TOTAL_TTC','warning','Total TTC absent.');}
        if(($ocrConfidence??1)<.6)$add('LOW_OCR_CONFIDENCE','critical','Document partiellement illisible ou OCR de faible qualité.');if(($classificationConfidence??1)<.7)$add('UNCERTAIN_DOCUMENT_TYPE','warning','Le type documentaire doit être vérifié.');return $out;
    }
}
