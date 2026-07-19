<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <title>P11D - {{ $employee->name ?? 'Employee' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #333; padding: 20px; }
        .p11d-box { max-width: 850px; margin: 0 auto; border: 2px solid #000; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .header h1 { font-size: 18pt; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        td, th { border: 1px solid #ccc; padding: 6px; font-size: 9pt; }
        th { background: #f5f5f5; text-align: left; }
        .label { font-weight: bold; }
        .footer { margin-top: 20px; font-size: 8pt; color: #666; border-top: 1px solid #000; padding-top: 10px; }
        .notice { background: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin-top: 15px; font-size: 9pt; }
    </style>
</head>
<body>
    <div class="p11d-box">
        <div class="header">
            <h1>P11D</h1>
            <p>HM Revenue & Customs - Benefits and expenses</p>
        </div>

        <table>
            <tr><td class="label">Employer name</td><td>{{ $company->name ?? 'Employer' }}</td></tr>
            <tr><td class="label">PAYE reference</td><td>{{ $company->paye_reference ?? '' }}</td></tr>
            <tr><td class="label">Employee name</td><td>{{ $employee->name ?? '' }}</td></tr>
            <tr><td class="label">NI number</td><td>{{ $employee->nino ?? $employee->user->nino ?? '' }}</td></tr>
            <tr><td class="label">Tax year</td><td>{{ $taxYear ?? date('Y') . '/' . (date('Y')+1) }}</td></tr>
        </table>

        <table>
            <tr><th>Benefit / Expense</th><th>Amount (&pound;)</th></tr>
            @foreach($benefits ?? [] as $benefit)
                <tr><td>{{ $benefit['name'] }}</td><td>&pound;{{ number_format($benefit['amount'], 2) }}</td></tr>
            @endforeach
            <tr style="font-weight: bold; background: #f5f5f5;">
                <td>Total benefits</td>
                <td>&pound;{{ number_format(($benefits ?? [])->sum('amount'), 2) }}</td>
            </tr>
        </table>

        <div class="notice">
            <strong>Note:</strong> This form is for information only. Complete the official P11D form and submit to HMRC by 6th July following the end of the tax year.
        </div>

        <div class="footer">
            Generated on {{ date('d/m/Y') }} | Tax year: {{ $taxYear ?? date('Y') . '/' . (date('Y')+1) }}
        </div>
    </div>
</body>
</html>
