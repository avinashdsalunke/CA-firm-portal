<?php
$content = file_get_contents(__DIR__ . '/../src/Controller/ComplianceController.php');
$pos = strpos($content, 'add_compliance');
if ($pos !== false) {
    echo "Found add_compliance in ComplianceController.php at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
    echo substr($content, $pos - 100, 600) . "\n";
}
