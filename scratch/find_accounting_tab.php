<?php
$content = file_get_contents(__DIR__ . '/../public/index.php');
$pos = strpos($content, "activeTab === 'accounting'");
if ($pos !== false) {
    echo "Found activeTab === 'accounting' at offset $pos (Line: " . (substr_count(substr($content, 0, $pos), "\n") + 1) . ")\n";
    // Find where the next elseif or endif is
    $rest = substr($content, $pos);
    preg_match('/<\?php\s+(elseif|endif)/i', $rest, $m, PREG_OFFSET_CAPTURE);
    if (!empty($m)) {
        $endPos = $pos + $m[0][1];
        echo "Found next elseif/endif at offset $endPos (Line: " . (substr_count(substr($content, 0, $endPos), "\n") + 1) . ")\n";
    }
}
