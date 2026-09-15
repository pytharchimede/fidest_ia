<?php
declare(strict_types=1);
namespace FidestIA\Services\DocumentIntelligence;
final class ConfidenceCalculator
{
    public function calculate(?float $ocr,?float $classification,array $fields,array $validation,array $anomalies):array
    {
        $fieldScores=[];foreach($fields as $field)if(is_array($field)&&isset($field['confidence']))$fieldScores[]=(float)$field['confidence'];$extraction=$fieldScores===[]?0.0:array_sum($fieldScores)/count($fieldScores);$results=$validation['results']??[];$passed=count(array_filter($results,fn($r)=>(bool)($r['passed']??false)));$validationScore=$results===[]?1.0:$passed/count($results);$critical=count(array_filter($anomalies,fn($a)=>($a['severity']??'')==='critical'));$overall=max(0,min(1,(($ocr??.5)*.25)+(($classification??.5)*.25)+($extraction*.3)+($validationScore*.2)-($critical*.15)));
        return ['ocr'=>round($ocr??.5,2),'classification'=>round($classification??.5,2),'extraction'=>round($extraction,2),'validation'=>round($validationScore,2),'overall'=>round($overall,2)];
    }
}
