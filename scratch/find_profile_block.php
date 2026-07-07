<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
$pos = strpos($content, "clientId > 0");
if ($pos !== false) {
    echo "Found clientId > 0 at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
    // Find client-details-grid or similar
    echo substr($content, $pos, 2500) . "\n";
}
