<?php

namespace Workdo\CountryGB\Services;

class UKPayrollEngine
{
    private array $taxCodes = [
        '1257L' => ['rate' => 0.20, 'allowance' => 12570, 'description' => 'Standard tax code'],
        '1257M' => ['rate' => 0.42, 'allowance' => 12570, 'description' => 'Marriage allowance transfer'],
        '1257N' => ['rate' => 0.42, 'allowance' => 0, 'description' => 'Marriage allowance receive'],
        'BR' => ['rate' => 0.20, 'allowance' => 0, 'description' => 'Basic rate (20%)'],
        'D0' => ['rate' => 0.40, 'allowance' => 0, 'description' => 'Higher rate (40%)'],
        'D1' => ['rate' => 0.45, 'allowance' => 0, 'description' => 'Additional rate (45%)'],
        'NT' => ['rate' => 0.00, 'allowance' => 0, 'description' => 'No tax'],
    ];

    private array $niRates = [
        'employee' => [
            'primary_threshold' => 12570,
            'secondary_threshold' => 12570,
            'rate_above_pt' => 0.12,
            'rate_above_uel' => 0.02,
            'upper_earnings_limit' => 50270,
        ],
        'employer' => [
            'secondary_threshold' => 12570,
            'rate' => 0.138,
        ],
    ];

    private array $pensionRates = [
        'employee' => 0.05,
        'employer' => 0.03,
        'minimum' => 0.08,
    ];

    public function calculatePAYE(float $grossPay, string $taxCode = '1257L', array $adjustments = []): array
    {
        if (!isset($this->taxCodes[$taxCode])) {
            $taxCode = '1257L';
        }

        $code = $this->taxCodes[$taxCode];
        $allowance = $code['allowance'];

        foreach ($adjustments as $key => $value) {
            if (in_array($key, ['allowance', 'tax_rate'])) {
                $code[$key] = $value;
                if ($key === 'allowance') {
                    $allowance = $value;
                }
            }
        }

        $taxablePay = max(0, $grossPay - $allowance);
        $tax = round($taxablePay * $code['rate'], 2);

        return [
            'gross_pay' => round($grossPay, 2),
            'tax_code' => $taxCode,
            'allowance' => round($allowance, 2),
            'taxable_pay' => round($taxablePay, 2),
            'tax_rate' => $code['rate'],
            'tax' => $tax,
            'description' => $code['description'],
        ];
    }

    public function calculateNationalInsurance(float $grossPay, bool $hasContractualNIC = false, int $age = 25): array
    {
        $rates = $this->niRates;

        $pt = $rates['employee']['primary_threshold'];
        $uel = $rates['employee']['upper_earnings_limit'];
        $rateAbovePT = $rates['employee']['rate_above_pt'];
        $rateAboveUEL = $rates['employee']['rate_above_uel'];
        $employerRate = $rates['employer']['rate'];
        $st = $rates['employer']['secondary_threshold'];

        $employeeNI = 0.0;
        if ($grossPay > $pt) {
            $band1 = min($grossPay, $uel) - $pt;
            $employeeNI += $band1 * $rateAbovePT;

            if ($grossPay > $uel) {
                $band2 = $grossPay - $uel;
                $employeeNI += $band2 * $rateAboveUEL;
            }
        }

        $employerNI = 0.0;
        if ($grossPay > $st) {
            $employerNI = ($grossPay - $st) * $employerRate;
        }

        return [
            'gross_pay' => round($grossPay, 2),
            'employee_ni' => round($employeeNI, 2),
            'employer_ni' => round($employerNI, 2),
            'total_ni' => round($employeeNI + $employerNI, 2),
            'rates' => [
                'employee_rate_pt_to_uel' => $rateAbovePT,
                'employee_rate_above_uel' => $rateAboveUEL,
                'employer_rate' => $employerRate,
            ],
        ];
    }

    public function calculatePension(float $grossPay, float $employeeRate = null, float $employerRate = null): array
    {
        $employeeRate = $employeeRate ?? $this->pensionRates['employee'];
        $employerRate = $employerRate ?? $this->pensionRates['employer'];

        $employeeContribution = round($grossPay * $employeeRate, 2);
        $employerContribution = round($grossPay * $employerRate, 2);

        return [
            'gross_pay' => round($grossPay, 2),
            'employee_contribution' => $employeeContribution,
            'employer_contribution' => $employerContribution,
            'total_contribution' => round($employeeContribution + $employerContribution, 2),
            'employee_rate' => $employeeRate,
            'employer_rate' => $employerRate,
            'qualifying_earnings' => round($grossPay, 2),
        ];
    }

