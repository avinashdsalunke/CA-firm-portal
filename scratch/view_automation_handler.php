<?php
$content = file_get_contents(__DIR__ . '/../src/Controller/ComplianceController.php');
$pos = strpos($content, 'trigger_automation_run');
if ($pos !== false) {
    echo substr($content, $pos - 100, 600) . "\n";
}
