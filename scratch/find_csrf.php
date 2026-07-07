<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
preg_match_all('/name="csrf_token"[^>]*value="[^"]*"/i', $content, $matches, PREG_OFFSET_CAPTURE);
foreach ($matches[0] as $match) {
    echo "Found CSRF: {$match[0]} at offset {$match[1]} (Line: " . (substr_count(substr($content, 0, $match[1]), "\n") + 1) . ")\n";
}
preg_match_all('/csrf/i', $content, $matches2, PREG_OFFSET_CAPTURE);
foreach ($matches2[0] as $match) {
    echo "Found 'csrf' at line " . (substr_count(substr($content, 0, $match[1]), "\n") + 1) . "\n";
}
