-- Database updates for Phase 4: Salary Slips & CA Service Charges Catalog

USE ca_firm_crm;

-- 1. Salary Slips Table
CREATE TABLE IF NOT EXISTS salary_slips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    month VARCHAR(7) NOT NULL, -- format YYYY-MM
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Services Table
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    charge DECIMAL(10,2) NOT NULL,
    description TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
