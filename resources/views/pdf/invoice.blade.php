<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 24px; margin: 0; color: #000; }
        .header p { margin: 4px 0; color: #666; }
        .details { margin-bottom: 20px; }
        .details table { width: 100%; border-collapse: collapse; }
        .details td { padding: 4px 8px; }
        .details .label { font-weight: bold; width: 120px; }
        .total { margin-top: 20px; border-top: 2px solid #333; padding-top: 10px; }
        .total table { width: 100%; border-collapse: collapse; }
        .total td { padding: 4px 8px; }
        .total .label { font-weight: bold; }
        .total .amount { text-align: right; font-weight: bold; font-size: 16px; }
        .footer { margin-top: 40px; text-align: center; color: #999; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $invoice->type === \App\Enums\InvoiceType::CreditNote ? 'Credit Note' : 'Invoice' }}</h1>
        <p>{{ $invoice->invoice_number }}</p>
        <p>{{ $invoice->organizer->name }}</p>
    </div>

    <div class="details">
        <table>
            <tr>
                <td class="label">Status:</td>
                <td>{{ ucfirst($invoice->status->value) }}</td>
            </tr>
            <tr>
                <td class="label">Date:</td>
                <td>{{ $invoice->created_at?->format('F j, Y') }}</td>
            </tr>
            <tr>
                <td class="label">Currency:</td>
                <td>{{ strtoupper($invoice->currency) }}</td>
            </tr>
            <tr>
                <td class="label">Order Ref:</td>
                <td>{{ $invoice->ticketOrder?->order_reference ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="total">
        <table>
            <tr>
                <td class="label">Amount:</td>
                <td class="amount">{{ $amountFormatted }}</td>
            </tr>
            @if($taxFormatted !== null)
            <tr>
                <td class="label">Tax:</td>
                <td class="amount">{{ $taxFormatted }}</td>
            </tr>
            @endif
            @if($feeFormatted !== null)
            <tr>
                <td class="label">Fee:</td>
                <td class="amount">{{ $feeFormatted }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('F j, Y \a\t H:i') }}</p>
    </div>
</body>
</html>
