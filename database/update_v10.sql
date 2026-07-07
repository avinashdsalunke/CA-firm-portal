-- Database updates for Phase 6: Document Management Upgrades

USE ca_firm_crm;

-- 1. Add fields for folder structure, versioning, signature, OCR index and scope to documents table
ALTER TABLE documents ADD COLUMN IF NOT EXISTS folder VARCHAR(255) DEFAULT '/';
ALTER TABLE documents ADD COLUMN IF NOT EXISTS version INT DEFAULT 1;
ALTER TABLE documents ADD COLUMN IF NOT EXISTS parent_document_id INT DEFAULT NULL;
ALTER TABLE documents ADD COLUMN IF NOT EXISTS signature_status ENUM('unsigned', 'signed') DEFAULT 'unsigned';
ALTER TABLE documents ADD COLUMN IF NOT EXISTS signed_by VARCHAR(255) DEFAULT NULL;
ALTER TABLE documents ADD COLUMN IF NOT EXISTS signed_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE documents ADD COLUMN IF NOT EXISTS ocr_text TEXT DEFAULT NULL;
ALTER TABLE documents ADD COLUMN IF NOT EXISTS sharing_scope ENUM('internal_only', 'client_shared') DEFAULT 'client_shared';

-- 2. Index ocr_text for full-text search capability
ALTER TABLE documents ADD FULLTEXT INDEX IF NOT EXISTS idx_documents_ocr (ocr_text);
