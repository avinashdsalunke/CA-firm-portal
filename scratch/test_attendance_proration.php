<?php
// scratch/test_attendance_proration.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/HRMS.php';

try {
    $db = Database::getConnection();
    echo "Starting attendance proration tests...\n";

    $empId = 2; // Test employee
    $testMonth = date('Y-m');
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, intval(date('m')), intval(date('Y')));

    // Clear attendance records for test employee in test month first
    $db->prepare("DELETE FROM attendance WHERE user_id = :uid AND DATE_FORMAT(date, '%Y-%m') = :month")
       ->execute(['uid' => $empId, 'month' => $testMonth]);

    // Insert 10 'present' days
    $stmtIns = $db->prepare("INSERT INTO attendance (user_id, date, check_in, check_out, status) VALUES (:uid, :date, '09:00:00', '17:00:00', 'present')");
    for ($i = 1; $i <= 10; $i++) {
        $dayStr = sprintf('%02d', $i);
        $date = date('Y-m-') . $dayStr;
        $stmtIns->execute(['uid' => $empId, 'date' => $date]);
    }

    // Insert 2 'on_leave' days
    $stmtLeave = $db->prepare("INSERT INTO attendance (user_id, date, check_in, check_out, status) VALUES (:uid, :date, NULL, NULL, 'on_leave')");
    for ($i = 11; $i <= 12; $i++) {
        $dayStr = sprintf('%02d', $i);
        $date = date('Y-m-') . $dayStr;
        $stmtLeave->execute(['uid' => $empId, 'date' => $date]);
    }

    echo "Inserted 12 paid days (10 present, 2 on leave).\n";

    // Simulate calling the API endpoint
    $_GET['action'] = 'calculate_attendance_salary';
    $_GET['employee_id'] = $empId;
    $_GET['month'] = $testMonth;

    // Start buffer to capture api output
    ob_start();
    require __DIR__ . '/../public/api.php';
    $output = ob_get_clean();

    $response = json_decode($output, true);

    if (!$response || !isset($response['success']) || !$response['success']) {
        throw new Exception("API failed to return successful calculated response. Output: " . $output);
    }

    echo "API returned response: Paid Days: " . $response['paid_days'] . "/" . $response['total_days'] . "\n";
    
    if (intval($response['paid_days']) !== 12) {
        throw new Exception("Expected 12 paid days, got " . $response['paid_days']);
    }

    $expectedRatio = 12 / $daysInMonth;
    $calculatedPercent = floatval($response['ratio_percent']);
    $expectedPercent = round($expectedRatio * 100, 2);

    if (abs($calculatedPercent - $expectedPercent) > 0.01) {
        throw new Exception("Expected ratio percent $expectedPercent, got $calculatedPercent");
    }
    
    echo "Proration ratio verified: " . $calculatedPercent . "%\n";
    echo "ALL ATTENDANCE PRORATION TESTS PASSED SUCCESSFULLY!\n";

} catch (Exception $e) {
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    die("\nTEST FAILED: " . $e->getMessage() . "\n");
}
