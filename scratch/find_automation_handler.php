<?php
$dir = __DIR__ . '/../src/';
foreach (glob($dir . '*.php') as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'trigger_automation_run') !== false) {
        echo "Found trigger_automation_run in $file\n";
    }
}
foreach (glob($dir . 'Controller/*.php') as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'trigger_automation_run') !== false) {
        echo "Found trigger_automation_run in $file\n";
    }
}
