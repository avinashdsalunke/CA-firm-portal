-- database/update_v12.sql

-- Add 2FA columns to users table
ALTER TABLE users 
ADD COLUMN two_fa_enabled TINYINT(1) DEFAULT 0,
ADD COLUMN two_fa_code VARCHAR(10) DEFAULT NULL,
ADD COLUMN two_fa_expires_at DATETIME DEFAULT NULL;

-- User Sessions Table
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(50) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- IP Whitelist Restriction Table
CREATE TABLE IF NOT EXISTS ip_restrictions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add Encrypted Tax Data column to clients table
ALTER TABLE clients ADD COLUMN encrypted_tax_data TEXT DEFAULT NULL;
