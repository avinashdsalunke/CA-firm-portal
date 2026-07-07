<?php
// config/database.php

class Database {
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            $envPath = dirname(__DIR__) . '/.env';
            
            // Standard defaults for local MySQL development
            $dbHost = '127.0.0.1';
            $dbName = 'ca_firm_crm';
            $dbUser = 'root';
            $dbPass = '';

            // Load and parse environment variables from .env
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || strpos($line, '#') === 0) {
                        continue;
                    }
                    if (strpos($line, '=') !== false) {
                        list($name, $value) = explode('=', $line, 2);
                        $name = trim($name);
                        $value = trim($value, " \t\n\r\0\x0B\"'");
                        
                        if ($name === 'DB_HOST') {
                            $dbHost = $value;
                        } elseif ($name === 'DB_NAME') {
                            $dbName = $value;
                        } elseif ($name === 'DB_USER') {
                            $dbUser = $value;
                        } elseif ($name === 'DB_PASS' || $name === 'DB_PASSWORD') {
                            $dbPass = $value;
                        } elseif ($name === 'DATABASE_URL') {
                            // Support parsing DATABASE_URL connection strings
                            $parsedUrl = parse_url($value);
                            if ($parsedUrl && isset($parsedUrl['scheme']) && ($parsedUrl['scheme'] === 'mysql' || $parsedUrl['scheme'] === 'postgresql')) {
                                if (isset($parsedUrl['host'])) $dbHost = $parsedUrl['host'];
                                if (isset($parsedUrl['user'])) $dbUser = $parsedUrl['user'];
                                if (isset($parsedUrl['pass'])) $dbPass = $parsedUrl['pass'];
                                if (isset($parsedUrl['path'])) $dbName = ltrim($parsedUrl['path'], '/');
                            }
                        }
                    }
                }
            }

            try {
                $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                self::$pdo = new PDO($dsn, $dbUser, $dbPass, $options);
            } catch (PDOException $e) {
                // If database does not exist (SQLSTATE 42000 or error 1049), try to auto-create it
                if ($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
                    try {
                        $dsnNoDb = "mysql:host={$dbHost};charset=utf8mb4";
                        $tempPdo = new PDO($dsnNoDb, $dbUser, $dbPass);
                        $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        
                        // Retry connection
                        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
                        $options = [
                            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES   => false,
                        ];
                        self::$pdo = new PDO($dsn, $dbUser, $dbPass, $options);
                    } catch (PDOException $ex) {
                        die("Database auto-creation and connection failed: " . $ex->getMessage());
                    }
                } else {
                    die("Database connection failed: " . $e->getMessage());
                }
            }
        }
        return self::$pdo;
    }
}
