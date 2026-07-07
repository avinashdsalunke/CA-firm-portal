<?php
$files = glob(__DIR__ . '/../**/*.css');
foreach ($files as $file) {
    echo "Found CSS file: $file\n";
}
