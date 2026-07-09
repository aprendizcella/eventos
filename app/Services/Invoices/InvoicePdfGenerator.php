<?php

declare(strict_types=1);

namespace App\Services\Invoices;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final readonly class InvoicePdfGenerator
{
    /**
     * Generate the PDF for the given invoice.
     */
    public function generate(Invoice $invoice): Response
    {
        $invoice->loadMissing(['ticketOrder', 'organizer']);

        $amountFormatted = number_format($invoice->amount / 100, 2);
        $taxFormatted = $invoice->tax_amount !== null
            ? number_format($invoice->tax_amount / 100, 2)
            : null;
        $feeFormatted = $invoice->fee_amount !== null
            ? number_format($invoice->fee_amount / 100, 2)
            : null;

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'amountFormatted' => $amountFormatted,
            'taxFormatted' => $taxFormatted,
            'feeFormatted' => $feeFormatted,
        ]);

        return $pdf->download(sprintf('invoice-%s.pdf', $invoice->invoice_number));
    }
}
