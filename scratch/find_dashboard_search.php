<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
$startPos = strpos($content, "activeTab === 'dashboard'");
$endPos = strpos($content, "activeTab === 'clients'");
if ($startPos !== false && $endPos !== false) {
    $dashboardBlock = substr($content, $startPos, $endPos - $startPos);
    preg_match_all('/search/i', $dashboardBlock, $matches, PREG_OFFSET_CAPTURE);
    foreach ($matches[0] as $match) {
        echo "Found search in dashboard block: " . substr($dashboardBlock, $match[1] - 50, 100) . "\n";
    }
} else {
    echo "Could not find blocks\n";
}
