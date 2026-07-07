<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
preg_match_all('/lucide|chart\.js/i', $content, $matches, PREG_OFFSET_CAPTURE);
foreach ($matches[0] as $match) {
    if (strpos(substr($content, $match[1] - 50, 100), 'script') !== false) {
        echo "Found script reference at offset {$match[1]} (Line: " . (substr_count(substr($content, 0, $match[1]), "\n") + 1) . "): " . substr($content, $match[1] - 50, 100) . "\n";
    }
}
