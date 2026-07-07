-- database/update_v14.sql

-- Index keys for database performance optimization
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_tenant ON users(tenant_id);
CREATE INDEX idx_tasks_client ON tasks(client_id);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_documents_client ON documents(client_id);
CREATE INDEX idx_clients_name ON clients(name);
