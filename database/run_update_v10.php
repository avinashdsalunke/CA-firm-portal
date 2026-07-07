<?php
// database/run_update_v10.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "Connected to the database.\n";

    // Read and execute update_v10.sql
    $sql = file_get_contents(__DIR__ . '/update_v10.sql');
    $db->exec($sql);
    echo "SQL script applied successfully.\n";
    echo "Update V10 applied successfully!\n";
} catch (Exception $e) {
    die("Update V10 failed: " . $e->getMessage() . "\n");
}
