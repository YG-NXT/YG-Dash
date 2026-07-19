<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #333; }
        .invoice-box { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .company-info { text-align: right; }
        .title { font-size: 24pt; font-weight: bold; color: #000; margin-bottom: 10px; }
        .invoice-details { margin-bottom: 30px; }
        .invoice-details table { width: 100%; border-collapse: collapse; }
        .invoice-details td { padding: 5px; vertical-align: top; }
        .invoice-details td:first-child { font-weight: bold; width: 150px; }
        .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .items-table th { background: #f5f5f5; padding: 10px; text-align: left; border-bottom: 2px solid #000; }
        .items-table td { padding: 10px; border-bottom: 1px solid #ddd; }
        .items-table td:last-child, .items-table th:last-child { text-align: right; }
        .items-table td:nth-child(2), .items-table th:nth-child(2) { text-align: right; }
        .items-table td:nth-child(3), .items-table th:nth-child(3) { text-align: right; }
        .totals { display: flex; justify-content: flex-end; margin-top: 20px; }
        .totals table { width: 300px; border-collapse: collapse; }
        .totals td { padding: 8px; border-bottom: 1px solid #ddd; }
        .totals td:last-child { text-align: right; font-weight: bold; }
        .totals tr.grand-total td { font-size: 14pt; font-weight: bold; border-bottom: 2px solid #000; }
        .vat-number { margin-top: 30px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; }
        .legal-footer { margin-top: 30px; font-size: 9pt; color: #666; text-align: center; }
        .reverse-charge { background: #fff3cd; padding: 15px; margin: 20px 0; border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="header">
            <div class="title">TAX INVOICE</div>
            <div class="company-info">
                <strong>{{ $invoice->createdBy->name ?? 'Your Company' }}</strong><br>
                {{ $invoice->createdBy->address ?? '' }}<br>
                @if(!empty($invoice->createdBy->vat_number))
                    VAT Registration No: {{ $invoice->createdBy->vat_number }}<br>
                @endif
                @if(!empty($invoice->createdBy->company_number))
                    Registered in England & Wales No: {{ $invoice->createdBy->company_number }}<br>
                @endif
            </div>
        </div>

        <div class="invoice-details">
            <table>
                <tr>
                    <td>Invoice Number:</td>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>Invoice Date:</td>
                    <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td>Due Date:</td>
                    <td>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</td>
                    <td>Status:</td>
                    <td>{{ ucfirst($invoice->status) }}</td>
                </tr>
            </table>
        </div>

        <div style="margin-bottom: 30px;">
            <strong>Bill To:</strong><br>
            {{ $invoice->customer->name ?? '' }}<br>
            {{ $invoice->customer->address ?? '' }}<br>
            @if(!empty($invoice->customer->vat_number))
                VAT Registration No: {{ $invoice->customer->vat_number }}<br>
            @endif
        </div>

        @if($invoice->vat_number ?? false)
            <div class="reverse-charge">
                <strong>Reverse Charge Notice:</strong><br>
                You receive this invoice as a VAT-registered business. Under UK VAT legislation, the reverse charge applies.
                You should account for the VAT in your VAT return. HMRC reference: <strong>{{ $invoice->vat_number }}</strong>
            </div>
        @endif

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Description</th>
                    <th style="width: 15%;">Quantity</th>
                    <th style="width: 20%;">Unit Price</th>
                    <th style="width: 25%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td>{{ $item->name ?? $item->product->name ?? 'Item' }}</td>
                        <td style="text-align: right;">{{ $item->quantity }}</td>
                        <td style="text-align: right;">&pound;{{ number_format($item->price, 2) }}</td>
                        <td style="text-align: right;">&pound;{{ number_format($item->price * $item->quantity, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td>&pound;{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td>VAT ({{ number_format(($invoice->tax_amount / $invoice->subtotal) * 100, 1) }}%):</td>
                    <td>&pound;{{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                <tr class="grand-total">
                    <td>Total:</td>
                    <td>&pound;{{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
                @if($invoice->paid_amount > 0)
                    <tr>
                        <td>Paid:</td>
                        <td>&pound;{{ number_format($invoice->paid_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Balance Due:</td>
                        <td>&pound;{{ number_format($invoice->balance_amount, 2) }}</td>
                    </tr>
                @endif
            </table>
        </div>

        @if(!empty($invoice->createdBy->vat_number))
            <div class="vat-number">
                <strong>VAT Registration Number:</strong> {{ $invoice->createdBy->vat_number }}<br>
                <strong>Company Registration Number:</strong> {{ $invoice->createdBy->company_number ?? 'N/A' }}
            </div>
        @endif

        <div class="legal-footer">
            <p>Registered in England & Wales | Company No: {{ $invoice->createdBy->company_number ?? 'N/A' }} | VAT Reg No: {{ $invoice->createdBy->vat_number ?? 'N/A' }}</p>
            <p>Terms: Payment due within 30 days of invoice date. Late payment may incur interest at 8% above Bank of England base rate.</p>
        </div>
    </div>
</body>
</html>
