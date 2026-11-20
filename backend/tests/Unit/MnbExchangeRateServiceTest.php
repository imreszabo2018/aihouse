<?php

namespace Tests\Unit;

use App\Services\MnbExchangeRateService;
use PHPUnit\Framework\TestCase;

class MnbExchangeRateServiceTest extends TestCase
{
    public function test_it_can_parse_mnb_exchange_rates(): void
    {
        $xml = '
            <MNBExchangeRates>
                <Day date="2026-11-19">
                    <Rate unit="1" curr="EUR">368,97000</Rate>
                    <Rate unit="1" curr="USD">320,15000</Rate>
                </Day>
            </MNBExchangeRates>
        ';

        $service = new MnbExchangeRateService();

        $rates = $service->parseExchangeRates($xml);

        $this->assertCount(2, $rates);

        $this->assertSame([
            'currency' => 'EUR',
            'rate_date' => '2026-11-19',
            'rate' => '368.97000',
            'unit' => 1,
        ], $rates[0]);

        $this->assertSame([
            'currency' => 'USD',
            'rate_date' => '2026-11-19',
            'rate' => '320.15000',
            'unit' => 1,
        ], $rates[1]);
    }

    public function test_it_throws_exception_for_invalid_xml(): void
    {
        $service = new MnbExchangeRateService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid XML received from MNB.');

        $service->parseExchangeRates('<invalid-xml>');
    }

}
