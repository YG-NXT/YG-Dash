<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Note - {{ $creditNote->credit_note_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #333; }
        .credit-box { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .company-info { text-align: right; }
        .title { font-size: 24pt; font-weight: bold; color: #000; margin-bottom: 10px; }
        .credit-details { margin-bottom: 30px; }
        .credit-details table { width: 100%; border-collapse: collapse; }
        .credit-details td { padding: 5px; vertical-align: top; }
        .credit-details td:first-child { font-weight: bold; width: 150px; }
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
        .reason { margin-top: 30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; }
        .legal-footer { margin-top: 30px; font-size: 9pt; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="credit-box">
        <div class="header">
            <div class="title">CREDIT NOTE</div>
            <div class="company-info">
                <strong>{{ $creditNote->createdBy->name ?? 'Your Company' }}</strong><br>
                {{ $creditNote->createdBy->address ?? '' }}<br>
                @if(!empty($creditNote->createdBy->vat_number))
                    VAT Registration No: {{ $creditNote->createdBy->vat_number }}<br>
                @endif
                @if(!empty($creditNote->createdBy->company_number))
                    Registered in England & Wales No: {{ $creditNote->createdBy->company_number }}<br>
                @endif
            </div>
        </div>

        <div class="credit-details">
            <table>
                <tr>
                    <td>Credit Note Number:</td>
                    <td>{{ $creditNote->credit_note_number }}</td>
                    <td>Credit Note Date:</td>
                    <td>{{ \Carbon\Carbon::parse($creditNote->credit_note_date)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td>Original Invoice:</td>
                    <td>{{ $creditNote->invoice->invoice_number ?? 'N/A' }}</td>
                    <td>Status:</td>
                    <td>{{ ucfirst($creditNote->status) }}</td>
                </tr>
            </table>
        </div>

        <div style="margin-bottom: 30px;">
            <strong>Customer:</strong><br>
            {{ $creditNote->customer->name ?? '' }}<br>
            {{ $creditNote->customer->address ?? '' }}<br>
            @if(!empty($creditNote->customer->vat_number))
                VAT Registration No: {{ $creditNote->customer->vat_number }}<br>
            @endif
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Description</th>
                    <th style="width: 15%;">Quantity</th>
                    <th style="width: 20%;">Unit Price</th>
                    <th style="width: 25%;">Credit Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($creditNote->items as $item)
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
                    <td>&pound;{{ number_format($creditNote->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td>VAT ({{ number_format(($creditNote->tax_amount / $creditNote->subtotal) * 100, 1) ?? 20 }}%):</td>
                    <td>&pound;{{ number_format($creditNote->tax_amount, 2) }}</td>
                </tr>
                <tr class="grand-total">
                    <td>Total Credit:</td>
                    <td>&pound;{{ number_format($creditNote->total_amount, 2) }}</td>
                </tr>
            </table>
        </div>

        @if(!empty($creditNote->reason))
            <div class="reason">
                <strong>Reason for Credit:</strong><br>
                {{ $creditNote->reason }}
            </div>
        @endif

        <div class="legal-footer">
            <p>Registered in England & Wales | Company No: {{ $creditNote->createdBy->company_number ?? 'N/A' }} | VAT Reg No: {{ $creditNote->createdBy->vat_number ?? 'N/A' }}</p>
            <p>This credit note should be retained for your VAT records.</p>
        </div>
    </div>
</body>
</html>
