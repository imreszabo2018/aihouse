<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class InvoiceUploadController extends Controller
{
    public function store(
        Request $request,
        InvoiceProcessingService $invoiceProcessingService
    ) {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf',
                'max:20480',
            ],
        ]);

        $file = $validated['file'];

        $filename = Str::uuid() . '.pdf';

        $filePath = $file->storeAs(
            'invoices',
            $filename,
            'local'
        );

        try {
            $invoice = $invoiceProcessingService->process(
                $filePath,
                $file->getClientOriginalName()
            );

            $invoice->load(['seller', 'buyer']);

            return response()->json([
                'message' => 'Invoice processed successfully.',
                'invoice' => $invoice,
            ], 201);

        } catch (Throwable $exception) {
            $invoice = Invoice::where('file_path', $filePath)
                ->with(['seller', 'buyer'])
                ->first();

            if ($invoice === null) {
                Storage::disk('local')->delete($filePath);
            }

            return response()->json([
                'message' => 'Invoice processing failed.',
                'error' => $exception->getMessage(),
                'invoice' => $invoice,
            ], 422);
        }
    }
}
