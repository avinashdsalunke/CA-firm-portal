<?php
// scratch/test_phase4.php

require_once __DIR__ . '/../src/HRMS.php';
require_once __DIR__ . '/../src/Accounting.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "1. Testing Salary Slip Payroll System...\n";
    
    $empId = 2; // Staff member (usually exists in seed)
    $month = "2026-07";
    
    // Delete existing slip if any
    $db->prepare("DELETE FROM salary_slips WHERE employee_id = :uid AND month = :month")->execute(['uid' => $empId, 'month' => $month]);
    
    // Generate salary slip
    $res = HRMS::generateSalarySlip(
        $empId,
        $month,
        30000.00, // basic
        12000.00, // hra
        2000.00,  // conveyance
        5000.00,  // allowance
        1800.00,  // pf
        200.00,   // pt
        1000.00   // tds
    );
    
    if (!isset($res['success'])) {
        throw new Exception("Failed to generate salary slip: " . ($res['error'] ?? ''));
    }
    echo "Salary slip generated successfully.\n";
    
    // Fetch and check slip details
    $slips = HRMS::getSalarySlips($empId);
    $targetSlip = null;
    foreach ($slips as $s) {
        if ($s['month'] === $month) {
            $targetSlip = $s;
            break;
        }
    }
    
    if (!$targetSlip) {
        throw new Exception("Could not find generated salary slip!");
    }
    
    // Net take-home should be basic + HRA + conveyance + allowance - (PF + PT + TDS)
    // 30000 + 12000 + 2000 + 5000 - (1800 + 200 + 1000) = 49000 - 3000 = 46000
    if (floatval($targetSlip['net_salary']) !== 46000.00) {
        throw new Exception("Salary Slip calculation error! Expected 46000.00, got " . $targetSlip['net_salary']);
    }
    echo "Net salary calculation verified: ₹" . $targetSlip['net_salary'] . "\n";
    
    // Pay salary slip
    $payRes = HRMS::paySalarySlip($targetSlip['id']);
    if (!isset($payRes['success'])) {
        throw new Exception("Failed to pay salary slip: " . ($payRes['error'] ?? ''));
    }
    
    $updatedSlip = HRMS::getSalarySlip($targetSlip['id']);
    if ($updatedSlip['status'] !== 'paid') {
        throw new Exception("Salary Slip pay status did not update!");
    }
    echo "Salary slip payment status verified.\n";
    
    echo "\n2. Testing Dynamic Services Catalog...\n";
    $services = Accounting::getServices();
    if (empty($services)) {
        throw new Exception("Services list is empty!");
    }
    echo "Successfully retrieved " . count($services) . " services from the database.\n";
    foreach ($services as $srv) {
        echo "Service: " . $srv['name'] . " -> Charge: ₹" . $srv['charge'] . "\n";
    }
    
    echo "\n3. Testing Monthly Employee History...\n";
    // Check-in staff 2 for testing monthly log if not present
    $testDate = "2026-07-05";
    $db->exec("DELETE FROM attendance WHERE user_id = $empId AND date = '$testDate'");
    $db->exec("INSERT INTO attendance (user_id, date, check_in, status) VALUES ($empId, '$testDate', '09:00:00', 'present')");
    
    // Fetch month-wise attendance
    $stmt = $db->prepare("
        SELECT * FROM attendance 
        WHERE user_id = :uid 
          AND DATE_FORMAT(date, '%Y-%m') = :month
    ");
    $stmt->execute(['uid' => $empId, 'month' => $month]);
    $monthAtt = $stmt->fetchAll();
    echo "Monthly attendance log count: " . count($monthAtt) . "\n";
    if (count($monthAtt) === 0) {
        throw new Exception("Failed to fetch monthly attendance logs!");
    }
    
    echo "\nALL PHASE 4 CHECKS AND SYSTEM TESTS PASSED!\n";
} catch (Exception $e) {
    die("\nTEST FAILED: " . $e->getMessage() . "\n");
}
