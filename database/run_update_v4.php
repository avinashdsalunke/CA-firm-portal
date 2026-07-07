<?php
// database/run_update_v4.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "Connected to the database.\n";
    
    // Create salary slips table
    $db->exec("CREATE TABLE IF NOT EXISTS salary_slips (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        month VARCHAR(7) NOT NULL,
        basic DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        hra DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        conveyance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        allowance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        pf DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        pt DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        tds DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        net_salary DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        status ENUM('unpaid', 'paid') NOT NULL DEFAULT 'unpaid',
        paid_date DATE DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY emp_month_unique (employee_id, month),
        FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Created table: salary_slips\n";

    // Create services table
    $db->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        charge DECIMAL(10,2) NOT NULL,
        description TEXT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Created table: services\n";

    // Seed services if empty
    $count = $db->query("SELECT COUNT(*) FROM services")->fetchColumn();
    if ($count == 0) {
        $stmt = $db->prepare("INSERT INTO services (name, charge, description) VALUES (:name, :charge, :description)");
        $defaultServices = [
            ["name" => "GST New Registration", "charge" => 3000.00, "description" => "Complete GST registration including ARN generation"],
            ["name" => "Monthly GSTR-3B Filing", "charge" => 1500.00, "description" => "Preparation and filing of GSTR-3B monthly returns"],
            ["name" => "ITR-1 Consultation & Filing", "charge" => 2500.00, "description" => "Income tax returns filing for salaried individuals"],
            ["name" => "Corporate Tax Audit Return", "charge" => 15000.00, "description" => "Detailed audit of accounts and form 3CD filing"],
            ["name" => "ROC Annual Filing Form AOC-4", "charge" => 8000.00, "description" => "Filing of annual financial statements of private limited company"]
        ];
        foreach ($defaultServices as $ds) {
            $stmt->execute($ds);
        }
        echo "Seeded services catalog.\n";
    }

    echo "Update V4 applied successfully!\n";
} catch (Exception $e) {
    die("Update V4 failed: " . $e->getMessage() . "\n");
}
