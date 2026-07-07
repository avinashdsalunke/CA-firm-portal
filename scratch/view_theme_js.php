<?php
$content = file_get_contents(__DIR__ . '/../public/js/main.js');
$pos = strpos($content, 'theme-toggle-btn');
if ($pos !== false) {
    echo substr($content, $pos, 600) . "\n";
}
