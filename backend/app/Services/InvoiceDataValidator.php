<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InvoiceDataValidator
{
    private const SUPPORTED_CURRENCIES = [
        'HUF',
        'EUR',
        'USD',
        'GBP',
        'PLN',
    ];

    public function validate(array $data): array
    {
        $validator = Validator::make($data, [
            'invoice_number' => [
                'required',
                'string',
                'max:100',
            ],

            'seller' => [
                'required',
                'array',
            ],
            'seller.name' => [
                'required',
                'string',
                'max:255',
            ],
            'seller.tax_number' => [
                'nullable',
                'string',
                'max:50',
            ],
            'seller.country_code' => [
                'nullable',
                'string',
                'size:2',
            ],

            'buyer' => [
                'required',
                'array',
            ],
            'buyer.name' => [
                'required',
                'string',
                'max:255',
            ],
            'buyer.tax_number' => [
                'nullable',
                'string',
                'max:50',
            ],
            'buyer.country_code' => [
                'nullable',
                'string',
                'size:2',
            ],

            'issue_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'fulfillment_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'due_date' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'currency' => [
                'required',
                'string',
                'in:' . implode(',', self::SUPPORTED_CURRENCIES),
            ],

            'gross_amount' => [
                'required',
                'numeric',
                'gt:0',
                'max:9999999999999.99',
            ],
        ]);

        $validator->validate();

        return $data;
    }
}
