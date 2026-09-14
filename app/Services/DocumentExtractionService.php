<?php

namespace FidestIA\Services;

final class DocumentExtractionService
{
    public function extract(string $text, string $documentTypeCode): array
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

        return array_filter($data, static fn ($value) => $value !== null && $value !== '');
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
            if (preg_match('/' . preg_quote($label, '/') . '\s*[:\-]\s*([^\r\n]{2,120})/iu', $text, $m)) {
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
