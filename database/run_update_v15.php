<?php
// database/run_update_v15.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "Applying update_v15.sql shift assignments table migration...\n";

    $sql = file_get_contents(__DIR__ . '/update_v15.sql');
    $db->exec($sql);
    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    die("Database migration error: " . $e->getMessage() . "\n");
}
