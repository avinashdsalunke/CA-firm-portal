<?php
// database/run_update_v12.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "Applying update_v12.sql schema upgrades...\n";

    $sql = file_get_contents(__DIR__ . '/update_v12.sql');
    $db->exec($sql);
    echo "Upgrade completed successfully!\n";
} catch (Exception $e) {
    die("Database migration error: " . $e->getMessage() . "\n");
}
