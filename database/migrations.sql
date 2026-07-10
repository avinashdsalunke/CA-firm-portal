-- database/migrations.sql

CREATE TABLE IF NOT EXISTS dsc_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    director_name VARCHAR(255) NOT NULL,
    expiry_date DATE NOT NULL,
    password_hint VARCHAR(255) NULL,
    pin_hint VARCHAR(255) NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS document_expiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    doc_type VARCHAR(255) NOT NULL,
    expiry_date DATE NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS timesheets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    client_id INT NOT NULL,
    hours DECIMAL(5,2) NOT NULL,
    description TEXT NOT NULL,
    date_logged DATE NOT NULL,
    billed_status VARCHAR(50) DEFAULT 'pending',
    invoice_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tax_computations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    financial_year VARCHAR(50) NOT NULL,
    gross_salary DECIMAL(15,2) DEFAULT 0,
    house_property DECIMAL(15,2) DEFAULT 0,
    cap_gains DECIMAL(15,2) DEFAULT 0,
    business_income DECIMAL(15,2) DEFAULT 0,
    other_sources DECIMAL(15,2) DEFAULT 0,
    deductions_old DECIMAL(15,2) DEFAULT 0,
    tax_old DECIMAL(15,2) DEFAULT 0,
    tax_new DECIMAL(15,2) DEFAULT 0,
    preferred_regime VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
