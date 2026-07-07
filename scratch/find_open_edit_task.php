<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
$pos = strpos($content, 'function openEditTask');
if ($pos !== false) {
    echo "Found openEditTask at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
    echo substr($content, $pos, 600) . "\n";
}
