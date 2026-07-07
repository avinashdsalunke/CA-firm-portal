<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
$pos = strpos($content, 'session_start');
if ($pos !== false) {
    echo "Found session_start at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
    echo substr($content, $pos, 200) . "\n";
} else {
    echo "session_start NOT found in index.php\n";
}
