<?php

namespace App\Services;

use SoapClient;
use RuntimeException;

class MnbExchangeRateService
{
    private const WSDL_URL = 'https://www.mnb.hu/arfolyamok.asmx?wsdl';

    private function createClient(): SoapClient
    {
        try {
            return new SoapClient(self::WSDL_URL, [
                'connection_timeout' => 10,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_MEMORY,
            ]);
        } catch (\SoapFault $exception) {
            throw new RuntimeException(
                'Failed to connect to MNB service: ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    public function getExchangeRates(
        string $startDate,
        string $endDate,
        string $currency
    ): string {
        $client = $this->createClient();

        try {
            $response = $client->GetExchangeRates([
                'startDate' => $startDate,
                'endDate' => $endDate,
                'currencyNames' => strtoupper($currency),
            ]);

            return $response->GetExchangeRatesResult;
        } catch (\SoapFault $exception) {
            throw new RuntimeException(
                'Failed to retrieve MNB exchange rates: ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    /**
     * Parse exchange rates returned by the MNB SOAP service.
     *
     * @return array<int, array{
     *     currency: string,
     *     rate_date: string,
     *     rate: string,
     *     unit: int
     * }>
     */
    public function parseExchangeRates(string $xml): array
    {
        $previousErrorSetting = libxml_use_internal_errors(true);

        try {
            $document = simplexml_load_string(
                $xml,
                \SimpleXMLElement::class,
                LIBXML_NONET
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorSetting);
        }

        if ($document === false) {
            throw new RuntimeException(
                'Invalid XML received from MNB.'
            );
        }

        $exchangeRates = [];

        foreach ($document->Day as $day) {
            $date = (string) $day['date'];

            foreach ($day->Rate as $rate) {
                $currency = (string) $rate['curr'];
                $unit = (int) $rate['unit'];

                $rateValue = str_replace(
                    ',',
                    '.',
                    trim((string) $rate)
                );

                if (
                    $currency === '' ||
                    $unit <= 0 ||
                    !is_numeric($rateValue) ||
                    (float) $rateValue <= 0
                ) {
                    throw new RuntimeException(
                        'Invalid exchange rate data received from MNB.'
                    );
                }

                $exchangeRates[] = [
                    'currency' => $currency,
                    'rate_date' => $date,
                    'rate' => $rateValue,
                    'unit' => $unit,
                ];
            }
        }

        return $exchangeRates;
    }

}
