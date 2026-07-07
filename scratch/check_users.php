<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = Database::getConnection();
    echo "--- users ---\n";
    foreach($db->query("SELECT id, name, email, role FROM users")->fetchAll() as $r) {
        print_r($r);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
