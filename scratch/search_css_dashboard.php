<?php
$content = file_get_contents(__DIR__ . '/../public/css/style.css');
preg_match_all('/#revenueChart|#clientGrowthChart|canvas|\.calendar-widget/i', $content, $matches, PREG_OFFSET_CAPTURE);
foreach ($matches[0] as $match) {
    echo "Found match: {$match[0]} at offset {$match[1]} (Line: " . (substr_count(substr($content, 0, $match[1]), "\n") + 1) . ")\n";
}
