<?php
$content = file_get_contents(__DIR__ . '/../src/Task.php');
$pos = strpos($content, 'function createTask');
if ($pos !== false) {
    echo "Found createTask in Task.php at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
    echo substr($content, $pos - 100, 700) . "\n";
}
$pos2 = strpos($content, 'function updateTask');
if ($pos2 !== false) {
    echo "Found updateTask in Task.php at offset $pos2 (Line: " . (substr_count(substr($content, 0, $pos2), "\n") + 1) . ")\n";
    echo substr($content, $pos2 - 100, 700) . "\n";
}
