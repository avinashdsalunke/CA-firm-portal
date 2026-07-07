<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
$pos = strpos($content, "activeTab === 'templates'");
if ($pos !== false) {
    echo "Found activeTab === 'templates' at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
}
