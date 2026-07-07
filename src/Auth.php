<?php
// src/Auth.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Util.php';
require_once __DIR__ . '/Security.php';

class Auth {
    /**
     * Authenticate user by email/ID and password
     */
    public static function login($email, $password) {
        Util::startSession();
        
        if (!Util::checkRateLimit()) {
            Security::logLoginAttempt($email, null, 'failed');
            return ["error" => "Too many attempts. Please try again after 30 seconds."];
        }

        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT *
            FROM users
            WHERE email = :email
               OR name = :name
            LIMIT 1
        ");

        $stmt->execute([
            ':email' => $email,
            ':name'  => $email
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $failures = 0;
        if ($user) {
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $rem = strtotime($user['locked_until']) - time();
                return ["error" => "Account locked. Try again in $rem seconds."];
            }

            if (password_verify($password, $user['password_hash'])) {
                Util::resetRateLimit();
                
                $db->prepare("UPDATE users SET login_failures = 0, locked_until = NULL WHERE id = :id")->execute(['id' => $user['id']]);
                
                // 2FA check
                if ($user['two_fa_enabled']) {
                    $code = sprintf("%06d", mt_rand(100000, 999999));
                    $expires = date('Y-m-d H:i:s', time() + 300); // 5 mins
                    $stmt2fa = $db->prepare("UPDATE users SET two_fa_code = :code, two_fa_expires_at = :expires WHERE id = :id");
                    $stmt2fa->execute([
                        'code' => $code,
                        'expires' => $expires,
                        'id' => $user['id']
                    ]);

                    // Enqueue 2FA email code
                    $stmtQueue = $db->prepare("
                        INSERT INTO automation_queue (event_type, recipient_email, subject, body, status)
                        VALUES ('2fa_code', :email, :subject, :body, 'pending')
                    ");
                    $stmtQueue->execute([
                        'email' => $user['email'],
                        'subject' => "Your 2FA Login Code - CA Associates",
                        'body' => "Dear {$user['name']},\n\nYour 6-digit login verification code is: $code\n\nThis code will expire in 5 minutes.\n\nBest Regards,\nCA Associates Security Desk"
                    ]);

                    $_SESSION['temp_2fa_user_id'] = $user['id'];
                    return ["2fa_required" => true];
                }

                // Store session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['last_activity'] = time();

                // Register session token
                $sessionToken = bin2hex(random_bytes(32));
                $_SESSION['session_token'] = $sessionToken;
                Security::registerSession($user['id'], $sessionToken);
                
                Security::logLoginAttempt($email, $user['id'], 'success');
                return ["success" => true, "user" => $user];
            } else {
                $failures = intval($user['login_failures']) + 1;
                $lockedUntil = null;
                if ($failures >= 5) {
                    $lockedUntil = date('Y-m-d H:i:s', time() + 300);
                }
                
                $stmtFail = $db->prepare("UPDATE users SET login_failures = :fail, locked_until = :locked WHERE id = :id");
                $stmtFail->execute([
                    'fail' => $failures,
                    'locked' => $lockedUntil,
                    'id' => $user['id']
                ]);
            }
        }

        Security::logLoginAttempt($email, $user ? $user['id'] : null, 'failed');
        if ($user && $failures >= 5) {
            return ["error" => "Too many failed attempts. Account locked for 5 minutes."];
        }
        return ["error" => "Invalid email or password."];
    }

    /**
     * Verify Staff PIN for authorization changes or secure updates
     */
    public static function verifyPIN($userId, $pin) {
        $db = Database::getConnection();
        

        $stmt = $db->prepare("SELECT pin_hash FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if ($user && password_verify($pin, $user['pin_hash'])) {
            return true;
        }
        return false;
    }

    /**
     * Log user out
     */
    public static function logout() {
        Util::startSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        Util::startSession();
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 900)) {
                self::logout();
                return false;
            }
            $_SESSION['last_activity'] = time();
            return true;
        }
        return false;
    }

    /**
     * Get currently logged-in user info
     */
    public static function getCurrentUser() {
        Util::startSession();
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'role' => $_SESSION['user_role']
            ];
        }
        return null;
    }

    /**
     * Access Control Guards
     */
    public static function requireLogin()
{
    if (!self::isLoggedIn()) {
        Util::redirect('login.php');
    }
}


    public static function requireAdminOrManager() {
        self::requireLogin();
        $user = self::getCurrentUser();
        if ($user['role'] !== 'super_admin' && $user['role'] !== 'admin_manager') {
            http_response_code(403);
            die("Access Denied. Admin or Manager role required.");
        }
    }

    public static function requireSuperAdmin() {
        self::requireLogin();
        $user = self::getCurrentUser();
        if ($user['role'] !== 'super_admin') {
            http_response_code(403);
            die("Access Denied. Super Admin role required.");
        }
    }

    /**
     * CRUD Operations for Managing Users (Staff)
     */
    public static function getStaffList() {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT id, name, email, role, created_at FROM users ORDER BY role, name");
        return $stmt->fetchAll();
    }

    public static function createStaff($name, $email, $password, $pin, $role) {
        require_once __DIR__ . '/Tenant.php';
        if (!Tenant::validateUserLimit(1)) {
            return ["error" => "Tenant user quota limit exceeded. Please upgrade your subscription plan."];
        }

        $policy = self::validatePasswordPolicy($password);
        if ($policy !== true) {
            return ["error" => $policy];
        }

        $db = Database::getConnection();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $pinHash = password_hash($pin, PASSWORD_DEFAULT);

        try {
            $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, pin_hash, role) VALUES (:name, :email, :password_hash, :pin_hash, :role)");
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => $passwordHash,
                'pin_hash' => $pinHash,
                'role' => $role
            ]);
            return ["success" => true, "id" => $db->lastInsertId()];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return ["error" => "Email address already registered."];
            }
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    public static function updateStaff($id, $name, $email, $role, $password = null, $pin = null) {
        if (!empty($password)) {
            $policy = self::validatePasswordPolicy($password);
            if ($policy !== true) {
                return ["error" => $policy];
            }
        }

        $db = Database::getConnection();
        
        $sql = "UPDATE users SET name = :name, email = :email, role = :role";
        $params = [
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'id' => $id
        ];

        if (!empty($password)) {
            $sql .= ", password_hash = :password_hash";
            $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if (!empty($pin)) {
            $sql .= ", pin_hash = :pin_hash";
            $params['pin_hash'] = password_hash($pin, PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = :id";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return ["success" => true];
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return ["error" => "Email address already in use."];
            }
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    public static function deleteStaff($id) {
        // Prevent deleting self
        $currentUser = self::getCurrentUser();
        if ($currentUser && $currentUser['id'] == $id) {
            return ["error" => "You cannot delete your own account."];
        }

        $db = Database::getConnection();
        try {
            $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);
            return ["success" => true];
        } catch (PDOException $e) {
            return ["error" => "Database error: " . $e->getMessage()];
        }
    }

    /**
     * Enforce strict password policy complexity
     */
    public static function validatePasswordPolicy($password) {
        if (strlen($password) < 8) {
            return "Password must be at least 8 characters long.";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return "Password must contain at least one uppercase letter.";
        }
        if (!preg_match('/[a-z]/', $password)) {
            return "Password must contain at least one lowercase letter.";
        }
        if (!preg_match('/[0-9]/', $password)) {
            return "Password must contain at least one number.";
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return "Password must contain at least one special character.";
        }
        return true;
    }

    /**
     * Verify 2FA challenge code
     */
    public static function verify2FA($userId, $code) {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM users 
            WHERE id = :id 
              AND two_fa_code = :code 
              AND two_fa_expires_at > NOW() 
            LIMIT 1
        ");
        $stmt->execute([
            'id' => $userId,
            'code' => $code
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            // Clear 2FA code
            $db->prepare("UPDATE users SET two_fa_code = NULL, two_fa_expires_at = NULL WHERE id = :id")->execute(['id' => $userId]);
            
            // Establish session
            Util::startSession();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();

            // Save active session token
            $sessionToken = bin2hex(random_bytes(32));
            $_SESSION['session_token'] = $sessionToken;
            Security::registerSession($user['id'], $sessionToken);

            Security::logLoginAttempt($user['email'], $user['id'], 'success');
            return true;
        }
        return false;
    }
}
