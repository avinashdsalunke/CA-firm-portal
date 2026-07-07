<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::getConnection();
$rowsAffected = $db->exec("
    DELETE p1 FROM accounting_payments p1
    INNER JOIN accounting_payments p2 
    WHERE p1.id > p2.id 
      AND p1.invoice_id = p2.invoice_id 
      AND p1.amount = p2.amount 
      AND p1.payment_date = p2.payment_date 
      AND p1.payment_method = p2.payment_method
");
echo "Cleaned up $rowsAffected duplicate payment rows.\n";
