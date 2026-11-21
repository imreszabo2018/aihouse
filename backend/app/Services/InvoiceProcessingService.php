<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceProcessingService
{
    public function __construct(
        private PdfParserService $pdfParser,
        private InvoiceAiService $invoiceAi,
        private InvoiceDataValidator $validator,
        private ExchangeRateService $exchangeRateService,
    ) {
    }


    private function calculateFileHash(string $filePath): string
    {
        $disk = Storage::disk('local');

        if (!$disk->exists($filePath)) {
            throw new \RuntimeException(
                "PDF file does not exist: {$filePath}"
            );
        }

        $absolutePath = $disk->path($filePath);

        if (!is_readable($absolutePath)) {
            throw new \RuntimeException(
                "PDF file is not readable: {$filePath}"
            );
        }

        $hash = hash_file('sha256', $absolutePath);

        if ($hash === false) {
            throw new \RuntimeException(
                'Failed to calculate PDF file hash.'
            );
        }

        return $hash;
    }

    private function findDuplicate(string $fileHash): ?Invoice
    {
        return Invoice::where('file_hash', $fileHash)->first();
    }

    public function process(
        string $filePath,
        string $originalFilename
    ): Invoice {
        $fileHash = $this->calculateFileHash($filePath);

        $absolutePath = Storage::disk('local')->path($filePath);
        $oldFilePath = null;
        $duplicate = $this->findDuplicate($fileHash);

        if ($duplicate !== null) {
            if ($duplicate->processing_status !== 'failed') {
                throw new \RuntimeException(
                    "This invoice has already been uploaded: {$originalFilename}"
                );
            }

            $oldFilePath = $duplicate->file_path;
            $invoice = $duplicate;

            $invoice->update([
                'seller_id' => null,
                'buyer_id' => null,
                'invoice_number' => null,
                'issue_date' => null,
                'fulfillment_date' => null,
                'due_date' => null,
                'currency' => null,
                'gross_amount' => null,
                'exchange_rate' => null,
                'exchange_rate_date' => null,
                'amount_huf' => null,
                'direction' => null,
                'file_path' => $filePath,
                'original_filename' => $originalFilename,
                'processing_status' => 'pending',
                'processing_error' => null,
            ]);
        } else {
            $invoice = Invoice::create([
                'file_path' => $filePath,
                'original_filename' => $originalFilename,
                'file_hash' => $fileHash,
                'processing_status' => 'pending',
            ]);
        }

        try {
            $text = $this->pdfParser->extractText($absolutePath);

            $extractedData = $this->invoiceAi->extractInvoiceData($text);

            $validatedData = $this->validator->validate($extractedData);

            $currency = strtoupper($validatedData['currency']);

            $grossAmount = (float) $validatedData['gross_amount'];

            $exchangeRateDate = null;

            if ($currency === 'HUF') {
                $exchangeRate = 1;
                $amountHuf = $grossAmount;
            } else {
                $rate = $this->exchangeRateService->getExchangeRate(
                    $currency,
                    $validatedData['fulfillment_date']
                );

                $exchangeRate = (float) $rate->rate;
                $exchangeRateDate = $rate->rate_date->format('Y-m-d');

                $amountHuf = $grossAmount * $exchangeRate / $rate->unit;
            }

            DB::transaction(function () use ($validatedData, $invoice, $currency, $exchangeRate, $exchangeRateDate, $amountHuf) {
                $seller = $this->findOrCreatePartner(
                    $validatedData['seller']
                );

                $buyer = $this->findOrCreatePartner(
                    $validatedData['buyer']
                );

                $direction = $this->determineDirection(
                    $validatedData['seller'],
                    $validatedData['buyer']
                );

                $invoice->update([
                    'seller_id' => $seller->id,
                    'buyer_id' => $buyer->id,
                    'invoice_number' => $validatedData['invoice_number'],
                    'issue_date' => $validatedData['issue_date'],
                    'fulfillment_date' => $validatedData['fulfillment_date'],
                    'due_date' => $validatedData['due_date'] ?? null,
                    'currency' => $currency,
                    'gross_amount' => $validatedData['gross_amount'],
                    'exchange_rate' => $exchangeRate,
                    'exchange_rate_date' => $exchangeRateDate,
                    'amount_huf' => round($amountHuf, 0, PHP_ROUND_HALF_UP),
                    'direction' => $direction,
                    'processing_status' => 'processed',
                    'processing_error' => null,
                ]);
            });
        } catch (\Throwable $exception) {
            $invoice->update([
                'processing_status' => 'failed',
                'processing_error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        if (isset($oldFilePath) && $oldFilePath !== $filePath) {
            Storage::disk('local')->delete($oldFilePath);
        }

        return $invoice;
    }

    private function findOrCreatePartner(array $partnerData): \App\Models\Partner
    {
        $taxNumber = trim($partnerData['tax_number'] ?? '');

        if ($taxNumber !== '') {
            $normalizedTaxNumber = strtoupper(
                preg_replace('/[\s.-]+/', '', $taxNumber)
            );

            $partner = \App\Models\Partner::whereRaw(
                "UPPER(REPLACE(REPLACE(REPLACE(tax_number, ' ', ''), '-', ''), '.', '')) = ?",
                [$normalizedTaxNumber]
            )->first();

            if ($partner !== null) {
                return $partner;
            }
        }

        return \App\Models\Partner::create([
            'name' => trim($partnerData['name']),
            'tax_number' => $taxNumber !== '' ? $taxNumber : null,
            'country_code' => $partnerData['country_code'] ?? null,
        ]);
    }

    private function determineDirection(
        array $sellerData,
        array $buyerData
    ): string {
        $companyTaxNumbers = [
            '26481937243',
            'HU26481937',
        ];

        $normalizeTaxNumber = function (?string $taxNumber): string {
            return strtoupper(
                preg_replace('/[\s.-]+/', '', $taxNumber ?? '')
            );
        };

        $sellerIsNordwind = in_array(
            $normalizeTaxNumber($sellerData['tax_number'] ?? null),
            $companyTaxNumbers,
            true
        );

        $buyerIsNordwind = in_array(
            $normalizeTaxNumber($buyerData['tax_number'] ?? null),
            $companyTaxNumbers,
            true
        );

        if ($sellerIsNordwind && !$buyerIsNordwind) {
            return 'outgoing';
        }

        if ($buyerIsNordwind && !$sellerIsNordwind) {
            return 'incoming';
        }

        throw new \RuntimeException(
            'Cannot determine invoice direction: Nordwind must appear exactly once.'
        );
    }

}
