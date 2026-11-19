<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class InvoiceAiService
{
    public function extractInvoiceData(string $text): array
    {
        $apiKey = config('services.openrouter.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException(
                'OpenRouter API key is not configured.'
            );
        }

        if (trim($text) === '') {
            throw new RuntimeException(
                'Invoice text cannot be empty.'
            );
        }

        $baseUrl = rtrim(
            config('services.openrouter.base_url'),
            '/'
        );

        $model = config('services.openrouter.model');

        $response = Http::withToken($apiKey)
            ->timeout(90)
            ->acceptJson()
            ->post($baseUrl . '/chat/completions', [
                'model' => $model,
                'temperature' => 0,
                'response_format' => [
                    'type' => 'json_object',
                ],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->getSystemPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => $text,
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenRouter API request failed: '
                . $response->status()
            );
        }

        $content = $response->json('choices.0.message.content');

        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException(
                'OpenRouter returned an empty response.'
            );
        }

        try {
            $data = json_decode(
                $content,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $exception) {
            throw new RuntimeException(
                'OpenRouter returned invalid JSON.',
                0,
                $exception
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException(
                'OpenRouter response must be a JSON object.'
            );
        }

        return $data;
    }

    private function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an invoice data extraction assistant.

Extract structured information from the provided invoice text.

Important rules:
- The invoice can be written in Hungarian, English, German,
  French, Spanish, Italian or Polish.
- Preserve the original meaning of names and identifiers.
- Never invent missing information.
- Return null for missing or uncertain values.
- Dates must use YYYY-MM-DD format.
- Amounts must be numeric values without currency symbols.
- Use ISO 4217 currency codes: HUF, EUR, USD, GBP, PLN.
- gross_amount must represent the final payable gross amount.
- Do not confuse net amount with gross amount.
- Do not determine incoming or outgoing direction yet.
  This will be determined by the application.

Return only a valid JSON object with this structure:

{
    "invoice_number": null,
    "seller": {
        "name": null,
        "tax_number": null,
        "country_code": null
    },
    "buyer": {
        "name": null,
        "tax_number": null,
        "country_code": null
    },
    "issue_date": null,
    "fulfillment_date": null,
    "due_date": null,
    "currency": null,
    "gross_amount": null
}
PROMPT;
    }
}
