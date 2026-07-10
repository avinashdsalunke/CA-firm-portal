<?php
// scratch/run_migration.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    $sql = file_get_contents(__DIR__ . '/../database/migrations.sql');
    
    // Execute raw SQL script
    $db->exec($sql);
    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
