<?php
// scratch/test_db_and_logic.php

require_once __DIR__ . '/../src/Accounting.php';
require_once __DIR__ . '/../src/Compliance.php';

try {
    echo "Testing Accounting Stats...\n";
    $stats = Accounting::getFinancialStats();
    print_r($stats);
    
    echo "\nTesting Compliance Stats...\n";
    $compStats = Compliance::getStats();
    print_r($compStats);

    echo "\nTesting Invoices Retrieval...\n";
    $invoices = Accounting::getInvoices();
    echo "Found " . count($invoices) . " invoices.\n";
    
    echo "\nTesting Compliance Tasks Retrieval...\n";
    $compliances = Compliance::getCompliances();
    echo "Found " . count($compliances) . " compliance tasks.\n";

    echo "\nALL SERVICE LOGIC AND DATABASE CHECKS PASSED!\n";
} catch (Exception $e) {
    die("\nTEST FAILED: " . $e->getMessage() . "\n");
}
