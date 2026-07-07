<?php
$content = file_get_contents(__DIR__ . '/../src/Accounting.php');
preg_match_all('/invoice_number/i', $content, $matches, PREG_OFFSET_CAPTURE);
foreach ($matches[0] as $match) {
    echo "Found invoice_number at offset {$match[1]} (Line: " . (substr_count(substr($content, 0, $match[1]), "\n") + 1) . "): " . substr($content, $match[1]-30, 80) . "\n";
}
