<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
$pos = strpos($content, 'class="data-table"');
while ($pos !== false) {
    $sub = substr($content, $pos, 600);
    if (strpos($sub, 'Filing Date') !== false || strpos($sub, 'Acknowledgement') !== false || strpos($sub, 'compliances') !== false) {
        echo "Found table at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
        echo $sub . "\n\n";
    }
    $pos = strpos($content, 'class="data-table"', $pos + 1);
}
