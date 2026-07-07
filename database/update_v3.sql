-- Database updates for Phase 3: API, Lockout, and Automation Queue

USE ca_firm_crm;

-- 1. Automation Queue Table
CREATE TABLE IF NOT EXISTS automation_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    scheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Alter users table to support API Tokens & Account Lockouts
ALTER TABLE users ADD COLUMN IF NOT EXISTS api_token VARCHAR(255) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS login_failures INT DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS locked_until TIMESTAMP NULL DEFAULT NULL;

-- 3. Add database indexes to speed up dashboards and reports queries
ALTER TABLE tasks ADD INDEX IF NOT EXISTS idx_tasks_status_due (status, due_date);
ALTER TABLE compliances ADD INDEX IF NOT EXISTS idx_compliances_status_due (status, due_date);
ALTER TABLE accounting_invoices ADD INDEX IF NOT EXISTS idx_invoices_status_due (status, due_date);
