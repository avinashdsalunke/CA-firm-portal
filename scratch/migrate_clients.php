<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = Database::getConnection();
    
    // Add columns if they don't exist
    $cols = [
        'pan' => 'VARCHAR(50) NULL',
        'gstin' => 'VARCHAR(50) NULL',
        'tan' => 'VARCHAR(50) NULL',
        'address' => 'TEXT NULL',
        'client_type' => 'VARCHAR(50) NULL',
        'incorporation_date' => 'DATE NULL'
    ];
    
    foreach ($cols as $col => $type) {
        // Check if column exists
        $stmt = $db->query("SHOW COLUMNS FROM clients LIKE '$col'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE clients ADD COLUMN $col $type");
            echo "Added column $col\n";
        } else {
            echo "Column $col already exists\n";
        }
    }
    
    echo "Migration complete!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
