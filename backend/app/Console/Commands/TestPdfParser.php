<?php

namespace App\Console\Commands;

use App\Services\PdfParserService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('app:test-pdf-parser')]
#[Description('Tests PDF text extraction')]
class TestPdfParser extends Command
{
    public function handle(PdfParserService $parser): int
    {
        $filePath = storage_path(
            'app/private/test/scan_20260824_8726.pdf'
        );

        $this->info('PDF parser test started...');
        $this->line('File: ' . basename($filePath));

        try {
            $text = $parser->extractText($filePath);

            $this->newLine();
            $this->info('PDF processed successfully!');

            $this->table(
                ['Property', 'Value'],
                [
                    ['File size', number_format(filesize($filePath)) . ' bytes'],
                    ['Extracted text length', number_format(mb_strlen($text)) . ' characters'],
                    ['Word count', number_format(str_word_count($text))],
                ]
            );

            $this->newLine();
            $this->line('Extracted text preview:');
            $this->line(mb_substr($text, 0, 500));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('PDF processing failed!');
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
