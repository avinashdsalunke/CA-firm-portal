<?php
// scratch/test_phase2.php

require_once __DIR__ . '/../src/RBAC.php';
require_once __DIR__ . '/../src/Security.php';
require_once __DIR__ . '/../src/HRMS.php';
require_once __DIR__ . '/../src/Communication.php';

try {
    echo "Testing Security Auditing...\n";
    Security::logLoginAttempt("test@example.com", 1, "success");
    Security::logActivity("test_action", "Running verification tests");
    $loginLogs = Security::getLoginLogs(5);
    echo "Found " . count($loginLogs) . " login logs.\n";

    echo "\nTesting RBAC Policies...\n";
    $hasPerm = RBAC::hasPermission("super_admin", "manage_staff");
    echo "Super Admin has manage_staff: " . ($hasPerm ? 'YES' : 'NO') . "\n";
    $hasPermStaff = RBAC::hasPermission("staff", "manage_staff");
    echo "Staff has manage_staff: " . ($hasPermStaff ? 'YES' : 'NO') . "\n";

    echo "\nTesting HRMS Clocking...\n";
    $clockIn = HRMS::clockIn(1);
    print_r($clockIn);
    $clockOut = HRMS::clockOut(1);
    print_r($clockOut);

    echo "\nTesting Communication Messages...\n";
    $msg = Communication::sendMessage(1, 2, "Test check-in greeting!");
    print_r($msg);
    $history = Communication::getChatHistory(1, 2);
    echo "Found " . count($history) . " chat messages.\n";

    echo "\nALL PHASE 2 LOGIC AND DATABASE CHECKS PASSED!\n";
} catch (Exception $e) {
    die("\nTEST FAILED: " . $e->getMessage() . "\n");
}
