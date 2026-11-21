<?php

namespace Tests\Unit;

use App\Models\ExchangeRate;
use App\Services\PdfParserService;
use App\Services\InvoiceAiService;
use App\Services\InvoiceDataValidator;
use App\Services\ExchangeRateService;
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

    public function test_it_converts_eur_invoice_amount_to_huf(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put(
            'test/test-invoice.pdf',
            'Sample PDF content'
        );

        $this->mock(PdfParserService::class)
            ->shouldReceive('extractText')
            ->once()
            ->andReturn('Invoice text');

        $this->mock(InvoiceAiService::class)
            ->shouldReceive('extractInvoiceData')
            ->once()
            ->andReturn(['invoice' => 'data']);

        $validatedData = [
            'seller' => [
                'name' => 'NORDWIND SOLUTIONS KFT.',
                'tax_number' => '26481937-2-43',
                'country_code' => 'HU',
            ],
            'buyer' => [
                'name' => 'PANNON IRODASZER KFT.',
                'tax_number' => 'HU23918466',
                'country_code' => 'HU',
            ],
            'invoice_number' => 'TEST-EUR-001',
            'issue_date' => '2026-11-19',
            'fulfillment_date' => '2026-11-19',
            'due_date' => '2026-12-03',
            'currency' => 'EUR',
            'gross_amount' => 100,
        ];

        $this->mock(InvoiceDataValidator::class)
            ->shouldReceive('validate')
            ->once()
            ->with(['invoice' => 'data'])
            ->andReturn($validatedData);

        $this->mock(ExchangeRateService::class)
            ->shouldReceive('getExchangeRate')
            ->once()
            ->with('EUR', '2026-11-19')
            ->andReturn(new ExchangeRate([
                'currency' => 'EUR',
                'rate_date' => '2026-11-19',
                'rate' => 368.970000,
                'unit' => 1,
            ]));

        $service = app(InvoiceProcessingService::class);

        $invoice = $service->process(
            'test/test-invoice.pdf',
            'test-invoice.pdf'
        );

        $this->assertSame('EUR', $invoice->currency);
        $this->assertEquals(100, $invoice->gross_amount);
        $this->assertEquals(368.970000, $invoice->exchange_rate);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'exchange_rate_date' => '2026-11-19 00:00:00',
        ]);
        $this->assertEquals(36897.00, $invoice->amount_huf);
        $this->assertSame('processed', $invoice->processing_status);
    }
    public function test_it_keeps_huf_invoice_amount_without_exchange_rate_lookup(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put(
            'test/test-huf-invoice.pdf',
            'Sample HUF PDF content'
        );

        $this->mock(PdfParserService::class)
            ->shouldReceive('extractText')
            ->once()
            ->andReturn('Invoice text');

        $this->mock(InvoiceAiService::class)
            ->shouldReceive('extractInvoiceData')
            ->once()
            ->andReturn(['invoice' => 'data']);

        $validatedData = [
            'seller' => [
                'name' => 'NORDWIND SOLUTIONS KFT.',
                'tax_number' => '26481937-2-43',
                'country_code' => 'HU',
            ],
            'buyer' => [
                'name' => 'PANNON IRODASZER KFT.',
                'tax_number' => 'HU23918466',
                'country_code' => 'HU',
            ],
            'invoice_number' => 'TEST-HUF-001',
            'issue_date' => '2026-11-19',
            'fulfillment_date' => '2026-11-19',
            'due_date' => '2026-12-03',
            'currency' => 'HUF',
            'gross_amount' => 12345.67,
        ];

        $this->mock(InvoiceDataValidator::class)
            ->shouldReceive('validate')
            ->once()
            ->andReturn($validatedData);

        $this->mock(ExchangeRateService::class)
            ->shouldNotReceive('getExchangeRate');

        $service = app(InvoiceProcessingService::class);

        $invoice = $service->process(
            'test/test-huf-invoice.pdf',
            'test-huf-invoice.pdf'
        );

        $this->assertSame('HUF', $invoice->currency);
        $this->assertEquals(12345.67, $invoice->gross_amount);
        $this->assertEquals(1, $invoice->exchange_rate);
        $this->assertEquals(12345.67, $invoice->amount_huf);
        $this->assertSame('processed', $invoice->processing_status);
    }
}
