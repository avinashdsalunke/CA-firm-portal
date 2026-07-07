<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = Database::getConnection();
    $stmt = $db->query("DESCRIBE compliances");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
