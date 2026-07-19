<?php

namespace Workdo\CountryGB\Services;

class UKReportsService
{
    private UKTaxEngine $taxEngine;
    private UKPayrollEngine $payrollEngine;

    public function __construct()
    {
        $this->taxEngine = new UKTaxEngine();
        $this->payrollEngine = new UKPayrollEngine();
    }

    public function generateVATReturnReport(string $from, string $to, array $transactions): array
    {
        $box1 = 0;
        $box2 = 0;
        $box3 = 0;
        $box4 = 0;
        $box5 = 0;
        $box6 = 0;
        $box7 = 0;
        $box8 = 0;
        $box9 = 0;

        foreach ($transactions as $transaction) {
            $amount = $transaction['amount'] ?? 0;
            $type = $transaction['type'] ?? 'sale';

            if ($type === 'sale') {
                $box6 += $amount;
                $tax = $transaction['tax'] ?? 0;
                $box1 += $tax;
            } elseif ($type === 'purchase') {
                $box7 += $amount;
                $tax = $transaction['tax'] ?? 0;
                $box4 += $tax;
            } elseif ($type === 'eu_sale') {
                $box8 += $amount;
            } elseif ($type === 'eu_purchase') {
                $box2 += $amount;
                $box9 += $amount;
            }
        }

        $box3 = $box1 + $box2;
        $box5 = $box3 - $box4;

        return [
            'report_type' => 'VAT Return',
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'boxes' => [
                ['box' => 1, 'label' => 'VAT due on sales', 'value' => round($box1, 2), 'formula' => 'VAT on sales'],
                ['box' => 2, 'label' => 'VAT due on EU acquisitions', 'value' => round($box2, 2), 'formula' => 'VAT on EU purchases'],
                ['box' => 3, 'label' => 'Total VAT due', 'value' => round($box3, 2), 'formula' => 'Box 1 + Box 2'],
                ['box' => 4, 'label' => 'VAT reclaimed on purchases', 'value' => round($box4, 2), 'formula' => 'Input VAT'],
                ['box' => 5, 'label' => 'Net VAT to HMRC', 'value' => round($box5, 2), 'formula' => 'Box 3 - Box 4'],
                ['box' => 6, 'label' => 'Total sales (excl. VAT)', 'value' => round($box6, 2), 'formula' => 'UK sales'],
                ['box' => 7, 'label' => 'Total purchases (excl. VAT)', 'value' => round($box7, 2), 'formula' => 'UK purchases'],
                ['box' => 8, 'label' => 'Total supplies (excl. VAT)', 'value' => round($box8, 2), 'formula' => 'EU sales'],
                ['box' => 9, 'label' => 'Total acquisitions (excl. VAT)', 'value' => round($box9, 2), 'formula' => 'EU purchases'],
            ],
            'summary' => [
                'vat_due' => round($box5, 2),
                'amount_due_to_hmrc' => $box5 > 0 ? round($box5, 2) : 0,
                'amount_due_from_hmrc' => $box5 < 0 ? round(abs($box5), 2) : 0,
            ],
        ];
    }

    public function generateCISReturn(string $period, array $contractors): array
    {
        $totalPayments = 0;
        $totalDeductions = 0;
        $totalMaterials = 0;

        $deductees = [];

        foreach ($contractors as $contractor) {
            $payment = $contractor['payment'] ?? 0;
            $deduction = $contractor['deduction'] ?? 0;
            $materials = $contractor['materials'] ?? 0;
            $verification = $contractor['verification_number'] ?? '';
            $utr = $contractor['utr'] ?? '';

            $totalPayments += $payment;
            $totalDeductions += $deduction;
            $totalMaterials += $materials;

            $deductees[] = [
                'name' => $contractor['name'],
                'utr' => $utr,
                'verification_number' => $verification,
                'gross_payment' => $payment,
                'materials' => $materials,
                'tax_deducted' => $deduction,
                'net_payment' => $payment - $materials - $deduction,
            ];
        }

        return [
            'report_type' => 'CIS Return',
            'period' => $period,
            'summary' => [
                'total_gross_payments' => round($totalPayments, 2),
                'total_materials' => round($totalMaterials, 2),
                'total_tax_deducted' => round($totalDeductions, 2),
                'total_net_paid' => round($totalPayments - $totalMaterials - $totalDeductions, 2),
            ],
            'deductees' => $deductees,
        ];
    }

