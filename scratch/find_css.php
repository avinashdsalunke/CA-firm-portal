<?php
$content = file_get_contents(__DIR__ . '/../public/css/style.css');
preg_match_all('/\.[\w-]+\s*\{[^}]*search[^}]*\}/i', $content, $matches1);
preg_match_all('/\.[\w-]+\s*\{[^}]*right[^}]*\}/i', $content, $matches2);
echo "--- Search classes ---\n";
print_r($matches1[0]);
echo "--- Right classes ---\n";
print_r($matches2[0]);
