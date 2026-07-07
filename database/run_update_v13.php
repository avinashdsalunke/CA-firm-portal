<?php
// database/run_update_v13.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "Applying update_v13.sql SaaS multi-tenant schema upgrades...\n";

    $sql = file_get_contents(__DIR__ . '/update_v13.sql');
    $db->exec($sql);
    echo "Upgrade completed successfully!\n";
} catch (Exception $e) {
    die("Database migration error: " . $e->getMessage() . "\n");
}
