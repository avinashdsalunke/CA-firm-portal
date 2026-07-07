<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
$pos = strpos($content, '$_SERVER[\'REQUEST_METHOD\'] === \'POST\'');
if ($pos !== false) {
    echo "Found POST handling at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
    echo substr($content, $pos, 800) . "\n";
}
