-- Database updates for Phase 5: Compliance Automation Settings & Escalation

USE ca_firm_crm;

-- 1. Client Compliance Configs Table
CREATE TABLE IF NOT EXISTS client_compliance_configs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL UNIQUE,
    auto_gst TINYINT(1) DEFAULT 0,
    auto_tds TINYINT(1) DEFAULT 0,
    auto_roc TINYINT(1) DEFAULT 0,
    auto_itr TINYINT(1) DEFAULT 0,
    remind_email TINYINT(1) DEFAULT 1,
    remind_sms TINYINT(1) DEFAULT 0,
    escalation_days INT DEFAULT 5,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Add columns to compliances to track reminders and escalations
ALTER TABLE compliances ADD COLUMN IF NOT EXISTS email_reminders_sent INT DEFAULT 0;
ALTER TABLE compliances ADD COLUMN IF NOT EXISTS sms_reminders_sent INT DEFAULT 0;
ALTER TABLE compliances ADD COLUMN IF NOT EXISTS escalated TINYINT(1) DEFAULT 0;
ALTER TABLE compliances ADD COLUMN IF NOT EXISTS escalated_at TIMESTAMP NULL DEFAULT NULL;

-- 3. SMS Logs table (for SMS simulation history)
CREATE TABLE IF NOT EXISTS sms_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    phone_number VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
