<?php
$dir = __DIR__ . '/../cache/';
if (is_dir($dir)) {
    foreach(glob($dir . '*') as $file) {
        if(is_file($file)) {
            unlink($file);
            echo "Deleted $file\n";
        }
    }
}
