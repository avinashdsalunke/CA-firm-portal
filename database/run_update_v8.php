<?php
// database/run_update_v8.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "Connected to the database.\n";

    $sqlPath = __DIR__ . '/update_v8.sql';
    if (!file_exists($sqlPath)) {
        throw new Exception("update_v8.sql file not found!");
    }

    // Apply migrations and suppress warnings if columns already exist
    $columns = [
        "ALTER TABLE accounting_invoices ADD COLUMN cgst DECIMAL(15, 2) DEFAULT 0.00",
        "ALTER TABLE accounting_invoices ADD COLUMN sgst DECIMAL(15, 2) DEFAULT 0.00",
        "ALTER TABLE accounting_invoices ADD COLUMN igst DECIMAL(15, 2) DEFAULT 0.00",
        "ALTER TABLE accounting_invoices ADD COLUMN tds_amount DECIMAL(15, 2) DEFAULT 0.00",
        "ALTER TABLE accounting_invoices ADD COLUMN net_amount DECIMAL(15, 2) DEFAULT 0.00",
        "ALTER TABLE accounting_invoices ADD COLUMN invoice_design TEXT DEFAULT NULL",
        "ALTER TABLE accounting_expenses ADD COLUMN status VARCHAR(50) DEFAULT 'approved'",
        "ALTER TABLE accounting_expenses ADD COLUMN approved_by INT DEFAULT NULL"
    ];

    foreach ($columns as $colSql) {
        try {
            $db->exec($colSql);
            echo "Executed query: $colSql\n";
        } catch (PDOException $e) {
            echo "Skipped: " . $e->getMessage() . "\n";
        }
    }

    echo "Update V8 applied successfully!\n";
} catch (Exception $e) {
    die("Update V8 failed: " . $e->getMessage() . "\n");
}
