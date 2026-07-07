<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::getConnection();
$stmt = $db->query("SELECT * FROM accounting_payments");
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']}, Invoice ID: {$row['invoice_id']}, Amount: {$row['amount']}, Date: {$row['payment_date']}, Method: {$row['payment_method']}\n";
}
