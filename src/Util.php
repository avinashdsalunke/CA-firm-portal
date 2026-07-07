<?php
// src/Util.php

class Util {
    /**
     * Start the session securely if not already started
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            
            // Secure cookie configuration if HTTPS is enabled
            $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            session_start();
        }
    }

    /**
     * Set standard security headers
     */
    public static function setSecurityHeaders() {
        header("X-Frame-Options: SAMEORIGIN");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        header("Content-Security-Policy: default-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com https://unpkg.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; script-src 'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; frame-ancestors 'none';");
    }

    /**
     * Generate a cryptographically secure random token
     */
    public static function generateToken() {
        return bin2hex(random_bytes(32));
    }

    /**
     * Clean/sanitize input strings
     */
    public static function sanitize($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Send JSON Response and exit
     */
    public static function sendJSON($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to a specific URL
     */
    public static function redirect($url) {
        header("Location: $url");
        exit;
    }

    /**
     * Basic login rate limiter using session
     */
    public static function checkRateLimit() {
        self::startSession();
        $currentTime = time();
        
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
            $_SESSION['last_attempt_time'] = $currentTime;
        }

        // Lock out for 30 seconds if more than 5 attempts within 1 minute
        if ($_SESSION['login_attempts'] >= 5) {
            if ($currentTime - $_SESSION['last_attempt_time'] < 30) {
                return false;
            } else {
                // Reset limit after lockout expires
                $_SESSION['login_attempts'] = 0;
            }
        }
        
        $_SESSION['last_attempt_time'] = $currentTime;
        $_SESSION['login_attempts']++;
        return true;
    }

    /**
     * Reset the rate limiter on successful login
     */
    public static function resetRateLimit() {
        self::startSession();
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = 0;
    }

    /**
     * Validate CSRF token
     */
    public static function validateCSRF($token) {
        self::startSession();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get or generate CSRF token
     */
    public static function getCSRFToken() {
        self::startSession();
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken();
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Format file size
     */
    public static function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Validate uploaded file (size, extensions, MIME)
     */
    public static function validateUpload($file, $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg'], $maxSize = 10485760) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return "Upload failed with error code: " . $file['error'];
        }

        if ($file['size'] > $maxSize) {
            return "File is too large. Maximum size is " . self::formatBytes($maxSize);
        }

        $fileName = $file['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExtensions)) {
            return "Invalid file extension. Allowed extensions are: " . implode(', ', $allowedExtensions);
        }

        // Validate MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/png',
            'image/jpeg',
            'image/jpg'
        ];

        if (!in_array($mimeType, $allowedMimeTypes)) {
            return "Invalid file content type.";
        }

        return true;
    }

    /**
     * Export data array to CSV format
     */
    public static function exportCSV($filename, $headers, $data) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
    /**
     * Compress uploaded image (JPG or PNG)
     */
    public static function compressImage($sourcePath, $targetPath, $quality = 75) {
        if (!extension_loaded('gd')) {
            return false;
        }
        $info = getimagesize($sourcePath);
        if (!$info) return false;

        $mime = $info['mime'];
        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            $image = @imagecreatefromjpeg($sourcePath);
            if ($image) {
                @imagejpeg($image, $targetPath, $quality);
                @imagedestroy($image);
                return true;
            }
        } elseif ($mime === 'image/png') {
            $image = @imagecreatefrompng($sourcePath);
            if ($image) {
                $pngQuality = round((100 - $quality) / 10);
                if ($pngQuality > 9) $pngQuality = 9;
                if ($pngQuality < 0) $pngQuality = 0;
                @imagepng($image, $targetPath, $pngQuality);
                @imagedestroy($image);
                return true;
            }
        }
        return false;
    }
}
