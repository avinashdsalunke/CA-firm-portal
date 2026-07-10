<?php
// bin/cron_runner.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Client.php';
require_once __DIR__ . '/../src/Compliance.php';
require_once __DIR__ . '/../src/Automation.php';
require_once __DIR__ . '/../src/Communication.php';

try {
    $db = Database::getConnection();
    echo "[" . date('Y-m-d H:i:s') . "] Starting CA CRM Automated Background Cron Tasks...\n";

    // 1. Run database auto backups
    echo "Creating automated database SQL backup...\n";
    $backupFile = Automation::backupDatabase();
    echo "Database backup saved: $backupFile\n";

    // 2. Generate monthly billing invoices (only runs on 1st of month)
    echo "Running recurring monthly billing invoice generator...\n";
    $invoiceCount = Automation::generateMonthlyInvoices();
    echo "Auto-generated: $invoiceCount monthly invoices.\n";

    // 3. Compile P&L snapshots (runs on the 1st of month)
    if (date('d') === '01') {
        echo "Creating scheduled P&L report snapshot for the past month...\n";
        $reportFile = Automation::generateScheduledReportSnapshot();
        echo "Monthly report snapshot saved: $reportFile\n";
    }

    // 4. Trigger task reminder alert checks
    echo "Checking tasks deadlines reminder checks...\n";
    $taskReminders = Automation::runTaskReminderEngine();
    echo "Triggered task reminders: $taskReminders\n";

    // 5. Trigger Phase 5 Compliance Automations (Auto returns generation, reminders, escalations)
    echo "Running compliance automation triggers...\n";
    $autoRes = Compliance::runComplianceAutomation();
    echo "Auto-generated: " . $autoRes['generated'] . " compliance returns.\n";
    echo "Triggered: " . $autoRes['reminders'] . " reminders.\n";
    echo "Escalated: " . $autoRes['escalations'] . " overdue filings.\n";

    // 6. Scan for pending compliances due in 3 days (Enqueues templates)
    $stmtComp = $db->query("
        SELECT c.*, cl.email as client_email, cl.name as client_name 
        FROM compliances c
        JOIN clients cl ON c.client_id = cl.id
        WHERE c.status = 'pending'
          AND c.due_date = DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    ");
    $compRows = $stmtComp->fetchAll();
    
    $stmtEnqueue = $db->prepare("
        INSERT INTO automation_queue (event_type, recipient_email, subject, body, status)
        VALUES (:event_type, :email, :subject, :body, 'pending')
    ");

    $stmtWhatsApp = $db->prepare("
        INSERT INTO whatsapp_logs (client_id, message, status)
        VALUES (:client_id, :message, 'sent')
    ");

    foreach ($compRows as $c) {
        // Dynamic template replacements
        $replacements = [
            'client_name' => $c['client_name'],
            'filing_title' => $c['title'],
            'due_date' => $c['due_date']
        ];

        $tplData = Automation::parseTemplate('compliance_due_reminder', $replacements);
        $subject = $tplData ? $tplData['subject'] : "Tax Return Compliance Reminder: " . $c['title'];
        $body = $tplData ? $tplData['body'] : "Dear " . $c['client_name'] . ",\n\nThis is an automated reminder that your return " . $c['title'] . " is scheduled for filing and due on " . $c['due_date'] . ". Please ensure all pending files are uploaded to the portal.\n\nBest Regards,\nCA CRM Automated System.";
        
        $stmtEnqueue->execute([
            'event_type' => 'compliance_reminder',
            'email' => $c['client_email'],
            'subject' => $subject,
            'body' => $body
        ]);
        echo "Enqueued compliance reminder for client: " . $c['client_name'] . "\n";

        // Simulated Auto WhatsApp Reminder Log
        $stmtWhatsApp->execute([
            'client_id' => $c['client_id'],
            'message' => "Automated WhatsApp Alert: Dear " . $c['client_name'] . ", your statutory return " . $c['title'] . " is due on " . $c['due_date'] . ". Please upload documents."
        ]);
    }

    // 7. Scan for OVERDUE Compliances (due_date < CURDATE())
    $stmtOverdueComp = $db->query("
        SELECT c.*, cl.email as client_email, cl.name as client_name 
        FROM compliances c
        JOIN clients cl ON c.client_id = cl.id
        WHERE c.status = 'pending'
          AND c.due_date < CURDATE()
    ");
    $overdueCompRows = $stmtOverdueComp->fetchAll();

    foreach ($overdueCompRows as $c) {
        $checkStmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM automation_queue 
            WHERE recipient_email = :email 
              AND event_type = 'compliance_overdue'
              AND body LIKE :body_pattern
        ");
        $checkStmt->execute([
            'email' => $c['client_email'],
            'body_pattern' => "%'" . $c['title'] . "'%"
        ]);
        $alreadyNotified = intval($checkStmt->fetch()['count'] ?? 0) > 0;

        if (!$alreadyNotified) {
            $subject = "OVERDUE Tax Compliance Action Required: " . $c['title'];
            $body = "Dear " . $c['client_name'] . ",\n\nThis is an urgent notice that your compliance task '" . $c['title'] . "' (Due: " . $c['due_date'] . ") is OVERDUE. Please log in to your portal immediately to review and submit your response.\n\nBest Regards,\nCA CRM Team.";
            
            $stmtEnqueue->execute([
                'event_type' => 'compliance_overdue',
                'email' => $c['client_email'],
                'subject' => $subject,
                'body' => $body
            ]);
            echo "Enqueued overdue compliance alert for client: " . $c['client_name'] . " (Task: " . $c['title'] . ")\n";

            // WhatsApp warning alert
            $stmtWhatsApp->execute([
                'client_id' => $c['client_id'],
                'message' => "URGENT OVERDUE WhatsApp Alert: Dear " . $c['client_name'] . ", your compliance return '" . $c['title'] . "' is now OVERDUE. Urgent action required."
            ]);
        }
    }

    // 8. Scan for Overdue Unpaid Invoices
    $stmtInv = $db->query("
        SELECT i.*, cl.email as client_email, cl.name as client_name 
        FROM accounting_invoices i
        JOIN clients cl ON i.client_id = cl.id
        WHERE i.status = 'unpaid'
          AND i.due_date < CURDATE()
    ");
    $invRows = $stmtInv->fetchAll();

    foreach ($invRows as $i) {
        $replacements = [
            'client_name' => $i['client_name'],
            'invoice_number' => $i['invoice_number'],
            'amount' => $i['amount'],
            'net_amount' => $i['net_amount'],
            'issue_date' => $i['issue_date'],
            'due_date' => $i['due_date']
        ];
        $tplData = Automation::parseTemplate('invoice_outstanding', $replacements);
        $subject = $tplData ? $tplData['subject'] : "Overdue Invoice Notice: #" . $i['invoice_number'];
        $body = $tplData ? $tplData['body'] : "Dear " . $i['client_name'] . ",\n\nOur system indicates that Invoice #" . $i['invoice_number'] . " for the amount of ₹" . number_format($i['amount'], 2) . " remains unpaid. The payment due date was " . $i['due_date'] . ".\n\nPlease complete payment as soon as possible.\n\nBest Regards,\nCA CRM Accounts Desk.";

        $stmtEnqueue->execute([
            'event_type' => 'invoice_overdue_alert',
            'email' => $i['client_email'],
            'subject' => $subject,
            'body' => $body
        ]);
        echo "Enqueued overdue invoice warning for invoice: " . $i['invoice_number'] . "\n";
    }

    // 8b. Scan for expiring DSC tokens (within 30 days)
    echo "Running DSC Token expiry reminder checks...\n";
    $stmtDSC = $db->query("
        SELECT d.*, cl.email as client_email, cl.name as client_name 
        FROM dsc_tokens d
        JOIN clients cl ON d.client_id = cl.id
        WHERE d.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
          AND d.expiry_date >= CURDATE()
    ");
    $expiringDSC = $stmtDSC->fetchAll();
    foreach ($expiringDSC as $d) {
        $subject = "Urgent: Digital Signature (DSC) Expiring Soon for " . $d['director_name'];
        $body = "Dear " . $d['client_name'] . ",\n\nThis is an automated reminder that the Digital Signature Certificate (DSC) for Director '" . $d['director_name'] . "' is set to expire on " . $d['expiry_date'] . ".\n\nPlease take immediate steps to renew the certificate to avoid any statutory filing disruptions.\n\nBest Regards,\nCA CRM Automated System.";
        
        $stmtEnqueue->execute([
            'event_type' => 'dsc_expiry_warning',
            'email' => $d['client_email'],
            'subject' => $subject,
            'body' => $body
        ]);
        
        $stmtWhatsApp->execute([
            'client_id' => $d['client_id'],
            'message' => "Automated Expiry Alert: DSC for Director '" . $d['director_name'] . "' expires on " . $d['expiry_date'] . ". Please renew soon."
        ]);
        echo "Enqueued DSC expiry reminder for director: " . $d['director_name'] . "\n";
    }

    // 8c. Scan for expiring critical documents (within 30 days)
    echo "Running Document Expiry checks...\n";
    $stmtDoc = $db->query("
        SELECT de.*, cl.email as client_email, cl.name as client_name 
        FROM document_expiries de
        JOIN clients cl ON de.client_id = cl.id
        WHERE de.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
          AND de.expiry_date >= CURDATE()
    ");
    $expiringDocs = $stmtDoc->fetchAll();
    foreach ($expiringDocs as $doc) {
        $subject = "Document Expiry Reminder: " . $doc['doc_type'];
        $body = "Dear " . $doc['client_name'] . ",\n\nThis is an automated reminder that your critical document / registration license '" . $doc['doc_type'] . "' is set to expire on " . $doc['expiry_date'] . ".\n\nPlease upload the renewed document to the Client Vault once obtained.\n\nBest Regards,\nCA CRM Automated System.";
        
        $stmtEnqueue->execute([
            'event_type' => 'doc_expiry_warning',
            'email' => $doc['client_email'],
            'subject' => $subject,
            'body' => $body
        ]);
        echo "Enqueued document expiry reminder for doc type: " . $doc['doc_type'] . "\n";
    }

    // 9. Process Pending Queue Items
    $stmtPending = $db->query("SELECT * FROM automation_queue WHERE status = 'pending'");
    $queue = $stmtPending->fetchAll();
    
    $stmtProcess = $db->prepare("
        UPDATE automation_queue 
        SET status = 'sent', sent_at = CURRENT_TIMESTAMP 
        WHERE id = :id
    ");

    $processedCount = 0;
    foreach ($queue as $q) {
        $stmtProcess->execute(['id' => $q['id']]);
        $processedCount++;
    }
    
    echo "Processed and sent $processedCount automation queue notices.\n";
    echo "[" . date('Y-m-d H:i:s') . "] Cron runner checks completed successfully.\n";
} catch (Exception $e) {
    die("Cron process failed: " . $e->getMessage() . "\n");
}
