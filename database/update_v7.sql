-- database/update_v7.sql
USE ca_firm_crm;

-- Add shift column to employees
ALTER TABLE employees ADD COLUMN shift VARCHAR(50) DEFAULT 'General';

-- Add comments and workflow_step to leave_requests
ALTER TABLE leave_requests ADD COLUMN comments TEXT DEFAULT NULL;
ALTER TABLE leave_requests ADD COLUMN workflow_step VARCHAR(100) DEFAULT 'approved_by_admin';
