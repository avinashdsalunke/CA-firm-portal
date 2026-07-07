<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT id, invoice_number, amount FROM accounting_invoices ORDER BY id DESC LIMIT 20");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
