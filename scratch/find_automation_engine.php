<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
preg_match_all('/action=[^\']*automation[^\']*|automation|spawn/i', $content, $matches, PREG_OFFSET_CAPTURE);
foreach ($matches[0] as $match) {
    echo "Found automation match: {$match[0]} at offset {$match[1]} (Line: " . (substr_count(substr($content, 0, $match[1]), "\n") + 1) . "): " . substr($content, $match[1]-20, 80) . "\n";
}
