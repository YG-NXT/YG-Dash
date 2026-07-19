<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote - {{ $quote->quote_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #333; }
        .quote-box { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .company-info { text-align: right; }
        .title { font-size: 24pt; font-weight: bold; color: #000; margin-bottom: 10px; }
        .quote-details { margin-bottom: 30px; }
        .quote-details table { width: 100%; border-collapse: collapse; }
        .quote-details td { padding: 5px; vertical-align: top; }
        .quote-details td:first-child { font-weight: bold; width: 150px; }
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
        .validity { margin-top: 30px; padding: 15px; background: #e7f3fe; border-left: 4px solid #2196F3; }
        .legal-footer { margin-top: 30px; font-size: 9pt; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="quote-box">
        <div class="header">
            <div class="title">QUOTATION</div>
            <div class="company-info">
                <strong>{{ $quote->createdBy->name ?? 'Your Company' }}</strong><br>
                {{ $quote->createdBy->address ?? '' }}<br>
                @if(!empty($quote->createdBy->vat_number))
                    VAT Registration No: {{ $quote->createdBy->vat_number }}<br>
                @endif
                @if(!empty($quote->createdBy->company_number))
                    Registered in England & Wales No: {{ $quote->createdBy->company_number }}<br>
                @endif
            </div>
        </div>

        <div class="quote-details">
            <table>
                <tr>
                    <td>Quote Number:</td>
                    <td>{{ $quote->quote_number }}</td>
                    <td>Quote Date:</td>
                    <td>{{ \Carbon\Carbon::parse($quote->quote_date)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td>Valid Until:</td>
                    <td>{{ \Carbon\Carbon::parse($quote->valid_until)->format('d/m/Y') }}</td>
                    <td>Status:</td>
                    <td>{{ ucfirst($quote->status) }}</td>
                </tr>
            </table>
        </div>

        <div style="margin-bottom: 30px;">
            <strong>Quoted To:</strong><br>
            {{ $quote->customer->name ?? '' }}<br>
            {{ $quote->customer->address ?? '' }}<br>
            @if(!empty($quote->customer->vat_number))
                VAT Registration No: {{ $quote->customer->vat_number }}<br>
            @endif
        </div>

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
                @foreach($quote->items as $item)
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
                    <td>&pound;{{ number_format($quote->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td>VAT ({{ number_format(($quote->tax_amount / $quote->subtotal) * 100, 1) ?? 20 }}%):</td>
                    <td>&pound;{{ number_format($quote->tax_amount, 2) }}</td>
                </tr>
                <tr class="grand-total">
                    <td>Total:</td>
                    <td>&pound;{{ number_format($quote->total_amount, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="validity">
            <strong>Quote Validity:</strong> This quote is valid until {{ \Carbon\Carbon::parse($quote->valid_until)->format('d/m/Y') }}.<br>
            Prices are subject to change after the validity period. VAT at the standard rate applies unless stated otherwise.
        </div>

        @if(!empty($quote->notes))
            <div style="margin-top: 20px;">
                <strong>Notes:</strong><br>
                {{ $quote->notes }}
            </div>
        @endif

        <div class="legal-footer">
            <p>Registered in England & Wales | Company No: {{ $quote->createdBy->company_number ?? 'N/A' }} | VAT Reg No: {{ $quote->createdBy->vat_number ?? 'N/A' }}</p>
            <p>This quotation is not a contract. Acceptance of this quote does not guarantee availability of goods/services.</p>
        </div>
    </div>
</body>
</html>
