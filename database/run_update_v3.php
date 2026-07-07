<?php
// database/run_update_v3.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "Connected to the database.\n";
    
    // Create automation queue
    $db->exec("CREATE TABLE IF NOT EXISTS automation_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_type VARCHAR(100) NOT NULL,
        recipient_email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
        scheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sent_at TIMESTAMP NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Created table: automation_queue\n";

    // Alter users table
    try {
        $db->exec("ALTER TABLE users ADD COLUMN api_token VARCHAR(255) DEFAULT NULL");
        echo "Altered users: Added api_token\n";
    } catch (PDOException $e) {
        echo "Users api_token column already exists or skipped.\n";
    }
    
    try {
        $db->exec("ALTER TABLE users ADD COLUMN login_failures INT DEFAULT 0");
        echo "Altered users: Added login_failures\n";
    } catch (PDOException $e) {
        echo "Users login_failures column already exists or skipped.\n";
    }
    
    try {
        $db->exec("ALTER TABLE users ADD COLUMN locked_until TIMESTAMP NULL DEFAULT NULL");
        echo "Altered users: Added locked_until\n";
    } catch (PDOException $e) {
        echo "Users locked_until column already exists or skipped.\n";
    }

    // Add indexes
    try {
        $db->exec("ALTER TABLE tasks ADD INDEX idx_tasks_status_due (status, due_date)");
        echo "Created index on tasks\n";
    } catch (PDOException $e) {
        echo "Index idx_tasks_status_due already exists or skipped.\n";
    }
    
    try {
        $db->exec("ALTER TABLE compliances ADD INDEX idx_compliances_status_due (status, due_date)");
        echo "Created index on compliances\n";
    } catch (PDOException $e) {
        echo "Index idx_compliances_status_due already exists or skipped.\n";
    }
    
    try {
        $db->exec("ALTER TABLE accounting_invoices ADD INDEX idx_invoices_status_due (status, due_date)");
        echo "Created index on invoices\n";
    } catch (PDOException $e) {
        echo "Index idx_invoices_status_due already exists or skipped.\n";
    }

    // Generate static api tokens for seeded users if not set
    $stmt = $db->query("SELECT id, name FROM users");
    $users = $stmt->fetchAll();
    $stmtUp = $db->prepare("UPDATE users SET api_token = :token WHERE id = :id");
    foreach ($users as $u) {
        $token = hash('sha256', $u['name'] . '_secure_token_secret_salt');
        $stmtUp->execute(['token' => $token, 'id' => $u['id']]);
    }
    echo "Generated tokens for seeded users.\n";

    echo "Update V3 applied successfully!\n";
} catch (Exception $e) {
    die("Update V3 failed: " . $e->getMessage() . "\n");
}
