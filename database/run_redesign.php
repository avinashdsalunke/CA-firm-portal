<?php
// database/run_redesign.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "Connected to the database.\n";
    
    // Drop existing tables to ensure clean recreation and avoid legacy column conflicts
    $db->exec("DROP TABLE IF EXISTS attendance");
    $db->exec("DROP TABLE IF EXISTS leave_requests");
    $db->exec("DROP TABLE IF EXISTS employees");
    $db->exec("DROP TABLE IF EXISTS messages");
    $db->exec("DROP TABLE IF EXISTS announcements");
    $db->exec("DROP TABLE IF EXISTS login_logs");
    $db->exec("DROP TABLE IF EXISTS activity_logs");
    $db->exec("DROP TABLE IF EXISTS role_permissions");
    echo "Dropped any legacy tables.\n";

    // Create new tables
    $db->exec("CREATE TABLE employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        department VARCHAR(100) NOT NULL,
        designation VARCHAR(100) NOT NULL,
        joining_date DATE NOT NULL,
        salary DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Created table: employees\n";

    $db->exec("CREATE TABLE leave_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        leave_type ENUM('casual', 'sick', 'earned') NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
        reason TEXT DEFAULT NULL,
        approved_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Created table: leave_requests\n";

    $db->exec("CREATE TABLE attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        date DATE NOT NULL,
        check_in TIME DEFAULT NULL,
        check_out TIME DEFAULT NULL,
        status ENUM('present', 'absent', 'on_leave') NOT NULL DEFAULT 'present',
        UNIQUE KEY user_date_unique (user_id, date),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Created table: attendance\n";

    $db->exec("CREATE TABLE messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message_text TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Created table: messages\n";

    $db->exec("CREATE TABLE announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Created table: announcements\n";

    $db->exec("CREATE TABLE login_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        email_attempted VARCHAR(255) NOT NULL,
        ip_address VARCHAR(50) NOT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        status ENUM('success', 'failed') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Created table: login_logs\n";

    $db->exec("CREATE TABLE activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT DEFAULT NULL,
        ip_address VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Created table: activity_logs\n";

    $db->exec("CREATE TABLE role_permissions (
        role ENUM('super_admin', 'admin_manager', 'staff') NOT NULL,
        permission VARCHAR(100) NOT NULL,
        PRIMARY KEY (role, permission)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Created table: role_permissions\n";

    // Alter documents table with helper logic to check column presence
    try {
        $db->exec("ALTER TABLE documents ADD COLUMN category VARCHAR(100) DEFAULT 'General' AFTER client_id");
        echo "Altered documents: Added category\n";
    } catch (PDOException $e) {
        echo "Documents category column already exists or alter skipped.\n";
    }
    
    try {
        $db->exec("ALTER TABLE documents ADD COLUMN version INT DEFAULT 1 AFTER file_size");
        echo "Altered documents: Added version\n";
    } catch (PDOException $e) {
        echo "Documents version column already exists or alter skipped.\n";
    }
    
    try {
        $db->exec("ALTER TABLE documents ADD COLUMN description TEXT DEFAULT NULL AFTER version");
        echo "Altered documents: Added description\n";
    } catch (PDOException $e) {
        echo "Documents description column already exists or alter skipped.\n";
    }

    // Populate role permissions
    $db->exec("INSERT INTO role_permissions (role, permission) VALUES
        ('super_admin', 'manage_staff'),
        ('super_admin', 'edit_roles'),
        ('super_admin', 'manage_clients'),
        ('super_admin', 'manage_tasks'),
        ('super_admin', 'manage_accounting'),
        ('super_admin', 'manage_compliance'),
        ('super_admin', 'manage_hrms'),
        ('super_admin', 'view_reports'),
        ('super_admin', 'view_security_logs'),
        ('admin_manager', 'manage_clients'),
        ('admin_manager', 'manage_tasks'),
        ('admin_manager', 'manage_accounting'),
        ('admin_manager', 'manage_compliance'),
        ('admin_manager', 'manage_hrms'),
        ('admin_manager', 'view_reports'),
        ('staff', 'manage_tasks'),
        ('staff', 'manage_compliance')
    ");
    echo "Seeded role_permissions.\n";

    echo "Database redesign applied successfully!\n";
} catch (Exception $e) {
    die("Database redesign failed: " . $e->getMessage() . "\n");
}
