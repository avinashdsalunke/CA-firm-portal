<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
preg_match_all('/\$servicesList\s*=\s*[^;]+/i', $content, $matches, PREG_OFFSET_CAPTURE);
foreach ($matches[0] as $match) {
    echo "Found servicesList: {$match[0]} at offset {$match[1]} (Line: " . (substr_count(substr($content, 0, $match[1]), "\n") + 1) . ")\n";
}
