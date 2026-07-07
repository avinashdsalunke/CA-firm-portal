<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
preg_match_all('/<input[^>]*type="[^"]*search[^"]*"|<input[^>]*placeholder="[^"]*search[^"]*"/i', $content, $matches, PREG_OFFSET_CAPTURE);
foreach ($matches[0] as $match) {
    echo "Found search input: {$match[0]} at offset {$match[1]} (Line: " . (substr_count(substr($content, 0, $match[1]), "\n") + 1) . ")\n";
}
preg_match_all('/<form[^>]*class="[^"]*search[^"]*"/i', $content, $matches2, PREG_OFFSET_CAPTURE);
foreach ($matches2[0] as $match) {
    echo "Found search form: {$match[0]} at offset {$match[1]} (Line: " . (substr_count(substr($content, 0, $match[1]), "\n") + 1) . ")\n";
}
