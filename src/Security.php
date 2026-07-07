<?php
// src/Security.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Auth.php';

class Security {
    /**
     * Get user's IP Address
     */
    public static function getIP() {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Log authentication attempts
     */
    public static function logLoginAttempt($email, $userId, $status) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO login_logs (user_id, email_attempted, ip_address, user_agent, status) 
                VALUES (:user_id, :email_attempted, :ip_address, :user_agent, :status)
            ");
            $stmt->execute([
                'user_id' => $userId ?: null,
                'email_attempted' => $email,
                'ip_address' => self::getIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'status' => $status
            ]);
        } catch (PDOException $e) {
            // Silently fail logging if database is busy
        }
    }

    /**
     * Log business/operational activity
     */
    public static function logActivity($action, $details = null) {
        $user = Auth::getCurrentUser();
        $userId = $user ? $user['id'] : null;

        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO activity_logs (user_id, action, details, ip_address) 
                VALUES (:user_id, :action, :details, :ip_address)
            ");
            $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'details' => $details,
                'ip_address' => self::getIP()
            ]);
        } catch (PDOException $e) {
            // Silently fail logging
        }
    }

    /**
     * Get login logs for auditing
     */
    public static function getLoginLogs($limit = 100) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT ll.*, u.name as user_name 
            FROM login_logs ll 
            LEFT JOIN users u ON ll.user_id = u.id 
            ORDER BY ll.created_at DESC LIMIT :limit
        ");
        $stmt->bindValue(':limit', intval($limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get activity logs for auditing
     */
    public static function getActivityLogs($limit = 100) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT al.*, u.name as user_name 
            FROM activity_logs al 
            LEFT JOIN users u ON al.user_id = u.id 
            ORDER BY al.created_at DESC LIMIT :limit
        ");
        $stmt->bindValue(':limit', intval($limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static $encryptionKey = 'ca-firm-crm-super-secure-encryption-key-2026';

    /**
     * Encrypt sensitive data using AES-256-CBC
     */
    public static function encryptData($data) {
        if (empty($data)) return null;
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', self::$encryptionKey, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Decrypt sensitive data
     */
    public static function decryptData($data) {
        if (empty($data)) return null;
        $parts = explode('::', base64_decode($data), 2);
        if (count($parts) !== 2) return null;
        list($encryptedData, $iv) = $parts;
        return openssl_decrypt($encryptedData, 'aes-256-cbc', self::$encryptionKey, 0, $iv);
    }

    /**
     * Register a user session
     */
    public static function registerSession($userId, $token) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("
                INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent) 
                VALUES (:user_id, :session_token, :ip, :ua)
            ");
            $stmt->execute([
                'user_id' => $userId,
                'session_token' => $token,
                'ip' => self::getIP(),
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Update active timestamp of session
     */
    public static function updateSessionActivity($token) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("UPDATE user_sessions SET last_active = CURRENT_TIMESTAMP WHERE session_token = :token");
            $stmt->execute(['token' => $token]);
        } catch (PDOException $e) {}
    }

    /**
     * Validate active session
     */
    public static function isSessionValid($token) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM user_sessions WHERE session_token = :token");
        $stmt->execute(['token' => $token]);
        return intval($stmt->fetch()['count'] ?? 0) > 0;
    }

    /**
     * Get active device sessions for a user
     */
    public static function getSessionsForUser($userId) {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM user_sessions WHERE user_id = :uid ORDER BY last_active DESC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Terminate other active sessions for user
     */
    public static function logoutOtherDevices($userId, $currentToken) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM user_sessions WHERE user_id = :uid AND session_token != :token");
            $stmt->execute(['uid' => $userId, 'token' => $currentToken]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Check if IP Whitelist restrictions permit request
     */
    public static function isIPWhitelisted($ip) {
        $db = Database::getConnection();
        // If whitelist is empty, allow all
        $stmtCount = $db->query("SELECT COUNT(*) as count FROM ip_restrictions");
        if (intval($stmtCount->fetch()['count'] ?? 0) === 0) {
            return true;
        }

        // Allow localhost always for development/testing
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return true;
        }

        $stmt = $db->prepare("SELECT COUNT(*) as count FROM ip_restrictions WHERE ip_address = :ip");
        $stmt->execute(['ip' => $ip]);
        return intval($stmt->fetch()['count'] ?? 0) > 0;
    }

    /**
     * Add whitelisted IP address
     */
    public static function addWhitelistedIP($ip, $desc) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("INSERT INTO ip_restrictions (ip_address, description) VALUES (:ip, :desc)");
            $stmt->execute(['ip' => trim($ip), 'desc' => trim($desc)]);
            return ["success" => "IP whitelisted successfully."];
        } catch (PDOException $e) {
            return ["error" => "Failed to whitelist IP: " . $e->getMessage()];
        }
    }

    /**
     * Delete whitelisted IP restriction
     */
    public static function deleteWhitelistedIP($id) {
        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM ip_restrictions WHERE id = :id");
            $stmt->execute(['id' => intval($id)]);
            return ["success" => "IP restriction deleted successfully."];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Get all whitelisted IP addresses
     */
    public static function getWhitelistedIPs() {
        $db = Database::getConnection();
        return $db->query("SELECT * FROM ip_restrictions ORDER BY ip_address ASC")->fetchAll();
    }
}
