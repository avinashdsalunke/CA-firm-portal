<?php
// database/run_update_v6.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "Connected to the database.\n";

    $sqlPath = __DIR__ . '/update_v6.sql';
    if (!file_exists($sqlPath)) {
        throw new Exception("update_v6.sql file not found!");
    }

    $sql = file_get_contents($sqlPath);
    // Execute raw SQL script
    $db->exec($sql);
    echo "Database tables for leads, opportunities, client notes, meetings, WhatsApp logs, and email logs created successfully.\n";
    echo "Update V6 applied successfully!\n";
} catch (Exception $e) {
    die("Update V6 failed: " . $e->getMessage() . "\n");
}
