<?php
// database/run_update_v9.php

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "Connected to the database.\n";

    // Read and execute update_v9.sql
    $sql = file_get_contents(__DIR__ . '/update_v9.sql');
    $db->exec($sql);
    echo "SQL script applied successfully.\n";

    // Auto-create compliance configs for any existing clients who don't have them
    $stmtClients = $db->query("SELECT id FROM clients");
    $clients = $stmtClients->fetchAll();

    $stmtInsert = $db->prepare("
        INSERT IGNORE INTO client_compliance_configs (client_id, auto_gst, auto_tds, auto_roc, auto_itr, remind_email, remind_sms, escalation_days) 
        VALUES (:client_id, 1, 1, 1, 1, 1, 1, 5)
    ");

    $count = 0;
    foreach ($clients as $c) {
        $stmtInsert->execute(['client_id' => $c['id']]);
        $count++;
    }

    echo "Default compliance configurations created for $count clients.\n";
    echo "Update V9 applied successfully!\n";
} catch (Exception $e) {
    die("Update V9 failed: " . $e->getMessage() . "\n");
}
