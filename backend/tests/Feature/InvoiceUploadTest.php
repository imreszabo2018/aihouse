<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Services\InvoiceProcessingService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class InvoiceUploadTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;
    public function test_it_requires_a_file_for_invoice_upload(): void
    {
        $response = $this->postJson('/api/invoices', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_it_rejects_non_pdf_files(): void
    {
        $file = UploadedFile::fake()->image('invoice.jpg');

        $response = $this->post('/api/invoices', [
            'file' => $file,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_it_rejects_files_larger_than_20_mb(): void
    {
        $file = UploadedFile::fake()->create(
            'invoice.pdf',
            20481,
            'application/pdf'
        );

        $response = $this->postJson('/api/invoices', [
            'file' => $file,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_it_uploads_and_processes_a_pdf_invoice(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create(
            'invoice.pdf',
            100,
            'application/pdf'
        );

        $this->mock(InvoiceProcessingService::class)
            ->shouldReceive('process')
            ->once()
            ->withArgs(function ($filePath, $originalFilename) {
                return Str::startsWith($filePath, 'invoices/')
                    && $originalFilename === 'invoice.pdf';
            })
            ->andReturn(new Invoice([
                'invoice_number' => 'TEST-2026-001',
                'currency' => 'EUR',
                'gross_amount' => 100,
                'exchange_rate' => 368.97,
                'amount_huf' => 36897,
                'processing_status' => 'processed',
            ]));

        $response = $this->post('/api/invoices', [
            'file' => $file,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Invoice processed successfully.')
            ->assertJsonPath('invoice.invoice_number', 'TEST-2026-001');
    }

    public function test_it_returns_error_when_invoice_processing_fails(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create(
            'invoice.pdf',
            100,
            'application/pdf'
        );

        $this->mock(InvoiceProcessingService::class)
            ->shouldReceive('process')
            ->once()
            ->andThrow(new \RuntimeException('MNB service unavailable.'));

        $response = $this->post('/api/invoices', [
            'file' => $file,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Invoice processing failed.')
            ->assertJsonPath('error', 'MNB service unavailable.');
    }

    public function test_it_deletes_new_file_when_duplicate_invoice_is_rejected(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put(
            'invoices/original-invoice.pdf',
            'Original invoice content'
        );

        Invoice::create([
            'file_hash' => hash('sha256', 'Original invoice'),
            'file_path' => 'invoices/original-invoice.pdf',
            'original_filename' => 'original-invoice.pdf',
            'processing_status' => 'processed',
        ]);

        $file = UploadedFile::fake()->create(
            'invoice.pdf',
            100,
            'application/pdf'
        );

        $this->mock(InvoiceProcessingService::class)
            ->shouldReceive('process')
            ->once()
            ->andThrow(new \RuntimeException(
                'This invoice has already been uploaded: invoice.pdf'
            ));

        $response = $this->post('/api/invoices', [
            'file' => $file,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Invoice processing failed.');

        $this->assertSame(
            ['invoices/original-invoice.pdf'],
            Storage::disk('local')->allFiles('invoices')
        );

        $this->assertDatabaseHas('invoices', [
            'original_filename' => 'original-invoice.pdf',
            'processing_status' => 'processed',
        ]);
    }
}
