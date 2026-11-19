<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Throwable;
use RuntimeException;

class PdfParserService
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    /**
     * Extract text from a PDF file.
     */
    public function extractText(string $filePath): string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new RuntimeException(
                "PDF file does not exist or is not readable: {$filePath}"
            );
        }

        try {
            $pdf = $this->parser->parseFile($filePath);

            $text = trim($pdf->getText());

            if ($text === '') {
                throw new RuntimeException(
                    'No readable text found in the PDF file.'
                );
            }

            return $text;
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Failed to extract text from PDF: ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }
}
