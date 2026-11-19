<?php

namespace Tests\Unit;

use App\Services\PdfParserService;
use Tests\TestCase;
use RuntimeException;

class PdfParserServiceTest extends TestCase
{
    public function test_it_extracts_text_from_a_valid_pdf(): void
    {
        $filePath = storage_path(
            'app/private/test/scan_20260824_8726.pdf'
        );

        $parser = new PdfParserService();

        $text = $parser->extractText($filePath);

        $this->assertNotEmpty($text);
        $this->assertStringContainsString('PI-26-0050', $text);
        $this->assertStringContainsString('NORDWIND SOLUTIONS KFT.', $text);
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
