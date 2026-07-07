<?php
$content = file_get_contents(__DIR__ . '/../src/Task.php');
$pos = strpos($content, 'function getAdminStats');
if ($pos !== false) {
    echo "Found getAdminStats in Task.php at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
    echo substr($content, $pos, 400) . "\n";
}
