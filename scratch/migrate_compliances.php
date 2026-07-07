<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = Database::getConnection();
    
    // Add columns if they don't exist
    $cols = [
        'financial_year' => 'VARCHAR(50) NULL',
        'assessment_year' => 'VARCHAR(50) NULL',
        'periodicity' => 'VARCHAR(50) NULL'
    ];
    
    foreach ($cols as $col => $type) {
        // Check if column exists
        $stmt = $db->query("SHOW COLUMNS FROM compliances LIKE '$col'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE compliances ADD COLUMN $col $type");
            echo "Added column $col to compliances\n";
        } else {
            echo "Column $col already exists in compliances\n";
        }
    }
    
    echo "Migration complete!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
