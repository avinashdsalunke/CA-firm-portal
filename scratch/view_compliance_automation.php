<?php
$content = file_get_contents(__DIR__ . '/../src/Compliance.php');
$pos = strpos($content, 'function runComplianceAutomation');
if ($pos !== false) {
    echo "Found runComplianceAutomation at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
    echo substr($content, $pos, 1500) . "\n";
}
