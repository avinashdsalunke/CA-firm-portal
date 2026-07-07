-- database/update_v8.sql
USE ca_firm_crm;

-- Alter invoices table for GST and TDS
ALTER TABLE accounting_invoices ADD COLUMN cgst DECIMAL(15, 2) DEFAULT 0.00;
ALTER TABLE accounting_invoices ADD COLUMN sgst DECIMAL(15, 2) DEFAULT 0.00;
ALTER TABLE accounting_invoices ADD COLUMN igst DECIMAL(15, 2) DEFAULT 0.00;
ALTER TABLE accounting_invoices ADD COLUMN tds_amount DECIMAL(15, 2) DEFAULT 0.00;
ALTER TABLE accounting_invoices ADD COLUMN net_amount DECIMAL(15, 2) DEFAULT 0.00;
ALTER TABLE accounting_invoices ADD COLUMN invoice_design TEXT DEFAULT NULL;

-- Alter expenses table for approval workflow
ALTER TABLE accounting_expenses ADD COLUMN status VARCHAR(50) DEFAULT 'approved';
ALTER TABLE accounting_expenses ADD COLUMN approved_by INT DEFAULT NULL;
