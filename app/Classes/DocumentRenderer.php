<?php

namespace App\Classes;

class DocumentRenderer
{
    public static function render(string $type, array $data, $company): string
    {
        $defaultTemplate = match ($type) {
            'invoice' => 'documents.invoice',
            'quote' => 'documents.quote',
            'credit_note' => 'documents.credit-note',
            'proposal' => 'documents.proposal',
            'payslip' => 'documents.payslip',
            default => 'documents.' . $type,
        };

        $template = Hooks::apply_filters('document_template', $defaultTemplate, $type, $company);

        return $template;
    }

    public static function getTemplateData(string $type, $document, $company): array
    {
        $data = [
            'type' => $type,
            'document' => $document,
            'company' => $company,
        ];

        return Hooks::apply_filters('document_template_data', $data, $type, $document, $company);
    }
}
