<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
$pos = strpos($content, 'id="inv-service"');
if ($pos !== false) {
    echo substr($content, $pos - 500, 2000);
} else {
    $pos2 = strpos($content, 'inv-service');
    if ($pos2 !== false) {
        echo substr($content, $pos2 - 500, 2000);
    } else {
        echo "Not found";
    }
}
