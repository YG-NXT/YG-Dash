<?php

namespace Workdo\CountryGB\Services;

use App\Classes\Hooks;
use App\Models\User;

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

    public static function registerHooks(): void
    {
        $engine = new self();

        // Register UK-specific deductions into core payroll
        Hooks::add_filter('payroll_calculate_deductions', [$engine, 'addPAYEDeductions'], 10, 3);
        Hooks::add_filter('payroll_calculate_deductions', [$engine, 'addNIDeductions'], 20, 3);
        Hooks::add_filter('payroll_calculate_deductions', [$engine, 'addPensionDeductions'], 30, 3);
        Hooks::add_filter('payroll_calculate_allowances', [$engine, 'addPensionAllowance'], 10, 3);

        // Modify gross/net pay for UK-specific adjustments
        Hooks::add_filter('payroll_calculate_totals', [$engine, 'adjustNetPay'], 10, 3);

        // Post-processing after payroll run (e.g., RTI submission)
        Hooks::add_action('payroll_run_completed', [$engine, 'submitRTI'], 10, 2);
    }

    public function addPAYEDeductions(array $deductionData, $employee, $basicSalary): array
    {
        $company = $employee->user ?? User::find($employee->created_by ?? $employee->id);
        $countryCode = $company->country_code ?? 'GB';

        if (strtoupper($countryCode) !== 'GB') {
            return $deductionData;
        }

        $taxCode = '1257L';
        $userSettings = $company->userSettings ?? [];
        if (!empty($userSettings->paye_reference)) {
            $taxCode = $userSettings->paye_reference ?? '1257L';
        }

        $paye = $this->calculatePAYE($basicSalary, $taxCode);
        $deductionData['breakdown']['PAYE Tax'] = $paye['tax'];
        $deductionData['total'] += $paye['tax'];

        return $deductionData;
    }

    public function addNIDeductions(array $deductionData, $employee, $basicSalary): array
    {
        $company = $employee->user ?? User::find($employee->created_by ?? $employee->id);
        $countryCode = $company->country_code ?? 'GB';

        if (strtoupper($countryCode) !== 'GB') {
            return $deductionData;
        }

        $ni = $this->calculateNationalInsurance($basicSalary);
        $deductionData['breakdown']['National Insurance'] = $ni['employee_ni'];
        $deductionData['total'] += $ni['employee_ni'];

        return $deductionData;
    }

    public function addPensionDeductions(array $deductionData, $employee, $basicSalary): array
    {
        $company = $employee->user ?? User::find($employee->created_by ?? $employee->id);
        $countryCode = $company->country_code ?? 'GB';

        if (strtoupper($countryCode) !== 'GB') {
            return $deductionData;
        }

        $pension = $this->calculatePension($basicSalary);
        $deductionData['breakdown']['Pension'] = $pension['employee_contribution'];
        $deductionData['total'] += $pension['employee_contribution'];

        return $deductionData;
    }

    public function addPensionAllowance(array $allowanceData, $employee, $basicSalary): array
    {
        $company = $employee->user ?? User::find($employee->created_by ?? $employee->id);
        $countryCode = $company->country_code ?? 'GB';

        if (strtoupper($countryCode) !== 'GB') {
            return $allowanceData;
        }

        $pension = $this->calculatePension($basicSalary);
        $allowanceData['breakdown']['Employer Pension'] = $pension['employer_contribution'];
        $allowanceData['total'] += $pension['employer_contribution'];

        return $allowanceData;
    }

    public function adjustNetPay(array $totals, $employee, $payroll): array
    {
        $company = $employee->user ?? User::find($employee->created_by ?? $employee->id);
        $countryCode = $company->country_code ?? 'GB';

        if (strtoupper($countryCode) !== 'GB') {
            return $totals;
        }

        // UK-specific net pay adjustments can be applied here
        // For example: student loan deductions, post-tax deductions
        return $totals;
    }

    public function submitRTI($payroll, $entries): void
    {
        // RTI submission would happen here when HMRC integration is complete
        // For now, this is a placeholder that logs the submission
        \Log::info('UK RTI submission placeholder', [
            'payroll_id' => $payroll->id,
            'employee_count' => $entries->count(),
        ]);
    }

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
