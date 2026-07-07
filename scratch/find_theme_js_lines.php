<?php
$content = file_get_contents(__DIR__ . '/../public/js/main.js');
$pos = strpos($content, 'const currentTheme');
if ($pos !== false) {
    echo "Found currentTheme at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
    echo substr($content, $pos, 400) . "\n";
}
