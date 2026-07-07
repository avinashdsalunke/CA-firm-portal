<?php
$content = file_get_contents(__DIR__ . '/../src/Accounting.php');
$pos = strpos($content, 'function createInvoice');
if ($pos !== false) {
    echo "Found createInvoice in Accounting.php at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
    echo substr($content, $pos, 1000) . "\n";
}