    public function calculatePayPacket(array $payrollData): array
    {
        $grossPay = $payrollData['gross_pay'] ?? 0;
        $taxCode = $payrollData['tax_code'] ?? '1257L';
        $overtime = $payrollData['overtime'] ?? 0;
        $bonus = $payrollData['bonus'] ?? 0;
        $deductions = $payrollData['deductions'] ?? [];

        $totalGross = $grossPay + $overtime + $bonus;

        $paye = $this->calculatePAYE($totalGross, $taxCode, $payrollData['tax_adjustments'] ?? []);
        $ni = $this->calculateNationalInsurance($totalGross);
        $pension = $this->calculatePension($totalGross);

        $totalDeductions = $paye['tax'] + $ni['employee_ni'] + $pension['employee_contribution'];

        foreach ($deductions as $deduction) {
            $totalDeductions += $deduction['amount'] ?? 0;
        }

        $netPay = $totalGross - $totalDeductions;

        return [
            'gross_pay' => round($totalGross, 2),
            'paye_tax' => $paye,
            'national_insurance' => $ni,
            'pension' => $pension,
            'other_deductions' => $deductions,
            'total_deductions' => round($totalDeductions, 2),
            'net_pay' => round($netPay, 2),
            'employer_ni' => round($ni['employer_ni'], 2),
            'employer_pension' => round($pension['employer_contribution'], 2),
        ];
    }

    public function generateRTIFPS(array $payrollData): array
    {
        $employees = [];

        foreach ($payrollData['employees'] as $employee) {
            $payPacket = $this->calculatePayPacket($employee);

            $employees[] = [
                'employee_id' => $employee['employee_id'],
                'nino' => $employee['nino'],
                'name' => $employee['name'],
                'gross_pay' => $payPacket['gross_pay'],
                'paye_tax' => $payPacket['paye_tax']['tax'],
                'employee_ni' => $payPacket['national_insurance']['employee_ni'],
                'net_pay' => $payPacket['net_pay'],
            ];
        }

        return [
            'submission_type' => 'FPS',
            'tax_period' => $payrollData['tax_period'] ?? date('Ym'),
            'employer_reference' => $payrollData['employer_reference'] ?? '',
            'payment_date' => $payrollData['payment_date'] ?? date('Y-m-d'),
            'total_payments' => array_sum(array_column($employees, 'gross_pay')),
            'total_tax_deducted' => array_sum(array_column($employees, 'paye_tax')),
            'total_employee_ni' => array_sum(array_column($employees, 'employee_ni')),
            'employees' => $employees,
        ];
    }

    public function validateNINO(string $nino): bool
    {
        $nino = strtoupper(preg_replace('/\s+/', '', $nino));

        $pattern = '/^[A-CEGHJ-PR-TW-Z]{1}[A-CEGHJ-NPR-TW-Z]{1}[0-9]{6}[A-DFM]{0,1}$/';

        return preg_match($pattern, $nino) === 1;
    }

    public function getTaxCodeDescription(string $taxCode): string
    {
        return $this->taxCodes[$taxCode]['description'] ?? 'Unknown tax code';
    }

    public function getApplicableTaxCodes(): array
    {
        return array_keys($this->taxCodes);
    }

    public function calculateAnnualTaxLiability(float $annualSalary, string $taxCode = '1257L'): array
    {
        $paye = $this->calculatePAYE($annualSalary, $taxCode);

        $monthlyPAYE = $this->calculatePAYE($annualSalary / 12, $taxCode);
        $weeklyPAYE = $this->calculatePAYE($annualSalary / 52, $taxCode);

        return [
            'annual_gross' => round($annualSalary, 2),
            'annual_paye' => $paye['tax'],
            'monthly_paye' => $monthlyPAYE['tax'],
            'weekly_paye' => $weeklyPAYE['tax'],
            'annual_net' => round($annualSalary - $paye['tax'], 2),
            'tax_code' => $taxCode,
        ];
    }
}
