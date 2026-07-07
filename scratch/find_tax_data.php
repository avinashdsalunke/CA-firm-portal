<?php
$content = file_get_contents(__DIR__ . '/../src/Client.php');
$pos = strpos($content, 'encrypted_tax_data');
if ($pos !== false) {
    echo "Found encrypted_tax_data in Client.php at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
    echo substr($content, $pos - 100, 500) . "\n";
}
