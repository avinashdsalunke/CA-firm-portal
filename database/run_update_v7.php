<?php
// database/run_update_v7.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "Connected to the database.\n";

    $sqlPath = __DIR__ . '/update_v7.sql';
    if (!file_exists($sqlPath)) {
        throw new Exception("update_v7.sql file not found!");
    }

    $sql = file_get_contents($sqlPath);
    // Execute SQL queries (ignore errors in case column already exists)
    try {
        $db->exec("ALTER TABLE employees ADD COLUMN shift VARCHAR(50) DEFAULT 'General'");
        echo "Altered employees: Added shift column.\n";
    } catch (PDOException $e) {
        echo "employees shift column already exists or skipped.\n";
    }

    try {
        $db->exec("ALTER TABLE leave_requests ADD COLUMN comments TEXT DEFAULT NULL");
        echo "Altered leave_requests: Added comments column.\n";
    } catch (PDOException $e) {
        echo "leave_requests comments column already exists or skipped.\n";
    }

    try {
        $db->exec("ALTER TABLE leave_requests ADD COLUMN workflow_step VARCHAR(100) DEFAULT 'approved_by_admin'");
        echo "Altered leave_requests: Added workflow_step column.\n";
    } catch (PDOException $e) {
        echo "leave_requests workflow_step column already exists or skipped.\n";
    }

    echo "Update V7 applied successfully!\n";
} catch (Exception $e) {
    die("Update V7 failed: " . $e->getMessage() . "\n");
}
