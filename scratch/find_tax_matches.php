<?php
$content = file_get_contents(__DIR__ . '/../src/Client.php');
preg_match_all('/encrypted|tax/i', $content, $matches, PREG_OFFSET_CAPTURE);
foreach ($matches[0] as $match) {
    echo "Found match: {$match[0]} at offset {$match[1]} (Line: " . (substr_count(substr($content, 0, $match[1]), "\n") + 1) . "): " . substr($content, $match[1]-30, 60) . "\n";
}
