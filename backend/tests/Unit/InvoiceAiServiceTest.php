<?php

namespace Tests\Unit;

use App\Services\InvoiceAiService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class InvoiceAiServiceTest extends TestCase
{
    public function test_it_rejects_empty_invoice_text(): void
    {
        config([
            'services.openrouter.api_key' => 'test-api-key',
        ]);

        $service = new InvoiceAiService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Invoice text cannot be empty.'
        );

        $service->extractInvoiceData('');
    }

    public function test_it_rejects_missing_api_key(): void
    {
        config([
            'services.openrouter.api_key' => null,
        ]);

        $service = new InvoiceAiService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'OpenRouter API key is not configured.'
        );

        $service->extractInvoiceData('Sample invoice text');
    }

    public function test_it_extracts_invoice_data_from_api_response(): void
    {
        config([
            'services.openrouter.api_key' => 'test-api-key',
            'services.openrouter.model' => 'test-model',
            'services.openrouter.base_url' => 'https://openrouter.ai/api/v1',
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'invoice_number' => 'PI-26-0050',
                                'seller' => [
                                    'name' => 'PANNON IRODASZER KFT.',
                                    'tax_number' => '23918466',
                                    'country_code' => 'HU',
                                ],
                                'buyer' => [
                                    'name' => 'NORDWIND SOLUTIONS KFT.',
                                    'tax_number' => '26481937-2-43',
                                    'country_code' => 'HU',
                                ],
                                'issue_date' => '2026-08-24',
                                'fulfillment_date' => '2026-08-18',
                                'due_date' => '2026-09-08',
                                'currency' => 'HUF',
                                'gross_amount' => 1946455,
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new InvoiceAiService();

        $result = $service->extractInvoiceData(
            'Sample invoice text'
        );

        $this->assertSame('PI-26-0050', $result['invoice_number']);
        $this->assertSame('HUF', $result['currency']);
        $this->assertSame(1946455, $result['gross_amount']);
        $this->assertSame(
            'NORDWIND SOLUTIONS KFT.',
            $result['buyer']['name']
        );

        Http::assertSent(function ($request) {
            return $request->url() ===
                'https://openrouter.ai/api/v1/chat/completions'
                && $request->hasHeader(
                    'Authorization',
                    'Bearer test-api-key'
                );
        });
    }
}
