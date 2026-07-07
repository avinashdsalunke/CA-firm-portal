<?php
// src/Tenant.php

require_once __DIR__ . '/../config/database.php';

class Tenant {
    /**
     * Get list of SaaS Tenants (Firms)
     */
    public static function getTenants() {
        $db = Database::getConnection();
        return $db->query("SELECT * FROM tenants ORDER BY created_at DESC")->fetchAll();
    }

    /**
     * Create another SaaS Tenant Firm
     */
    public static function createTenant($name, $planName) {
        $db = Database::getConnection();
        
        // Quota Presets
        $userLimit = 5;
        $storageLimit = 1024; // 1GB
        if ($planName === 'professional') {
            $userLimit = 15;
            $storageLimit = 5120; // 5GB
        } elseif ($planName === 'enterprise') {
            $userLimit = 999; // Unlimited
            $storageLimit = 102400; // 100GB
        }

        try {
            $stmt = $db->prepare("
                INSERT INTO tenants (name, plan_name, user_limit, storage_limit_mb, status, billing_due_date)
                VALUES (:name, :plan, :ulimit, :slimit, 'active', DATE_ADD(CURDATE(), INTERVAL 30 DAY))
            ");
            $stmt->execute([
                'name' => trim($name),
                'plan' => $planName,
                'ulimit' => $userLimit,
                'slimit' => $storageLimit
            ]);
            $tenantId = $db->lastInsertId();

            // Seed initial subscription billing
            $amount = $planName === 'enterprise' ? 15000.00 : ($planName === 'professional' ? 5000.00 : 1500.00);
            self::addBillingRecord($tenantId, $amount, date('Y-m-d', strtotime('+30 days')));

            return ["success" => "Firm registration complete! Tenant ID: $tenantId"];
        } catch (PDOException $e) {
            return ["error" => "Failed to create firm tenant: " . $e->getMessage()];
        }
    }

    /**
     * Get active usage stats for limits checks
     */
    public static function getTenantStats($tenantId) {
        $db = Database::getConnection();
        
        // 1. Get active user count
        $stmtUser = $db->prepare("SELECT COUNT(*) as count FROM users WHERE tenant_id = :id");
        $stmtUser->execute(['id' => $tenantId]);
        $usersCount = intval($stmtUser->fetch()['count'] ?? 0);

        // 2. Get active storage sum size (computed from documents table sizes)
        $stmtStorage = $db->prepare("SELECT SUM(file_size) as total FROM documents");
        $stmtStorage->execute();
        $bytes = floatval($stmtStorage->fetch()['total'] ?? 0);
        $megabytes = round($bytes / (1024 * 1024), 2);

        return [
            'users_active' => $usersCount,
            'storage_mb' => $megabytes
        ];
    }

    /**
     * Verify User creation capacity limit
     */
    public static function validateUserLimit($tenantId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT plan_name, user_limit FROM tenants WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $tenantId]);
        $tenant = $stmt->fetch();
        if (!$tenant) return true;

        $stats = self::getTenantStats($tenantId);
        if ($stats['users_active'] >= intval($tenant['user_limit'])) {
            return false;
        }
        return true;
    }

    /**
     * Verify Storage upload capacity limit
     */
    public static function validateStorageLimit($tenantId, $incomingBytes) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT plan_name, storage_limit_mb FROM tenants WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $tenantId]);
        $tenant = $stmt->fetch();
        if (!$tenant) return true;

        $stats = self::getTenantStats($tenantId);
        $incomingMB = $incomingBytes / (1024 * 1024);
        
        if (($stats['storage_mb'] + $incomingMB) > floatval($tenant['storage_limit_mb'])) {
            return false;
        }
        return true;
    }

    /**
     * Get Tenant Invoices list
     */
    public static function getBillingHistory() {
        $db = Database::getConnection();
        return $db->query("
            SELECT tb.*, t.name as tenant_name 
            FROM tenant_billing tb
            JOIN tenants t ON tb.tenant_id = t.id
            ORDER BY tb.created_at DESC
        ")->fetchAll();
    }

    /**
     * Add subscription billing log
     */
    public static function addBillingRecord($tenantId, $amount, $dueDate) {
        $db = Database::getConnection();
        $invNum = 'SUB-' . date('Ymd') . '-' . sprintf('%03d', $tenantId) . '-' . mt_rand(10, 99);
        try {
            $stmt = $db->prepare("
                INSERT INTO tenant_billing (tenant_id, amount, status, due_date, invoice_number)
                VALUES (:tid, :amount, 'unpaid', :due, :inv)
            ");
            $stmt->execute([
                'tid' => $tenantId,
                'amount' => $amount,
                'due' => $dueDate,
                'inv' => $invNum
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
