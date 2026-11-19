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

    public function test_it_reuses_existing_partner_with_different_tax_number_format(): void
    {
        \App\Models\Partner::create([
            'name' => 'Test Partner Ltd.',
            'tax_number' => 'HU 123-456-78',
            'country_code' => 'HU',
        ]);

        $service = app(InvoiceProcessingService::class);

        $method = new \ReflectionMethod(
            InvoiceProcessingService::class,
            'findOrCreatePartner'
        );

        $method->setAccessible(true);

        $partner = $method->invoke($service, [
            'name' => 'Test Partner Ltd.',
            'tax_number' => 'HU12345678',
            'country_code' => 'HU',
        ]);

        $this->assertSame(1, \App\Models\Partner::count());
        $this->assertSame('Test Partner Ltd.', $partner->name);
    }

    public function test_it_creates_new_partner_when_tax_number_is_not_found(): void
    {
        $service = app(InvoiceProcessingService::class);

        $method = new \ReflectionMethod(
            InvoiceProcessingService::class,
            'findOrCreatePartner'
        );

        $method->setAccessible(true);

        $partner = $method->invoke($service, [
            'name' => 'New Test Partner Ltd.',
            'tax_number' => 'HU98765432',
            'country_code' => 'HU',
        ]);

        $this->assertSame(1, \App\Models\Partner::count());
        $this->assertSame('New Test Partner Ltd.', $partner->name);
        $this->assertSame('HU98765432', $partner->tax_number);
    }

    public function test_it_identifies_outgoing_invoice(): void
    {
        $service = app(InvoiceProcessingService::class);

        $method = new \ReflectionMethod(
            InvoiceProcessingService::class,
            'determineDirection'
        );

        $method->setAccessible(true);

        $direction = $method->invoke(
            $service,
            [
                'name' => 'NORDWIND SOLUTIONS KFT.',
                'tax_number' => '26481937-2-43',
            ],
            [
                'name' => 'PANNON IRODASZER KFT.',
                'tax_number' => 'HU23918466',
            ]
        );

        $this->assertSame('outgoing', $direction);
    }

    public function test_it_identifies_incoming_invoice(): void
    {
        $service = app(InvoiceProcessingService::class);

        $method = new \ReflectionMethod(
            InvoiceProcessingService::class,
            'determineDirection'
        );

        $method->setAccessible(true);

        $direction = $method->invoke(
            $service,
            [
                'name' => 'PANNON IRODASZER KFT.',
                'tax_number' => 'HU23918466',
            ],
            [
                'name' => 'NORDWIND SOLUTIONS KFT.',
                'tax_number' => 'HU26481937',
            ]
        );

        $this->assertSame('incoming', $direction);
    }

    public function test_it_throws_exception_when_nordwind_is_not_involved(): void
    {
        $service = app(InvoiceProcessingService::class);

        $method = new \ReflectionMethod(
            InvoiceProcessingService::class,
            'determineDirection'
        );

        $method->setAccessible(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot determine invoice direction: Nordwind must appear exactly once.'
        );

        $method->invoke(
            $service,
            [
                'name' => 'PANNON IRODASZER KFT.',
                'tax_number' => 'HU23918466',
            ],
            [
                'name' => 'OTHER COMPANY LTD.',
                'tax_number' => 'HU12345678',
            ]
        );
    }

    public function test_it_throws_exception_when_nordwind_is_both_seller_and_buyer(): void
    {
        $service = app(InvoiceProcessingService::class);

        $method = new \ReflectionMethod(
            InvoiceProcessingService::class,
            'determineDirection'
        );

        $method->setAccessible(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot determine invoice direction: Nordwind must appear exactly once.'
        );

        $method->invoke(
            $service,
            [
                'name' => 'NORDWIND SOLUTIONS KFT.',
                'tax_number' => '26481937-2-43',
            ],
            [
                'name' => 'NORDWIND SOLUTIONS KFT.',
                'tax_number' => 'HU26481937',
            ]
        );
    }

}
