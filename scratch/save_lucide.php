<?php
$sourceFile = 'C:\Users\avina\.gemini\antigravity-ide\brain\30b03769-931d-4844-bcc6-91e6175f8c6c\.system_generated\steps\371\content.md';
$content = file_get_contents($sourceFile);
$parts = explode("---", $content, 2);
if (count($parts) === 2) {
    $js = trim($parts[1]);
    file_put_contents(__DIR__ . '/../public/js/lucide.min.js', $js);
    echo "Saved lucide.min.js successfully: " . strlen($js) . " bytes\n";
} else {
    echo "Could not find delimiter\n";
}
