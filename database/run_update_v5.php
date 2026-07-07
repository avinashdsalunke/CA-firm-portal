<?php
// database/run_update_v5.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "Connected to the database.\n";

    // Alter compliances table to support client responses
    try {
        $db->exec("ALTER TABLE compliances ADD COLUMN client_response TEXT DEFAULT NULL");
        echo "Altered compliances: Added client_response\n";
    } catch (PDOException $e) {
        echo "Compliances client_response column already exists or skipped.\n";
    }

    try {
        $db->exec("ALTER TABLE compliances ADD COLUMN client_responded_at TIMESTAMP NULL DEFAULT NULL");
        echo "Altered compliances: Added client_responded_at\n";
    } catch (PDOException $e) {
        echo "Compliances client_responded_at column already exists or skipped.\n";
    }

    echo "Update V5 applied successfully!\n";
} catch (Exception $e) {
    die("Update V5 failed: " . $e->getMessage() . "\n");
}
