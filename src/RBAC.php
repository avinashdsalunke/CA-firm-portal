<?php
// src/RBAC.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Auth.php';

class RBAC {
    /**
     * Cache permissions list to minimize DB lookups
     */
    private static $permissionsCache = [];

    /**
     * Check if a specific role has a permission
     */
    public static function hasPermission($role, $permission) {
        // Super Admin has all permissions implicitly
        if ($role === 'super_admin') {
            return true;
        }

        if (empty(self::$permissionsCache)) {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT * FROM role_permissions");
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                self::$permissionsCache[$row['role']][] = $row['permission'];
            }
        }

        return isset(self::$permissionsCache[$role]) && in_array($permission, self::$permissionsCache[$role]);
    }

    /**
     * Require a specific permission for the logged-in user
     */
    public static function requirePermission($permission) {
        $user = Auth::getCurrentUser();
        if (!$user) {
            header("Location: login.php");
            exit;
        }

        if (!self::hasPermission($user['role'], $permission)) {
            http_response_code(403);
            die("<!DOCTYPE html><html><head><title>Access Denied</title><link rel='stylesheet' href='css/style.css'></head><body style='display:flex;align-items:center;justify-content:center;height:100vh;background-color:var(--bg-base);'><div class='card' style='max-width:400px;text-align:center;'><h1 style='color:var(--danger);'>Access Denied</h1><p style='color:var(--text-muted);margin-top:0.5rem;'>You do not have the required permissions ('$permission') to perform this action.</p><br><a href='index.php' class='btn btn-secondary'>Back to Dashboard</a></div></body></html>");
        }
    }

    /**
     * Get all permission mapping details
     */
    public static function getAllRolePermissions() {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM role_permissions ORDER BY role ASC, permission ASC");
        return $stmt->fetchAll();
    }

    /**
     * Update permissions mapping list for a role
     */
    public static function updateRolePermissions($role, $permissions = []) {
        if ($role === 'super_admin') {
            return ["error" => "Cannot customize Super Admin permissions."];
        }

        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $stmtDel = $db->prepare("DELETE FROM role_permissions WHERE role = :role");
            $stmtDel->execute(['role' => $role]);

            $stmtIns = $db->prepare("INSERT INTO role_permissions (role, permission) VALUES (:role, :permission)");
            foreach ($permissions as $perm) {
                $stmtIns->execute(['role' => $role, 'permission' => trim($perm)]);
            }

            $db->commit();
            self::$permissionsCache = []; // clear cache
            return ["success" => true];
        } catch (Exception $e) {
            $db->rollBack();
            return ["error" => "Failed to update permissions: " . $e->getMessage()];
        }
    }
}
