<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = Database::getConnection();
    
    // Add columns if they don't exist
    $cols = [
        'financial_year' => 'VARCHAR(50) NULL',
        'assessment_year' => 'VARCHAR(50) NULL',
        'periodicity' => 'VARCHAR(50) NULL',
        'estimated_fees' => 'DECIMAL(10,2) NULL'
    ];
    
    foreach ($cols as $col => $type) {
        // Check if column exists
        $stmt = $db->query("SHOW COLUMNS FROM tasks LIKE '$col'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE tasks ADD COLUMN $col $type");
            echo "Added column $col to tasks\n";
        } else {
            echo "Column $col already exists in tasks\n";
        }
    }
    
    echo "Migration complete!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
