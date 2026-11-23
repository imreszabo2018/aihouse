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
     * Get the latest available exchange rate on or before the requested date.
     */
    public function getExchangeRate(
        string $currency,
        string $date
    ): ExchangeRate {
        $currency = strtoupper($currency);

        // Only use a cached rate if it matches the exact requested date.
        $existingRate = ExchangeRate::where('currency', $currency)
            ->whereDate('rate_date', $date)
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

        // Never accept a rate later than the requested date.
        $rates = array_filter(
            $rates,
            fn(array $rate): bool =>
                $rate['currency'] === $currency &&
                $rate['rate_date'] <= $date
        );

        if (empty($rates)) {
            throw new \RuntimeException(
                "No MNB exchange rate found for {$currency} on or before {$date}."
            );
        }

        // Select the latest available rate.
        usort(
            $rates,
            fn(array $a, array $b): int =>
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
