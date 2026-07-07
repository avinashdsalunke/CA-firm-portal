<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = Database::getConnection();
    echo "--- services ---\n";
    foreach($db->query("SELECT * FROM services")->fetchAll() as $r) {
        print_r($r);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
