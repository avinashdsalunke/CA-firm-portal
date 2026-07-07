<?php
// scratch/test_phase5.php

require_once __DIR__ . '/../src/HRMS.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "Testing Structured Salary profiles...\n";
    
    $empId = 2; // Staff member
    
    // Save structured details
    $res = HRMS::updateEmployeeDetails(
        $empId,
        "Taxation",          // department
        "Tax Lead Specialist", // designation
        "2026-01-15",        // joining date
        60000.00,            // gross salary
        "active",            // status
        35000.00,            // basic
        15000.00,            // HRA
        3000.00,             // Conveyance
        7000.00,             // Special Allowance
        1800.00,             // PF
        200.00,              // PT
        3000.00              // TDS
    );
    
    if (!isset($res['success'])) {
        throw new Exception("Failed to update employee details with salary structure: " . ($res['error'] ?? ''));
    }
    echo "Employee details and salary structure updated successfully.\n";
    
    // Retrieve details and assert values
    $details = HRMS::getEmployeeDetails($empId);
    if (!$details) {
        throw new Exception("Could not find employee details!");
    }
    
    if (floatval($details['basic']) !== 35000.00 || floatval($details['hra']) !== 15000.00 || floatval($details['pf']) !== 1800.00) {
        throw new Exception("Retrieved structured salary values do not match input!");
    }
    echo "Retrieved values match successfully! Basic: " . $details['basic'] . ", HRA: " . $details['hra'] . ", PF: " . $details['pf'] . "\n";
    
    echo "ALL STRUCTURED SALARY TESTS PASSED SUCCESSFULLY!\n";
} catch (Exception $e) {
    die("\nTEST FAILED: " . $e->getMessage() . "\n");
}
