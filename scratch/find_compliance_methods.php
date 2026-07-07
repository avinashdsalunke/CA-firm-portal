<?php
$content = file_get_contents(__DIR__ . '/../src/Compliance.php');
$pos = strpos($content, 'function createCompliance');
if ($pos !== false) {
    echo "Found createCompliance in Compliance.php at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
    echo substr($content, $pos, 600) . "\n";
}
$pos2 = strpos($content, 'function updateCompliance');
if ($pos2 !== false) {
    echo "Found updateCompliance in Compliance.php at offset $pos2 (Line: " . (substr_count(substr($content, 0, $pos2), "\n") + 1) . ")\n";
    echo substr($content, $pos2, 600) . "\n";
}
