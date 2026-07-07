<?php
function testGen($lastInv) {
    if ($lastInv) {
        if (preg_match('/(?:INV-\d+-|INV-)(\d+)$/i', $lastInv, $matches)) {
            $num = intval($matches[1]) + 1;
            $len = strlen($matches[1]);
            $nextNum = str_pad($num, $len, '0', STR_PAD_LEFT);
            return preg_replace('/(\d+)$/', $nextNum, $lastInv);
        }
    }
    return 'INV-' . date('Y') . '-001';
}

echo "INV-2026-13 -> " . testGen("INV-2026-13") . "\n";
echo "INV-2026-013 -> " . testGen("INV-2026-013") . "\n";
echo "INV-13 -> " . testGen("INV-13") . "\n";
echo "None -> " . testGen(null) . "\n";
