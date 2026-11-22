<?php

$content = "BT\n"
    . "/F1 12 Tf\n"
    . "50 780 Td\n"
    . "(FICTIVE TEST INVOICE) Tj\n"
    . "0 -25 Td\n"
    . "(Invoice number: TEST-2026-001) Tj\n"
    . "0 -25 Td\n"
    . "(Seller: Example Test Company Ltd.) Tj\n"
    . "0 -25 Td\n"
    . "(Buyer: Sample Customer Ltd.) Tj\n"
    . "ET";

$objects = [
    1 => '<< /Type /Catalog /Pages 2 0 R >>',
    2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
    3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
    4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
    5 => "<< /Length " . strlen($content) . " >>\nstream\n"
        . $content . "\nendstream",
];

$pdf = "%PDF-1.4\n";
$offsets = [0];

foreach ($objects as $number => $object) {
    $offsets[$number] = strlen($pdf);

    $pdf .= $number . " 0 obj\n"
        . $object . "\n"
        . "endobj\n";
}

$xrefOffset = strlen($pdf);

$pdf .= "xref\n";
$pdf .= "0 " . (count($objects) + 1) . "\n";
$pdf .= "0000000000 65535 f \n";

foreach ($objects as $number => $object) {
    $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
}

$pdf .= "trailer\n";
$pdf .= "<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
$pdf .= "startxref\n";
$pdf .= $xrefOffset . "\n";
$pdf .= "%%EOF\n";

file_put_contents(__DIR__ . '/sample-invoice.pdf', $pdf);

echo "Test PDF created successfully.\n";
