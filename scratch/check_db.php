<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = Database::getConnection();
    echo "--- clients ---\n";
    foreach($db->query("SELECT id, name, created_at FROM clients")->fetchAll() as $r) {
        print_r($r);
    }
    echo "--- tasks ---\n";
    foreach($db->query("SELECT id, title, due_date FROM tasks")->fetchAll() as $r) {
        print_r($r);
    }
    echo "--- compliances ---\n";
    foreach($db->query("SELECT id, title, due_date FROM compliances")->fetchAll() as $r) {
        print_r($r);
    }
    echo "--- invoices ---\n";
    foreach($db->query("SELECT id, amount, issue_date, status FROM accounting_invoices")->fetchAll() as $r) {
        print_r($r);
    }
    echo "--- payments ---\n";
    foreach($db->query("SELECT id, amount, payment_date FROM accounting_payments")->fetchAll() as $r) {
        print_r($r);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
