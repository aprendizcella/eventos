<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Organizer;
use App\Services\Invoices\InvoicePdfGenerator;
use Illuminate\Http\Response;

final class DownloadInvoiceController extends Controller
{
    public function __invoke(Organizer $organizer, Invoice $invoice, InvoicePdfGenerator $generator): Response
    {
        // Verify the invoice belongs to the organizer
        if ($invoice->organizer_id !== $organizer->getKey()) {
            abort(404);
        }

        // Verify the authenticated user belongs to this organizer
        $user = auth()->user();

        if ($user === null) {
            abort(401);
        }

        $isOrganizerMember = $user->organizers()
            ->where('organizer_id', $organizer->getKey())
            ->exists();

        if (!$isOrganizerMember) {
            abort(403);
        }

        return $generator->generate($invoice);
    }
}
