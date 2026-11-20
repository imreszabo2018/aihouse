<?php

namespace Tests\Unit;

use App\Models\ExchangeRate;
use App\Services\ExchangeRateService;
use App\Services\MnbExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExchangeRateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_save_exchange_rates(): void
    {
        $service = app(ExchangeRateService::class);

        $rates = [
            [
                'currency' => 'EUR',
                'rate_date' => '2026-11-19',
                'rate' => '368.97000',
                'unit' => 1,
            ],
        ];

        $service->saveExchangeRates($rates);

        $this->assertDatabaseHas('exchange_rates', [
            'currency' => 'EUR',
            'rate' => 368.97,
            'unit' => 1,
        ]);

        $exchangeRate = ExchangeRate::where('currency', 'EUR')->first();

        $this->assertNotNull($exchangeRate);
        $this->assertSame(
            '2026-11-19',
            substr((string) $exchangeRate->rate_date, 0, 10)
        );
    }

    public function test_it_updates_existing_exchange_rates_instead_of_creating_duplicates(): void
    {
        $service = app(ExchangeRateService::class);

        $rates = [
            [
                'currency' => 'EUR',
                'rate_date' => '2026-11-19',
                'rate' => '368.97000',
                'unit' => 1,
            ],
        ];

        $service->saveExchangeRates($rates);

        $rates[0]['rate'] = '370.25000';

        $service->saveExchangeRates($rates);

        $this->assertDatabaseCount('exchange_rates', 1);

        $this->assertDatabaseHas('exchange_rates', [
            'currency' => 'EUR',
            'rate' => 370.25,
            'unit' => 1,
        ]);
    }

    public function test_it_returns_existing_exchange_rate_without_fetching_from_mnb(): void
    {
        ExchangeRate::create([
            'currency' => 'EUR',
            'rate_date' => '2026-11-19',
            'rate' => '368.97000',
            'unit' => 1,
        ]);

        $service = app(ExchangeRateService::class);

        $exchangeRate = $service->getExchangeRate(
            'EUR',
            '2026-11-19'
        );

        $this->assertSame('EUR', $exchangeRate->currency);
        $this->assertSame('368.970000', $exchangeRate->rate);
        $this->assertDatabaseCount('exchange_rates', 1);
    }
    public function test_it_uses_the_latest_available_rate_for_weekends(): void
    {
        $mnbService = \Mockery::mock(MnbExchangeRateService::class);

        $mnbService->shouldReceive('getExchangeRates')
            ->once()
            ->with('2026-11-11', '2026-11-21', 'EUR')
            ->andReturn(
                '<MNBExchangeRates>
                <Day date="2026-11-20">
                    <Rate unit="1" curr="EUR">369,10000</Rate>
                </Day>
            </MNBExchangeRates>'
            );

        $mnbService->shouldReceive('parseExchangeRates')
            ->once()
            ->andReturn([
                [
                    'currency' => 'EUR',
                    'rate_date' => '2026-11-20',
                    'rate' => '369.10000',
                    'unit' => 1,
                ],
            ]);

        $this->app->instance(MnbExchangeRateService::class, $mnbService);

        $service = app(ExchangeRateService::class);

        $exchangeRate = $service->getExchangeRate(
            'EUR',
            '2026-11-21'
        );

        $this->assertSame(
            '2026-11-20',
            substr((string) $exchangeRate->rate_date, 0, 10)
        );

        $this->assertSame('369.100000', $exchangeRate->rate);

        $this->assertDatabaseCount('exchange_rates', 1);
    }
    public function test_it_throws_exception_when_no_exchange_rate_is_available(): void
    {
        $mnbService = \Mockery::mock(MnbExchangeRateService::class);

        $mnbService->shouldReceive('getExchangeRates')
            ->once()
            ->with('2026-11-11', '2026-11-21', 'EUR')
            ->andReturn('<MNBExchangeRates></MNBExchangeRates>');

        $mnbService->shouldReceive('parseExchangeRates')
            ->once()
            ->andReturn([]);

        $this->app->instance(MnbExchangeRateService::class, $mnbService);

        $service = app(ExchangeRateService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'No MNB exchange rate found for EUR on or before 2026-11-21.'
        );

        $service->getExchangeRate('EUR', '2026-11-21');
    }

    public function test_it_uses_cached_previous_rate_for_weekends(): void
    {
        ExchangeRate::create([
            'currency' => 'EUR',
            'rate_date' => '2026-11-20',
            'rate' => '369.10000',
            'unit' => 1,
        ]);

        $mnbService = \Mockery::mock(MnbExchangeRateService::class);

        $mnbService->shouldNotReceive('getExchangeRates');

        $this->app->instance(MnbExchangeRateService::class, $mnbService);

        $service = app(ExchangeRateService::class);

        $exchangeRate = $service->getExchangeRate(
            'EUR',
            '2026-11-21'
        );

        $this->assertSame('369.100000', $exchangeRate->rate);

        $this->assertSame(
            '2026-11-20',
            substr((string) $exchangeRate->rate_date, 0, 10)
        );

        $this->assertDatabaseCount('exchange_rates', 1);
    }
}
