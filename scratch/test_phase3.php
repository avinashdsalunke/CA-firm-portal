<?php
// scratch/test_phase3.php

require_once __DIR__ . '/../src/Cache.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../config/database.php';

try {
    echo "1. Testing Performance Cache Layer...\n";
    Cache::delete('test_cache_key');
    $val = ["kpis" => 12345.67, "count" => 42];
    Cache::set('test_cache_key', $val, 2); // 2 seconds TTL
    
    $retrieved = Cache::get('test_cache_key');
    if ($retrieved !== $val) {
        throw new Exception("Cache Retrieval mismatch!");
    }
    echo "Cache SET and GET verified.\n";
    
    sleep(3);
    $expired = Cache::get('test_cache_key');
    if ($expired !== null) {
        throw new Exception("Cache TTL failed to expire!");
    }
    echo "Cache TTL Expiration verified.\n";

    echo "\n2. Testing Authentication Lockout System...\n";
    $db = Database::getConnection();
    
    // Create temporary dummy user for test
    $email = 'lockout_test@example.com';
    $password = 'WrongPass123';
    $hash = password_hash('CorrectPass123', PASSWORD_BCRYPT);
    $pin = password_hash('1234', PASSWORD_BCRYPT);
    
    $db->exec("DELETE FROM users WHERE email = '$email'");
    $db->prepare("
        INSERT INTO users (name, email, password_hash, pin_hash, role) 
        VALUES ('LockoutTest', :email, :pass, :pin, 'staff')
    ")->execute([
        'email' => $email,
        'pass' => $hash,
        'pin' => $pin
    ]);
    
    // Fail login 5 times
    for ($i = 1; $i <= 5; $i++) {
        Util::resetRateLimit();
        $res = Auth::login($email, $password);
        echo "Attempt $i outcome: " . ($res['error'] ?? 'success') . "\n";
    }
    
    // Try correct login, it should fail due to lockout
    Util::resetRateLimit();
    $resCorrect = Auth::login($email, 'CorrectPass123');
    echo "Correct pass during lockout outcome: " . ($resCorrect['error'] ?? 'success') . "\n";
    if (strpos($resCorrect['error'] ?? '', 'locked') === false) {
        throw new Exception("Lockout verification failed. Correct credentials should have been blocked.");
    }
    echo "Lockout system successfully activated!\n";
    
    // Reset test user
    $db->exec("DELETE FROM users WHERE email = '$email'");
    echo "Test user deleted.\n";

    echo "\n3. Testing Automated Cron Alerts...\n";
    $output = shell_exec("php -f bin/cron_runner.php");
    echo "Cron Runner CLI Output:\n" . $output . "\n";
    
    $stmtQ = $db->query("SELECT COUNT(*) as total FROM automation_queue");
    $totalReminders = $stmtQ->fetch()['total'];
    echo "Total records in Automation Queue: $totalReminders\n";

    echo "\n4. Testing REST API Operations...\n";
    // Get super_admin to read token
    $stmtAdmin = $db->query("SELECT * FROM users WHERE role = 'super_admin' LIMIT 1");
    $admin = $stmtAdmin->fetch();
    $token = $admin['api_token'];
    
    echo "Admin API Token: $token\n";
    
    // Simulate HTTP Request to api.php using cURL or stream wrapper
    $url = "http://127.0.0.1/ca-firm-crm-main/public/api.php?action=tasks";
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "Authorization: Bearer " . $token . "\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    
    // We can also test locally by including it, but testing endpoint is cleaner
    $localFile = dirname(__DIR__) . '/public/api.php';
    $_GET['action'] = 'tasks';
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    
    ob_start();
    @include $localFile;
    $apiResponse = ob_get_clean();
    
    $jsonStart = strpos($apiResponse, '{');
    if ($jsonStart !== false) {
        $apiResponse = substr($apiResponse, $jsonStart);
    }
    
    $json = json_decode($apiResponse, true);
    if (!isset($json['success']) || !$json['success']) {
        throw new Exception("REST API simulation request failed: " . $apiResponse);
    }
    echo "REST API endpoint verified (Tasks retrieved: " . count($json['tasks'] ?? []) . ")\n";

    echo "\nALL PHASE 3 ARCHITECTURE AND AUTOMATION CHECKS PASSED!\n";
} catch (Exception $e) {
    die("\nTEST FAILED: " . $e->getMessage() . "\n");
}
