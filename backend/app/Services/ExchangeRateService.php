<?php

namespace App\Services;

use App\Models\ExchangeRate;

class ExchangeRateService
{

    public function __construct(
        private MnbExchangeRateService $mnbService
    ) {
    }

    /**
     * Get an exchange rate for a given currency and date.
     * Uses the latest available MNB rate on or before the requested date.
     */
    public function getExchangeRate(
        string $currency,
        string $date
    ): ExchangeRate {
        $currency = strtoupper($currency);

        // Check whether an exchange rate is already stored
        // for the requested date or an earlier date within 10 days.
        $startDate = date(
            'Y-m-d',
            strtotime($date . ' -10 days')
        );

        $existingRate = ExchangeRate::where('currency', $currency)
            ->whereDate('rate_date', '<=', $date)
            ->whereDate('rate_date', '>=', $startDate)
            ->orderByDesc('rate_date')
            ->first();

        if ($existingRate !== null) {
            return $existingRate;
        }

        // Look back up to 10 calendar days for an available MNB rate.
        $startDate = date(
            'Y-m-d',
            strtotime($date . ' -10 days')
        );

        $xml = $this->mnbService->getExchangeRates(
            $startDate,
            $date,
            $currency
        );

        $rates = $this->mnbService->parseExchangeRates($xml);

        if (empty($rates)) {
            throw new \RuntimeException(
                "No MNB exchange rate found for {$currency} on or before {$date}."
            );
        }

        // Select the latest rate that is not later than the requested date.
        usort(
            $rates,
            fn(array $a, array $b) =>
                strcmp($b['rate_date'], $a['rate_date'])
        );

        $selectedRate = $rates[0];

        $this->saveExchangeRates([$selectedRate]);

        return ExchangeRate::where('currency', $currency)
            ->whereDate('rate_date', $selectedRate['rate_date'])
            ->firstOrFail();
    }

    /**
     * Save exchange rates to the database.
     *
     * @param array<int, array{
     *     currency: string,
     *     rate_date: string,
     *     rate: string,
     *     unit: int
     * }> $rates
     */
    public function saveExchangeRates(array $rates): void
    {
        foreach ($rates as $rate) {
            $exchangeRate = ExchangeRate::where(
                'currency',
                $rate['currency']
            )
                ->whereDate('rate_date', $rate['rate_date'])
                ->first();

            if ($exchangeRate === null) {
                $exchangeRate = new ExchangeRate([
                    'currency' => $rate['currency'],
                    'rate_date' => $rate['rate_date'],
                ]);
            }

            $exchangeRate->rate = $rate['rate'];
            $exchangeRate->unit = $rate['unit'];

            $exchangeRate->save();
        }
    }

}
