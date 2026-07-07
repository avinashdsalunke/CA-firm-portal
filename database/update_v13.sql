-- database/update_v13.sql

-- SaaS Tenants/Companies Table
CREATE TABLE IF NOT EXISTS tenants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    plan_name VARCHAR(50) DEFAULT 'basic', -- basic, professional, enterprise
    user_limit INT DEFAULT 5,
    storage_limit_mb INT DEFAULT 1024, -- 1GB (1024 MB)
    status VARCHAR(50) DEFAULT 'active', -- active, suspended
    billing_due_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SaaS Subscription Billing Table
CREATE TABLE IF NOT EXISTS tenant_billing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'unpaid', -- paid, unpaid, overdue
    due_date DATE NOT NULL,
    invoice_number VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add Tenant Relation to Users
ALTER TABLE users ADD COLUMN tenant_id INT DEFAULT 1;

-- Seed Default Primary Tenant
INSERT INTO tenants (id, name, plan_name, user_limit, storage_limit_mb, status, billing_due_date)
VALUES (1, 'Primary CA Firm Ltd', 'professional', 15, 5120, 'active', DATE_ADD(CURDATE(), INTERVAL 30 DAY))
ON DUPLICATE KEY UPDATE id=id;
