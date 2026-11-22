<?php

namespace Tests\Unit;

use App\Services\PdfParserService;
use Tests\TestCase;
use RuntimeException;

class PdfParserServiceTest extends TestCase
{
    public function test_it_extracts_text_from_a_valid_pdf(): void
    {
        $filePath = base_path(
            'tests/Fixtures/sample-invoice.pdf'
        );

        $parser = new PdfParserService();

        $text = $parser->extractText($filePath);

        $this->assertNotEmpty($text);
        $this->assertStringContainsString('TEST-2026-001', $text);
        $this->assertStringContainsString('Example Test Company Ltd.', $text);
    }

    public function test_it_throws_exception_for_a_missing_pdf(): void
    {
        $parser = new PdfParserService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'PDF file does not exist or is not readable'
        );

        $parser->extractText(
            storage_path('app/private/test/non-existent.pdf')
        );
    }
}
