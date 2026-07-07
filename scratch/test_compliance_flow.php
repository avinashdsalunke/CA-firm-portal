<?php
// scratch/test_compliance_flow.php

require_once __DIR__ . '/../src/Client.php';
require_once __DIR__ . '/../src/Compliance.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "Connected to the database.\n";

    // 1. Get or create a test client
    $clients = Client::getClients();
    if (empty($clients)) {
        echo "Creating a test client...\n";
        $res = Client::createClient("Test Client Ltd", "client_test@example.com", "9876543210");
        $clientId = $res['id'];
    } else {
        $clientId = $clients[0]['id'];
        echo "Using existing client ID: $clientId (" . $clients[0]['name'] . ")\n";
    }

    // 2. Create a compliance task
    echo "Creating a compliance task...\n";
    $title = "GST Filing Q1 2026";
    $category = "GST Return";
    $dueDate = "2026-06-30"; // Past date to trigger overdue alert in cron runner
    $notes = "Please review your details.";
    $res = Compliance::createCompliance($clientId, $title, $category, $dueDate, $notes);
    if (!isset($res['success'])) {
        throw new Exception("Failed to create compliance: " . ($res['error'] ?? ''));
    }
    $compId = $res['id'];
    echo "Compliance task created with ID: $compId\n";

    // 3. Verify notification was enqueued in automation_queue
    $stmt = $db->prepare("SELECT * FROM automation_queue WHERE event_type = 'compliance_created' AND recipient_email = (SELECT email FROM clients WHERE id = :client_id) ORDER BY id DESC LIMIT 1");
    $stmt->execute(['client_id' => $clientId]);
    $queueItem = $stmt->fetch();
    if ($queueItem) {
        echo "SUCCESS: Notification enqueued in automation_queue! Subject: " . $queueItem['subject'] . "\n";
    } else {
        throw new Exception("Failed: No notification enqueued in automation_queue.");
    }

    // 4. Test Overdue Cron Runner scanning
    echo "Running cron runner to scan for overdue items...\n";
    ob_start();
    include __DIR__ . '/../bin/cron_runner.php';
    $cronOutput = ob_get_clean();
    echo "Cron Runner Output:\n" . $cronOutput . "\n";

    // Check if overdue alert is enqueued
    $stmtOverdue = $db->prepare("SELECT * FROM automation_queue WHERE event_type = 'compliance_overdue' AND recipient_email = (SELECT email FROM clients WHERE id = :client_id) ORDER BY id DESC LIMIT 1");
    $stmtOverdue->execute(['client_id' => $clientId]);
    $overdueQueueItem = $stmtOverdue->fetch();
    if ($overdueQueueItem) {
        echo "SUCCESS: Overdue alert enqueued! Subject: " . $overdueQueueItem['subject'] . "\n";
    } else {
        throw new Exception("Failed: No overdue alert enqueued in automation_queue.");
    }

    // 5. Submit client response
    echo "Updating client response for compliance ID: $compId...\n";
    $clientResponseText = "All invoices uploaded to Document Center.";
    $resResp = Compliance::updateClientResponse($compId, $clientResponseText);
    if (!isset($resResp['success'])) {
        throw new Exception("Failed to update client response: " . ($resResp['error'] ?? ''));
    }

    // Verify client response in database
    $comp = Compliance::getCompliance($compId);
    if ($comp && $comp['client_response'] === $clientResponseText && !empty($comp['client_responded_at'])) {
        echo "SUCCESS: Client response saved correctly! Response: " . $comp['client_response'] . " at " . $comp['client_responded_at'] . "\n";
    } else {
        throw new Exception("Failed: Client response not saved or matches incorrectly.");
    }

    // 6. Record filing (admin marks as completed)
    echo "Recording return filing...\n";
    $ackNo = "ACK-987654321-GST";
    $filingDate = date('Y-m-d');
    $resFiling = Compliance::recordFiling($compId, $filingDate, $ackNo, "Filing successfully processed.");
    if (!isset($resFiling['success'])) {
        throw new Exception("Failed to record filing: " . ($resFiling['error'] ?? ''));
    }
    
    // Verify updated status
    $compFiled = Compliance::getCompliance($compId);
    if ($compFiled && $compFiled['status'] === 'filed' && $compFiled['acknowledgement_number'] === $ackNo) {
        echo "SUCCESS: Compliance status updated to 'filed' with ack number $ackNo\n";
    } else {
        throw new Exception("Failed: Status not updated to filed or ack number mismatch.");
    }

    // 7. Cleanup (delete the test compliance)
    echo "Deleting compliance task...\n";
    $resDel = Compliance::deleteCompliance($compId);
    if (!isset($resDel['success'])) {
        throw new Exception("Failed to delete compliance.");
    }
    
    $compDeleted = Compliance::getCompliance($compId);
    if (!$compDeleted) {
        echo "SUCCESS: Compliance task deleted successfully.\n";
    } else {
        throw new Exception("Failed: Compliance task still exists after delete call.");
    }

    echo "\nALL COMPLIANCE FLOW AND NOTIFICATION TESTS PASSED!\n";
} catch (Exception $e) {
    die("\nTEST SUITE FAILED: " . $e->getMessage() . "\n");
}
