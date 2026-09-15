<?php
declare(strict_types=1);
namespace FidestIA\Services\DocumentIntelligence\Extractors;
use FidestIA\Contracts\DocumentExtractorInterface;
final class InvoiceExtractor extends AbstractExtractor implements DocumentExtractorInterface
{
    public function supports(string $type):bool{return (bool)preg_match('/INVOICE|FACTURE|FNE/i',$type);}
    public function extract(string $text):array{return array_merge($this->common($text),$this->field($text,'invoice_number',['/(?:N[°ºo]?\s*(?:FACTURE)?|FACTURE\s*N[°ºo]?)\s*[:#-]?\s*([A-Z0-9\/-]{3,40})/iu'],'string',.9),$this->field($text,'amount_ht',['/(?:TOTAL\s*)?H\.?T\.?(?:VA)?\s*[: ]+([0-9][0-9 .,:]*\s*(?:FCFA|F\s*CFA|CFA|XOF)?)/iu'],'amount',.9),$this->field($text,'amount_tax',['/(?:MONTANT\s*)?TVA\s*[: ]+([0-9][0-9 .,:]*\s*(?:FCFA|F\s*CFA|CFA|XOF)?)/iu'],'amount',.88),$this->field($text,'tax_rate',['/TVA\s*(?:AU\s*TAUX\s*DE)?\s*([0-9]{1,2}(?:[,.][0-9]+)?)\s*%/iu'],'string',.87),$this->field($text,'amount_ttc',['/(?:TOTAL\s*TTC|NET\s+[ÀA]\s+PAYER)\s*[: ]+([0-9][0-9 .,:]*\s*(?:FCFA|F\s*CFA|CFA|XOF)?)/iu'],'amount',.94),$this->field($text,'currency',['/\b(FCFA|F\s*CFA|CFA|XOF)\b/iu'],'string',.95));}
}
