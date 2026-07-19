<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <title>P60 - {{ $employee->name ?? 'Employee' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10pt; color: #333; padding: 20px; }
        .p60-box { max-width: 800px; margin: 0 auto; border: 2px solid #000; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .header h1 { font-size: 18pt; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        td, th { border: 1px solid #ccc; padding: 6px; font-size: 9pt; }
        th { background: #f5f5f5; text-align: left; }
        .label { font-weight: bold; }
        .totals { margin-top: 15px; }
        .totals table { width: 50%; margin-left: auto; }
        .footer { margin-top: 20px; font-size: 8pt; color: #666; border-top: 1px solid #000; padding-top: 10px; }
        .notice { background: #e7f3fe; border: 1px solid #2196F3; padding: 10px; margin-top: 15px; font-size: 9pt; }
    </style>
</head>
<body>
    <div class="p60-box">
        <div class="header">
            <h1>P60</h1>
            <p>HM Revenue & Customs - End of year certificate</p>
        </div>

        <table>
            <tr><td class="label">Employer name</td><td>{{ $company->name ?? 'Employer' }}</td></tr>
            <tr><td class="label">Employer PAYE reference</td><td>{{ $company->paye_reference ?? '' }}</td></tr>
            <tr><td class="label">Employee name</td><td>{{ $employee->name ?? '' }}</td></tr>
            <tr><td class="label">NI number</td><td>{{ $employee->nino ?? $employee->user->nino ?? '' }}</td></tr>
            <tr><td class="label">Tax year</td><td>{{ $taxYear ?? date('Y') . '/' . (date('Y')+1) }}</td></tr>
            <tr><td class="label">Tax code</td><td>{{ $taxCode ?? '1257L' }}</td></tr>
        </table>

        <div class="totals">
            <table>
                <tr><th colspan="2">Total payments and tax</th></tr>
                <tr><td>Total pay for year</td><td>&pound;{{ number_format($totalPay ?? 0, 2) }}</td></tr>
                <tr><td>Total tax deducted</td><td>&pound;{{ number_format($totalTax ?? 0, 2) }}</td></tr>
                <tr><td>Total NIC</td><td>&pound;{{ number_format($totalNI ?? 0, 2) }}</td></tr>
            </table>
        </div>

        <div class="notice">
            <strong>Keep this certificate safe.</strong> You may need it to claim benefits, make a tax refund claim, or prove your income.
        </div>

        <div class="footer">
            Generated on {{ date('d/m/Y') }} | Tax year: {{ $taxYear ?? date('Y') . '/' . (date('Y')+1) }}
        </div>
    </div>
</body>
</html>
