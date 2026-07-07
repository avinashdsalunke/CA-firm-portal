<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
preg_match_all('/action="[^"]*add_invoice[^"]*"|<form[^>]*>[^<]*invoice/i', $content, $matches, PREG_OFFSET_CAPTURE);
foreach ($matches[0] as $match) {
    echo "Found invoice form/action: " . substr($content, $match[1], 150) . "...\n";
}
preg_match_all('/service_charge|service charge/i', $content, $matches2, PREG_OFFSET_CAPTURE);
foreach ($matches2[0] as $match) {
    echo "Found service charge: " . substr($content, $match[1]-50, 100) . "...\n";
}
