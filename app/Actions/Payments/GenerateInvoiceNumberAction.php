<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Organizer;
use Illuminate\Support\Facades\DB;

final readonly class GenerateInvoiceNumberAction
{
    /**
     * Generate the next sequential invoice number for an organizer and year.
     *
     * Uses a transaction-level lock to prevent race conditions when
     * multiple invoices are created concurrently for the same organizer/year.
     *
     * @return array{year: int, number: int, invoice_number: string}
     */
    public function __invoke(Organizer $organizer, InvoiceType $type = InvoiceType::Invoice): array
    {
        $year = (int) now()->format('Y');

        /** @var array{year: int, number: int, invoice_number: string} */
        return DB::transaction(function () use ($organizer, $year, $type): array {
            /** @var Invoice|null $lastInvoice */
            $lastInvoice = Invoice::query()
                ->where('organizer_id', $organizer->getKey())
                ->where('year', $year)
                ->orderBy('number', 'desc')
                ->lockForUpdate()
                ->first();

            $nextNumber = $lastInvoice !== null ? $lastInvoice->number + 1 : 1;
            $invoiceNumber = sprintf(
                '%s-%d-%04d',
                $type->prefix(),
                $year,
                $nextNumber,
            );

            return [
                'year' => $year,
                'number' => $nextNumber,
                'invoice_number' => $invoiceNumber,
            ];
        });
    }
}
