<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = Database::getConnection();
    echo "--- tasks ---\n";
    foreach($db->query("SELECT * FROM tasks")->fetchAll() as $r) {
        print_r($r);
    }
    echo "--- compliances ---\n";
    foreach($db->query("SELECT * FROM compliances")->fetchAll() as $r) {
        print_r($r);
    }
    echo "--- invoices ---\n";
    foreach($db->query("SELECT * FROM accounting_invoices")->fetchAll() as $r) {
        print_r($r);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
