<?php
$files = glob(__DIR__ . '/../**/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'session_start') !== false) {
        echo "Found session_start in $file\n";
    }
}
