<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
$pos = strpos($content, 'id="add-client-modal"');
if ($pos !== false) {
    echo "Found add-client-modal at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
    echo substr($content, $pos, 1000) . "\n";
}
$pos2 = strpos($content, 'id="edit-client-modal"');
if ($pos2 !== false) {
    echo "Found edit-client-modal at offset $pos2 (Line: " . (substr_count(substr($content, 0, $pos2), "\n") + 1) . ")\n";
    echo substr($content, $pos2, 1000) . "\n";
}
