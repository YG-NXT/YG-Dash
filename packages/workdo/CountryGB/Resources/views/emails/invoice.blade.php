<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #333; }
        .email-container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px; background: #f5f5f5; margin-bottom: 20px; }
        .header h1 { font-size: 20pt; color: #000; }
        .content { padding: 20px; }
        .invoice-details { background: #f9f9f9; padding: 15px; margin: 15px 0; border: 1px solid #ddd; }
        .invoice-details table { width: 100%; border-collapse: collapse; }
        .invoice-details td { padding: 5px; }
        .invoice-details td:first-child { font-weight: bold; width: 150px; }
        .totals { text-align: right; margin-top: 20px; }
        .totals table { margin-left: auto; border-collapse: collapse; }
        .totals td { padding: 5px 10px; }
        .totals td:last-child { text-align: right; font-weight: bold; }
        .footer { margin-top: 30px; padding: 15px; background: #f5f5f5; font-size: 9pt; color: #666; text-align: center; }
        .btn { display: inline-block; padding: 12px 24px; background: #000; color: #fff; text-decoration: none; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>TAX INVOICE</h1>
            <p>{{ $invoice->createdBy->name ?? 'Your Company' }}</p>
        </div>

        <div class="content">
            <p>Dear {{ $invoice->customer->name ?? 'Valued Customer' }},</p>
            <p>Please find attached your tax invoice {{ $invoice->invoice_number }} dated {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}.</p>

            <div class="invoice-details">
                <table>
                    <tr>
                        <td>Invoice Number:</td>
                        <td>{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td>Invoice Date:</td>
                        <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td>Due Date:</td>
                        <td>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</td>
                    </tr>
                    @if(!empty($invoice->createdBy->vat_number))
                        <tr>
                            <td>VAT Reg No:</td>
                            <td>{{ $invoice->createdBy->vat_number }}</td>
                        </tr>
                    @endif
                </table>
            </div>

            <div class="totals">
                <table>
                    <tr>
                        <td>Subtotal:</td>
                        <td>&pound;{{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td>VAT:</td>
                        <td>&pound;{{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                    <tr style="font-size: 14pt; font-weight: bold;">
                        <td>Total:</td>
                        <td>&pound;{{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                </table>
            </div>

            <p style="margin-top: 20px;">Payment is due by {{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}.</p>
            <p>If you have any questions about this invoice, please contact us.</p>
        </div>

        <div class="footer">
            <p><strong>{{ $invoice->createdBy->name ?? 'Your Company' }}</strong></p>
            <p>{{ $invoice->createdBy->address ?? '' }}</p>
            @if(!empty($invoice->createdBy->vat_number))
                <p>VAT Registration No: {{ $invoice->createdBy->vat_number }}</p>
            @endif
            @if(!empty($invoice->createdBy->company_number))
                <p>Registered in England & Wales No: {{ $invoice->createdBy->company_number }}</p>
            @endif
        </div>
    </div>
</body>
</html>
