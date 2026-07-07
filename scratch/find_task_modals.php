<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
$pos = strpos($content, 'id="add-task-modal"');
if ($pos !== false) {
    echo "Found add-task-modal at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
    echo substr($content, $pos, 800) . "\n";
}
$pos2 = strpos($content, 'id="edit-task-modal"');
if ($pos2 !== false) {
    echo "Found edit-task-modal at offset $pos2 (Line: " . (substr_count(substr($content, 0, $pos2), "\n") + 1) . ")\n";
    echo substr($content, $pos2, 800) . "\n";
}
