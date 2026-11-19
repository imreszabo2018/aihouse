<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;

class InvoiceController extends Controller
{
    /**
     * List all invoices.
     */
    public function index()
    {
        $invoices = Invoice::with(['seller', 'buyer'])
            ->orderByDesc('issue_date')
            ->get();

        return response()->json($invoices);
    }

    /**
     * Show a single invoice.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['seller', 'buyer']);

        return response()->json($invoice);
    }

    /**
     * Delete an invoice.
     */
    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return response()->json(null, 204);
    }
}
