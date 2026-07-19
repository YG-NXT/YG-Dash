<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <title>P45 - {{ $employee->name ?? 'Employee' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #333; padding: 20px; }
        .p45-box { max-width: 900px; margin: 0 auto; border: 2px solid #000; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .header h1 { font-size: 18pt; text-transform: uppercase; }
        .parts { display: flex; gap: 15px; margin-bottom: 15px; }
        .part { flex: 1; border: 1px solid #000; padding: 10px; }
        .part-header { background: #f0f0f0; padding: 5px; font-weight: bold; margin-bottom: 8px; text-align: center; border-bottom: 1px solid #000; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        td, th { border: 1px solid #ccc; padding: 5px; font-size: 9pt; }
        th { background: #f5f5f5; text-align: left; }
        .label { font-weight: bold; }
        .footer { margin-top: 15px; font-size: 8pt; color: #666; border-top: 1px solid #000; padding-top: 10px; }
        .notice { background: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin-top: 15px; font-size: 9pt; }
    </style>
</head>
<body>
    <div class="p45-box">
        <div class="header">
            <h1>P45</h1>
            <p>HM Revenue & Customs - Employee's leaving details</p>
        </div>

        <div class="parts">
            <div class="part">
                <div class="part-header">Part 1 - Employer</div>
                <table>
                    <tr><td class="label">Employer name</td><td>{{ $company->name ?? 'Employer' }}</td></tr>
                    <tr><td class="label">PAYE reference</td><td>{{ $company->paye_reference ?? '' }}</td></tr>
                    <tr><td class="label">Tax Office</td><td>{{ $company->tax_office ?? '' }}</td></tr>
                    <tr><td class="label">Leaving date</td><td>{{ $leavingDate ?? $payroll->pay_period_end ?? '' }}</td></tr>
                </table>
            </div>
            <div class="part">
                <div class="part-header">Part 1A - Employee</div>
                <table>
                    <tr><td class="label">Name</td><td>{{ $employee->name ?? '' }}</td></tr>
                    <tr><td class="label">NI number</td><td>{{ $employee->nino ?? $employee->user->nino ?? '' }}</td></tr>
                    <tr><td class="label">Date of birth</td><td>{{ $employee->date_of_birth ?? $employee->user->date_of_birth ?? '' }}</td></tr>
                    <tr><td class="label">Tax code</td><td>{{ $taxCode ?? '1257L' }}</td></tr>
                </table>
            </div>
            <div class="part">
                <div class="part-header">Parts 2 & 3 - Details</div>
                <table>
                    <tr><td class="label">Pay to date</td><td>&pound;{{ number_format($payToDate ?? 0, 2) }}</td></tr>
                    <tr><td class="label">Tax to date</td><td>&pound;{{ number_format($taxToDate ?? 0, 2) }}</td></tr>
                    <tr><td class="label">Week/Month</td><td>{{ $weekMonth ?? '' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="notice">
            <strong>Important:</strong> Parts 2 and 3 of this form must be given to the employee. 
            Part 1 must be sent to HMRC within 14 days of the employee leaving.
        </div>

        <div class="footer">
            Generated on {{ date('d/m/Y') }} | Company: {{ $company->name ?? 'Employer' }} | PAYE Ref: {{ $company->paye_reference ?? 'N/A' }}
        </div>
    </div>
</body>
</html>
