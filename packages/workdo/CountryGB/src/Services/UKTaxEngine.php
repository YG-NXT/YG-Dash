<?php

namespace Workdo\CountryGB\Services;

class UKTaxEngine
{
    private array $vatRates = [
        'standard' => ['rate' => 0.20, 'name' => 'Standard Rated'],
        'reduced' => ['rate' => 0.05, 'name' => 'Reduced Rated'],
        'zero' => ['rate' => 0.00, 'name' => 'Zero Rated'],
        'exempt' => ['rate' => null, 'name' => 'Exempt'],
        'outside_scope' => ['rate' => null, 'name' => 'Outside Scope'],
    ];

    public function getTaxCategories(): array
    {
        return [
            ['code' => 'standard', 'name' => 'Standard Rated (20%)', 'rate' => 0.20],
            ['code' => 'reduced', 'name' => 'Reduced Rated (5%)', 'rate' => 0.05],
            ['code' => 'zero', 'name' => 'Zero Rated (0%)', 'rate' => 0.00],
            ['code' => 'exempt', 'name' => 'Exempt', 'rate' => null],
            ['code' => 'outside_scope', 'name' => 'Outside Scope', 'rate' => null],
        ];
    }

    public function calculate(array $items, array $context = []): array
    {
        $company = $context['company'] ?? null;
        $customer = $context['customer'] ?? null;
        $vatRegistered = $company && !empty($company->vat_number);

        $subtotal = 0;
        $totalTax = 0;
        $taxDetails = [];

        foreach ($items as $item) {
            $itemSubtotal = ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
            $subtotal += $itemSubtotal;

            $taxCode = $item['tax_code'] ?? 'standard';
            $itemTaxRate = $this->getEffectiveTaxRate($taxCode, $company, $customer);
            $itemTax = $itemSubtotal * $itemTaxRate;
            $totalTax += $itemTax;

            $taxDetails[] = [
                'item' => $item['name'] ?? 'Item',
                'subtotal' => round($itemSubtotal, 2),
                'rate' => $itemTaxRate,
                'tax' => round($itemTax, 2),
                'tax_code' => $taxCode,
            ];
        }

        return [
            'subtotal' => round($subtotal, 2),
            'total_tax' => round($totalTax, 2),
            'total' => round($subtotal + $totalTax, 2),
            'currency' => 'GBP',
            'tax_details' => $taxDetails,
            'breakdown' => [
                ['label' => 'Subtotal', 'amount' => round($subtotal, 2)],
                ['label' => 'VAT', 'amount' => round($totalTax, 2)],
                ['label' => 'Total', 'amount' => round($subtotal + $totalTax, 2)],
            ],
            'vat_registered' => $vatRegistered,
        ];
    }

    private function getEffectiveTaxRate(string $taxCode, $company, $customer): float
    {
        if (!isset($this->vatRates[$taxCode])) {
            $taxCode = 'standard';
        }

        $rate = $this->vatRates[$taxCode]['rate'];

        if ($rate === null) {
            return 0.0;
        }

        if ($taxCode === 'exempt' || $taxCode === 'outside_scope') {
            return 0.0;
        }

        if ($taxCode === 'standard') {
            if ($this->isReverseChargeApplicable($company, $customer)) {
                return 0.0;
            }
        }

        return $rate;
    }

    private function isReverseChargeApplicable($company, $customer): bool
    {
        if (!$company || !$customer) {
            return false;
        }

        if ($company->country_code !== 'GB' || $customer->country_code !== 'GB') {
            return false;
        }

        $companyVat = str_replace([' ', '-', '.'], '', $company->vat_number ?? '');
        $customerVat = str_replace([' ', '-', '.'], '', $customer->vat_number ?? '');

        if (empty($companyVat) || empty($customerVat)) {
            return false;
        }

        if (!str_starts_with(strtoupper($companyVat), 'GB') || !str_starts_with(strtoupper($customerVat), 'GB')) {
            return false;
        }

        return true;
    }

    public function getTaxReport(string $period, string $reportType): array
    {
        if ($reportType === 'vat_return') {
            return $this->getVATReturn9Box($period);
        }

        return [];
    }

    private function getVATReturn9Box(string $period): array
    {
        return [
            'period' => $period,
            'return_type' => 'VAT',
            'boxes' => [
                ['box' => 1, 'label' => 'VAT due on sales', 'value' => 0, 'formula' => 'Box 1 = Box 3 - Box 4'],
                ['box' => 2, 'label' => 'VAT due on acquisitions (EU)', 'value' => 0, 'formula' => 'Box 2 = VAT on EU purchases'],
                ['box' => 3, 'label' => 'Total VAT due', 'value' => 0, 'formula' => 'Box 3 = Box 1 + Box 2'],
                ['box' => 4, 'label' => 'VAT reclaimed on purchases', 'value' => 0, 'formula' => 'Box 4 = Input VAT'],
                ['box' => 5, 'label' => 'Net VAT to pay HMRC', 'value' => 0, 'formula' => 'Box 5 = Box 3 - Box 4'],
                ['box' => 6, 'label' => 'Total sales (excluding VAT)', 'value' => 0, 'formula' => 'Box 6 = Net sales'],
                ['box' => 7, 'label' => 'Total purchases (excluding VAT)', 'value' => 0, 'formula' => 'Box 7 = Net purchases'],
                ['box' => 8, 'label' => 'Total supplies (excluding VAT)', 'value' => 0, 'formula' => 'Box 8 = EU sales'],
                ['box' => 9, 'label' => 'Total acquisitions (excluding VAT)', 'value' => 0, 'formula' => 'Box 9 = EU purchases'],
            ],
        ];
    }

    public function getFilingDueDate(string $period, string $taxType): \DateTime
    {
        if ($taxType === 'vat') {
            $quarterEnd = new \DateTime($period);
            $quarterEnd->modify('last day of this month');

            $dueDate = clone $quarterEnd;
            $dueDate->modify('+1 month');

            if ($dueDate->format('d') !== '07') {
                $dueDate->setDate($dueDate->format('Y'), $dueDate->format('m'), '07');
            }

            return $dueDate;
        }

        return new \DateTime('now');
    }

    public function calculateVATOnAmount(float $amount, string $taxCode = 'standard'): float
    {
        $rate = $this->vatRates[$taxCode]['rate'] ?? 0.20;

        if ($rate === null) {
            return 0.0;
        }

        return round($amount * $rate, 2);
    }

    public function extractVATFromTotal(float $total, string $taxCode = 'standard'): array
    {
        $rate = $this->vatRates[$taxCode]['rate'] ?? 0.20;

        if ($rate === null || $rate === 0.0) {
            return [
                'net' => $total,
                'vat' => 0.0,
                'gross' => $total,
            ];
        }

        $net = round($total / (1 + $rate), 2);
        $vat = round($total - $net, 2);

        return [
            'net' => $net,
            'vat' => $vat,
            'gross' => $total,
        ];
    }
}
