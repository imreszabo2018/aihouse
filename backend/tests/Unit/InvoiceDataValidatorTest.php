<?php

namespace Tests\Unit;

use App\Services\InvoiceDataValidator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvoiceDataValidatorTest extends TestCase
{
    private function validInvoiceData(): array
    {
        return [
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
        ];
    }

    public function test_it_accepts_valid_invoice_data(): void
    {
        $data = $this->validInvoiceData();

        $validator = new InvoiceDataValidator();

        $result = $validator->validate($data);

        $this->assertSame($data, $result);
    }

    public function test_it_rejects_missing_invoice_number(): void
    {
        $data = $this->validInvoiceData();

        unset($data['invoice_number']);

        $validator = new InvoiceDataValidator();

        $this->expectException(ValidationException::class);

        $validator->validate($data);
    }

    public function test_it_rejects_unsupported_currency(): void
    {
        $data = $this->validInvoiceData();
        $data['currency'] = 'RON';

        $validator = new InvoiceDataValidator();

        $this->expectException(ValidationException::class);

        $validator->validate($data);
    }

    public function test_it_rejects_zero_or_negative_gross_amount(): void
    {
        $validator = new InvoiceDataValidator();

        foreach ([0, -100] as $amount) {
            $data = $this->validInvoiceData();
            $data['gross_amount'] = $amount;

            try {
                $validator->validate($data);

                $this->fail(
                    'Expected validation to reject gross amount: ' . $amount
                );
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_it_rejects_invalid_date_format(): void
    {
        $data = $this->validInvoiceData();
        $data['issue_date'] = '24/08/2026';

        $validator = new InvoiceDataValidator();

        $this->expectException(ValidationException::class);

        $validator->validate($data);
    }
}
