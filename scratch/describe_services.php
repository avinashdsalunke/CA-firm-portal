<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = Database::getConnection();
    echo "Table schema of 'services':\n";
    $q = $db->query("DESCRIBE services");
    if ($q) {
        foreach($q->fetchAll() as $r) {
            print_r($r);
        }
    } else {
        echo "Table does not exist!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
