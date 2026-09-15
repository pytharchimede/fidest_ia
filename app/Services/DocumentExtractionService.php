<?php

declare(strict_types=1);

namespace FidestIA\Services;

final class DocumentExtractionService
{
    public function extract(string $text, string $documentTypeCode, array $schema = []): array
    {
        $clean = preg_replace('/[\t ]+/', ' ', $text) ?? $text;
        $data = [];

        if ($documentTypeCode === 'FNE_INVOICE') {
            $data['invoice_number'] = $this->match($clean, [
                '/(?:facture|fne|n[°o]|num[eé]ro)\s*[:#-]?\s*([A-Z0-9\/-]{4,40})/iu',
            ]);
            $data['date'] = $this->match($clean, ['/\b(\d{2}[\/.-]\d{2}[\/.-]\d{4})\b/u']);
            $data['amount_ttc'] = $this->amount($clean, ['total ttc', 'montant ttc', 'net a payer', 'net à payer']);
        }

        if ($documentTypeCode === 'PURCHASE_ORDER') {
            $data['order_number'] = $this->match($clean, [
                '/(?:bon\s+de\s+commande|commande|bc|n[°o])\s*[:#-]?\s*([A-Z0-9\/-]{3,40})/iu',
            ]);
            $data['date'] = $this->match($clean, ['/\b(\d{2}[\/.-]\d{2}[\/.-]\d{4})\b/u']);
            $data['amount'] = $this->amount($clean, ['total', 'montant']);
        }

        foreach (['client_name', 'supplier_name'] as $field) {
            $data[$field] = $this->labeledValue($clean, $field === 'client_name' ? ['client', 'destinataire'] : ['fournisseur', 'vendeur']);
        }

        foreach (($schema['fields'] ?? []) as $definition) {
            $field = is_array($definition) ? trim((string)($definition['name']??'')) : trim((string)$definition);
            if ($field === '' || array_key_exists($field, $data)) {
                continue;
            }
            $labels = array_unique(array_merge((array)(is_array($definition)?($definition['labels']??[]):[]),(array)(is_array($definition)?($definition['aliases']??[]):[]),[
                $field,
                str_replace('_', ' ', $field),
                str_replace(['_', '-'], ' ', mb_strtolower($field)),
            ]));
            $value=null;
            foreach((array)(is_array($definition)?($definition['regex']??[]):[]) as $pattern){$value=$this->match($clean,[(string)$pattern]);if($value!==null)break;}
            $value ??= $this->labeledValue($clean,$labels);
            $normalizer=is_array($definition)?(string)($definition['normalizer']??$definition['type']??'whitespace'):'whitespace';
            $data[$field]=$this->normalizeValue($value,$normalizer);
        }

        return array_filter($data, static fn ($value) => $value !== null && $value !== '');
    }

    private function normalizeValue(?string $value,string $type):mixed
    {
        if($value===null)return null;$value=trim((string)preg_replace('/\s+/u',' ',$value));
        if(in_array($type,['number','currency','montant'],true)){$number=preg_replace('/[^0-9,.-]/','',$value)??'';$number=str_replace(['.',','],['','.'],$number);return is_numeric($number)?(float)$number:null;}
        if($type==='boolean')return in_array(mb_strtolower($value),['oui','yes','true','1'],true);
        if($type==='email')return filter_var($value,FILTER_VALIDATE_EMAIL)?mb_strtolower($value):null;
        if($type==='phone')return preg_replace('/(?!^\+)[^0-9]/','',$value);
        if($type==='ncc')return strtoupper((string)preg_replace('/[^A-Z0-9]/i','',$value));
        if($type==='iban')return strtoupper((string)preg_replace('/\s+/','',$value));
        if($type==='date'){foreach(['d/m/Y','d-m-Y','Y-m-d','d.m.Y'] as $format){$date=\DateTimeImmutable::createFromFormat($format,$value);if($date&&$date->format($format)===$value)return $date->format('Y-m-d');}}
        return $value;
    }

    private function match(string $text, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }
        return null;
    }

    private function labeledValue(string $text, array $labels): ?string
    {
        foreach ($labels as $label) {
            if (preg_match('/' . preg_quote($label, '/') . '\s*[:\-]\s*([^\r\n]{2,160})/iu', $text, $m)) {
                return trim($m[1]);
            }
        }
        return null;
    }

    private function amount(string $text, array $labels): ?float
    {
        foreach ($labels as $label) {
            if (preg_match('/' . preg_quote($label, '/') . '.{0,25}?([0-9][0-9 .]{1,18}(?:,[0-9]{2})?)/iu', $text, $m)) {
                $normalized = str_replace([' ', '.'], '', $m[1]);
                $normalized = str_replace(',', '.', $normalized);
                return is_numeric($normalized) ? (float) $normalized : null;
            }
        }
        return null;
    }
}
