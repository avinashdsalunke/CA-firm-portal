<?php
$content = file_get_contents(__DIR__ . '/../public/css/style.css');
preg_match_all('/\.[\w-]+\s*\{[^}]*grid[^}]*\}/i', $content, $matches, PREG_OFFSET_CAPTURE);
foreach ($matches[0] as $match) {
    echo "Found grid class: " . substr($content, $match[1], 150) . "...\n";
}
