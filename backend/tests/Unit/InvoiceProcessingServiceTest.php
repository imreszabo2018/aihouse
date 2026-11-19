<?php

namespace Tests\Unit;

use App\Services\InvoiceProcessingService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceProcessingServiceTest extends TestCase
{

    use RefreshDatabase;
    public function test_it_can_calculate_a_pdf_file_hash(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put(
            'test/test-invoice.pdf',
            'Sample PDF content'
        );

        $service = app(InvoiceProcessingService::class);

        $method = new \ReflectionMethod(
            InvoiceProcessingService::class,
            'calculateFileHash'
        );

        $method->setAccessible(true);

        $hash = $method->invoke(
            $service,
            'test/test-invoice.pdf'
        );

        $this->assertSame(
            hash('sha256', 'Sample PDF content'),
            $hash
        );
    }

    public function test_it_can_find_an_existing_invoice_by_file_hash(): void
    {
        $hash = hash('sha256', 'Sample PDF content');

        $invoice = \App\Models\Invoice::create([
            'file_hash' => $hash,
            'file_path' => 'test/test-invoice.pdf',
            'original_filename' => 'test-invoice.pdf',
        ]);

        $service = app(InvoiceProcessingService::class);

        $method = new \ReflectionMethod(
            InvoiceProcessingService::class,
            'findDuplicate'
        );

        $method->setAccessible(true);

        $duplicate = $method->invoke($service, $hash);

        $this->assertNotNull($duplicate);
        $this->assertSame($invoice->id, $duplicate->id);
    }


    public function test_it_throws_exception_when_pdf_file_does_not_exist(): void
    {
        Storage::fake('local');

        $service = app(InvoiceProcessingService::class);

        $method = new \ReflectionMethod(
            InvoiceProcessingService::class,
            'calculateFileHash'
        );

        $method->setAccessible(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'PDF file does not exist: test/missing.pdf'
        );

        $method->invoke($service, 'test/missing.pdf');
    }


}
