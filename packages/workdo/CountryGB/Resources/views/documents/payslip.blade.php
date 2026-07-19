<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $payroll->employee->name ?? 'Employee' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #333; }
        .payslip-box { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .company-info { text-align: right; }
        .title { font-size: 18pt; font-weight: bold; color: #000; margin-bottom: 5px; }
        .subtitle { font-size: 10pt; color: #666; }
        .employee-info { margin-bottom: 20px; }
        .employee-info table { width: 100%; border-collapse: collapse; }
        .employee-info td { padding: 4px; vertical-align: top; }
        .employee-info td:first-child { font-weight: bold; width: 150px; }
        .main-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .main-table th { background: #f5f5f5; padding: 8px; text-align: left; border-bottom: 2px solid #000; font-size: 9pt; }
        .main-table td { padding: 6px 8px; border-bottom: 1px solid #ddd; font-size: 9pt; }
        .main-table td:last-child, .main-table th:last-child { text-align: right; }
        .main-table .section-header { background: #e8e8e8; font-weight: bold; }
        .totals { display: flex; justify-content: flex-end; margin-top: 15px; }
        .totals table { width: 350px; border-collapse: collapse; }
        .totals td { padding: 6px 8px; border-bottom: 1px solid #ddd; }
        .totals td:last-child { text-align: right; }
        .totals tr.net-pay td { font-size: 12pt; font-weight: bold; border-bottom: 2px solid #000; background: #f9f9f9; }
        .info-box { margin-top: 20px; padding: 12px; background: #f5f5f5; border: 1px solid #ddd; font-size: 9pt; }
        .info-box h3 { margin-bottom: 5px; font-size: 10pt; }
        .info-box p { margin: 2px 0; }
        .legal-footer { margin-top: 20px; font-size: 8pt; color: #666; text-align: center; border-top: 1px solid #ddd; padding-top: 10px; }
        .ni-box { background: #fff3cd; padding: 10px; margin: 10px 0; border-left: 3px solid #ffc107; }
        .tax-code-box { background: #e7f3fe; padding: 10px; margin: 10px 0; border-left: 3px solid #2196F3; }
    </style>
</head>
<body>
    <div class="payslip-box">
        <div class="header">
            <div>
                <div class="title">PAYSLIP</div>
                <div class="subtitle">Pay Period: {{ $payroll->pay_period_start ?? '' }} to {{ $payroll->pay_period_end ?? '' }}</div>
                <div class="subtitle">Payment Date: {{ $payroll->payment_date ?? '' }}</div>
            </div>
            <div class="company-info">
                <strong>{{ $payroll->company->name ?? 'Employer' }}</strong><br>
                {{ $payroll->company->address ?? '' }}<br>
                @if(!empty($payroll->company->paye_reference))
                    PAYE Ref: {{ $payroll->company->paye_reference }}<br>
                @endif
                @if(!empty($payroll->company->company_number))
                    Company No: {{ $payroll->company->company_number }}<br>
                @endif
                @if(!empty($payroll->company->accounts_office_reference))
                    Accounts Office Ref: {{ $payroll->company->accounts_office_reference }}
                @endif
            </div>
        </div>

        <div class="employee-info">
            <table>
                <tr>
                    <td>Employee:</td>
                    <td>{{ $payroll->employee->name ?? 'N/A' }}</td>
                    <td>Employee ID:</td>
                    <td>{{ $payroll->employee->employee_id ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>NINO:</td>
                    <td>{{ $payroll->employee->nino ?? 'N/A' }}</td>
                    <td>Tax Code:</td>
                    <td>{{ $payroll->tax_code ?? '1257L' }}</td>
                </tr>
                <tr>
                    <td>Department:</td>
                    <td>{{ $payroll->employee->department ?? 'N/A' }}</td>
                    <td>Pay Method:</td>
                    <td>BACS</td>
                </tr>
            </table>
        </div>

        <div class="tax-code-box">
            <strong>Tax Code: {{ $payroll->tax_code ?? '1257L' }}</strong>
            @if(isset($payroll->tax_code_description))
                <p style="margin-top: 4px;">{{ $payroll->tax_code_description }}</p>
            @endif
        </div>

        <table class="main-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Description</th>
                    <th style="width: 15%;">Reference</th>
                    <th style="width: 15%;">Rate</th>
                    <th style="width: 15%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr class="section-header">
                    <td colspan="4">EARNINGS</td>
                </tr>
                <tr>
                    <td>Basic Salary</td>
                    <td>BAS</td>
                    <td></td>
                    <td>&pound;{{ number_format($payroll->basic_salary ?? 0, 2) }}</td>
                </tr>
                @if(($payroll->overtime ?? 0) > 0)
                    <tr>
                        <td>Overtime</td>
                        <td>OT</td>
                        <td></td>
                        <td>&pound;{{ number_format($payroll->overtime ?? 0, 2) }}</td>
                    </tr>
                @endif
                @if(($payroll->bonus ?? 0) > 0)
                    <tr>
                        <td>Bonus</td>
                        <td>BON</td>
                        <td></td>
                        <td>&pound;{{ number_format($payroll->bonus ?? 0, 2) }}</td>
                    </tr>
                @endif
                <tr style="font-weight: bold; background: #f9f9f9;">
                    <td>Total Gross Pay</td>
                    <td></td>
                    <td></td>
                    <td>&pound;{{ number_format($payroll->gross_pay ?? 0, 2) }}</td>
                </tr>

                <tr class="section-header">
                    <td colspan="4">DEDUCTIONS</td>
                </tr>
                <tr>
                    <td>Income Tax (PAYE)</td>
                    <td>TAX</td>
                    <td>{{ number_format(($payroll->paye_tax_rate ?? 0.20) * 100, 0) }}%</td>
                    <td>&pound;{{ number_format($payroll->paye_tax ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td>Employee National Insurance</td>
                    <td>NI</td>
                    <td>12%</td>
                    <td>&pound;{{ number_format($payroll->employee_ni ?? 0, 2) }}</td>
                </tr>
                @if(($payroll->pension_employee ?? 0) > 0)
                    <tr>
                        <td>Pension Contribution</td>
                        <td>PEN</td>
                        <td>{{ number_format(($payroll->pension_employee_rate ?? 0.05) * 100, 0) }}%</td>
                        <td>&pound;{{ number_format($payroll->pension_employee ?? 0, 2) }}</td>
                    </tr>
                @endif
                @if(!empty($payroll->other_deductions))
                    @foreach($payroll->other_deductions as $deduction)
                        <tr>
                            <td>{{ $deduction['description'] ?? 'Other Deduction' }}</td>
                            <td>{{ $deduction['code'] ?? 'OTH' }}</td>
                            <td></td>
                            <td>&pound;{{ number_format($deduction['amount'] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                @endif
                <tr style="font-weight: bold; background: #f9f9f9;">
                    <td>Total Deductions</td>
                    <td></td>
                    <td></td>
                    <td>&pound;{{ number_format($payroll->total_deductions ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td>Gross Pay:</td>
                    <td>&pound;{{ number_format($payroll->gross_pay ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td>Total Deductions:</td>
                    <td>-&pound;{{ number_format($payroll->total_deductions ?? 0, 2) }}</td>
                </tr>
                <tr class="net-pay">
                    <td>Net Pay:</td>
                    <td>&pound;{{ number_format($payroll->net_pay ?? 0, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="ni-box">
            <strong>Employer Contributions</strong>
            <p style="margin-top: 5px;">
                Employer National Insurance: <strong>&pound;{{ number_format($payroll->employer_ni ?? 0, 2) }}</strong>
                @if(($payroll->employer_pension ?? 0) > 0)
                    | Employer Pension: <strong>&pound;{{ number_format($payroll->employer_pension ?? 0, 2) }}</strong>
                @endif
            </p>
            <p style="margin-top: 5px; font-size: 8pt; color: #666;">
                Total employer cost: &pound;{{ number_format(($payroll->gross_pay ?? 0) + ($payroll->employer_ni ?? 0) + ($payroll->employer_pension ?? 0), 2) }}
            </p>
        </div>

        <div class="info-box">
            <h3>Payment Information</h3>
            <p><strong>Payment Method:</strong> BACS</p>
            <p><strong>Sort Code:</strong> {{ $payroll->company->sort_code ?? 'N/A' }}</p>
            <p><strong>Account Number:</strong> {{ $payroll->company->account_number ?? 'N/A' }}</p>
            <p><strong>Reference:</strong> {{ $payroll->employee->name ?? 'Employee' }}</p>
        </div>

        <div class="info-box">
            <h3>Tax Year Information</h3>
            <p><strong>Tax Year:</strong> {{ $payroll->tax_year ?? date('Y') . '/' . (date('Y') + 1) }}</p>
            <p><strong>Tax Period:</strong> {{ $payroll->tax_period ?? date('m') }}</p>
            <p><strong>Week/Month:</strong> {{ $payroll->week_number ?? date('W') }}</p>
        </div>

        <div class="legal-footer">
            <p><strong>This is a payslip for tax purposes. Please retain for your records.</strong></p>
            <p>Employer: {{ $payroll->company->name ?? 'N/A' }} | PAYE Ref: {{ $payroll->company->paye_reference ?? 'N/A' }} | Accounts Office Ref: {{ $payroll->company->accounts_office_reference ?? 'N/A' }}</p>
            <p>Generated by DashSaaS | {{ date('d/m/Y') }}</p>
        </div>
    </div>
</body>
</html>
