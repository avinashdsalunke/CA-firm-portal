<?php
$content = file_get_contents(__DIR__ . '/../public/css/style.css');
$pos = strpos($content, '.calendar-widget');
if ($pos !== false) {
    echo substr($content, $pos, 800);
}
