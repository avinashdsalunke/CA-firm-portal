-- CA Firm CRM Database Schema & Seed Data

CREATE DATABASE IF NOT EXISTS ca_firm_crm DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ca_firm_crm;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    pin_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin_manager', 'staff') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clients Table
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    portal_token VARCHAR(255) DEFAULT NULL UNIQUE,
    portal_token_expires_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tasks Table
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    assigned_to_user_id INT DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'in_progress', 'completed', 'overdue') NOT NULL DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    category VARCHAR(100) NOT NULL, -- GST, TDS, ROC, Audit, etc.
    due_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recurring Compliance Task Templates
CREATE TABLE IF NOT EXISTS recurring_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    assigned_to_user_id INT DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    category VARCHAR(100) NOT NULL, -- GST, TDS, ROC
    frequency ENUM('monthly', 'quarterly', 'yearly') NOT NULL DEFAULT 'monthly',
    next_spawn_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily Work Logs
CREATE TABLE IF NOT EXISTS work_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    description TEXT NOT NULL,
    hours_spent DECIMAL(5,2) NOT NULL,
    log_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Document Requests
CREATE TABLE IF NOT EXISTS document_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending', 'uploaded', 'reviewed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Client Documents
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    document_request_id INT DEFAULT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    uploaded_by ENUM('client', 'staff') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (document_request_id) REFERENCES document_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Client Activity Timeline
CREATE TABLE IF NOT EXISTS client_timeline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    user_id INT DEFAULT NULL, -- Null if client action
    event_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Users Data
-- test / test (PIN 1111 - hash: $2a$10$Lfj4BjmO9PUc196K8zwLdefiHcSnP4LxJgcXvhhgH5l4Pgk5PRpLS)
-- PIN 1111 hash: $2a$10$ZG0gPL79cC2xxcTl5iQrleKsBJMhlIDbSHFa2SpaspnwNmnFzIRKe
-- manager@example.test / Test@1234 / PIN 2222 (hash: $2a$10$oh.8G7U1p.ZDnhQsREoN9O6evqFeWuobNW.bPlO09ks.fKrD8DMFy)
-- staff1@example.test / Test@1234 / PIN 3333 (hash: $2a$10$ZG0gPL79cC2xxcTl5iQrleKsBJMhlIDbSHFa2SpaspnwNmnFzIRKe)
-- staff2@example.test / Test@1234 / PIN 4444 (hash: $2a$10$fG4Xfdr3vHVIuF35b0nofeFIv1KloqpFvsoGxyFVc3L69h8xKyaMC)
-- staff3@example.test / Test@1234 / PIN 5555 (hash: $2a$10$FhTIUOrgafECh/Om47Hnm.fV0WrsAULEYjk4PBGJPpKNDd1l/hE/S)

INSERT INTO users (name, email, password_hash, pin_hash, role) VALUES
('Super Admin', 'test', '$2a$10$Lfj4BjmO9PUc196K8zwLdefiHcSnP4LxJgcXvhhgH5l4Pgk5PRpLS', '$2a$10$ZG0gPL79cC2xxcTl5iQrleKsBJMhlIDbSHFa2SpaspnwNmnFzIRKe', 'super_admin'),
('Admin Manager', 'manager@example.test', '$2a$10$PhHFcKXRcatbH8nLqkXAxuPq/j2NlUG.1nOZCTyabvvXtYbL6p9nm', '$2a$10$oh.8G7U1p.ZDnhQsREoN9O6evqFeWuobNW.bPlO09ks.fKrD8DMFy', 'admin_manager'),
('Staff 1', 'staff1@example.test', '$2a$10$PhHFcKXRcatbH8nLqkXAxuPq/j2NlUG.1nOZCTyabvvXtYbL6p9nm', '$2a$10$ZG0gPL79cC2xxcTl5iQrleKsBJMhlIDbSHFa2SpaspnwNmnFzIRKe', 'staff'),
('Staff 2', 'staff2@example.test', '$2a$10$PhHFcKXRcatbH8nLqkXAxuPq/j2NlUG.1nOZCTyabvvXtYbL6p9nm', '$2a$10$fG4Xfdr3vHVIuF35b0nofeFIv1KloqpFvsoGxyFVc3L69h8xKyaMC', 'staff'),
('Staff 3', 'staff3@example.test', '$2a$10$PhHFcKXRcatbH8nLqkXAxuPq/j2NlUG.1nOZCTyabvvXtYbL6p9nm', '$2a$10$FhTIUOrgafECh/Om47Hnm.fV0WrsAULEYjk4PBGJPpKNDd1l/hE/S', 'staff');
