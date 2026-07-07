<?php
// database/run_migration.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "Connected successfully to the database.\n";
    
    $sql = file_get_contents(__DIR__ . '/update.sql');
    if ($sql === false) {
        die("Error: Could not read update.sql\n");
    }
    
    // Execute raw SQL containing multiple queries
    $db->exec($sql);
    echo "Migration completed successfully. New tables created or already exist.\n";
} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
