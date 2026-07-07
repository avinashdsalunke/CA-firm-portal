<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
$pos = strpos($content, 'id="log-invoice-modal"');
if ($pos !== false) {
    echo "Found log-invoice-modal at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
    echo substr($content, $pos, 1500) . "\n";
}
