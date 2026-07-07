<?php
// database/run_update_v14.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "Applying update_v14.sql performance index upgrades statement by statement...\n";

    $sql = file_get_contents(__DIR__ . '/update_v14.sql');
    $queries = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($queries as $q) {
        if (empty($q)) continue;
        try {
            echo "Executing: $q\n";
            $db->exec($q);
        } catch (PDOException $ex) {
            if ($ex->getCode() == '42000' || strpos($ex->getMessage(), 'Duplicate key') !== false) {
                echo " - Index already exists, skipping.\n";
            } else {
                throw $ex;
            }
        }
    }
    echo "Index migration completed successfully!\n";
} catch (Exception $e) {
    die("Database migration error: " . $e->getMessage() . "\n");
}