    public function generatePAYESummary(string $taxYear, array $employees): array
    {
        $totalPayments = 0;
        $totalTax = 0;
        $totalEmployeeNI = 0;
        $totalEmployerNI = 0;

        $payrollData = [];

        $payrollEngine = new UKPayrollEngine();

        foreach ($employees as $employee) {
            $gross = $employee['gross_pay'] ?? 0;
            $taxCode = $employee['tax_code'] ?? '1257L';

            $paye = $payrollEngine->calculatePAYE($gross, $taxCode);
            $ni = $payrollEngine->calculateNationalInsurance($gross);

            $totalPayments += $gross;
            $totalTax += $paye['tax'];
            $totalEmployeeNI += $ni['employee_ni'];
            $totalEmployerNI += $ni['employer_ni'];

            $payrollData[] = [
                'employee_id' => $employee['id'],
                'name' => $employee['name'],
                'nino' => $employee['nino'],
                'tax_code' => $taxCode,
                'gross_pay' => $gross,
                'paye_tax' => $paye['tax'],
                'employee_ni' => $ni['employee_ni'],
                'employer_ni' => $ni['employer_ni'],
                'total_deductions' => $paye['tax'] + $ni['employee_ni'],
                'net_pay' => $gross - $paye['tax'] - $ni['employee_ni'],
            ];
        }

        return [
            'report_type' => 'PAYE Summary',
            'tax_year' => $taxYear,
            'summary' => [
                'total_employees' => count($employees),
                'total_gross_payments' => round($totalPayments, 2),
                'total_paye_tax' => round($totalTax, 2),
                'total_employee_ni' => round($totalEmployeeNI, 2),
                'total_employer_ni' => round($totalEmployerNI, 2),
                'total_ni' => round($totalEmployeeNI + $totalEmployerNI, 2),
            ],
            'employees' => $payrollData,
        ];
    }

    public function generateProfitAndLoss(string $from, string $to, array $accounts): array
    {
        $revenue = 0;
        $costOfSales = 0;
        $operatingExpenses = 0;

        foreach ($accounts as $account) {
            $balance = $account['balance'] ?? 0;
            $type = $account['type'] ?? 'other';

            if ($type === 'revenue') {
                $revenue += $balance;
            } elseif ($type === 'cost_of_sales') {
                $costOfSales += $balance;
            } elseif ($type === 'expense') {
                $operatingExpenses += $balance;
            }
        }

        $grossProfit = $revenue - $costOfSales;
        $netProfit = $grossProfit - $operatingExpenses;

        return [
            'report_type' => 'Profit and Loss',
            'period' => ['from' => $from, 'to' => $to],
            'currency' => 'GBP',
            'revenue' => round($revenue, 2),
            'cost_of_sales' => round($costOfSales, 2),
            'gross_profit' => round($grossProfit, 2),
            'operating_expenses' => round($operatingExpenses, 2),
            'net_profit' => round($netProfit, 2),
            'gross_profit_margin' => $revenue > 0 ? round(($grossProfit / $revenue) * 100, 2) : 0,
            'net_profit_margin' => $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : 0,
        ];
    }

    public function generateBalanceSheet(string $asOf, array $accounts): array
    {
        $assets = 0;
        $currentAssets = 0;
        $fixedAssets = 0;
        $liabilities = 0;
        $currentLiabilities = 0;
        $longTermLiabilities = 0;
        $equity = 0;

        foreach ($accounts as $account) {
            $balance = $account['balance'] ?? 0;
            $category = $account['category'] ?? 'other';

            if ($category === 'current_assets') {
                $currentAssets += $balance;
                $assets += $balance;
            } elseif ($category === 'fixed_assets') {
                $fixedAssets += $balance;
                $assets += $balance;
            } elseif ($category === 'current_liabilities') {
                $currentLiabilities += $balance;
                $liabilities += $balance;
            } elseif ($category === 'long_term_liabilities') {
                $longTermLiabilities += $balance;
                $liabilities += $balance;
            } elseif ($category === 'equity') {
                $equity += $balance;
            }
        }

        $netAssets = $assets - $liabilities;

        return [
            'report_type' => 'Balance Sheet',
            'as_of' => $asOf,
            'currency' => 'GBP',
            'assets' => [
                'fixed_assets' => round($fixedAssets, 2),
                'current_assets' => round($currentAssets, 2),
                'total_assets' => round($assets, 2),
            ],
            'liabilities' => [
                'current_liabilities' => round($currentLiabilities, 2),
                'long_term_liabilities' => round($longTermLiabilities, 2),
                'total_liabilities' => round($liabilities, 2),
            ],
            'equity' => round($equity, 2),
            'net_assets' => round($netAssets, 2),
            'balance_check' => abs(($assets - $liabilities) - $equity) < 0.01,
        ];
    }
}
