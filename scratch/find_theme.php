<?php
$content = file_get_contents(__DIR__ . '/../public/js/main.js');
$pos = strpos($content, 'theme-toggle-btn');
if ($pos !== false) {
    echo "Found theme-toggle-btn in main.js at offset $pos:\n";
    echo substr($content, $pos - 100, 400) . "\n";
}
