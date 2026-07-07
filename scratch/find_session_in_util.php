<?php
$content = file_get_contents(__DIR__ . '/../src/Util.php');
$pos = strpos($content, 'session_start');
if ($pos !== false) {
    echo "Found session_start in Util.php at offset $pos:\n";
    echo substr($content, $pos - 50, 200) . "\n";
}
